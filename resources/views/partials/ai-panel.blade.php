{{-- AI analysis side panel, included once per page by partials.ai-button.
     Every value produced by the AI is inserted with textContent, never as HTML. --}}
@php
    $aiPalette = [
        'status' => ['conforme' => '#3AB87A', 'partiellement_conforme' => '#E09B1A', 'non_conforme' => '#D94F45', 'non_evaluable' => '#6C757D'],
        'risk_level' => ['faible' => '#3AB87A', 'moyen' => '#E09B1A', 'eleve' => '#E8731A', 'critique' => '#D94F45'],
        'priority' => ['haute' => '#D94F45', 'moyenne' => '#E09B1A', 'basse' => '#3AB87A'],
        'roles' => ['ceo' => '#3A72C4', 'rssi' => '#D94F45', 'dpo' => '#8059C8', 'auditeur' => '#17A8C4'],
    ];
    $aiContrast = [];
    foreach ($aiPalette as $group => $colors) {
        foreach ($colors as $key => $color) {
            $aiContrast[$group][$key] = contrast_color($color);
        }
    }
    // Status labels depend on the analysed type, with a default fallback
    $aiStatus = [];
    foreach (\App\Services\Ai\AiSubjects::types() as $aiType) {
        foreach (array_keys($aiPalette['status']) as $value) {
            $key = "ai.status.{$aiType}.{$value}";
            $aiStatus[$aiType][$value] = Lang::has($key) ? trans($key) : trans("ai.status.default.{$value}");
        }
    }
    $aiConfig = [
        'palette' => $aiPalette,
        'contrast' => $aiContrast,
        'status' => $aiStatus,
        'risk_level' => trans('ai.risk_level'),
        'confidence' => trans('ai.confidence'),
        'finding' => trans('ai.finding'),
        'priority' => trans('ai.priority'),
        'roles' => trans('ai.roles'),
        'labels' => trans('ai.panel'),
        'errors' => trans('ai.errors'),
    ];
