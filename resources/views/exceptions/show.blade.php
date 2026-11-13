@extends("layout")

@section('title', $exception->name)

@section("content")

@php
    $statusColors = [
        \App\Models\Exception::STATUS_DRAFT     => '#888888',
        \App\Models\Exception::STATUS_SUBMITTED => '#3A72C4',
        \App\Models\Exception::STATUS_APPROVED  => '#3AB87A',
        \App\Models\Exception::STATUS_REJECTED  => '#D94F45',
        \App\Models\Exception::STATUS_EXPIRED   => '#E09B1A',
    ];
    $color = $statusColors[$exception->status] ?? '#888888';
@endphp

<div class="card">
    <div class="card-header">@include('partials.help', ['page' => 'exceptions.show'])<i class="bi bi-x-octagon"></i> {{ trans('cruds.exception.title_singular') }}</div>
    <div class="card-body">

    @include('partials.errors')

    <div class="container-fluid px-0">

        {{-- Nom --}}
        <div class="row">
            <div class="col-lg-1 col-md-2">
                <strong>{{ trans('cruds.exception.fields.name') }}</strong>
            </div>
            <div class="col-lg-11 col-md-10">
                {{ $exception->name }}
            </div>
        </div>

        {{-- Statut --}}
        <div class="row">
            <div class="col-lg-1 col-md-2">
                <strong>{{ trans('cruds.exception.fields.status') }}</strong>
            </div>
            <div class="col-lg-11 col-md-10">
                <span style="color:{{ $color }}; font-weight:bold; font-size:1.05em;">
                    {{ $exception->status_label }}
                </span>
            </div>
        </div>

        {{-- Mesure liée --}}
        <div class="row">
            <div class="col-lg-1 col-md-2">
                <strong>{{ trans('cruds.exception.fields.measure') }}</strong>
            </div>
            <div class="col-lg-11 col-md-10">
                @if($exception->measure)
                    <a href="/alice/show/{{ $exception->measure->id }}">
                        {{ $exception->measure->clause }} – {{ $exception->measure->name }}
                    </a>
                @else
                    –
                @endif
            </div>
        </div>

        {{-- Description --}}
        @if($exception->description)
        <div class="row">
            <div class="col-lg-1 col-md-2">
                <strong>{{ trans('cruds.exception.fields.description') }}</strong>
            </div>
            <div class="col-lg-11 col-md-10">
                <pre style="white-space:pre-wrap;">{{ $exception->description }}</pre>
            </div>
        </div>
        @endif

        {{-- Justification --}}
        @if($exception->justification)
        <div class="row">
            <div class="col-lg-1 col-md-2">
                <strong>{{ trans('cruds.exception.fields.justification') }}</strong>
            </div>
            <div class="col-lg-11 col-md-10">
                <pre style="white-space:pre-wrap;">{{ $exception->justification }}</pre>
            </div>
        </div>
        @endif

        {{-- Mesures compensatoires --}}
        @if($exception->compensating_controls)
        <div class="row">
            <div class="col-lg-1 col-md-2">
                <strong>{{ trans('cruds.exception.fields.compensating_controls') }}</strong>
            </div>
            <div class="col-lg-11 col-md-10">
                <pre style="white-space:pre-wrap;">{{ $exception->compensating_controls }}</pre>
            </div>
        </div>
        @endif

        {{-- Période de validité --}}
        <div class="row">
            <div class="col-lg-1 col-md-2">
                <strong>{{ trans('cruds.exception.fields.start_date') }}</strong>
            </div>
            <div class="col-lg-1 col-md-2 text-nowrap">
                {{ $exception->start_date?->format('Y-m-d') ?? '–' }}
            </div>
            <div class="col-lg-1 col-md-2 text-md-end">
                <strong>{{ trans('cruds.exception.fields.end_date') }}</strong>
            </div>
            <div class="col-lg-1 col-md-2 text-nowrap">
                @if($exception->end_date)
                    @if($exception->end_date->isPast())
                        <font color="#D94F45"><strong>{{ $exception->end_date->format('Y-m-d') }}</strong></font>
                        &nbsp;<span title="{{ trans('cruds.exception.expired_hint') }}" data-bs-toggle="tooltip" class="bi bi-exclamation-triangle" style="color:#D94F45;"></span>
                    @else
                        {{ $exception->end_date->format('Y-m-d') }}
                    @endif
                @else
                    –
                @endif
            </div>
        </div>

        {{-- Créé par --}}
        <div class="row">
            <div class="col-lg-1 col-md-2">
                <strong>{{ trans('cruds.exception.fields.created_by') }}</strong>
            </div>
            <div class="col-lg-11 col-md-10">
                {{ $exception->createdBy?->name ?? '–' }}
                &nbsp;–&nbsp;
                {{ $exception->created_at?->format('Y-m-d H:i') }}
            </div>
        </div>

        {{-- Soumise par --}}
        @if($exception->submittedBy)
        <div class="row">
            <div class="col-lg-1 col-md-2">
                <strong>{{ trans('cruds.exception.fields.submitted_by') }}</strong>
            </div>
            <div class="col-lg-11 col-md-10">
                {{ $exception->submittedBy->name }}
                &nbsp;–&nbsp;
                {{ $exception->submitted_at?->format('Y-m-d H:i') }}
            </div>
        </div>
        @endif

        {{-- Décision --}}
        @if($exception->approvedBy)
        <div class="row">
            <div class="col-lg-1 col-md-2">
                <strong>
                    @if($exception->status === \App\Models\Exception::STATUS_APPROVED)
                        {{ trans('cruds.exception.fields.approved_by') }}
                    @else
                        {{ trans('cruds.exception.fields.rejected_by') }}
                    @endif
                </strong>
            </div>
            <div class="col-lg-11 col-md-10">
                {{ $exception->approvedBy->name }}
                &nbsp;–&nbsp;
                {{ $exception->approved_at?->format('Y-m-d H:i') }}
                @if($exception->approval_comment)
                    <br><em>{{ $exception->approval_comment }}</em>
                @endif
            </div>
        </div>
        @endif

        {{-- ═══════════════════════════════════════════════════════════ --}}
        {{-- BOUTONS D'ACTION                                           --}}
        {{-- ═══════════════════════════════════════════════════════════ --}}
        <div class="row">
            <div class="col-12">

                {{-- Soumettre (Brouillon → Soumise) : créateur ou admin --}}
                @if($exception->canSubmit() && ((Auth::User()->role === 1) || (Auth::User()->role === 2)))
                    <form action="/exception/submit" method="POST" class="d-inline"
                        data-confirm="{{ trans('cruds.exception.confirm_submit') }}">
                        @csrf
                        <input type="hidden" name="id" value="{{ $exception->id }}"/>
                        <button class="btn btn-info" type="submit">
                            <span class="bi bi-upload"></span> {{ trans('cruds.exception.actions.submit') }}
                        </button>
                    </form>
                    &nbsp;
                @endif

                {{-- Éditer (Brouillon ou Refusée pour un User ; l'Admin peut toujours éditer) --}}
                @if(Auth::User()->isAdmin() || ($exception->canEdit() && Auth::User()->role === 2))
                    <a href="/exception/edit/{{ $exception->id }}" class="btn btn-primary">
                        <span class="bi bi-pencil"></span> {{ trans('common.edit') }}
                    </a>
                    &nbsp;
                @endif

                {{-- Supprimer (Admin uniquement) --}}
                @if(Auth::User()->role === 1)
                    <form action="/exception/delete/{{ $exception->id }}" method="GET" class="d-inline"
                        data-confirm="{{ trans('common.confirm') }}">
                        @csrf
                        <button class="btn btn-danger" type="submit">
                            <span class="bi bi-trash"></span> {{ trans('common.delete') }}
                        </button>
                    </form>
                    &nbsp;
                @endif

                {{-- Retour liste --}}
                <a class="btn btn-light border" href="/exception/index">
                    <span class="bi bi-x-circle"></span> {{ trans('common.cancel') }}
                </a>

            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════════ --}}
        {{-- PANNEAUX APPROVE / REJECT (Admin, exception Soumise)       --}}
        {{-- ═══════════════════════════════════════════════════════════ --}}
        @if($exception->canReview() && Auth::User()->role === 1)
        <div class="row mt-4">
            <div class="col-12">
                <hr>
                <h6 style="color:#3A72C4;">{{ trans('cruds.exception.review_section') }}</h6>
            </div>
        </div>

        {{-- Approuver --}}
        <div class="row">
            <div class="col-lg-1 col-md-2">
                <strong>{{ trans('cruds.exception.actions.approve') }}</strong>
            </div>
            <div class="col-lg-11 col-md-10">
                <form action="/exception/approve" method="POST" class="d-inline-block" style="width:100%;">
                    @csrf
                    <input type="hidden" name="id" value="{{ $exception->id }}"/>
                    <textarea class="form-control" name="approval_comment" rows="2"
                        placeholder="{{ trans('cruds.exception.fields.approval_comment_optional') }}"></textarea>
                    <br>
                    <button type="submit" class="btn btn-success mt-1"
                        data-confirm="{{ trans('cruds.exception.confirm_approve') }}">
                        <span class="bi bi-check-lg"></span> {{ trans('cruds.exception.actions.approve') }}
                    </button>
                </form>
            </div>
        </div>

        {{-- Refuser --}}
        <div class="row mt-2">
            <div class="col-lg-1 col-md-2">
                <strong>{{ trans('cruds.exception.actions.reject') }}</strong>
            </div>
            <div class="col-lg-11 col-md-10">
                <form action="/exception/reject" method="POST" class="d-inline-block" style="width:100%;">
                    @csrf
                    <input type="hidden" name="id" value="{{ $exception->id }}"/>
                    <textarea class="form-control" name="approval_comment" rows="2"
                        placeholder="{{ trans('cruds.exception.fields.approval_comment_required') }}"
                        required></textarea>
                    <br>
                    <button type="submit" class="btn btn-danger mt-1"
                        data-confirm="{{ trans('cruds.exception.confirm_reject') }}">
                        <span class="bi bi-x-octagon"></span> {{ trans('cruds.exception.actions.reject') }}
                    </button>
                </form>
            </div>
        </div>

        @endif {{-- canReview --}}

    </div>
</div>
</div>
@endsection
