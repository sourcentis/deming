@extends("layout")

@section("content")
<div class="card">
    <div class="card-header">@include('partials.help', ['page' => 'risks.create'])<i class="bi bi-exclamation-triangle"></i> {{ trans("cruds.risk.create") }}</div>
    <div class="card-body">

    @include('partials.errors')

    <form method="POST" action="/risk/store">
        @csrf

        <div class="container-fluid px-0">

            {{-- Nom --}}
            <div class="row">
                <div class="col-lg-1 col-md-2">
                    <strong>{{ trans("cruds.risk.fields.name") }}</strong>
                </div>
                <div class="col-lg-11 col-md-10">
                    <input class="form-control" type="text" name="name"
                           value="{{ old('name') }}" maxlength="255" required>
                </div>
            </div>

            {{-- Description --}}
            <div class="row">
                <div class="col-lg-1 col-md-2">
                    <strong>{{ trans("cruds.risk.fields.description") }}</strong>
                </div>
                <div class="col-lg-11 col-md-10">
                    <textarea class="form-control" name="description" rows="3">{{ old('description') }}</textarea>
                </div>
            </div>

            {{-- Propriétaire + Fréquence de revue --}}
            <div class="row">
                <div class="col-lg-1 col-md-2">
                    <strong>{{ trans("cruds.risk.fields.owner") }}</strong>
                </div>
                <div class="col-lg-3 col-md-5">
                    <select class="form-select select2" name="owner_id">
                        <option value="">— {{ trans('cruds.risk.fields.no_owner') }} —</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}"
                                {{ old('owner_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-1 col-md-2">
                    <strong>{{ trans("cruds.risk.fields.review_frequency") }}</strong>
                </div>
                <div class="col-lg-2 col-xl-1 col-md-2">
                    <input class="form-control" type="number" name="review_frequency"
                           value="{{ old('review_frequency', 12) }}" min="1" max="60">
                </div>
                <div class="col-lg-1 col-md-1">
                    {{ trans('common.months') }}
                </div>
            </div>

            <div class="row"></div>

            {{-- Section probabilité / menace (masquée si formule likelihood) --}}
            <div id="probability-section" @if($scoringConfig->usesLikelihood()) style="display:none" @endif>
                <div class="row">
                    <div class="col-lg-1 col-md-2">
                        <strong>
                            @if ($scoringConfig->usesMonarc())
                                {{ trans("cruds.risk.fields.threat") }}
                            @else
                                {{ trans("cruds.risk.fields.probability") }}
                            @endif
                        </strong>
                    </div>
                    <div class="col-lg-11 col-md-10">
                        @foreach ($scoringConfig->probability_levels ?? [] as $level)
                        <div class="form-check"><input id="probability_{{ $level['value'] }}" class="form-check-input" type="radio" name="probability" value="{{ $level['value'] }}"
                               {{ old('probability', 1) == $level['value'] ? 'checked' : '' }}/><label class="form-check-label" for="probability_{{ $level['value'] }}"><b>{{ $level['value'] }}</b> — {{ $level['label'] }}@if (!empty($level['description'])) <span class="text-muted">— {{ $level['description'] }}</span>@endif</label></div>
                        @endforeach
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-1 col-md-2"></div>
                    <div class="col-lg-11 col-md-10">
                        <textarea class="form-control" name="probability_comment" rows="2"
                                  placeholder="{{ trans('cruds.risk.fields.probability_comment') }}">{{ old('probability_comment') }}</textarea>
                    </div>
                </div>
            </div>

            {{-- Section exposition (likelihood_x_impact uniquement) --}}
            @if ($scoringConfig->usesLikelihood())
            <div class="row">
                <div class="col-lg-1 col-md-2">
                    <strong>{{ trans("cruds.risk.fields.exposure") }}</strong>
                </div>
                <div class="col-lg-11 col-md-10">
                    @foreach ($scoringConfig->exposure_levels ?? [] as $level)
                    <div class="form-check"><input id="exposure_{{ $level['value'] }}" class="form-check-input" type="radio" name="exposure" value="{{ $level['value'] }}"
                           {{ old('exposure', 0) == $level['value'] ? 'checked' : '' }}/><label class="form-check-label" for="exposure_{{ $level['value'] }}"><b>{{ $level['value'] }}</b> — {{ $level['label'] }}@if (!empty($level['description'])) <span class="text-muted">— {{ $level['description'] }}</span>@endif</label></div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Section vulnérabilité (likelihood_x_impact ou monarc) --}}
            @if ($scoringConfig->usesLikelihood() || $scoringConfig->usesMonarc())
            <div class="row">
                <div class="col-lg-1 col-md-2">
                    <strong>{{ trans("cruds.risk.fields.vulnerability") }}</strong>
                </div>
                <div class="col-lg-11 col-md-10">
                    @foreach ($scoringConfig->vulnerability_levels ?? [] as $level)
                    <div class="form-check"><input id="vulnerability_{{ $level['value'] }}" class="form-check-input" type="radio" name="vulnerability" value="{{ $level['value'] }}"
                           {{ old('vulnerability', 0) == $level['value'] ? 'checked' : '' }}/><label class="form-check-label" for="vulnerability_{{ $level['value'] }}"><b>{{ $level['value'] }}</b> — {{ $level['label'] }}@if (!empty($level['description'])) <span class="text-muted">— {{ $level['description'] }}</span>@endif</label></div>
                    @endforeach
                </div>
            </div>
            @endif

            <div class="row"></div>

            {{-- Impact --}}
            <div class="row">
                <div class="col-lg-1 col-md-2">
                    <strong>{{ trans("cruds.risk.fields.impact") }}</strong>
                </div>
                <div class="col-lg-11 col-md-10">
                    @foreach ($scoringConfig->impact_levels ?? [] as $level)
                    <div class="form-check"><input id="impact_{{ $level['value'] }}" class="form-check-input" type="radio" name="impact" value="{{ $level['value'] }}"
                           {{ old('impact', 1) == $level['value'] ? 'checked' : '' }}/><label class="form-check-label" for="impact_{{ $level['value'] }}"><b>{{ $level['value'] }}</b> — {{ $level['label'] }}@if (!empty($level['description'])) <span class="text-muted">— {{ $level['description'] }}</span>@endif</label></div>
                    @endforeach
                </div>
            </div>
            <div class="row">
                <div class="col-lg-1 col-md-2"></div>
                <div class="col-lg-11 col-md-10">
                    <textarea class="form-control" name="impact_comment" rows="2"
                              placeholder="{{ trans('cruds.risk.fields.impact_comment') }}">{{ old('impact_comment') }}</textarea>
                </div>
            </div>

            {{-- Score calculé (dynamique) --}}
            <div class="row">
                <div class="col-lg-1 col-md-2">
                    <strong>{{ trans("cruds.risk.fields.score") }}</strong>
                </div>
                <div class="col-lg-11 col-md-10">
                    <span id="score-badge" class="badge text-bg-secondary" style="font-size:1.1rem">—</span>
                    &nbsp;
                    <span id="score-label" class="text-muted"></span>
                    &nbsp;
                    <small id="likelihood-display" class="text-muted"></small>
                </div>
            </div>

            <div class="row"></div>

            {{-- Statut de traitement --}}
            <div class="row">
                <div class="col-lg-1 col-md-2">
                    <strong>{{ trans("cruds.risk.fields.status") }}</strong>
                </div>
                <div class="col-lg-2 col-md-3">
                    <select class="form-select select2" name="status" id="risk-status">
                        @foreach (\App\Models\Risk::availableStatuses() as $value => $label)
                            <option value="{{ $value }}" {{ old('status', 'not_evaluated') === $value ? 'selected' : '' }}>
                                {{ trans($label) }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-1 col-md-2"></div>
                <div class="col-lg-11 col-md-10">
                    <textarea class="form-control" name="status_comment" rows="2"
                              placeholder="{{ trans('cruds.risk.fields.status_comment') }}">{{ old('status_comment') }}</textarea>
                </div>
            </div>

            {{-- Contrôles liés (visible si mitigated) --}}
            <div id="controls-section" style="display:none">
                <div class="row">
                    <div class="col-lg-1 col-md-2">
                        <strong>{{ trans("cruds.risk.fields.controls") }}</strong>
                        <br><small class="text-muted">{{ trans('cruds.risk.fields.controls_hint') }}</small>
                    </div>
                    <div class="col-lg-11 col-md-10">
                        <select class="form-select select2" name="control_ids[]" multiple>
                            @foreach ($controls as $control)
                                <option value="{{ $control->id }}"
                                    {{ in_array($control->id, old('control_ids', [])) ? 'selected' : '' }}>
                                    {{ $control->clause }} - {{ $control->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            {{-- Plans d'action liés (visible si not_accepted) --}}
            <div id="actions-section" style="display:none">
                <div class="row">
                    <div class="col-lg-1 col-md-2">
                        <strong>{{ trans("cruds.risk.fields.action_plan") }}</strong>
                        <br><small class="text-muted">{{ trans('cruds.risk.fields.actions_hint') }}</small>
                    </div>
                    <div class="col-lg-11 col-md-10">
                        <select class="form-select select2" name="action_ids[]" multiple>
                            @foreach ($actions as $action)
                                <option value="{{ $action->id }}"
                                    {{ in_array($action->id, old('action_ids', [])) ? 'selected' : '' }}>
                                    {{ $action->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            {{-- Risque résiduel (saisie manuelle, MONARC uniquement) --}}
            @if ($scoringConfig->usesMonarc())
            <div class="row"></div>

            <div class="row">
                <div class="col-lg-1 col-md-2">
                    <strong>{{ trans("cruds.risk.fields.residual_risk") }}</strong>
                </div>
                <div class="col-lg-2 col-xl-1 col-md-2">
                    <input class="form-control" type="number" name="residual_risk" min="0" max="99" step="1"
                           value="{{ old('residual_risk') }}">
                </div>
            </div>
            @endif

            <div class="row"></div>

            {{-- Boutons --}}
            <div class="row">
                <div class="col-lg-12 col-md-12">
                    <button type="submit" class="btn btn-success">
                        <span class="bi bi-floppy"></span> {{ trans("common.save") }}
                    </button>
                    &nbsp;
                    <a class="btn cancel btn-light border" href="/risk/index" role="button">
                        <span class="bi bi-x-circle"></span> {{ trans("common.cancel") }}
                    </a>
                </div>
            </div>

        </div>
    </form>
</div>
</div>

@include('risks._scoring_script')
@endsection