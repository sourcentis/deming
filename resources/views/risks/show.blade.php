@extends("layout")

@section('title', $risk->name)

@section("content")
<div class="card">
    <div class="card-header">@include('partials.help', ['page' => 'risks.show'])<i class="bi bi-exclamation-triangle"></i> {{ trans("cruds.risk.singular") }}</div>
    <div class="card-body">
    @include('partials.ai-button', ['type' => 'risk', 'object' => $risk])

<div class="container-fluid px-0">

    {{-- Nom --}}
    <div class="row">
        <div class="col-lg-1 col-md-2">
            <strong>{{ trans("cruds.risk.fields.name") }}</strong>
        </div>
        <div class="col-lg-10 col-md-9">
            {{ $risk->name }}
        </div>

        <div class="col-lg-1 col-md-1 justify-content-end d-flex">
            @if (Auth::User()->role === 1 || Auth::User()->role === 2)
                <button class="btn btn-primary" onclick="location.href='/risk/create'">
                    <span class="bi bi-plus-lg"></span>
                    {{ trans('common.new') }}
                </button>
            @endif
        </div>

    </div>

    {{-- Description --}}
    @if ($risk->description)
    <div class="row">
        <div class="col-lg-1 col-md-2">
            <strong>{{ trans("cruds.risk.fields.description") }}</strong>
        </div>
        <div class="col-lg-11 col-md-10">
            <pre>{{ $risk->description }}</pre>
        </div>
    </div>
    @endif

    {{-- Propriétaire --}}
    <div class="row">
        <div class="col-lg-1 col-md-2">
            <strong>{{ trans("cruds.risk.fields.owner") }}</strong>
        </div>
        <div class="col-lg-3 col-md-4">
            {{ $risk->owner?->name ?? '—' }}
        </div>
    </div>
    <div class="row">
        <div class="col-lg-1 col-md-2">
            <strong>{{ trans("cruds.risk.fields.review_frequency") }}</strong>
        </div>
        <div class="col-lg-2 col-md-3">
            {{ $risk->review_frequency }} {{ trans('common.months') }}
            @if ($risk->next_review_at)
                &nbsp;·&nbsp;
                @if ($risk->is_overdue)
                    <font color="red"><b>{{ $risk->next_review_at->format('Y-m-d') }}</b></font>
                    &nbsp;&#9888;
                @else
                    <font color="green">{{ $risk->next_review_at->format('Y-m-d') }}</font>
                @endif
            @endif
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
        <div class="col-lg-1 col-md-3">
            <span class="badge" style="font-size:1.1rem;background:#7f8c8d;color:#fff">{{ $risk->impact }}</span>
            &nbsp;
            {{ $scoringConfig->levelLabel('impact', $risk->impact) }}
        </div>
        @if ($risk->impact_comment)
        <div class="col-lg-10 col-md-7">
            <small class="text-muted">{{ $risk->impact_comment }}</small>
        </div>
        @endif
    </div>
    @endif

    {{-- Évaluation : probabilité / menace --}}
    @if (!$scoringConfig->usesLikelihood())
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
        <div class="col-lg-1 col-md-3">
            <span class="badge" style="font-size:1.1rem;background:#7f8c8d;color:#fff">{{ $risk->probability }}</span>
            &nbsp;
            {{ $scoringConfig->levelLabel('probability', $risk->probability) }}
        </div>
        @if ($risk->probability_comment)
        <div class="col-lg-10 col-md-7">
            <small class="text-muted">{{ $risk->probability_comment }}</small>
        </div>
        @endif
    </div>
    @endif

    {{-- Exposition (likelihood_x_impact uniquement) --}}
    @if ($scoringConfig->usesLikelihood())
    <div class="row">
        <div class="col-lg-1 col-md-2">
            <strong>{{ trans("cruds.risk.fields.exposure") }}</strong>
        </div>
        <div class="col-lg-1 col-md-2">
            <span class="badge" style="font-size:1.1rem;background:#7f8c8d;color:#fff">{{ $risk->exposure ?? '—' }}</span>
            &nbsp;{{ $scoringConfig->levelLabel('exposure', $risk->exposure ?? 0) }}
        </div>
    </div>
    @endif

    {{-- Vulnérabilité (likelihood_x_impact ou monarc) --}}
    @if ($scoringConfig->usesLikelihood() || $scoringConfig->usesMonarc())
    <div class="row">
        <div class="col-lg-1 col-md-2">
            <strong>{{ trans("cruds.risk.fields.vulnerability") }}</strong>
        </div>
        <div class="col-lg-2 col-md-3">
            <span class="badge" style="font-size:1.1rem;background:#7f8c8d;color:#fff">{{ $risk->vulnerability ?? '—' }}</span>
            &nbsp;{{ $scoringConfig->levelLabel('vulnerability', $risk->vulnerability ?? 0) }}
        </div>
    </div>
    @endif

    {{-- Vraisemblance calculée (likelihood_x_impact uniquement) --}}
    @if ($scoringConfig->usesLikelihood())
    <div class="row">
        <div class="col-lg-1 col-md-2">
            <strong>{{ trans("cruds.risk.fields.likelihood") }}</strong>
        </div>
        <div class="col-lg-1 col-md-3">
            <span class="badge" style="font-size:1.1rem;background:#7f8c8d;color:#fff">{{ $risk->risk_likelihood ?? '—' }}</span>
        </div>
    </div>
    @endif

    {{-- Impact (pour les formules non-MONARC) --}}
    @if (!$scoringConfig->usesMonarc())
    <div class="row">
        <div class="col-lg-1 col-md-2">
            <strong>{{ trans("cruds.risk.fields.impact") }}</strong>
        </div>
        <div class="col-lg-1 col-md-3">
            <span class="badge" style="font-size:1.1rem;background:#7f8c8d;color:#fff">{{ $risk->impact }}</span>
            &nbsp;
            {{ $scoringConfig->levelLabel('impact', $risk->impact) }}
        </div>
        @if ($risk->impact_comment)
        <div class="col-lg-10 col-md-7">
            <small class="text-muted">{{ $risk->impact_comment }}</small>
        </div>
        @endif
    </div>
    @endif

    <div class="row">
    </div>

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
            <span class="badge"
                  style="
                    font-size:1.1rem;
                    background:{{ $threshold['color'] }};
                    color: {{ contrast_color($threshold['color']) }}"
            >
                {{ $risk->risk_score }}
            </span>
            &nbsp;
            <strong>{{ $threshold['label'] }}</strong>
        </div>
    </div>

    <div class="row">
    </div>

    {{-- Statut de traitement --}}
    <div class="row">
        <div class="col-lg-1 col-md-2">
            <strong>{{ trans("cruds.risk.fields.status") }}</strong>
        </div>
        <div class="col-lg-11 col-md-10">
            <span class="badge text-bg-{{ \App\Models\Risk::STATUS_COLORS[$risk->status] ?? 'secondary' }}" style="font-size:1rem;">
                {{ \App\Models\Risk::statusLabel($risk->status) }}
            </span>
            @if ($risk->status_comment)
                <pre class="mt-2">{{ $risk->status_comment }}</pre>
            @endif
        </div>
    </div>

    {{-- Contrôles liés --}}
    @if ($risk->controls->isNotEmpty())
    <div class="row">
        <div class="col-lg-1 col-md-2">
            <strong>{{ trans("cruds.risk.fields.controls") }}</strong>
        </div>
        <div class="col-lg-11 col-md-10">
            @foreach ($risk->controls as $control)
                <a href="/alice/show/{{ $control->id }}">{{ $control->clause }} - {{ $control->name }}</a>
                @if (!$loop->last) <br> @endif
            @endforeach
        </div>
    </div>
    @endif

    {{-- Plans d'action liés --}}
    @if ($risk->actions->isNotEmpty())
    <div class="row">
        <div class="col-lg-1 col-md-2">
            <strong>{{ trans("cruds.risk.fields.action_plan") }}</strong>
        </div>
        <div class="col-lg-11 col-md-10">
            @foreach ($risk->actions as $action)
                <a href="/action/show/{{ $action->id }}">{{ $action->name }}</a>
                @if (!$loop->last) , @endif
            @endforeach
        </div>
    </div>
    @endif

    {{-- Risque résiduel (MONARC uniquement) --}}
    @if ($scoringConfig->usesMonarc())
    <div class="row">
        <div class="col-lg-1 col-md-2">
            <strong>{{ trans("cruds.risk.fields.residual_risk") }}</strong>
        </div>
        <div class="col-lg-1 col-md-3">
            @if ($risk->residual_risk !== null)
                @php
                    $residualThreshold = $scoringConfig->thresholdFor($risk->residual_risk);
                @endphp
                <span class="badge"
                      style="
                        font-size:1.1rem;
                        background:{{ $residualThreshold['color'] }};
                        color: {{ contrast_color($residualThreshold['color']) }}"
                >
                    {{ $risk->residual_risk }}
                </span>
            @else
                <span class="badge text-bg-secondary" style="font-size:1.1rem">—</span>
            @endif
        </div>
    </div>
    @endif

    <div class="row">
    </div>

    {{-- Boutons d'action --}}
    <div class="row">
        <div class="col-12">

            @if (Auth::User()->role === 1 || Auth::User()->role === 2)
                <a href="/risk/edit/{{ $risk->id }}" class="btn btn-primary">
                    <span class="bi bi-pencil"></span> {{ trans("common.edit") }}
                </a>
                &nbsp;
            @endif

            @if (Auth::User()->role === 1)
                <form action="/risk/delete/{{ $risk->id }}" onSubmit="if(!confirm('{{ trans('common.confirm') }}')){return false;}" class="d-inline">
                    @csrf
                    <button class="btn btn-danger">
                        <span class="bi bi-trash"></span> {{ trans("common.delete") }}
                    </button>
                </form>
                &nbsp;
                <a class="btn btn-light border" href="/logs/history/risk/{{ $risk->id }}">
                    <span class="bi bi-journal-text"></span> {{ trans("common.history") }}
                </a>
                &nbsp;
            @endif

            <a class="btn btn-light border" href="/risk/index">
                <span class="bi bi-x-circle"></span> {{ trans("common.cancel") }}
            </a>

        </div>
    </div>

</div>
</div>
</div>
@endsection