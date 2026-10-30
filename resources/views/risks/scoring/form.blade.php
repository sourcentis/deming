@extends("layout")

@section("content")
<div class="card">
    <div class="card-header">@include('partials.help', ['page' => 'risks.scoring_form'])<i class="bi bi-gear"></i> {{ isset($config->id) ? trans('cruds.risk_scoring.edit') : trans('cruds.risk_scoring.create') }}</div>
    <div class="card-body">

    @include('partials.errors')

    <form method="POST"
          action="{{ isset($config->id) ? '/risk/scoring/'.$config->id.'/save' : '/risk/scoring/store' }}">
        @csrf

        <div class="container-fluid px-0">

            {{-- ================================================================
                 Section : Paramètres généraux
            ================================================================ --}}

            {{-- Nom --}}
            <div class="row">
                <div class="col-lg-1 col-md-2 pt-2">
                    <strong>{{ trans("cruds.risk_scoring.fields.name") }}</strong>
                </div>
                <div class="col-lg-11 col-md-10">
                    <input class="form-control" type="text" name="name"
                           value="{{ old('name', $config->name ?? '') }}" maxlength="255" required>
                </div>
            </div>

            {{-- Formule --}}
            <div class="row">
                <div class="col-lg-1 col-md-2 pt-2">
                    <strong>{{ trans("cruds.risk_scoring.fields.formula") }}</strong>
                </div>
                <div class="col-lg-3 col-md-4">
                    <select name="formula" id="formula-select" class="form-select">
                        @foreach ($formulas as $key => $info)
                            <option value="{{ $key }}"
                                    data-requires-exposure="{{ $info['requires_exposure'] ? '1' : '0' }}"
                                    data-requires-vulnerability="{{ ($info['requires_vulnerability'] ?? false) ? '1' : '0' }}"
                                    data-description="{{ $info['description'] }}"
                                    {{ old('formula', $config->formula ?? 'probability_x_impact') === $key ? 'selected' : '' }}>
                                {{ $info['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-8 col-md-6" style="padding-top:10px">
                    <small id="formula-description" class="text-muted"></small>
                </div>
            </div>

            {{-- ================================================================
                 Section : Niveaux
            ================================================================ --}}

            <div class="row">

                {{-- COL GAUCHE : Probabilité (masquée si likelihood) --}}
                <div class="col-lg-6 col-md-12 mb-4" id="probability-col">
                    <div class="level-section-header">
                        <span class="bi bi-graph-up"></span> {{ trans("cruds.risk.fields.probability") }}
                        <small class="text-white" style="opacity:.75;font-weight:400">
                            &nbsp;— {{ trans('cruds.risk_scoring.levels_hint') }}
                        </small>
                    </div>
                    <table class="table table-sm border" style="margin-top:0">
                        <thead>
                            <tr>
                                <th width="15%">{{ trans('cruds.risk_scoring.fields.value') }}</th>
                                <th width="30%">{{ trans('cruds.risk_scoring.fields.label') }}</th>
                                <th>{{ trans('cruds.risk_scoring.fields.description') }}</th>
                                <th width="36px"></th>
                            </tr>
                        </thead>
                        <tbody id="probability-body">
                        @foreach (old('probability_levels', $probLevels) as $idx => $level)
                        <tr class="level-row">
                            <td><input type="number" name="probability_levels[{{ $idx }}][value]" class="form-control" value="{{ $level['value'] }}" min="0" required style="width:80px; text-align:center;"></td>
                            <td><input type="text"   name="probability_levels[{{ $idx }}][label]" class="form-control" value="{{ $level['label'] }}" required></td>
                            <td><input type="text"   name="probability_levels[{{ $idx }}][description]" class="form-control" value="{{ $level['description'] ?? '' }}"></td>
                            <td><button type="button" class="btn btn-sm btn-danger js-remove-level"><span class="bi bi-trash"></span></button></td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                    <button type="button" class="btn btn-sm btn-success mt-1 js-add-level" data-field="probability">
                        <span class="bi bi-plus-lg"></span> {{ trans('cruds.risk_scoring.add_level') }}
                    </button>
                </div>

                {{-- COL GAUCHE (alt) : Exposition (visible si likelihood) --}}
                <div class="col-lg-6 col-md-12 mb-4" id="exposure-col" style="display:none">
                    <div class="level-section-header" style="background:#5a7fb5">
                        <span class="bi bi-diagram-3"></span> {{ trans("cruds.risk.fields.exposure") }}
                        <small class="text-white" style="opacity:.75;font-weight:400">
                            &nbsp;— 0 = hors réseau &middot; 1 = interne &middot; 2 = Internet
                        </small>
                    </div>
                    <table class="table table-sm border" style="margin-top:0">
                        <thead>
                            <tr>
                                <th width="15%">{{ trans('cruds.risk_scoring.fields.value') }}</th>
                                <th width="30%">{{ trans('cruds.risk_scoring.fields.label') }}</th>
                                <th>{{ trans('cruds.risk_scoring.fields.description') }}</th>
                                <th width="36px"></th>
                            </tr>
                        </thead>
                        <tbody id="exposure-body">
                        @foreach (old('exposure_levels', $expLevels) as $idx => $level)
                        <tr class="level-row">
                            <td><input type="number" name="exposure_levels[{{ $idx }}][value]" class="form-control" value="{{ $level['value'] }}" min="0" style="width:80px; text-align:center;"></td>
                            <td><input type="text"   name="exposure_levels[{{ $idx }}][label]" class="form-control" value="{{ $level['label'] }}"></td>
                            <td><input type="text"   name="exposure_levels[{{ $idx }}][description]" class="form-control" value="{{ $level['description'] ?? '' }}"></td>
                            <td><button type="button" class="btn btn-sm btn-danger js-remove-level"><span class="bi bi-trash"></span></button></td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                    <button type="button" class="btn btn-sm btn-success mt-1 js-add-level" data-field="exposure">
                        <span class="bi bi-plus-lg"></span> {{ trans('cruds.risk_scoring.add_level') }}
                    </button>
                </div>

                {{-- COL DROITE : Impact (toujours visible) --}}
                <div class="col-lg-6 col-md-12 mb-4" id="impact-col">
                    <div class="level-section-header" style="background:#c0392b">
                        <span class="bi bi-exclamation-triangle"></span> {{ trans("cruds.risk.fields.impact") }}
                    </div>
                    <table class="table table-sm border" style="margin-top:0">
                        <thead>
                            <tr>
                                <th width="15%">{{ trans('cruds.risk_scoring.fields.value') }}</th>
                                <th width="30%">{{ trans('cruds.risk_scoring.fields.label') }}</th>
                                <th>{{ trans('cruds.risk_scoring.fields.description') }}</th>
                                <th width="36px"></th>
                            </tr>
                        </thead>
                        <tbody id="impact-body">
                        @foreach (old('impact_levels', $impLevels) as $idx => $level)
                        <tr class="level-row">
                            <td><input type="number" name="impact_levels[{{ $idx }}][value]" class="form-control" value="{{ $level['value'] }}" min="0" required style="width:80px; text-align:center;"></td>
                            <td><input type="text"   name="impact_levels[{{ $idx }}][label]" class="form-control" value="{{ $level['label'] }}" required></td>
                            <td><input type="text"   name="impact_levels[{{ $idx }}][description]" class="form-control" value="{{ $level['description'] ?? '' }}"></td>
                            <td><button type="button" class="btn btn-sm btn-danger js-remove-level"><span class="bi bi-trash"></span></button></td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                    <button type="button" class="btn btn-sm btn-success mt-1 js-add-level" data-field="impact">
                        <span class="bi bi-plus-lg"></span> {{ trans('cruds.risk_scoring.add_level') }}
                    </button>
                </div>

            </div>{{-- /row niveaux --}}

            {{-- Vulnérabilité : pleine largeur, visible si likelihood --}}
            <div class="row" id="vulnerability-row" style="display:none">
                <div class="col-lg-6 col-md-6 mb-4">
                    <div class="level-section-header" style="background:#7d3c98">
                        <span class="bi bi-bug"></span> {{ trans("cruds.risk.fields.vulnerability") }}
                        <small class="text-white" style="opacity:.75;font-weight:400">
                            &nbsp;— 1 = aucune &middot; 2 = connue &middot; 3 = exploitable interne &middot; 4 = exploitable externe
                        </small>
                    </div>
                    <table class="table table-sm border" style="margin-top:0">
                        <thead>
                            <tr>
                                <th width="8%">{{ trans('cruds.risk_scoring.fields.value') }}</th>
                                <th width="20%">{{ trans('cruds.risk_scoring.fields.label') }}</th>
                                <th>{{ trans('cruds.risk_scoring.fields.description') }}</th>
                                <th width="36px"></th>
                            </tr>
                        </thead>
                        <tbody id="vulnerability-body">
                        @foreach (old('vulnerability_levels', $vulnLevels) as $idx => $level)
                        <tr class="level-row">
                            <td><input type="number" name="vulnerability_levels[{{ $idx }}][value]" class="form-control" value="{{ $level['value'] }}" min="0" style="width:80px; text-align:center;"></td>
                            <td><input type="text"   name="vulnerability_levels[{{ $idx }}][label]" class="form-control" value="{{ $level['label'] }}"></td>
                            <td><input type="text"   name="vulnerability_levels[{{ $idx }}][description]" class="form-control" value="{{ $level['description'] ?? '' }}"></td>
                            <td><button type="button" class="btn btn-sm btn-danger js-remove-level"><span class="bi bi-trash"></span></button></td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                    <button type="button" class="btn btn-sm btn-success mt-1 js-add-level" data-field="vulnerability">
                        <span class="bi bi-plus-lg"></span> {{ trans('cruds.risk_scoring.add_level') }}
                    </button>
                </div>
            </div>

            {{-- ================================================================
                 Section : Seuils de classification
            ================================================================ --}}
            <div class="row mt-2 mb-0">
                <div class="col-lg-12">
                    <div class="section-header" style="background:#8059C8; margin-bottom:0;">
                        <span class="bi bi-sliders"></span> {{ trans("cruds.risk_scoring.fields.thresholds") }}
                        <small style="opacity:.75;font-weight:400">
                            &nbsp;— {{ trans('cruds.risk_scoring.thresholds_hint') }}
                        </small>
                    </div>
                </div>
            </div>

            <div class="row mt-0">
                <div class="col-lg-8 col-md-12 mb-4">
                    <table class="table table-sm border mt-1">
                        <thead>
                            <tr>
                                <th>{{ trans('cruds.risk_scoring.fields.level_key') }}</th>
                                <th>{{ trans('cruds.risk_scoring.fields.label') }}</th>
                                <th>{{ trans('cruds.risk_scoring.fields.score_max') }}</th>
                                <th>{{ trans('cruds.risk_scoring.fields.color') }}</th>
                                <th></th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="thresholds-body">
                            @foreach (old('risk_thresholds', $thresholds) as $idx => $t)
                            <tr class="threshold-row">
                                <td><input type="text"   name="risk_thresholds[{{ $idx }}][level]" class="form-control" value="{{ $t['level'] ?? '' }}" required></td>
                                <td><input type="text"   name="risk_thresholds[{{ $idx }}][label]" class="js-threshold-label form-control" value="{{ $t['label'] ?? $t['level'] ?? '' }}" required></td>
                                <td>
                                    <input type="number"
                                           name="risk_thresholds[{{ $idx }}][max]"
                                           class="js-threshold-max form-control"
                                           value="{{ isset($t['max']) && $t['max'] !== null ? $t['max'] : '' }}"
                                           placeholder="∞"
                                           min="1"
                                           style="width:80px; text-align:center;">
                                </td>
                                <td>
                                    <input type="color"
                                           style="width:40px;"
                                           name="risk_thresholds[{{ $idx }}][color]"
                                           class="js-color-input form-control form-control-color"
                                           value="{{ $t['color'] ?? '#cccccc' }}"
                                           onchange="updatePreview(this)">
                                </td>
                                <td class="text-center">
                                    <span class="badge js-preview"
                                          style="
                                            background:{{ $t['color'] ?? '#cccccc' }};
                                            color:{{ contrast_color($t['color'] ?? '#cccccc') }};
                                            padding:2px 8px;
                                            font-size:1rem"
                                    >
                                        {{ $t['label'] ?? $t['level'] ?? '?' }}
                                    </span>
                                </td>
                                <td><button type="button" class="btn btn-sm btn-danger js-remove-threshold"><span class="bi bi-trash"></span></button></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <button type="button" class="btn btn-sm btn-success mt-1" id="js-add-threshold">
                        <span class="bi bi-plus-lg"></span> {{ trans('cruds.risk_scoring.add_threshold') }}
                    </button>
                </div>
            </div>

            {{-- ================================================================
                 Boutons d'action
            ================================================================ --}}
            <div class="row mt-2">
                <div class="col-lg-12 col-md-12">
                    <button type="submit" class="btn btn-success">
                        <span class="bi bi-floppy"></span> {{ trans("common.save") }}
                    </button>
                    &nbsp;
                    <a class="btn btn-light border" href="/config/parameters?tab=scoring" role="button">
                        <span class="bi bi-x-circle"></span> {{ trans("common.cancel") }}
                    </a>
                </div>
            </div>

        </div>{{-- /grid --}}
    </form>
</div>
</div>

<style>
/*
 * section-header : barre de titre générique des sections du formulaire
 */
.section-header {
    background : #3d3d3d;
    color      : #fff;
    padding    : 8px 14px;
    font-size  : 14px;
    font-weight: 600;
    border-radius: 3px;
    margin-bottom: 12px;
}

/*
 * level-section-header : variante colorée pour les blocs Probabilité / Exposition / Impact / Vulnérabilité
 * — fond par défaut bleu-gris (Probabilité), surchargé en ligne pour les autres
 */
.level-section-header {
    background   : #2c6e9c;   /* Probabilité — bleu */
    color        : #fff;
    padding      : 7px 12px;
    font-size    : 13px;
    font-weight  : 600;
    border-radius: 3px 3px 0 0;
    margin-bottom: 0;
}

/* Colle le header à la table qui suit */
.level-section-header + .table {
    border-top: none;
}
</style>

<script>
const isNew = {{ isset($config->id) ? 'false' : 'true' }};
const formulaDefaults = @json($formulaDefaults);

document.addEventListener("DOMContentLoaded", function () {

    // =========================================================================
    // Formule
    // =========================================================================
    const formulaSelect = document.getElementById('formula-select');

    function escapeAttr(s) {
        return String(s ?? '').replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    function buildLevelRow(field, idx, level) {
        return '<tr class="level-row">' +
            '<td><input type="number" name="' + field + '_levels[' + idx + '][value]" class="form-control" value="' + escapeAttr(level.value) + '" min="0" style="width:80px; text-align:center;" required></td>' +
            '<td><input type="text"   name="' + field + '_levels[' + idx + '][label]" class="form-control" value="' + escapeAttr(level.label) + '" required></td>' +
            '<td><input type="text"   name="' + field + '_levels[' + idx + '][description]" class="form-control" value="' + escapeAttr(level.description) + '"></td>' +
            '<td><button type="button" class="btn btn-sm btn-danger js-remove-level"><span class="bi bi-trash"></span></button></td>' +
            '</tr>';
    }

    function buildThresholdRow(idx, t) {
        const color    = t.color || '#cccccc';
        const maxVal   = (t.max !== null && t.max !== undefined) ? t.max : '';
        const contrast = typeof getContrastColor === 'function' ? getContrastColor(color) : '#fff';
        return '<tr class="threshold-row">' +
            '<td><input type="text"   name="risk_thresholds[' + idx + '][level]" class="form-control" value="' + escapeAttr(t.level) + '" required></td>' +
            '<td><input type="text"   name="risk_thresholds[' + idx + '][label]" class="js-threshold-label form-control" value="' + escapeAttr(t.label) + '" required></td>' +
            '<td><input type="number" name="risk_thresholds[' + idx + '][max]"   class="js-threshold-max form-control" value="' + escapeAttr(maxVal) + '" placeholder="∞" min="1" style="width:80px; text-align:center;"></td>' +
            '<td><input type="color"  name="risk_thresholds[' + idx + '][color]" class="js-color-input form-control form-control-color" value="' + escapeAttr(color) + '" style="width:40px;" onchange="updatePreview(this)"></td>' +
            '<td class="text-center"><span class="badge js-preview" style="background:' + color + ';color:' + contrast + ';padding:2px 8px;font-size:1rem">' + escapeAttr(t.label) + '</span></td>' +
            '<td><button type="button" class="btn btn-sm btn-danger js-remove-threshold"><span class="bi bi-trash"></span></button></td>' +
            '</tr>';
    }

    function fillFormulaDefaults(formula) {
        if (!isNew) return;
        const defaults = formulaDefaults[formula];
        if (!defaults) return;

        ['probability', 'exposure', 'impact', 'vulnerability'].forEach(function (field) {
            const levels = defaults[field + '_levels'];
            if (!levels) return;
            const tbody = document.getElementById(field + '-body');
            if (!tbody) return;
            tbody.innerHTML = '';
            levels.forEach(function (level, idx) {
                tbody.insertAdjacentHTML('beforeend', buildLevelRow(field, idx, level));
            });
            counters[field] = levels.length;
        });

        if (defaults.risk_thresholds) {
            const tbody = document.getElementById('thresholds-body');
            if (tbody) {
                tbody.innerHTML = '';
                defaults.risk_thresholds.forEach(function (t, idx) {
                    const tr = document.createElement('tr');
                    tr.className = 'threshold-row';
                    tr.innerHTML = buildThresholdRow(idx, t);
                    // Assigner .value explicitement : les navigateurs n'appliquent
                    // pas toujours l'attribut value= sur les input[type=number] injectés.
                    const maxInput = tr.querySelector('.js-threshold-max');
                    if (maxInput) {
                        maxInput.value = (t.max !== null && t.max !== undefined) ? t.max : '';
                    }
                    tbody.appendChild(tr);
                });
                thrIdx = defaults.risk_thresholds.length;
            }
        }
    }

    const labelThreat      = @json(trans('cruds.risk.fields.threat'));
    const labelProbability = @json(trans('cruds.risk.fields.probability'));

    function updateFormulaUI() {
        const opt      = formulaSelect.options[formulaSelect.selectedIndex];
        const needsExp  = opt ? opt.dataset.requiresExposure === '1' : false;
        const needsVuln = opt ? opt.dataset.requiresVulnerability === '1' : false;
        const isMonarc  = formulaSelect.value === 'monarc';
        const desc      = opt ? opt.dataset.description : '';

        document.getElementById('formula-description').textContent = desc;
        document.getElementById('probability-col').style.display   = needsExp ? 'none' : '';
        document.getElementById('exposure-col').style.display      = needsExp ? ''     : 'none';
        document.getElementById('vulnerability-row').style.display = (needsExp || needsVuln) ? '' : 'none';

        // Pour MONARC : Impact en premier, Menace en second
        document.getElementById('impact-col').style.order      = isMonarc ? '1' : '';
        document.getElementById('probability-col').style.order = isMonarc ? '2' : '';

        // Renommer le header de la colonne probabilité
        const probHeader = document.querySelector('#probability-col .level-section-header');
        if (probHeader) {
            const icon = probHeader.querySelector('span.mif-chart-line');
            probHeader.textContent = ' ' + (isMonarc ? labelThreat : labelProbability);
            if (icon) probHeader.prepend(icon);
        }
    }

    formulaSelect.addEventListener('change', function () {
        updateFormulaUI();
        fillFormulaDefaults(formulaSelect.value);
    });
    updateFormulaUI();

    // =========================================================================
    // Aperçu badge couleur
    // =========================================================================
    window.updatePreview = function (inputEl) {
        const row     = inputEl.closest('tr');
        const preview = row.querySelector('.js-preview');
        const label   = row.querySelector('.js-threshold-label');
        if (!preview) return;

        const bg = inputEl.value;
        preview.style.background = bg;
        preview.style.color      = getContrastColor(bg);

        if (label) preview.textContent = label.value || '—';
    };

    document.getElementById('thresholds-body').addEventListener('input', function (e) {
        if (!e.target.classList.contains('js-threshold-label')) return;
        const row     = e.target.closest('tr');
        const preview = row.querySelector('.js-preview');
        if (preview) preview.textContent = e.target.value || '—';
    });

    // =========================================================================
    // Suppression de lignes
    // =========================================================================
    document.addEventListener('click', function (e) {

        const removeLevel = e.target.closest('.js-remove-level');
        if (removeLevel) {
            const tbody = removeLevel.closest('tbody');
            if (tbody.querySelectorAll('tr.level-row').length > 2) {
                removeLevel.closest('tr').remove();
            }
            return;
        }

        const removeThr = e.target.closest('.js-remove-threshold');
        if (removeThr) {
            const tbody = document.getElementById('thresholds-body');
            if (tbody.querySelectorAll('tr.threshold-row').length > 2) {
                removeThr.closest('tr').remove();
            }
        }
    });

    // =========================================================================
    // Ajout dynamique de niveaux
    // =========================================================================
    const counters = {
        probability   : {{ count(old('probability_levels', $probLevels)) }},
        impact        : {{ count(old('impact_levels', $impLevels)) }},
        exposure      : {{ count(old('exposure_levels', $expLevels)) }},
        vulnerability : {{ count(old('vulnerability_levels', $vulnLevels)) }},
    };

    document.querySelectorAll('.js-add-level').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const field = btn.dataset.field;
            const idx   = counters[field]++;
            const tbody = document.getElementById(field + '-body');
            const tr    = document.createElement('tr');
            tr.className = 'level-row';
            tr.innerHTML =
                '<td><input type="number" name="' + field + '_levels[' + idx + '][value]" class="form-control" min="0" style="width:80px; text-align:center;" required></td>' +
                '<td><input type="text"   name="' + field + '_levels[' + idx + '][label]" class="form-control" required></td>' +
                '<td><input type="text"   name="' + field + '_levels[' + idx + '][description]" class="form-control"></td>' +
                '<td><button type="button" class="btn btn-sm btn-danger js-remove-level"><span class="bi bi-trash"></span></button></td>';
            tbody.appendChild(tr);
        });
    });

    // =========================================================================
    // Ajout dynamique de seuils
    // =========================================================================
    let thrIdx = {{ count(old('risk_thresholds', $thresholds)) }};

    document.getElementById('js-add-threshold').addEventListener('click', function () {
        const tbody  = document.getElementById('thresholds-body');
        const tr     = document.createElement('tr');
        tr.className = 'threshold-row';

        tr.innerHTML =
            '<td><input type="text"   name="risk_thresholds[' + thrIdx + '][level]" class="form-control" required></td>' +
            '<td><input type="text"   name="risk_thresholds[' + thrIdx + '][label]" class="js-threshold-label form-control" required></td>' +
            '<td><input type="number" name="risk_thresholds[' + thrIdx + '][max]"   class="form-control" placeholder="∞" min="1" style="width:80px; text-align:center;"></td>' +
            '<td><input type="color" name="risk_thresholds[' + thrIdx + '][color]" class="js-color-input form-control form-control-color"' +
                ' value="#cccccc" style="width:40px;" onchange="updatePreview(this)"></td>' +
            '<td class="text-center"><span class="badge js-preview" style="background:#cccccc;color:#fff">—</span></td>' +
            '<td><button type="button" class="btn btn-sm btn-danger js-remove-threshold"><span class="bi bi-trash"></span></button></td>';

        tbody.appendChild(tr);

        thrIdx++;
    });

});
</script>
@endsection