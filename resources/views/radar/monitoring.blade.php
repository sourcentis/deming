@extends("layout")

@section("content")
@php
    $pct = fn (float $value) => \App\Services\MonitoringService::formatPercent($value);
    $statHeaders = ['col_live', 'col_overdue', 'col_not_yet', 'col_pending', 'col_rate', 'col_max_days'];
    // User pages are open to admins and to the user themselves, group pages to admins only
    $isAdmin = Auth::user()->isAdmin();
    $canShowUser = fn (int $id) => $isAdmin || Auth::id() === $id;
@endphp
<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between">
        <span><i class="bi bi-activity"></i> {{ trans('cruds.monitoring.title') }}</span>
        <span>@include('partials.help', ['page' => 'radar.monitoring']){{ trans('cruds.monitoring.extraction_date') }} : {{ \Carbon\Carbon::parse($today)->format('d/m/Y') }}</span>
    </div>
    <div class="card-body">

    {{-- Level 1: overview --}}
    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3">
            <div class="card h-100 text-center" style="background-color:#E7F0FB;color:{{ contrast_color('#E7F0FB') }}">
                <div class="card-body">
                    <div class="small">{{ trans('cruds.monitoring.total') }}</div>
                    <div class="fs-2 fw-bold" id="tile-total">{{ $total }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card h-100 text-center">
                <div class="card-body">
                    <div class="small">{{ trans('cruds.monitoring.live') }}</div>
                    <div class="fs-2 fw-bold" id="tile-live">{{ $live }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card h-100 text-center" style="background-color:#D94F45;color:{{ contrast_color('#D94F45') }}">
                <div class="card-body">
                    <div class="small">{{ trans('cruds.monitoring.overdue_live') }}</div>
                    <div class="fs-2 fw-bold" id="tile-overdue">{{ $overdue }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card h-100 text-center" style="background-color:#E8731A;color:{{ contrast_color('#E8731A') }}">
                <div class="card-body">
                    <div class="small">{{ trans('cruds.monitoring.overdue_rate') }}</div>
                    <div class="fs-2 fw-bold" id="tile-rate">{{ $pct($overdue_rate) }}</div>
                </div>
            </div>
        </div>
    </div>
    @include('radar.monitoring-value', ['key' => 'population'])
    @include('radar.monitoring-value', ['key' => 'backlog_health'])
    <div class="alert alert-light border mt-3 mb-0">
        <span class="badge" style="background-color:#3A72C4;color:{{ contrast_color('#3A72C4') }}">&nbsp;</span>
        {{ trans('cruds.monitoring.security_owned') }} : <strong id="owned-security">{{ $ownership_all['security'] }}</strong>
        &nbsp;·&nbsp;
        <span class="badge" style="background-color:#D94F45;color:{{ contrast_color('#D94F45') }}">&nbsp;</span>
        {{ trans('cruds.monitoring.non_security_owned') }} : <strong id="owned-non-security">{{ $ownership_all['non_security'] }}</strong>
    </div>
    @include('radar.monitoring-value', ['key' => 'ownership_distribution'])

    </div>
</div>

{{-- Level 2: ownership and lateness of live/active measures --}}
<div class="card mt-3">
    <div class="card-header"><i class="bi bi-shield-check"></i> {{ trans('cruds.monitoring.ownership_title') }}</div>
    <div class="card-body">
    <div class="row g-3">
        <div class="col-lg-8">
            <table class="table table-bordered table-sm" id="ownership-table">
                <thead>
                    <tr>
                        <th>{{ trans('cruds.monitoring.ownership') }}</th>
                        @foreach (array_slice($statHeaders, 0, 5) as $header)
                        <th class="text-end">{{ trans('cruds.monitoring.' . $header) }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach (['security', 'non_security', 'total'] as $owner)
                    <tr class="{{ $owner === 'total' ? 'fw-bold' : '' }}">
                        <td>{{ trans('cruds.monitoring.' . ($owner === 'total' ? 'total_row' : $owner)) }}</td>
                        @include('radar.monitoring-cells', ['row' => $ownership[$owner], 'withMaxDays' => false])
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @include('radar.monitoring-value', ['key' => 'ownership_performance'])

            <div class="card mt-3">
                <div class="card-header"><i class="bi bi-info-circle"></i> {{ trans('cruds.monitoring.meaning_title') }}</div>
                <div class="card-body">
                    <ul class="mb-0">
                        @foreach (['security', 'non_security', 'total'] as $owner)
                        <li>{{ trans('cruds.monitoring.meaning_' . $owner, [
                            'overdue' => $ownership[$owner]['overdue'],
                            'live' => $ownership[$owner]['live'],
                            'rate' => $pct($ownership[$owner]['rate']),
                        ]) }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">{{ trans('cruds.monitoring.chart_title') }}</div>
                <div class="card-body">
                    <canvas id="ownershipChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    </div>
</div>

{{-- Contributor action priorities --}}
<div class="card mt-3">
    <div class="card-header"><i class="bi bi-list-ol"></i> {{ trans('cruds.monitoring.priorities_title') }}</div>
    <div class="card-body">
    <table class="table table-bordered table-sm mb-0" id="priorities-table">
        <thead>
            <tr>
                <th>{{ trans('cruds.monitoring.priority') }}</th>
                <th>{{ trans('cruds.monitoring.population') }}</th>
                <th class="text-end">{{ trans('cruds.monitoring.count') }}</th>
                <th class="text-end">{{ trans('cruds.monitoring.percent_live') }}</th>
                <th>{{ trans('cruds.monitoring.expected_action') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($priorities as $priority)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ trans('cruds.monitoring.priorities.' . $priority['key']) }}</td>
                <td class="text-end" @if (in_array($priority['key'], ['security_overdue', 'non_security_overdue'], true) && $priority['count'] > 0) style="background-color:#D94F45;color:{{ contrast_color('#D94F45') }}" @elseif ($priority['key'] === 'not_yet_due' && $priority['count'] > 0) style="background-color:#3AB87A;color:{{ contrast_color('#3AB87A') }}" @endif>{{ $priority['count'] }}</td>
                <td class="text-end">{{ $pct($priority['percent']) }}</td>
                <td>{{ trans('cruds.monitoring.actions.' . $priority['key']) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @include('radar.monitoring-value', ['key' => 'priorities'])

    </div>
</div>

{{-- Contributor analysis --}}
<div class="card mt-3">
    <div class="card-header"><i class="bi bi-people"></i> {{ trans('cruds.monitoring.contributors_title') }}</div>
    <div class="card-body">

    <h6>{{ trans('cruds.monitoring.combinations_title') }}</h6>
    <table class="table table-striped table-hover table-bordered table-sm datatable" id="combinations-table"
        data-order="[]" data-paging="false" data-searching="false" data-info="false" data-buttons="false">
        <thead>
            <tr>
                <th>{{ trans('cruds.monitoring.assignees') }}</th>
                @foreach ($statHeaders as $header)
                <th class="text-end">{{ trans('cruds.monitoring.' . $header) }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($combinations as $row)
            <tr>
                <td>
                    @if ($row['unassigned'])
                    <em>{{ trans('cruds.monitoring.unassigned') }}</em>
                    @else
                    @foreach ($row['users'] as $id => $name)
                    @if ($canShowUser($id))<a href="/users/{{ $id }}">{{ $name }}</a>@else{{ $name }}@endif{{ ($loop->last && $row['groups'] === []) ? '' : ',' }}
                    @endforeach
                    @foreach ($row['groups'] as $id => $name)
                    @if ($isAdmin)<a href="/groups/{{ $id }}">{{ $name }}</a>@else{{ $name }}@endif{{ $loop->last ? '' : ',' }}
                    @endforeach
                    @endif
                </td>
                @include('radar.monitoring-cells', ['row' => $row])
            </tr>
            @endforeach
        </tbody>
    </table>
    @include('radar.monitoring-value', ['key' => 'combinations', 'label' => 'purpose_label'])

    <h6 class="mt-4">{{ trans('cruds.monitoring.individuals_title') }}</h6>
    <table class="table table-striped table-hover table-bordered table-sm datatable" id="individuals-table"
        data-order="[]" data-paging="false" data-searching="false" data-info="false" data-buttons="false">
        <thead>
            <tr>
                <th>{{ trans('cruds.monitoring.user') }}</th>
                <th class="text-end">{{ trans('cruds.monitoring.direct') }}</th>
                <th class="text-end">{{ trans('cruds.monitoring.via_group') }}</th>
                @foreach ($statHeaders as $header)
                <th class="text-end">{{ trans('cruds.monitoring.' . $header) }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($individuals as $row)
            <tr>
                <td>
                    @if ($canShowUser($row['id']))
                    <a href="/users/{{ $row['id'] }}">{{ $row['name'] }}</a>
                    @else
                    {{ $row['name'] }}
                    @endif
                    @if ($row['disabled'])
                    <span class="badge text-bg-secondary">{{ trans('cruds.monitoring.disabled') }}</span>
                    @endif
                </td>
                <td class="text-end">{{ $row['direct'] }}</td>
                <td class="text-end">{{ $row['via_group'] }}</td>
                @include('radar.monitoring-cells', ['row' => $row])
            </tr>
            @endforeach
        </tbody>
    </table>
    @include('radar.monitoring-value', ['key' => 'individuals'])

    <h6 class="mt-4">{{ trans('cruds.monitoring.groups_title') }}</h6>
    <table class="table table-striped table-hover table-bordered table-sm datatable" id="groups-table"
        data-order="[]" data-paging="false" data-searching="false" data-info="false" data-buttons="false">
        <thead>
            <tr>
                <th>{{ trans('cruds.monitoring.group') }}</th>
                <th>{{ trans('cruds.monitoring.type') }}</th>
                <th class="text-end">{{ trans('cruds.monitoring.members') }}</th>
                @foreach ($statHeaders as $header)
                <th class="text-end">{{ trans('cruds.monitoring.' . $header) }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($groups as $row)
            <tr>
                <td>
                    @if ($isAdmin)
                    <a href="/groups/{{ $row['id'] }}">{{ $row['name'] }}</a>
                    @else
                    {{ $row['name'] }}
                    @endif
                </td>
                <td>@include('groups.type_badge', ['group' => (object) $row])</td>
                <td class="text-end">{{ $row['members'] }}</td>
                @include('radar.monitoring-cells', ['row' => $row])
            </tr>
            @endforeach
        </tbody>
    </table>
    @include('radar.monitoring-value', ['key' => 'groups'])

    </div>
</div>

{{-- Definitions --}}
<div class="card mt-3">
    <div class="card-header"><i class="bi bi-book"></i> {{ trans('cruds.monitoring.definitions_title') }}</div>
    <div class="card-body">
        <ul class="mb-0">
            @foreach (['live', 'overdue', 'pending', 'ratios', 'ownership', 'ownership_totals'] as $definition)
            <li>{{ trans('cruds.monitoring.definitions.' . $definition) }}</li>
            @endforeach
        </ul>
    </div>
</div>

<script @nonce>
document.addEventListener("DOMContentLoaded", () => {
    new Chart(document.getElementById('ownershipChart'), {
        type: 'doughnut',
        data: {
            labels: @json([trans('cruds.monitoring.security'), trans('cruds.monitoring.non_security')]),
            datasets: [{
                data: @json([$ownership['security']['live'], $ownership['non_security']['live']]),
                backgroundColor: ['#3A72C4', '#D94F45'],
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom' },
                datalabels: {
                    color: @json([contrast_color('#3A72C4'), contrast_color('#D94F45')]),
                    font: { weight: 'bold' },
                    formatter: (value) => value > 0 ? value : '',
                },
            },
        },
    });
});
</script>
@endsection