@endphp
<style>
    .btn-ai { position: relative; display: inline-flex; align-items: center; justify-content: center; width: 2.1rem; height: 2.1rem; min-width: 0; padding: 0; font-size: 1.2rem; line-height: 1; color: #8059C8; background-color: #fff; border: 1px solid #c9b8ea; }
    /* Center the robot: no margin from ".btn .bi" (custom.css) nor vertical shift from Bootstrap Icons */
    .btn-ai .bi { display: block; line-height: 1; margin: 0; }
    .btn-ai .bi::before { display: block; vertical-align: 0; }
    .btn-ai:hover, .btn-ai:focus { color: #5f3fa3; background-color: #f5f0ff; border-color: #8059C8; }
    .btn-ai.has-analysis::after { content: ""; position: absolute; top: -3px; right: -3px; width: 8px; height: 8px; border-radius: 50%; background: #3AB87A; border: 1px solid #fff; }

    .ai-panel { position: fixed; top: 0; right: 0; height: 100vh; width: 30vw; min-width: 360px; z-index: 1060;
        display: flex; flex-direction: column; background: var(--bs-body-bg, #fff); color: var(--bs-body-color, #212529);
        box-shadow: -4px 0 16px rgba(0, 0, 0, .15); transform: translateX(105%); transition: transform .25s ease-in-out; visibility: hidden; }
    .ai-panel.open { transform: translateX(0); visibility: visible; }
    @media (max-width: 768px) { .ai-panel { width: 100vw; min-width: 0; } }
    @media (prefers-reduced-motion: reduce) { .ai-panel { transition: none; } }
    .ai-panel-header { padding: .75rem 1rem; border-bottom: 1px solid var(--bs-border-color, #dee2e6); }
    .ai-panel-header h2 { font-size: 1rem; font-weight: 600; margin: 0; padding-right: 4.5rem; }
    .ai-panel-close { position: absolute; top: .55rem; right: .75rem; border: 0; background: transparent; font-size: 1.25rem; line-height: 1; padding: .25rem; color: inherit; }
    .ai-panel-header .btn-help { position: absolute; top: .7rem; right: 2.75rem; }
    .ai-panel-meta { display: flex; align-items: center; justify-content: space-between; gap: .5rem; margin-top: .5rem; font-size: .8rem; color: var(--bs-secondary-color, #6c757d); }
    .ai-panel-body { flex: 1; overflow-y: auto; padding: 1rem; font-size: .9rem; scrollbar-width: none; }
    .ai-panel-body::-webkit-scrollbar { display: none; }
    .ai-panel-body h3 { font-size: .8rem; text-transform: uppercase; letter-spacing: .03em; color: var(--bs-secondary-color, #6c757d); margin: 1.1rem 0 .4rem; }
    .ai-panel-body ul, .ai-panel-body ol { padding-left: 0; list-style: none; margin: 0; }
    .ai-panel-body li { margin-bottom: .5rem; }
    .ai-panel-footer { padding: .5rem 1rem; border-top: 1px solid var(--bs-border-color, #dee2e6); font-size: .75rem; color: var(--bs-secondary-color, #6c757d); }
    .ai-badge { display: inline-block; padding: .2rem .5rem; border-radius: .25rem; font-size: .75rem; font-weight: 600; margin: 0 .25rem .25rem 0; }
    .ai-badge-outline { border: 1px solid var(--bs-border-color, #ced4da); color: inherit; font-weight: 400; }
    .ai-finding { display: flex; gap: .5rem; }
    .ai-finding .bi { flex: none; margin-top: .1rem; }
    .ai-reco { border-left: 3px solid var(--bs-border-color, #dee2e6); padding-left: .6rem; }
    .ai-reco-meta { font-size: .75rem; color: var(--bs-secondary-color, #6c757d); }
    .ai-viewpoint { display: flex; gap: .6rem; margin-bottom: .75rem; }
    .ai-avatar { flex: none; width: 2.2rem; height: 2.2rem; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; }
    .ai-viewpoint p { font-style: italic; margin: 0; }
    .ai-preview { white-space: pre-wrap; word-break: break-word; font-size: .75rem; background: var(--bs-tertiary-bg, #f8f9fa); padding: .5rem; border-radius: .25rem; }
</style>

<aside id="ai-panel" class="ai-panel" role="dialog" aria-modal="false" aria-labelledby="ai-panel-title" aria-hidden="true">
    <div class="ai-panel-header position-relative">
        <h2 id="ai-panel-title">{{ trans('ai.panel.title') }}</h2>
        <a href="{{ trans('help.url') . trans('help.pages.ai_use') }}" target="_blank" rel="noopener" class="btn btn-help">{{ trans('help.button') }}</a>
        <button type="button" class="ai-panel-close" id="ai-panel-close" aria-label="{{ trans('ai.panel.close') }}" title="{{ trans('ai.panel.close') }}">
            <i class="bi bi-x-lg" aria-hidden="true"></i>
        </button>
        <div class="ai-panel-meta">
            <span id="ai-panel-meta" role="status" aria-live="polite"></span>
            <button type="button" class="btn btn-sm btn-light border" id="ai-panel-rerun">
                <i class="bi bi-arrow-repeat" aria-hidden="true"></i> {{ trans('ai.panel.rerun') }}
            </button>
        </div>
    </div>
    <div class="ai-panel-body" id="ai-panel-body" aria-busy="false"></div>
    <div class="ai-panel-footer">
        <i class="bi bi-stars" aria-hidden="true"></i> {{ trans('ai.panel.footer') }}
        · <span id="ai-panel-model">{{ \App\Models\Configuration::get('ai.model') }}</span>
        · <span>{{ parse_url((string) \App\Models\Configuration::get('ai.base_url'), PHP_URL_HOST) }}</span>
        · <a href="#" id="ai-panel-preview">{{ trans('ai.panel.data_sent') }}</a>
    </div>
</aside>

<script>
(function () {
    const cfg = @json($aiConfig);
    const L = cfg.labels;
    const panel = document.getElementById('ai-panel');
    // Out of the card, so that no ancestor can affect the fixed positioning
    document.body.appendChild(panel);
    const title = document.getElementById('ai-panel-title');
    const meta = document.getElementById('ai-panel-meta');
    const body = document.getElementById('ai-panel-body');
    const model = document.getElementById('ai-panel-model');
    const rerun = document.getElementById('ai-panel-rerun');
    const closeButton = document.getElementById('ai-panel-close');
    const token = document.querySelector('meta[name="csrf-token"]').content;
    const locale = document.documentElement.lang || undefined;

    let current = null;
    let lastForce = false;
    let lastResult = null;

    // DOM helpers: text is always set with textContent
    function el(tag, className, text) {
        const node = document.createElement(tag);
        if (className) { node.className = className; }
        if (text !== undefined && text !== null) { node.textContent = text; }
        return node;
    }
    function icon(name, color) {
        const node = el('i', 'bi ' + name);
        node.setAttribute('aria-hidden', 'true');
        if (color) { node.style.color = color; }
        return node;
    }
    function badge(group, key, label) {
        const node = el('span', 'ai-badge', label);
        const color = (cfg.palette[group] || {})[key];
        if (color) {
            node.style.backgroundColor = color;
            node.style.color = cfg.contrast[group][key];
        } else {
            node.classList.add('ai-badge-outline');
        }
        return node;
    }
    function section(label) {
        body.appendChild(el('h3', null, label));
    }
    function formatDate(iso) {
        const date = new Date(iso);
        return isNaN(date) ? iso : date.toLocaleString(locale, { dateStyle: 'medium', timeStyle: 'short' });
    }
    function setBusy(busy) {
        rerun.disabled = busy;
        body.setAttribute('aria-busy', busy ? 'true' : 'false');
    }

    function post(url, payload) {
        return fetch(url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify(payload || {}),
        }).then(function (response) {
            return response.json()
                .catch(function () { return {}; })
                .then(function (data) { return { status: response.status, data: data }; });
        });
    }

    function showLoading() {
        body.replaceChildren();
        const loading = el('div', 'text-center text-muted py-5');
        loading.appendChild(el('div', 'spinner-border spinner-border-sm me-2'));
        loading.appendChild(document.createTextNode(L.loading));
        body.appendChild(loading);
        meta.textContent = '';
    }

    function showError(message, retry) {
        body.replaceChildren();
        const box = el('div', 'alert alert-danger');
        box.setAttribute('role', 'alert');
        box.appendChild(el('p', 'mb-2', message));
        const button = el('button', 'btn btn-sm btn-outline-danger', L.retry);
        button.type = 'button';
        button.addEventListener('click', retry);
        box.appendChild(button);
        body.appendChild(box);
    }

    function errorMessage(status, data) {
        if (data && typeof data.error === 'string') { return data.error; }
        return status === 429 ? cfg.errors.too_many : cfg.errors.generic;
    }

    function render(result) {
        const data = result.data;
        lastResult = result;
        body.replaceChildren();
        meta.textContent = result.cached ? L.cached.replace(':date', formatDate(result.analyzed_at)) : L.fresh + ' · ' + formatDate(result.analyzed_at);
        model.textContent = result.model;

        // 1. Header: assessed status, risk level, confidence
        const header = el('div');
        header.appendChild(badge('status', data.statut_evalue, cfg.status[current.type][data.statut_evalue]));
        header.appendChild(badge('risk_level', data.niveau_risque, L.risk_level + L.colon + cfg.risk_level[data.niveau_risque]));
        header.appendChild(badge('confidence', data.confiance, L.confidence + L.colon + cfg.confidence[data.confiance]));
        body.appendChild(header);

        // 2. Summary
        section(L.summary);
        body.appendChild(el('p', null, data.synthese));

        // 3. Findings
        const findingIcons = {
            force: ['bi-check-circle', '#3AB87A'],
            faiblesse: ['bi-exclamation-triangle', '#E8731A'],
            manque_preuve: ['bi-file-earmark-x', '#D94F45'],
            incoherence: ['bi-question-diamond', '#8059C8'],
        };
        if (data.constats.length) {
            section(L.findings);
            const list = el('ul');
            data.constats.forEach(function (finding) {
                const item = el('li', 'ai-finding');
                const def = findingIcons[finding.type] || ['bi-dot', null];
                const i = icon(def[0], def[1]);
                i.title = cfg.finding[finding.type] || finding.type;
                item.appendChild(i);
                const text = el('div');
                text.appendChild(el('strong', null, (cfg.finding[finding.type] || finding.type) + ' — '));
                text.appendChild(document.createTextNode(finding.description));
                item.appendChild(text);
                list.appendChild(item);
            });
            body.appendChild(list);
        }

        // 4. Recommendations
        if (data.recommandations.length) {
            section(L.recommendations);
            const list = el('ol');
            data.recommandations.forEach(function (reco) {
                const item = el('li', 'ai-reco');
                const head = el('div');
                head.appendChild(badge('priority', reco.priorite, cfg.priority[reco.priorite] || reco.priorite));
                head.appendChild(el('strong', null, reco.action));
                item.appendChild(head);
                if (reco.justification) {
                    item.appendChild(el('div', null, L.justification + L.colon + reco.justification));
                }
                const details = [];
                if (reco.echeance_suggeree) { details.push(L.due + L.colon + reco.echeance_suggeree); }
                if (reco.reference) { details.push(L.reference + L.colon + reco.reference); }
                if (details.length) { item.appendChild(el('div', 'ai-reco-meta', details.join(' · '))); }
                list.appendChild(item);
            });
            body.appendChild(list);
        }

        // 5. Open questions
        if (data.questions_ouvertes.length) {
            section(L.questions);
            const list = el('ul');
            data.questions_ouvertes.forEach(function (question) {
                const item = el('li', 'ai-finding');
                item.appendChild(icon('bi-question-circle'));
                item.appendChild(el('span', null, question));
                list.appendChild(item);
            });
            body.appendChild(list);
        }

        // 6. Points of view
        section(L.viewpoints);
        ['ceo', 'rssi', 'dpo', 'auditeur'].forEach(function (role) {
            const block = el('div', 'ai-viewpoint');
            const avatar = el('span', 'ai-avatar');
            avatar.style.backgroundColor = cfg.palette.roles[role];
            avatar.style.color = cfg.contrast.roles[role];
            avatar.appendChild(icon('bi-person-fill'));
            block.appendChild(avatar);
            const text = el('div');
            text.appendChild(el('strong', 'd-block', cfg.roles[role]));
            text.appendChild(el('p', null, data.points_de_vue[role]));
            block.appendChild(text);
            body.appendChild(block);
        });
    }

    function analyze(force) {
        if (!current) { return; }
        lastForce = force;
        showLoading();
        setBusy(true);
        post('/ai/' + current.type + '/' + current.id, force ? { force: 1 } : {})
            .then(function (response) {
                if (response.status === 200 && response.data.ok) {
                    render(response.data);
                    current.trigger.classList.add('has-analysis');
                } else {
                    showError(errorMessage(response.status, response.data), function () { analyze(lastForce); });
                }
            })
            .catch(function () { showError(cfg.errors.generic, function () { analyze(lastForce); }); })
            .finally(function () { setBusy(false); });
    }

    function preview() {
        if (!current) { return; }
        showLoading();
        setBusy(true);
        post('/ai/' + current.type + '/' + current.id + '/preview')
            .then(function (response) {
                if (response.status !== 200 || !response.data.ok) {
                    showError(errorMessage(response.status, response.data), preview);
                    return;
                }
                body.replaceChildren();
                body.appendChild(el('h3', null, L.data_sent_title));
                body.appendChild(el('p', 'text-muted small', L.data_sent_help));
                body.appendChild(el('pre', 'ai-preview', JSON.stringify(response.data.context, null, 2)));
                if (lastResult) {
                    const back = el('button', 'btn btn-sm btn-light border', L.back);
                    back.type = 'button';
                    back.addEventListener('click', function () { render(lastResult); });
                    body.appendChild(back);
                }
            })
            .catch(function () { showError(cfg.errors.generic, preview); })
            .finally(function () { setBusy(false); });
    }

    function open(trigger) {
        current = { type: trigger.dataset.aiType, id: trigger.dataset.aiId, trigger: trigger };
        lastResult = null;
        title.textContent = L.title + ' — ' + trigger.dataset.aiTitle;
        panel.classList.add('open');
        panel.setAttribute('aria-hidden', 'false');
        trigger.setAttribute('aria-expanded', 'true');
        closeButton.focus();
        analyze(false);
    }

    function close() {
        panel.classList.remove('open');
        panel.setAttribute('aria-hidden', 'true');
        if (current) {
            current.trigger.setAttribute('aria-expanded', 'false');
            current.trigger.focus();
        }
    }

    document.querySelectorAll('[data-ai-type]').forEach(function (trigger) {
        trigger.setAttribute('aria-expanded', 'false');
        trigger.addEventListener('click', function () { open(trigger); });
    });
    closeButton.addEventListener('click', close);
    rerun.addEventListener('click', function () { analyze(true); });
    document.getElementById('ai-panel-preview').addEventListener('click', function (event) {
        event.preventDefault();
        preview();
    });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && panel.classList.contains('open')) { close(); }
    });
})();
</script>
