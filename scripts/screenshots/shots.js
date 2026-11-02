// Takes the documentation screenshots (docs/images) in English, French and German.
//
// Usage: npm run docs:screenshots
//
// Environment:
//   DEMING_URL       application URL (default: http://localhost:8000)
//   DEMING_LOGIN     administrator login (default: admin@admin.localhost)
//   DEMING_PASSWORD  administrator password (default: admin)
//   DEMING_AUDITEE_LOGIN     auditee login (default: julien.moreau@example.local)
//   DEMING_AUDITEE_PASSWORD  auditee password (default: password)
//   CHROME_PATH      browser executable (default: Playwright's bundled Chromium)
//   LANGS            languages to capture: en, fr, de (default: en,fr)
//   ONLY             comma-separated screenshot names to capture (default: all)
//   OUT_DIR          output directory (default: docs/images)
//   ID_<lookup>      fixed value for a lookup of screens.json (e.g. ID_control=18)

const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const BASE = (process.env.DEMING_URL || 'http://localhost:8000').replace(/\/$/, '');
// Accounts used to take the screens, selected by the "user" of a screen or a lookup
const USERS = {
    admin: { login: process.env.DEMING_LOGIN || 'admin@admin.localhost', password: process.env.DEMING_PASSWORD || 'admin' },
    auditee: {
        login: process.env.DEMING_AUDITEE_LOGIN || 'julien.moreau@example.local',
        password: process.env.DEMING_AUDITEE_PASSWORD || 'password',
    },
};
const LANGS = (process.env.LANGS || 'en,fr').split(',');
const ONLY = process.env.ONLY ? process.env.ONLY.split(',') : null;
const OUT_DIR = process.env.OUT_DIR || path.join(__dirname, '..', '..', 'docs', 'images');
const spec = JSON.parse(fs.readFileSync(path.join(__dirname, 'screens.json')));

// Image name: "{fr}" becomes the language in French and German and is dropped in English, "{lang}" is always replaced.
function fileName(name, lang) {
    return name
        .replace('.{fr}', lang === 'en' ? '' : '.' + lang)
        .replace('{lang}', lang);
}

async function login(page, user) {
    const { login, password } = USERS[user];
    await page.goto(BASE + '/login');
    await page.fill('#login', login);
    await page.fill('input[name=password]', password);
    await Promise.all([page.waitForNavigation(), page.click('form button.btn-primary')]);
    if (page.url().includes('/login')) throw new Error('Login failed for ' + login);
}

// Resolves the {placeholders} used in screen URLs from the first matching element of a list page.
async function resolveLookups(pageOf, screens) {
    const values = {};
    const used = screens.map(s => s.url).join();
    for (const [key, l] of Object.entries(spec.lookups)) {
        if (!used.includes('{' + key + '}')) continue;
        if (process.env['ID_' + key]) {
            values[key] = process.env['ID_' + key];
            continue;
        }
        const page = await pageOf(l.user || 'admin');
        await page.goto(BASE + l.page, { waitUntil: 'networkidle' });
        const raw = await page.locator(l.selector).first().getAttribute(l.attr || 'href');
        const match = l.regex ? new RegExp(l.regex).exec(raw || '') : [raw, raw];
        if (!match) throw new Error(`Lookup "${key}" found nothing on ${l.page}`);
        values[key] = match[1].trim();
    }
    return values;
}

function fillUrl(url, values) {
    return url.replace(/\{(\w+)\}/g, (_, key) => encodeURIComponent(values[key]));
}

// Height of the element's content, ignoring the empty space of a full-height container.
async function contentHeight(el) {
    return el.evaluate(e => {
        const top = e.getBoundingClientRect().top;
        let bottom = 0;
        for (const c of e.children) {
            const r = c.getBoundingClientRect();
            if (r.height) bottom = Math.max(bottom, r.bottom - top);
        }
        return bottom ? bottom + 14 : e.getBoundingClientRect().height;
    });
}

async function capture(page, screen, lang, values) {
    const url = fillUrl(screen.url, values);
    const sep = url.includes('?') ? '&' : '?';
    await page.goto(BASE + url + sep + 'change_language=' + lang, { waitUntil: 'networkidle' });
    await page.addStyleTag({ content: '*{animation:none!important;transition:none!important}' });
    // Keep DataTables lists short
    await page.evaluate(() => {
        try {
            if (window.jQuery && jQuery.fn.dataTable) jQuery.fn.dataTable.tables({ api: true }).page.len(10).draw();
        } catch (e) {}
    });
    // Steps before the capture: click on an element, then wait for another one (e.g. the result of an AI analysis)
    for (const step of screen.steps || []) {
        if (step.click) await page.locator(step.click).first().click();
        if (step.waitFor) await page.locator(step.waitFor).first().waitFor({ timeout: 180000 });
    }
    await page.waitForTimeout(screen.wait || 1200);

    const el = page.locator(screen.selector || 'main#content-home').first();
    const box = await el.boundingBox();
    let height = Math.min(await contentHeight(el), box.height);
    if (screen.until) {
        const until = await page.locator(screen.until).first().boundingBox();
        height = until.y - box.y;
    }
    if (screen.maxHeight) height = Math.min(height, screen.maxHeight);

    const file = path.join(OUT_DIR, fileName(screen.name, lang) + '.png');
    await page.screenshot({ path: file, fullPage: true, clip: { x: box.x, y: box.y, width: box.width, height } });
    console.log(path.relative(process.cwd(), file));
}

(async () => {
    const screens = spec.screens.filter(s => !ONLY || ONLY.includes(s.name.split('.{')[0]));
    let values = null;
    for (const lang of LANGS) {
        // The browser locale drives the format of date inputs: Chrome takes it from --lang, or from LANGUAGE on Linux
        const locale = { en: 'en-GB', fr: 'fr-FR', de: 'de-DE' }[lang];
        const browser = await chromium.launch({
            args: ['--lang=' + locale],
            env: { ...process.env, LANGUAGE: locale.replace('-', '_'), LANG: locale.replace('-', '_') + '.UTF-8' },
            ...(process.env.CHROME_PATH ? { executablePath: process.env.CHROME_PATH } : {}),
        });
        try {
            // One logged-in page per account, opened when first needed
            const pages = {};
            const pageOf = async user => {
                if (!pages[user]) {
                    const context = await browser.newContext({ viewport: { width: 1680, height: 1000 }, locale });
                    pages[user] = await context.newPage();
                    await login(pages[user], user);
                }
                return pages[user];
            };
            if (!values) values = await resolveLookups(pageOf, screens);
            for (const screen of screens) {
                await capture(await pageOf(screen.user || 'admin'), screen, lang, values);
            }
        } finally {
            await browser.close();
        }
    }
})().catch(e => {
    console.error(e.message);
    process.exit(1);
});
