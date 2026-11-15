@extends("layout")

@section("content")
<div class="card">
    <div class="card-header">@include('partials.help', ['page' => 'exceptions.index'])<i class="bi bi-x-octagon"></i> {{ trans('cruds.exception.list') }}</div>
    <div class="card-body">

    <div class="container-fluid px-0 mb-2">
        <div class="row">
            {{-- Filtre statut --}}
            <div class="col-lg-2 col-md-2">
                <select class="form-select select2" id="filter-status" name="status">
                    <option value="">-- {{ trans('cruds.exception.fields.choose_status') }} --</option>
                    @foreach(\App\Models\Exception::STATUS_LABELS as $value => $label)
                        <option value="{{ $value }}" {{ (string)($filters['status'] ?? '') === (string)$value ? 'selected' : '' }}>
                            {{ trans($label) }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Filtre mesure --}}
            <div class="col-lg-3 col-md-3">
                <select class="form-select select2" id="filter-measure" name="measure_id">
                    <option value="">-- {{ trans('cruds.exception.fields.choose_measure') }} --</option>
                    @foreach($controls as $control)
                        <option value="{{ $control->id }}"
                            {{ (string)($filters['measure_id'] ?? '') === (string)$control->id ? 'selected' : '' }}>
                            {{ $control->clause }} – {{ $control->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Filtre expirées --}}
            <div class="col-lg-2 col-md-2 mt-2">
                <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" id="filter-expired"
                    {{ ($filters['expired'] ?? false) ? 'checked' : '' }}><label class="form-check-label" for="filter-expired">{{ trans('cruds.exception.fields.expired_only') }}</label></div>
            </div>

            {{-- Bouton Nouveau --}}
            <div class="col-lg-5 col-md-5 text-end">
                <button class="btn btn-primary" data-href="/exception/create">
                    <span class="bi bi-plus-lg"></span> {{ trans('common.new') }}
                </button>
            </div>
        </div>
    </div>

    <table data-order="[]" data-page-length="50"
        id="exceptions-table"
        class="table table-striped table-hover table-bordered datatable">
        <thead>
            <tr>
                <th>{{ trans('cruds.exception.fields.name') }}</th>
                <th>{{ trans('cruds.exception.fields.measure') }}</th>
                <th>{{ trans('cruds.exception.fields.start_date') }}</th>
                <th>{{ trans('cruds.exception.fields.end_date') }}</th>
                <th>{{ trans('cruds.exception.fields.status') }}</th>
                <th>{{ trans('cruds.exception.fields.created_by') }}</th>
            </tr>
        </thead>
        <tbody>
        @foreach($exceptions as $exception)
        <tr data-href="/exception/show/{{ $exception->id }}" style="cursor:pointer;">
            <td><a href="/exception/show/{{ $exception->id }}">{{ $exception->name }}</a></td>
            <td>
                @if($exception->measure)
                    <a href="/alice/show/{{ $exception->measure->id }}">
                        {{ $exception->measure->clause }}
                    </a>
                @else
                    –
                @endif
            </td>
            <td>
                {{-- Tri caché sur date ISO --}}
                <span style="display:none;">{{ $exception->start_date?->format('Y-m-d') ?? '0000-00-00' }}</span>
                {{ $exception->start_date?->format('Y-m-d') ?? '–' }}
            </td>
            <td>
                <span style="display:none;">{{ $exception->end_date?->format('Y-m-d') ?? '9999-99-99' }}</span>
                @if($exception->end_date)
                    @if($exception->end_date->isPast())
                        <font color="#D94F45"><strong>{{ $exception->end_date->format('Y-m-d') }}</strong></font>
                    @else
                        {{ $exception->end_date->format('Y-m-d') }}
                    @endif
                @else
                    –
                @endif
            </td>
            <td>
                @php
                    $colors = [
                        \App\Models\Exception::STATUS_DRAFT     => '#888888',
                        \App\Models\Exception::STATUS_SUBMITTED => '#3A72C4',
                        \App\Models\Exception::STATUS_APPROVED  => '#3AB87A',
                        \App\Models\Exception::STATUS_REJECTED  => '#D94F45',
                        \App\Models\Exception::STATUS_EXPIRED   => '#E09B1A',
                    ];
                    $color = $colors[$exception->status] ?? '#888888';
                @endphp
                <span style="color:{{ $color }}; font-weight:bold;">
                    {{ $exception->status_label }}
                </span>
            </td>
            <td>{{ $exception->createdBy?->name ?? '–' }}</td>
        </tr>
        @endforeach
        </tbody>
    </table>

    <div class="mt-2">
        {{ $exceptions->withQueryString()->links() }}
    </div>

</div>
</div>

<script @nonce>
document.addEventListener("DOMContentLoaded", function () {
    let ready = false;
    window.addEventListener('load', () => {
        requestAnimationFrame(() => requestAnimationFrame(() => { ready = true; }));
    });

    function navigate(patch = {}) {
        const url = new URL('/exception/index', location.origin);
        const cur = new URLSearchParams(location.search);

        const params = {
            status:     document.getElementById('filter-status')?.value     ?? cur.get('status')     ?? '',
            measure_id: document.getElementById('filter-measure')?.value    ?? cur.get('measure_id') ?? '',
            expired:    document.getElementById('filter-expired')?.checked  ? '1' : '0',
            ...patch
        };

        for (const [k, v] of Object.entries(params)) {
            if (v === '' || v === null) url.searchParams.delete(k);
            else url.searchParams.set(k, v);
        }
        if (url.toString() !== location.href) location.assign(url.toString());
    }

    const bindChange = (id) => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('change', () => { if (ready) navigate(); });
    };

    bindChange('filter-status');
    bindChange('filter-measure');
    bindChange('filter-expired');
});
</script>
@endsection
