# Documentation screenshots

Takes the screenshots of `docs/images` in English, French and German with Playwright.

## Prerequisites

* Deming running with test data (`php artisan deming:generate-tests`), an administrator account and, for the
  delegation screens, the auditee account of the test data
* For the crosswalk screens, the ReCyF framework and its crosswalk to ISO 27001:2022:
  ```bash
  php artisan deming:import-framework storage/app/repository/NIS2-ReCyF-2.5-FR.xlsx
  php artisan deming:import-mappings storage/app/crosswalks/ReCyF-2.5-ISO27001-2022.mappings.xlsx
  ```
* Node dependencies installed (`npm install`)
* A Chromium browser: `npx playwright install chromium`, or an existing one set with `CHROME_PATH`

## Usage

```bash
npm run docs:screenshots
```

Examples:

```bash
# Use an installed Chrome and another account
CHROME_PATH=/usr/bin/google-chrome DEMING_LOGIN=admin DEMING_PASSWORD=secret npm run docs:screenshots

# Only some screens, in French
LANGS=fr ONLY=home1,r0,risk.matrix npm run docs:screenshots

# The creation screens in the three languages
LANGS=en,fr,de ONLY=m4,c4,a3,a4,ex3,ex4 npm run docs:screenshots

# Use a given control and measurement instead of the first ones of the lists
ID_control=18 ID_measure_done=1231 npm run docs:screenshots
```

| Variable          | Default                  | Description                                   |
|-------------------|--------------------------|-----------------------------------------------|
| `DEMING_URL`      | `http://localhost:8000`  | Application URL                               |
| `DEMING_LOGIN`    | `admin@admin.localhost`  | Administrator login                           |
| `DEMING_PASSWORD` | `admin`                  | Administrator password                        |
| `DEMING_AUDITEE_LOGIN` | `julien.moreau@example.local` | Auditee login                       |
| `DEMING_AUDITEE_PASSWORD` | `password`         | Auditee password                              |
| `CHROME_PATH`     | Playwright's Chromium    | Browser executable                            |
| `LANGS`           | `en,fr`                  | Languages to capture (`en`, `fr`, `de`)       |
| `ONLY`            | all screens              | Screen names to capture (without `.{fr}`)     |
| `OUT_DIR`         | `docs/images`            | Output directory                              |
| `ID_<lookup>`     |                          | Fixed value for a lookup of `screens.json`    |

## screens.json

* `lookups`: values used in the screen URLs (`{control}`, `{risk}`...). Each one is read from the first element
  matching `selector` on `page` (attribute `attr`, `href` by default, filtered by `regex`), seen by `user`
  (`admin` by default, or `auditee`).
* `screens`: one entry per image.
    * `name`: image name; `{fr}` is replaced by the language in French and German (`fr`, `de`) and removed in English, `{lang}` is replaced by the language
    * `url`: page to capture
    * `user`: account used, `admin` (default) or `auditee`
    * `selector`: element to capture (default: the main content)
    * `until`: stops the capture at the top of this element
    * `maxHeight`: maximum height in pixels
    * `wait`: delay in ms before the capture (default: 1200)
    * `steps`: actions before the capture, each one with a `click` (element to click) and/or a `waitFor` (element to
      wait for, up to 3 minutes), e.g. to open the AI panel and wait for the analysis

The AI analysis is written in the language of the administrator account, not in the language of the capture: change
the language of the account before capturing `ai-panel` in each language.

The language is selected with the `change_language` parameter, so the user's language is not modified.
The lookups set the filters of the measurement list in the session of the account used.
