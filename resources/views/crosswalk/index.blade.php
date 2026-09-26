@extends('layout')

@section('content')
<div data-role="panel"
     data-title-caption="{{ trans('cruds.crosswalk.list') }}"
     data-collapsible="false"
     data-title-icon="<span class='mif-shuffle'></span>">

    @include('partials.errors')

    <form method="GET" action="{{ route('crosswalk.index') }}" class="mb-4">
        <div class="grid">
            <div class="row">
                <div class="cell-lg-3 cell-md-6">
                    <input type="text" data-role="input" name="search"
                           value="{{ $filters['search'] ?? '' }}"
                           placeholder="{{ trans('cruds.crosswalk.search_clause') }}">
                </div>
                <div class="cell-lg-2 cell-md-6">
                    <select name="source_framework" data-role="select">
                        <option value="">-- {{ trans('cruds.crosswalk.source_framework') }} --</option>
                        @foreach($frameworks as $framework)
                            <option value="{{ $framework->code }}"
                                {{ ($filters['source_framework'] ?? '') === $framework->code ? 'selected' : '' }}>
                                {{ $framework->code }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="cell-lg-2 cell-md-6">
                    <select name="target_framework" data-role="select">
                        <option value="">-- {{ trans('cruds.crosswalk.target_framework') }} --</option>
                        @foreach($frameworks as $framework)
                            <option value="{{ $framework->code }}"
                                {{ ($filters['target_framework'] ?? '') === $framework->code ? 'selected' : '' }}>
                                {{ $framework->code }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="cell-lg-2 cell-md-6">
                    <select name="mapping_type" data-role="select">
                        <option value="">-- {{ trans('cruds.crosswalk.fields.mapping_type') }} --</option>
                        @foreach(\App\Models\ControlMapping::MAPPING_TYPES as $mappingType)
                            <option value="{{ $mappingType }}"
                                {{ ($filters['mapping_type'] ?? '') === $mappingType ? 'selected' : '' }}>
                                {{ trans('cruds.crosswalk.mapping_types.' . $mappingType) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="cell-lg-2 cell-md-6">
                    <select name="coverage" data-role="select">
                        <option value="">-- {{ trans('cruds.crosswalk.fields.coverage') }} --</option>
                        @foreach(\App\Models\ControlMapping::COVERAGE_LEVELS as $coverage)
                            <option value="{{ $coverage }}"
                                {{ ($filters['coverage'] ?? '') === $coverage ? 'selected' : '' }}>
                                {{ trans('cruds.crosswalk.coverage_levels.' . $coverage) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="cell-lg-1 cell-md-6">
                    <button type="submit" class="button info">
                        <span class="mif-search"></span>
                    </button>
                </div>
            </div>
            <div class="row mt-2">
                <div class="cell-12 text-right">
                    <a class="button" href="{{ route('crosswalk.matrix') }}">
                        <span class="mif-grid"></span>&nbsp;{{ trans('cruds.crosswalk.matrix') }}
                    </a>
                    <a class="button success" href="{{ route('crosswalk.export', request()->query()) }}">
                        <span class="mif-file-excel"></span>&nbsp;{{ trans('common.export') }}
                    </a>
                    @if(Auth::user()->isAdmin())
                        <a class="button primary" href="{{ route('crosswalk.create') }}">
                            <span class="mif-plus"></span>&nbsp;{{ trans('common.new') }}
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </form>

    <div class="overflow-auto">
        <table id="crosswalk-mappings" class="table striped row-hover cell-border" data-role="table"
               data-show-search="false" data-show-pagination="false" data-show-rows-steps="false">
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
                                <span class="mif-checkmark fg-green"></span>
                            @else
                                <span class="mif-hour-glass fg-orange"></span>
                            @endif
                        </td>
                        <td class="no-wrap">
                            <a class="button small" href="{{ route('crosswalk.show', $mapping) }}">
                                <span class="mif-eye"></span>
                            </a>
                            @if(Auth::user()->isAdmin())
                                <a class="button primary small" href="{{ route('crosswalk.edit', $mapping) }}">
                                    <span class="mif-pencil"></span>
                                </a>
                                <form action="{{ route('crosswalk.destroy', $mapping) }}" method="POST"
                                      class="d-inline"
                                      onsubmit="return confirm('{{ trans('common.confirm') }}');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="button alert small" type="submit">
                                        <span class="mif-bin"></span>
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
@endsection
