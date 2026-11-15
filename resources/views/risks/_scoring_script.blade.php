<script @nonce>
{{-- Calcul dynamique du score + affichage conditionnel des sections --}}
document.addEventListener("DOMContentLoaded", function () {

    const config         = @json($scoringConfig);
    const usesLikelihood = config.formula === 'likelihood_x_impact';
    const usesMonarc     = config.formula === 'monarc';

    // ---- Helpers ----
    function getRadioVal(name) {
        const el = document.querySelector('input[name="' + name + '"]:checked');
        return el ? parseInt(el.value) : 0;
    }

    function findThreshold(score) {
        const thresholds = config.risk_thresholds;
        for (const t of thresholds) {
            if (t.max === null || score <= t.max) return t;
        }
        return thresholds[thresholds.length - 1];
    }

    // ---- Calcul du score ----
    function computeScore() {
        const impact = getRadioVal('impact');
        let score, likelihood = null;

        switch (config.formula) {
            case 'likelihood_x_impact':
                likelihood = getRadioVal('exposure') + getRadioVal('vulnerability');
                score = likelihood * impact;
                break;
            case 'monarc':
                score = impact * getRadioVal('probability') * getRadioVal('vulnerability');
                break;
            case 'additive':
                score = getRadioVal('probability') + impact;
                break;
            case 'max_pi':
                score = Math.max(getRadioVal('probability'), impact);
                break;
            default: // probability_x_impact
                score = getRadioVal('probability') * impact;
        }

        return { score, likelihood };
    }

    // ---- Mise à jour de l'affichage du score ----
    function updateScore() {
        const { score, likelihood } = computeScore();
        const badge  = document.getElementById('score-badge');
        const label  = document.getElementById('score-label');
        const likeEl = document.getElementById('likelihood-display');

        if (score > 0) {
            const t = findThreshold(score);
            badge.textContent      = score;
            badge.className        = 'badge';
            badge.style.background = t.color;
            badge.style.color      = getContrastColor(t.color);
            label.textContent      = t.label;
        } else {
            badge.textContent      = '—';
            badge.className        = 'badge';
            badge.style.background = '#7f8c8d';
            badge.style.color      = '#fff';
            label.textContent      = '';
        }

        if (likeEl) {
            likeEl.textContent = (usesLikelihood && likelihood !== null && likelihood > 0)
                ? '{{ trans("cruds.risk.fields.likelihood") }} : ' + likelihood
                : '';
            if (usesMonarc) likeEl.textContent = '';
        }
    }
    // ---- Affichage conditionnel des sections contrôles / actions ----
    function updateSections() {
        const statusEl = document.getElementById('risk-status');
        if (!statusEl) return;
        const status = statusEl.value;

        const measuresSection = document.getElementById('controls-section');
        const actionsSection  = document.getElementById('actions-section');

        if (measuresSection) measuresSection.style.display = (status === 'mitigated') ? '' : 'none';
        if (actionsSection)  actionsSection.style.display  = (status === 'not_accepted' || (status === 'mitigated' && usesMonarc)) ? '' : 'none';
    }

    // ---- Bindings ----
    const scoringFields = new Set(['probability', 'impact', 'exposure', 'vulnerability']);

    document.addEventListener('change', function (e) {
        if (scoringFields.has(e.target.name)) updateScore();
    });

    const statusEl = document.getElementById('risk-status');
    if (statusEl) statusEl.addEventListener('change', updateSections);

    // ---- Init ----
    updateScore();
    updateSections();
});
</script>