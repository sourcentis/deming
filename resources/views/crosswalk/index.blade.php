@extends('layout')

@section('content')
<div class="card">
    <div class="card-header">@include('partials.help', ['page' => 'crosswalk'])<i class="bi bi-shuffle"></i> {{ trans('cruds.crosswalk.list') }}</div>
    <div class="card-body">

    @include('partials.errors')

    <form method="GET" action="{{ route('crosswalk.index') }}" class="mb-4">
        <div class="container-fluid px-0">
            <div class="row">
                <div class="col-lg-3 col-md-6">
                    <input class="form-control" type="text" name="search"
                           value="{{ $filters['search'] ?? '' }}"
                           placeholder="{{ trans('cruds.crosswalk.search_clause') }}">
                </div>
                <div class="col-lg-2 col-md-6">
                    <select class="form-select select2" name="source_framework">
                        <option value="">-- {{ trans('cruds.crosswalk.source_framework') }} --</option>
                        @foreach($frameworks as $framework)
                            <option value="{{ $framework->code }}"
                                {{ ($filters['source_framework'] ?? '') === $framework->code ? 'selected' : '' }}>
                                {{ $framework->code }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 col-md-6">
                    <select class="form-select select2" name="target_framework">
                        <option value="">-- {{ trans('cruds.crosswalk.target_framework') }} --</option>
                        @foreach($frameworks as $framework)
                            <option value="{{ $framework->code }}"
                                {{ ($filters['target_framework'] ?? '') === $framework->code ? 'selected' : '' }}>
                                {{ $framework->code }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 col-md-6">
                    <select class="form-select select2" name="mapping_type">
                        <option value="">-- {{ trans('cruds.crosswalk.fields.mapping_type') }} --</option>
                        @foreach(\App\Models\ControlMapping::MAPPING_TYPES as $mappingType)
                            <option value="{{ $mappingType }}"
                                {{ ($filters['mapping_type'] ?? '') === $mappingType ? 'selected' : '' }}>
                                {{ trans('cruds.crosswalk.mapping_types.' . $mappingType) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 col-md-6">
                    <select class="form-select select2" name="coverage">
                        <option value="">-- {{ trans('cruds.crosswalk.fields.coverage') }} --</option>
                        @foreach(\App\Models\ControlMapping::COVERAGE_LEVELS as $coverage)
                            <option value="{{ $coverage }}"
                                {{ ($filters['coverage'] ?? '') === $coverage ? 'selected' : '' }}>
                                {{ trans('cruds.crosswalk.coverage_levels.' . $coverage) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-1 col-md-6">
                    <button type="submit" class="btn btn-info">
                        <span class="bi bi-search"></span>
                    </button>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-12 text-end">
                    <a class="btn btn-light border" href="{{ route('crosswalk.matrix') }}">
                        <span class="bi bi-grid-3x3"></span> {{ trans('cruds.crosswalk.matrix') }}
                    </a>
                    <a class="btn btn-success" href="{{ route('crosswalk.export', request()->query()) }}">
                        <span class="bi bi-file-earmark-excel"></span> {{ trans('common.export') }}
                    </a>
                    @if(Auth::user()->isAdmin())
                        <a class="btn btn-primary" href="{{ route('crosswalk.create') }}">
                            <span class="bi bi-plus-lg"></span> {{ trans('common.new') }}
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </form>

    <div class="overflow-auto">
        <table id="crosswalk-mappings" class="table table-striped table-hover table-bordered">
            <thead>
                <tr>
                    <th>{{ trans('cruds.crosswalk.source') }}</th>
                    <th>{{ trans('cruds.crosswalk.target') }}</th>
                    <th>{{ trans('cruds.crosswalk.fields.mapping_type') }}</th>
                    <th>{{ trans('cruds.crosswalk.fields.coverage') }}</th>
                    <th>{{ trans('cruds.crosswalk.fields.validated') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($mappings as $mapping)
                    <tr>
                        <td>
                            <strong>{{ $mapping->sourceControl->domain->framework }}</strong><br>
                            <a href="/alice/show/{{ $mapping->sourceControl->id }}">
                                {{ trim($mapping->sourceControl->clause) }}
                            </a>
                            – {{ $mapping->sourceControl->name }}
                        </td>
                        <td>
                            <strong>{{ $mapping->targetControl->domain->framework }}</strong><br>
                            <a href="/alice/show/{{ $mapping->targetControl->id }}">
                                {{ trim($mapping->targetControl->clause) }}
                            </a>
                            – {{ $mapping->targetControl->name }}
                        </td>
                        <td>{{ trans('cruds.crosswalk.mapping_types.' . $mapping->mapping_type) }}</td>
                        <td>
                            {{ $mapping->coverage
                                ? trans('cruds.crosswalk.coverage_levels.' . $mapping->coverage)
                                : '–' }}
                        </td>
                        <td>
                            @if($mapping->validated)
                                <span class="bi bi-check-lg text-success"></span>
                            @else
                                <span class="bi bi-hourglass-split text-orange"></span>
                            @endif
                        </td>
                        <td class="text-nowrap">
                            <a class="btn btn-sm btn-light border" href="{{ route('crosswalk.show', $mapping) }}">
                                <span class="bi bi-eye"></span>
                            </a>
                            @if(Auth::user()->isAdmin())
                                <a class="btn btn-primary btn-sm" href="{{ route('crosswalk.edit', $mapping) }}">
                                    <span class="bi bi-pencil"></span>
                                </a>
                                <form action="{{ route('crosswalk.destroy', $mapping) }}" method="POST"
                                      class="d-inline"
                                      data-confirm="{{ trans('common.confirm') }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-danger btn-sm" type="submit">
                                        <span class="bi bi-trash"></span>
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">{{ trans('cruds.crosswalk.no_mappings') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-2">
        {{ $mappings->links() }}
    </div>
</div>
</div>
@endsection
