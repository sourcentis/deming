@extends("layout")

@section("content")
<div class="card">
    <div class="card-header">@include('partials.help', ['page' => 'risks.index'])<i class="bi bi-exclamation-triangle"></i> {{ trans("cruds.risk.list") }}</div>
    <div class="card-body">

    <div class="container-fluid px-0 mb-2">
        <div class="row">

            {{-- Filtre par niveau de risque --}}
            <div class="col-lg-2 col-md-3">
                <select class="form-select select2" id="filter-threshold">
                    <option value="none">-- {{ trans("cruds.risk.fields.choose_level") }} --</option>
                    @foreach ($scoringConfig->risk_thresholds as $idx => $t)
                        <option value="{{ $idx }}"
                                @if(Session::get('risk_threshold') !== null && (int)Session::get('risk_threshold') === $idx) selected @endif>
                            {{ $t['label'] }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Filtre par statut --}}
            <div class="col-lg-2 col-md-3">
                <select class="form-select select2" id="filter-status">
                    <option value="none">-- {{ trans("cruds.risk.fields.choose_status") }} --</option>
                    @foreach (\App\Models\Risk::availableStatuses() as $value => $label)
                        <option value="{{ $value }}" @if(Session::get('risk_status') === $value) selected @endif>
                            {{ trans($label) }}
                        </option>
                    @endforeach
                </select>
            </div>

            @if (Auth::User()->role !== 3)
            <div class="col-lg-2 col-md-3">
                <select class="form-select select2" id="filter-owner">
                    <option value="none">-- {{ trans("cruds.risk.fields.choose_owner") }} --</option>
                    @foreach ($owners as $owner)
                        <option value="{{ $owner->id }}" @if(Session::get('risk_owner') == $owner->id) selected @endif>
                            {{ $owner->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endif

            <div class="col-lg-2 col-md-3 mt-2">
                <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="overdue" value="0" id="overdue0" {{ Session::get('risk_overdue','0')==='0' ? 'checked' : '' }}><label class="form-check-label" for="overdue0">{{ trans('cruds.risk.fields.overdue_all') }}</label></div>
                <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="overdue" value="1" id="overdue1" {{ Session::get('risk_overdue')==='1' ? 'checked' : '' }}><label class="form-check-label" for="overdue1">{{ trans('cruds.risk.fields.overdue_only') }}</label></div>
            </div>

            <div class="col-auto ms-auto">
                @if (Auth::User()->role === 1 || Auth::User()->role === 2)
                    <button class="btn btn-primary" data-href="/risk/create">
                        <span class="bi bi-plus-lg"></span>
                        {{ trans('common.new') }}
                    </button>
                @endif
            </div>
        </div>
    </div>

    <table data-order='[[{{ ($scoringConfig->usesMonarc() || $scoringConfig->usesLikelihood()) ? 5 : 4 }},"desc"]]' data-page-length="50"
        id="risks-table"
        class="table table-striped table-hover table-bordered datatable">
        <thead>
            <tr>
                <th data-priority="1">{{ trans("cruds.risk.fields.name") }}</th>
                <th>{{ trans("cruds.risk.fields.owner") }}</th>

                @if ($scoringConfig->usesMonarc())
                    <th>{{ trans("cruds.risk.fields.impact") }}</th>
                    <th>{{ trans("cruds.risk.fields.threat") }}</th>
                    <th>{{ trans("cruds.risk.fields.vulnerability") }}</th>
                @elseif ($scoringConfig->usesLikelihood())
                    <th>{{ trans("cruds.risk.fields.likelihood") }}</th>
                    <th>{{ trans("cruds.risk.fields.vulnerability") }}</th>
                    <th>{{ trans("cruds.risk.fields.impact") }}</th>
                @else
                    <th>{{ trans("cruds.risk.fields.probability") }}</th>
                    <th>{{ trans("cruds.risk.fields.impact") }}</th>
                @endif

                <th data-priority="2">{{ trans("cruds.risk.fields.score") }}</th>

                <th>{{ trans("cruds.risk.fields.status") }}</th>
                @if ($scoringConfig->usesMonarc())
                    <th>{{ trans("cruds.risk.fields.residual_risk") }}</th>
                @endif
                <th>{{ trans("cruds.risk.fields.next_review") }}</th>
                <th class="dt-hidden status-comment">{{ trans("cruds.risk.fields.status_comment") }}</th>
            </tr>
        </thead>
        <tbody>
        @foreach ($risks as $risk)
        <tr data-href="/risk/show/{{ $risk->id }}">
            {{-- Name --}}
            <td>{{ $risk->name }}</td>
            {{-- Owner --}}
            <td>{{ $risk->owner?->name ?? '—' }}</td>

            @if ($scoringConfig->usesMonarc())
                <td>{{ $risk->impact }}</td>
                <td>{{ $risk->probability }}</td>
                <td>{{ $risk->vulnerability }}</td>
            @elseif ($scoringConfig->usesLikelihood())
                <td>{{ $risk->risk_likelihood }}</td>
                <td>{{ $risk->vulnerability }}</td>
                <td>{{ $risk->impact }}</td>
            @else
                <td>{{ $risk->probability }}</td>
                <td>{{ $risk->impact }}</td>
            @endif

            {{-- Score délégué au modèle Risk --}}
            <td>
                @php
                    $score     = $risk->computedScore($scoringConfig);
                    $threshold = $scoringConfig->thresholdFor($score);
                @endphp
                <span style="display:none">{{ str_pad($score, 4, '0', STR_PAD_LEFT) }}</span>
                <span class="badge"
                      style="background:{{ $threshold['color'] }};
                            color:{{ contrast_color($threshold['color']) }};
                            padding:2px 8px;
                            font-size:1rem">
                    {{ $score }}
                </span>
            </td>

            {{-- Status --}}
            <td>
                <span class="badge text-bg-{{ \App\Models\Risk::STATUS_COLORS[$risk->status] ?? 'secondary' }}" style="font-size:1rem;">
                    {{ \App\Models\Risk::statusLabel($risk->status) }}
                </span>
            </td>
            @if ($scoringConfig->usesMonarc())
            {{-- Résiduel --}}
            <td>
                <span style="display:none">{{ str_pad((string) ($risk->residual_risk ?? 0), 4, '0', STR_PAD_LEFT) }}</span>
                @if ($risk->residual_risk !== null)
                    @php
                        $residualThreshold = $scoringConfig->thresholdFor($risk->residual_risk);
                    @endphp
                    <span class="badge"
                          style="background:{{ $residualThreshold['color'] }};
                                color:{{ contrast_color($residualThreshold['color']) }};
                                padding:2px 8px;
                                font-size:1rem;">
                        {{ $risk->residual_risk }}
                    </span>
                @else
                    —
                @endif
            </td>
            @endif
            {{-- Review date --}}
            <td style="white-space:nowrap">
                @if ($risk->next_review_at)
                    @if ($risk->is_overdue)
                        <font color="red"><b>{{ $risk->next_review_at->format('Y-m-d') }}</b></font>
                    @else
                        <font color="green">{{ $risk->next_review_at->format('Y-m-d') }}</font>
                    @endif
                @else
                    —
                @endif
            </td>
            <td class="status-comment"><pre>{{ $risk->status_comment }}</pre></td>
        </tr>
        @endforeach
        </tbody>
    </table>

</div>
</div>

{{-- Alignemet de la table --}}
<style>
#risks-table td:nth-child(2),
#risks-table td:nth-child(5),
#risks-table td:nth-child(6),
#risks-table td:nth-child(7),
#risks-table td:nth-child(8),
#risks-table td:nth-child(9),
#risks-table td:nth-child(10),

#risks-table th:nth-child(2),
#risks-table th:nth-child(5),
#risks-table th:nth-child(6),
#risks-table th:nth-child(7),
#risks-table th:nth-child(8),
#risks-table th:nth-child(9),
#risks-table th:nth-child(10)
{
    text-align: center !important;
}

/* Le commentaire reste aligné à gauche quelle que soit sa position */
#risks-table td.status-comment,
#risks-table th.status-comment {
    text-align: left !important;
}
</style>

<script @nonce>
document.addEventListener("DOMContentLoaded", () => {
    const getParam = (k) => new URLSearchParams(location.search).get(k) ?? '';

    function snapshotFilters() {
        return {
            threshold : document.getElementById('filter-threshold')?.value ?? getParam('threshold'),
            status  : document.getElementById('filter-status')?.value ?? getParam('status'),
            owner   : document.getElementById('filter-owner')?.value  ?? getParam('owner'),
            overdue : document.getElementById('overdue1')?.checked ? '1' : '0',
        };
    }

    function navigateWithAll(patch = {}) {
        const next = new URL('/risk/index', location.origin);
        const all  = { ...snapshotFilters(), ...patch };
        for (const [k, v] of Object.entries(all)) {
            if (v == null || String(v) === '' || v === 'none') next.searchParams.delete(k);
            else next.searchParams.set(k, String(v));
        }
        if (next.toString() !== location.href) location.assign(next.toString());
    }

    const bindChange = (id, key) => {
        const el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('change', () => {
            navigateWithAll({ [key]: el.value });
        });
    };

    bindChange('filter-threshold', 'threshold');
    bindChange('filter-status', 'status');
    bindChange('filter-owner',  'owner');

    ['overdue0', 'overdue1'].forEach((id, i) => {
        const el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('change', () => {
            navigateWithAll({ overdue: String(i) });
        });
    });
});
</script>
@endsection