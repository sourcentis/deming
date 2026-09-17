@extends('layout')

@section('content')
<div data-role="panel"
     data-title-caption="{{ trans('cruds.crosswalk.show') }}"
     data-collapsible="false"
     data-title-icon="<span class='mif-shuffle'></span>">
    <div class="grid">
        <div class="row">
            <div class="cell-lg-2 cell-md-3"><strong>{{ trans('cruds.crosswalk.source') }}</strong></div>
            <div class="cell-lg-8 cell-md-9">
                <strong>{{ $mapping->sourceControl->domain->framework }}</strong> —
                <a href="/alice/show/{{ $mapping->sourceControl->id }}">
                    {{ trim($mapping->sourceControl->clause) }} — {{ $mapping->sourceControl->name }}
                </a>
            </div>
        </div>
        <div class="row">
            <div class="cell-lg-2 cell-md-3"><strong>{{ trans('cruds.crosswalk.target') }}</strong></div>
            <div class="cell-lg-8 cell-md-9">
                <strong>{{ $mapping->targetControl->domain->framework }}</strong> —
                <a href="/alice/show/{{ $mapping->targetControl->id }}">
                    {{ trim($mapping->targetControl->clause) }} — {{ $mapping->targetControl->name }}
                </a>
            </div>
        </div>
        <div class="row">
            <div class="cell-lg-2 cell-md-3"><strong>{{ trans('cruds.crosswalk.fields.mapping_type') }}</strong></div>
            <div class="cell-lg-3 cell-md-4">{{ trans('cruds.crosswalk.mapping_types.' . $mapping->mapping_type) }}</div>
            <div class="cell-lg-2 cell-md-2"><strong>{{ trans('cruds.crosswalk.fields.coverage') }}</strong></div>
            <div class="cell-lg-3 cell-md-3">
                {{ $mapping->coverage ? trans('cruds.crosswalk.coverage_levels.' . $mapping->coverage) : '–' }}
            </div>
        </div>
        <div class="row">
            <div class="cell-lg-2 cell-md-3"><strong>{{ trans('cruds.crosswalk.fields.confidence') }}</strong></div>
            <div class="cell-lg-8 cell-md-9">{{ $mapping->confidence !== null ? $mapping->confidence . '%' : '–' }}</div>
        </div>
        <div class="row">
            <div class="cell-lg-2 cell-md-3"><strong>{{ trans('cruds.crosswalk.fields.rationale') }}</strong></div>
            <div class="cell-lg-8 cell-md-9"><pre style="white-space:pre-wrap">{{ $mapping->rationale ?? '–' }}</pre></div>
        </div>
        <div class="row">
            <div class="cell-lg-2 cell-md-3"><strong>{{ trans('cruds.crosswalk.fields.source_reference') }}</strong></div>
            <div class="cell-lg-8 cell-md-9">{{ $mapping->source_reference ?? '–' }}</div>
        </div>
        <div class="row">
            <div class="cell-lg-2 cell-md-3"><strong>{{ trans('cruds.crosswalk.fields.source_url') }}</strong></div>
            <div class="cell-lg-8 cell-md-9">
                @if($mapping->source_url)
                    <a href="{{ $mapping->source_url }}" target="_blank" rel="noopener noreferrer">
                        {{ $mapping->source_url }}
                    </a>
                @else
                    –
                @endif
            </div>
        </div>
        <div class="row">
            <div class="cell-lg-2 cell-md-3"><strong>{{ trans('cruds.crosswalk.fields.validated') }}</strong></div>
            <div class="cell-lg-8 cell-md-9">
                @if($mapping->validated)
                    {{ trans('cruds.crosswalk.validated_by', [
                        'name' => $mapping->validator?->name ?? '–',
                        'date' => $mapping->validated_at?->format('Y-m-d H:i') ?? '–',
                    ]) }}
                @else
                    {{ trans('cruds.crosswalk.not_validated') }}
                @endif
            </div>
        </div>
        <div class="row mt-3">
            <div class="cell-12">
                @if(Auth::user()->isAdmin())
                    <a class="button primary" href="{{ route('crosswalk.edit', $mapping) }}">
                        <span class="mif-pencil"></span>&nbsp;{{ trans('common.edit') }}
                    </a>
                @endif
                <a class="button" href="{{ route('crosswalk.index') }}">
                    <span class="mif-cancel"></span>&nbsp;{{ trans('common.cancel') }}
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
