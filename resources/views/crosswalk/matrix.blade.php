@extends('layout')

@section('content')
<div data-role="panel"
     data-title-caption="{{ trans('cruds.crosswalk.matrix') }}"
     data-collapsible="false"
     data-title-icon="<span class='mif-grid'></span>">

    @include('partials.errors')

    <form method="GET" action="{{ route('crosswalk.matrix') }}" class="mb-4">
        <div class="grid">
            <div class="row">
                <div class="cell-lg-3 cell-md-6">
                    <label>{{ trans('cruds.crosswalk.source_framework') }}</label>
                    <select name="source_framework" data-role="select" required>
                        <option value="">-- {{ trans('cruds.crosswalk.source_framework') }} --</option>
                        @foreach($frameworks as $framework)
                            <option value="{{ $framework->code }}"
                                {{ ($filters['source_framework'] ?? '') === $framework->code ? 'selected' : '' }}>
                                {{ $framework->code }} — {{ $framework->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="cell-lg-3 cell-md-6">
                    <label>{{ trans('cruds.crosswalk.target_framework') }}</label>
                    <select name="target_framework" data-role="select" required>
                        <option value="">-- {{ trans('cruds.crosswalk.target_framework') }} --</option>
                        @foreach($frameworks as $framework)
                            <option value="{{ $framework->code }}"
                                {{ ($filters['target_framework'] ?? '') === $framework->code ? 'selected' : '' }}>
                                {{ $framework->code }} — {{ $framework->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="cell-lg-2 cell-md-6">
                    <label>{{ trans('cruds.crosswalk.fields.mapping_type') }}</label>
                    <select name="mapping_type" data-role="select">
                        <option value="">-- {{ trans('cruds.crosswalk.all') }} --</option>
                        @foreach(\App\Models\ControlMapping::DIRECTIONAL_MAPPING_TYPES as $mappingType)
                            <option value="{{ $mappingType }}"
                                {{ ($filters['mapping_type'] ?? '') === $mappingType ? 'selected' : '' }}>
                                {{ trans('cruds.crosswalk.mapping_types.' . $mappingType) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="cell-lg-2 cell-md-6">
                    <label>{{ trans('cruds.crosswalk.fields.coverage') }}</label>
                    <select name="coverage" data-role="select">
                        <option value="">-- {{ trans('cruds.crosswalk.all') }} --</option>
                        @foreach(\App\Models\ControlMapping::COVERAGE_LEVELS as $coverage)
                            <option value="{{ $coverage }}"
                                {{ ($filters['coverage'] ?? '') === $coverage ? 'selected' : '' }}>
                                {{ trans('cruds.crosswalk.coverage_levels.' . $coverage) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="cell-lg-2 cell-md-6 d-flex flex-align-end">
                    <button type="submit" class="button info">
                        <span class="mif-filter"></span>&nbsp;{{ trans('cruds.crosswalk.display') }}
                    </button>
                </div>
            </div>
            <div class="row mt-2">
                <div class="cell-lg-6 cell-md-12">
                    <input type="hidden" name="unmapped" value="0">
                    <input type="checkbox" data-role="checkbox" name="unmapped" value="1"
                           data-caption="{{ trans('cruds.crosswalk.show_unmapped') }}"
                           {{ ($filters['unmapped'] ?? false) ? 'checked' : '' }}>
                </div>
                <div class="cell-lg-6 cell-md-12 text-right">
                    @if(($filters['source_framework'] ?? null) && ($filters['target_framework'] ?? null))
                        <a class="button" href="{{ route('crosswalk.matrix', array_merge($filters, [
                            'source_framework' => $filters['target_framework'],
                            'target_framework' => $filters['source_framework'],
                        ])) }}">
                            <span class="mif-loop2"></span>&nbsp;{{ trans('cruds.crosswalk.reverse') }}
                        </a>
                        <a class="button success" href="{{ route('crosswalk.export', array_merge($filters, ['directional' => 1])) }}">
                            <span class="mif-file-excel"></span>&nbsp;{{ trans('common.export') }}
                        </a>
                    @endif
                    <a class="button" href="{{ route('crosswalk.index') }}">
                        <span class="mif-list"></span>&nbsp;{{ trans('cruds.crosswalk.list') }}
                    </a>
                </div>
            </div>
        </div>
    </form>

    @if(($filters['source_framework'] ?? null) === ($filters['target_framework'] ?? null)
        && ($filters['source_framework'] ?? null))
        <div class="remark warning">{{ trans('cruds.crosswalk.distinct_frameworks') }}</div>
    @elseif($matrix === null)
        <div class="remark info">{{ trans('cruds.crosswalk.select_pair') }}</div>
    @else
        <div class="remark warning">{{ trans('cruds.crosswalk.documentary_notice') }}</div>

        <div class="grid">
            <div class="row">
                <div class="cell-lg-3 cell-sm-6">
                    <div class="more-info-box bg-cyan fg-white">
                        <div class="content"><span class="mif-books icon"></span>
                            <div class="text">{{ $matrix['stats']['source_total'] }}</div>
                            <div>{{ trans('cruds.crosswalk.source_total') }}</div>
                        </div>
                    </div>
                </div>
                <div class="cell-lg-3 cell-sm-6">
                    <div class="more-info-box bg-green fg-white">
                        <div class="content"><span class="mif-link icon"></span>
                            <div class="text">{{ $matrix['stats']['mapped_source'] }}</div>
                            <div>{{ trans('cruds.crosswalk.mapped_source') }}</div>
                        </div>
                    </div>
                </div>
                <div class="cell-lg-3 cell-sm-6">
                    <div class="more-info-box bg-orange fg-white">
                        <div class="content"><span class="mif-unlink icon"></span>
                            <div class="text">{{ $matrix['stats']['unmapped_source'] }}</div>
                            <div>{{ trans('cruds.crosswalk.unmapped_source') }}</div>
                        </div>
                    </div>
                </div>
                <div class="cell-lg-3 cell-sm-6">
                    <div class="more-info-box bg-steel fg-white">
                        <div class="content"><span class="mif-shuffle icon"></span>
                            <div class="text">{{ $matrix['stats']['relations'] }}</div>
                            <div>{{ trans('cruds.crosswalk.relations') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="overflow-auto mt-4">
            <table id="crosswalk-matrix" class="table striped row-hover cell-border"
                   data-role="table" data-show-search="true" data-rows="50">
                <thead>
                    <tr>
                        <th>{{ $filters['source_framework'] }}</th>
                        <th>{{ $filters['target_framework'] }}</th>
                        <th>{{ trans('cruds.crosswalk.fields.mapping_type') }}</th>
                        <th>{{ trans('cruds.crosswalk.fields.coverage') }}</th>
                        <th>{{ trans('cruds.crosswalk.stored_direction') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($matrix['rows'] as $row)
                        @if($row['mappings']->isEmpty())
                            <tr>
                                <td>
                                    <a href="/alice/show/{{ $row['control']->id }}">
                                        {{ trim($row['control']->clause) }} — {{ $row['control']->name }}
                                    </a>
                                </td>
                                <td colspan="4"><em>
                                    {{ $row['has_any_mapping']
                                        ? trans('cruds.crosswalk.no_filtered_mapping')
                                        : trans('cruds.crosswalk.unmapped') }}
                                </em></td>
                            </tr>
                        @else
                            @foreach($row['mappings'] as $item)
                                <tr>
                                    <td>
                                        <a href="/alice/show/{{ $row['control']->id }}">
                                            {{ trim($row['control']->clause) }} — {{ $row['control']->name }}
                                        </a>
                                    </td>
                                    <td>
                                        <a href="/alice/show/{{ $item['target_control']->id }}">
                                            {{ trim($item['target_control']->clause) }} — {{ $item['target_control']->name }}
                                        </a>
                                    </td>
                                    <td>
                                        <a href="{{ route('crosswalk.show', $item['mapping']) }}">
                                            {{ trans('cruds.crosswalk.mapping_types.' . $item['mapping_type']) }}
                                        </a>
                                    </td>
                                    <td>
                                        {{ $item['mapping']->coverage
                                            ? trans('cruds.crosswalk.coverage_levels.' . $item['mapping']->coverage)
                                            : '–' }}
                                    </td>
                                    <td>
                                        {{ $item['reversed']
                                            ? trans('cruds.crosswalk.reversed')
                                            : trans('cruds.crosswalk.direct') }}
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    @empty
                        <tr><td colspan="5">{{ trans('cruds.crosswalk.no_controls') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
