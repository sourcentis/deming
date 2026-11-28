@extends("layout")

@section('title', $risk->name)

@section("content")
<div class="card">
    <div class="card-header">@include('partials.help', ['page' => 'risks.edit'])<i class="bi bi-exclamation-triangle"></i> {{ trans("cruds.risk.edit") }}</div>
    <div class="card-body">

    @include('partials.errors')

    <form method="POST" action="/risk/save">
        @csrf
        <input type="hidden" name="id" value="{{ $risk->id }}"/>

        <div class="container-fluid px-0">

            {{-- Nom --}}
            <div class="row">
                <div class="col-lg-1 col-md-2">
                    <strong>{{ trans("cruds.risk.fields.name") }}</strong>
                </div>
                <div class="col-lg-11 col-md-10">
                    <input class="form-control" type="text" name="name"
                           value="{{ $risk->name }}" maxlength="255" required>
                </div>
            </div>

            {{-- Description --}}
            <div class="row">
                <div class="col-lg-1 col-md-2">
                    <strong>{{ trans("cruds.risk.fields.description") }}</strong>
                </div>
                <div class="col-lg-11 col-md-10">
                    <textarea class="form-control" name="description" rows="3">{{ old('description', $risk->description) }}</textarea>
                </div>
            </div>

            {{-- Propriétaire + Fréquence de revue + Prochaine revue --}}
            <div class="row">
                <div class="col-lg-1 col-md-2">
                    <strong>{{ trans("cruds.risk.fields.owner") }}</strong>
                </div>
                <div class="col-lg-3 col-md-4">
                    <select class="form-select select2" name="owner_id">
                        <option value="">— {{ trans('cruds.risk.fields.no_owner') }} —</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}"
                                {{ old('owner_id', $risk->owner_id) == $user->id ? 'selected' : '' }}>
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
                           value="{{ old('review_frequency', $risk->review_frequency) }}"
                           min="1" max="60">
                </div>
                <div class="col-lg-1 col-md-1 text-md-end">
                    <strong>{{ trans("cruds.risk.fields.next_review") }}</strong>
                </div>
                <div class="col-lg-2 col-md-3">
                    <input class="form-control" type="date"
                           name="next_review_at"
                           value="{{ old('next_review_at', $risk->next_review_at?->format('Y-m-d')) }}"/>
                </div>
            </div>

            <div class="row">
            </div>

            {{-- Impact (en premier pour MONARC) --}}
            @if ($scoringConfig->usesMonarc())
            <div class="row">
                <div class="col-lg-1 col-md-2">
                    <strong>{{ trans("cruds.risk.fields.impact") }}</strong>
                </div>
                <div class="col-lg-11 col-md-10">
                    @foreach ($scoringConfig->impact_levels ?? [] as $level)
                    <div class="form-check"><input id="impact_{{ $level['value'] }}" class="form-check-input" type="radio" name="impact" value="{{ $level['value'] }}"
                           {{ old('impact', $risk->impact) == $level['value'] ? 'checked' : '' }}/><label class="form-check-label" for="impact_{{ $level['value'] }}"><b>{{ $level['value'] }}</b> — {{ $level['label'] }}@if (!empty($level['description'])) <span class="text-muted">— {{ $level['description'] }}</span>@endif</label></div>
                    @endforeach
                </div>
            </div>
            <div class="row">
                <div class="col-lg-1 col-md-2"></div>
                <div class="col-lg-11 col-md-10">
                    <textarea class="form-control" name="impact_comment" rows="2"
                              placeholder="{{ trans('cruds.risk.fields.impact_comment') }}">{{ old('impact_comment', $risk->impact_comment) }}</textarea>
                </div>
            </div>
            <div class="row"></div>
            @endif

            {{-- Section probabilité / menace --}}
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
                               {{ old('probability', $risk->probability) == $level['value'] ? 'checked' : '' }}/><label class="form-check-label" for="probability_{{ $level['value'] }}"><b>{{ $level['value'] }}</b> — {{ $level['label'] }}@if (!empty($level['description'])) <span class="text-muted">— {{ $level['description'] }}</span>@endif</label></div>
                        @endforeach
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-1 col-md-2"></div>
                    <div class="col-lg-11 col-md-10">
                        <textarea class="form-control" name="probability_comment" rows="2"
                                  placeholder="{{ trans('cruds.risk.fields.probability_comment') }}">{{ old('probability_comment', $risk->probability_comment) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="row">
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
                           {{ old('exposure', $risk->exposure ?? 0) == $level['value'] ? 'checked' : '' }}/><label class="form-check-label" for="exposure_{{ $level['value'] }}"><b>{{ $level['value'] }}</b> — {{ $level['label'] }}@if (!empty($level['description'])) <span class="text-muted">— {{ $level['description'] }}</span>@endif</label></div>
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
                           {{ old('vulnerability', $risk->vulnerability ?? 0) == $level['value'] ? 'checked' : '' }}/><label class="form-check-label" for="vulnerability_{{ $level['value'] }}"><b>{{ $level['value'] }}</b> — {{ $level['label'] }}@if (!empty($level['description'])) <span class="text-muted">— {{ $level['description'] }}</span>@endif</label></div>
                    @endforeach
                </div>
            </div>
            @endif

            <div class="row">
            </div>

            {{-- Impact (pour les formules non-MONARC) --}}
            @if (!$scoringConfig->usesMonarc())
            <div class="row">
                <div class="col-lg-1 col-md-2">
                    <strong>{{ trans("cruds.risk.fields.impact") }}</strong>
                </div>
                <div class="col-lg-11 col-md-10">
                    @foreach ($scoringConfig->impact_levels ?? [] as $level)
                    <div class="form-check"><input id="impact_{{ $level['value'] }}" class="form-check-input" type="radio" name="impact" value="{{ $level['value'] }}"
                           {{ old('impact', $risk->impact) == $level['value'] ? 'checked' : '' }}/><label class="form-check-label" for="impact_{{ $level['value'] }}"><b>{{ $level['value'] }}</b> — {{ $level['label'] }}@if (!empty($level['description'])) <span class="text-muted">— {{ $level['description'] }}</span>@endif</label></div>
                    @endforeach
                </div>
            </div>
            <div class="row">
                <div class="col-lg-1 col-md-2"></div>
                <div class="col-lg-11 col-md-10">
                    <textarea class="form-control" name="impact_comment" rows="2"
                              placeholder="{{ trans('cruds.risk.fields.impact_comment') }}">{{ old('impact_comment', $risk->impact_comment) }}</textarea>
                </div>
            </div>
            @endif

            {{-- Score calculé --}}
            <div class="row">
                <div class="col-lg-1 col-md-2">
                    <strong>{{ trans("cruds.risk.fields.score") }}</strong>
                </div>
                <div class="col-lg-11 col-md-10">
                @php
                    $score     = $risk->computedScore($scoringConfig);
                    $threshold = $scoringConfig->thresholdFor($score);
                @endphp
                    <span id="score-badge" class="badge"
                      style="background:{{ $threshold['color'] }};
                            color:{{ contrast_color($threshold['color']) }};
                            padding:2px 8px;
                            font-size:1rem;
                            cursor:pointer">
                        {{ $score }}
                    </span>
                    &nbsp;
                    <span id="score-label" class="text-muted">{{ $risk->risk_level_label }}</span>
                    &nbsp;
                    <small id="likelihood-display" class="text-muted">
                        @if ($scoringConfig->usesLikelihood() && $risk->risk_likelihood !== null)
                            {{ trans('cruds.risk.fields.likelihood') }} : {{ $risk->risk_likelihood }}
                        @endif
                    </small>
                </div>
            </div>

            <div class="row">
            </div>

            {{-- Statut --}}
            <div class="row">
                <div class="col-lg-1 col-md-2">
                    <strong>{{ trans("cruds.risk.fields.status") }}</strong>
                </div>
                <div class="col-lg-2 col-md-3">
                    <select class="form-select select2" name="status" id="risk-status">
                        @foreach (\App\Models\Risk::availableStatuses() as $value => $label)
                            <option value="{{ $value }}"
                                {{ old('status', $risk->status) === $value ? 'selected' : '' }}>
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
                              placeholder="{{ trans('cruds.risk.fields.status_comment') }}">{{ old('status_comment', $risk->status_comment) }}</textarea>
                </div>
            </div>

            {{-- Contrôles liés (mitigated) --}}
            <div id="controls-section" @if($risk->status !== 'mitigated') style="display:none" @endif>
                <div class="row">
                    <div class="col-lg-1 col-md-2">
                        <strong>{{ trans("cruds.risk.fields.controls") }}</strong>
                    </div>
                    <div class="col-lg-11 col-md-10">
                        <select class="form-select select2" name="control_ids[]" multiple>
                            @foreach ($controls as $control)
                                <option value="{{ $control->id }}"
                                    {{ in_array($control->id, old('control_ids', $risk->controls->pluck('id')->toArray())) ? 'selected' : '' }}>
                                    {{ $control->clause }} - {{ $control->name }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">{{ trans('cruds.risk.fields.controls_hint') }}</small>
                    </div>
                </div>
            </div>

            {{-- Plans d'action (not_accepted, ou mitigated en mode MONARC) --}}
            @php
                $showActions = $risk->status === 'not_accepted'
                    || ($risk->status === 'mitigated' && $scoringConfig->usesMonarc());
            @endphp
            <div id="actions-section" @if(!$showActions) style="display:none" @endif>
                <div class="row">
                    <div class="col-lg-1 col-md-2">
                        <strong>{{ trans("cruds.risk.fields.action_plan") }}</strong>
                    </div>
                    <div class="col-lg-11 col-md-10">
                        <select class="form-select select2" name="action_ids[]" multiple>
                            @foreach ($actions as $action)
                                <option value="{{ $action->id }}"
                                    {{ in_array($action->id, old('action_ids', $risk->actions->pluck('id')->toArray())) ? 'selected' : '' }}>
                                    {{ $action->name }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">{{ trans('cruds.risk.fields.actions_hint') }}</small>
                    </div>
                </div>
            </div>


            {{-- Risque résiduel (saisie manuelle, MONARC uniquement) --}}
            @if ($scoringConfig->usesMonarc())
            <div class="row">
            </div>

            <div class="row">
                <div class="col-lg-1 col-md-2">
                    <strong>{{ trans("cruds.risk.fields.residual_risk") }}</strong>
                </div>
                <div class="col-lg-2 col-xl-1 col-md-2">
                    <input class="form-control" type="number" name="residual_risk" min="0" max="99" step="1"
                           value="{{ old('residual_risk', $risk->residual_risk) }}">
                </div>
            </div>
            @endif

            <div class="row">
            </div>

            {{-- Boutons --}}
            <div class="row">
                <div class="col-lg-12 col-md-12">
                    <button type="submit" class="btn btn-success">
                        <span class="bi bi-floppy"></span> {{ trans("common.save") }}
                    </button>
                    &nbsp;
                    <a class="btn btn-light border" href="/risk/show/{{ $risk->id }}" role="button">
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