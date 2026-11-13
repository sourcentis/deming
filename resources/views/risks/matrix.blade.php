@extends("layout")

@section("content")
<div class="card">
    <div class="card-header">@include('partials.help', ['page' => 'risks.matrix'])<i class="bi bi-grid-3x3"></i> {{ trans("cruds.risk.matrix") }}</div>
    <div class="card-body">

    {{-- Filtres ----------------------------------------------------------------}}
    <div class="container-fluid px-0 mb-4">
        <div class="row">

            {{-- Statut --}}
            <div class="col-lg-2 col-md-3">
                <select class="form-select select2" id="filter-status">
                    <option value="none">-- {{ trans("cruds.risk.fields.choose_status") }} --</option>
                    @foreach (\App\Models\Risk::availableStatuses() as $value => $label)
                        <option value="{{ $value }}"
                                @if(($filters['status'] ?? '') === $value) selected @endif>
                            {{ trans($label) }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Propriétaire --}}
            @if (Auth::user()->role !== 3)
            <div class="col-lg-2 col-md-3">
                <select class="form-select select2" id="filter-owner">
                    <option value="none">-- {{ trans("cruds.risk.fields.choose_owner") }} --</option>
                    @foreach ($owners as $owner)
                        <option value="{{ $owner->id }}"
                                @if(($filters['owner'] ?? '') == $owner->id) selected @endif>
                            {{ $owner->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endif

            {{-- En retard --}}
            <div class="col-lg-2 col-md-3 mt-2">
                <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="overdue"
                       value="0" id="overdue0"
                       {{ ($filters['overdue'] ?? '0') === '0' ? 'checked' : '' }}><label class="form-check-label" for="overdue0">{{ trans('cruds.risk.fields.overdue_all') }}</label></div>
                <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="overdue"
                       value="1" id="overdue1"
                       {{ ($filters['overdue'] ?? '0') === '1' ? 'checked' : '' }}><label class="form-check-label" for="overdue1">{{ trans('cruds.risk.fields.overdue_only') }}</label></div>
            </div>

        </div>
    </div>

    <script @nonce>
    document.addEventListener("DOMContentLoaded", () => {
        let ready = false;
        window.addEventListener('load', () => {
            requestAnimationFrame(() => requestAnimationFrame(() => { ready = true; }));
        });

        const getParam = (k) => new URLSearchParams(location.search).get(k) ?? '';

        function navigate(patch = {}) {
            const next = new URL('/risk/matrix', location.origin);
            const all  = {
                status  : document.getElementById('filter-status')?.value ?? getParam('status'),
                owner   : document.getElementById('filter-owner')?.value  ?? getParam('owner'),
                overdue : document.getElementById('overdue1')?.checked ? '1' : '0',
                ...patch,
            };
            for (const [k, v] of Object.entries(all)) {
                if (!v || v === 'none') next.searchParams.delete(k);
                else next.searchParams.set(k, v);
            }
            if (next.toString() !== location.href) location.assign(next.toString());
        }

        const bindChange = (id, key) => {
            const el = document.getElementById(id);
            if (!el) return;
            el.addEventListener('change', () => { if (ready) navigate({ [key]: el.value }); });
        };

        bindChange('filter-status', 'status');
        bindChange('filter-owner',  'owner');

        ['overdue0', 'overdue1'].forEach((id, i) => {
            const el = document.getElementById(id);
            if (el) el.addEventListener('change', () => {
                if (ready) navigate({ overdue: String(i) });
            });
        });
    });
    </script>
    {{-- Fin filtres ------------------------------------------------------------}}

    @php
        $colCount      = count($xAxis);
        $matrixCompact = $colCount > 8;
        $cellFs        = $matrixCompact ? '0.68rem' : '0.85rem';
    @endphp

    <div class="container-fluid px-0">
    {{-- Matrice --}}
    <div class="row">
        <div class="col-lg-9 col-md-12">
            <div class="overflow-auto">
            <table class="table table-borderless border text-center" style="table-layout:fixed; min-width:400px; font-size:{{ $cellFs }}">
                <thead>
                    <tr>
                        <th style="width:{{ $matrixCompact ? '90px' : '140px' }}"></th>
                        @foreach ($xAxis as $impact)
                        <th style="font-size:{{ $cellFs }};text-align:center">
                            @if ($scoringConfig->usesMonarc())
                                {{ $impact['value'] }}
                            @else
                                {{ trans('cruds.risk.fields.impact') }} {{ $impact['value'] }}
                                <br><small class="text-muted" style="font-size:{{ $cellFs }}">{{ $impact['label'] }}</small>
                            @endif
                        </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                @foreach (array_reverse($yAxis) as $yLevel)
                <tr>
                    <th class="text-end" style="font-size:{{ $cellFs }}">
                        @if ($scoringConfig->usesMonarc())
                            {{ trans('cruds.risk.fields.impact') }} {{ $yLevel['value'] }}
                            <br><small class="text-muted" style="font-size:{{ $cellFs }}">{{ $yLevel['label'] }}</small>
                        @elseif ($scoringConfig->usesLikelihood())
                            {{ trans('cruds.risk.fields.likelihood') }} {{ $yLevel['value'] }}
                        @else
                            {{ trans('cruds.risk.fields.probability') }} {{ $yLevel['value'] }}
                            <br><small class="text-muted" style="font-size:{{ $cellFs }}">{{ $yLevel['label'] }}</small>
                        @endif
                    </th>
                    @foreach ($xAxis as $impact)
                        @php
                            $cell      = $matrix[$yLevel['value']][$impact['value']] ?? [];
                            $count     = count($cell);
                            $score     = $yLevel['value'] * $impact['value'];
                            $threshold = $scoringConfig->thresholdFor($score);
                            $thresholdIndex = $scoringConfig->thresholdIndexFor($score);
                            $bgColor   = $threshold['color'];
                            $txtColor  = contrast_color($threshold['color']);

                            @endphp
                        <td data-bs-placement="top" title="{{ collect($cell)->pluck('name')->take(5)->implode(', ') }}{{ $count > 5 ? ' …' : '' }}" data-bs-toggle="tooltip" style="background:{{ $bgColor }};color:{{ $txtColor }};padding:10px;vertical-align:middle;{{ $count > 0 ? 'cursor:pointer' : '' }}"
                            @if($count > 0)
                                data-href="/risk/index?threshold={{ $thresholdIndex }}"
                            @endif>
                            @if ($count > 0)
                                <div style="font-size:1.5rem;font-weight:700;line-height:1">{{ $count }}</div>
                                {{--
                                <small>{{ $count === 1 ? trans('cruds.risk.singular') : trans('cruds.risk.plural') }}</small>
                                --}}
                            @endif
                        </td>
                    @endforeach
                </tr>
                @endforeach
                </tbody>
            </table>
            </div>
        </div>

        {{-- Répartition par statut --}}
        <div class="col-lg-3 col-md-12" style="font-size:1rem">
            <table class="table table-sm table-borderless border mt-2">
            <tr>
                <td colspan=3>
            <strong>{{ trans('cruds.risk.fields.by_risks') }}</strong>
            </td>
            </tr>
            @foreach ($scoringConfig->risk_thresholds as $i => $t)
            @php
                $prevMax = $i > 0 ? $scoringConfig->risk_thresholds[$i-1]['max'] + 1 : 1;
            @endphp
            <tr>
                <td>
                    @if(($stats['by_level'][$i] ?? 0) > 0)
                    <a href="/risk/index?threshold={{ $i }}" class="no-underline">
                        <span class="bi bi-chevron-right"></span>
                    </a>
                    @endif
                </td>
                <td class="text-end">
                    <b>{{ $stats['by_level'][$i] ?? 0 }}</b>
                </td>
                <td class="text-start">
                    <a href="/risk/index?threshold={{ $i }}" class="no-underline">
                        <span class="badge"
                              style="
                                background:{{ $t['color'] ?? '#cccccc' }};
                                color: {{ contrast_color($t['color'] ?? '#cccccc') }};
                                padding:4px 10px;
                                pointer-events:none"
                                >
                            {{ $t['label'] }}
                        </span>
                    </a>
                    @if ($t['max']) {{ $prevMax }}–{{ $t['max'] }}
                    @else &gt; {{ $scoringConfig->risk_thresholds[$i-1]['max'] ?? 0 }} @endif
                </td>
            </tr>
            @endforeach

                <tr>
                    <td>
                        @if($stats['total'] > 0)
                        <a href="/risk/index" class="no-underline">
                            <span class="bi bi-chevron-right"></span>
                        </a>
                        @endif
                    </td>
                    <td class="text-end">
                        <b>{{ $stats['total'] }}</b>
                    </td>
                    <td class="text-start">
                        <a href="/risk/index" class="no-underline">
                        <b>{{ trans('cruds.risk.fields.total') }}</b>
                        </a>
                    </td>
                </tr>
            <tr>
                <td colspan="3">
                    <strong>{{ trans('cruds.risk.fields.by_status') }}</strong>
                </td>
            </tr>
                @foreach (\App\Models\Risk::availableStatuses() as $status => $label)
                <tr>
                    <td>
                        @if(($stats['by_status'][$status] ?? 0) > 0)
                        <a href="/risk/index?status={{$status}}" class="no-underline">
                                <span class="bi bi-chevron-right"></span>
                            </a>
                        @endif
                    </td>
                    <td class="text-end">
                        <b>{{ $stats['by_status'][$status] ?? 0 }}</b>
                    </td>
                    <td class="text-start">
                        <a href="/risk/index?status={{$status}}" class="no-underline">
                            <span class="badge text-bg-{{ \App\Models\Risk::STATUS_COLORS[$status] }}"
                                  style="padding:4px 10px;pointer-events:none">
                                {{ \App\Models\Risk::statusLabel($status) }}
                            </span>
                        </a>
                    </td>
                </tr>
                @endforeach
            </table>
        </div>
    </div>

    {{-- Navigation
    <div class="row mt-4">
        <div class="col-12">
            <a class="btn btn-light border" href="/risk/index">
                <span class="bi bi-x-circle"></span> {{ trans("common.cancel") }}
            </a>
        </div>
    </div>
    --}}

</div>
</div>
</div>
@endsection
