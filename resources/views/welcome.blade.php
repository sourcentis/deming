@extends("layout")

@section("content")
<div class="card">
    <div class="card-header">@include('partials.help', ['page' => 'welcome'])<i class="bi bi-house"></i> {{ trans('cruds.welcome.dashboard') }}</div>
    <div class="card-body">
    <div class="row">
        <div class="col-lg-2 col-md-6 mt-2">
            {{-- Domaines — navy : ancre structurelle --}}
            <div class="more-info-box text-white" style="background-color: #3A72C4 !important;">
                <div class="content">
                    <h2 class="fw-bold mb-0">
                        {{ $active_domains_count }}
                    </h2>
                    <div>{{ trans('menu.domains') }}</div>
                </div>
                <div class="icon">
                    <span class="icon-library"></span>
                </div>
                <a href="/domains" class="more stretched-link"> {{ trans('common.more_info') }} <span class="bi bi-arrow-right"></span></a>
            </div>
        </div>
        <div class="col-lg-2 col-md-6 mt-2">
            {{-- Mesures — teal : référentiel/politique --}}
            <div class="more-info-box text-white" style="background-color: #17A8C4 !important;">                <div class="content">
                    <h2 class="fw-bold mb-0">
                        {{ $active_measures_count }}
                    </h2>
                    <div>{{ trans('menu.measures') }}</div>
                </div>
                <div class="icon">
                    <span class="bi bi-book"></span>
                </div>
                <a href="/alice/index" class="more stretched-link"> {{ trans('common.more_info') }} <span class="bi bi-arrow-right"></span></a>
            </div>
        </div>
        <div class="col-lg-2 col-md-6 mt-2">
            {{-- Contrôles — vert foncé : conformité réalisée --}}
            <div class="more-info-box text-white" style="background-color: #3AB87A !important;">                <div class="content">
                    <h2 class="fw-bold mb-0">
                        {{ $controls_made_count }}
                    </h2>
                    <div>{{ trans('menu.controls') }}</div>
                </div>
                <div class="icon">
                    <span class="bi bi-clipboard-check"></span>
                </div>
                <a href="/bob/index?attribute=none&amp;domain=0&amp;scope=none&amp;period=99&amp;status=1" class="more stretched-link"> {{ trans('common.more_info') }} <span class="bi bi-arrow-right"></span></a>
            </div>
        </div>
        <div class="col-lg-2 col-md-6 mt-2">

            {{-- Plans d'action — ambre : vigilance/remédiation --}}
            <div class="more-info-box text-white" style="background-color: #E09B1A !important;">
                <div class="content">
                    <h2 class="fw-bold mb-0">
                        {{ $action_plans_count }}
                    </h2>
                <div>{{ trans('menu.action_plan') }}</div>
                </div>
                <div class="icon">
                    <span class="bi bi-list-check"></span>
                </div>
                <a href="/actions" class="more stretched-link"> {{ trans('common.more_info') }} <span class="bi bi-arrow-right"></span></a>
            </div>
        </div>

        <div class="col-lg-2 col-md-6 mt-2">
            {{-- Risques — rouge profond : criticité --}}
            <div class="more-info-box text-white" style="background-color: #D94F45 !important;">
                <div class="content">
                    <h2 class="fw-bold mb-0">
                       {{ $risks_count }}
                    </h2>
                <div>{{ trans('cruds.risk.plural') }}</div>
                </div>
                <div class="icon">
                    <span class="bi bi-exclamation-triangle"></span>
                </div>
                <a href="/risk/index" class="more stretched-link"> {{ trans('common.more_info') }} <span class="bi bi-arrow-right"></span></a>
            </div>
        </div>

        <div class="col-lg-2 col-md-6 mt-2">
            {{-- Exceptions — violet : statut dérogatoire --}}
            <div class="more-info-box text-white" style="background-color: #8059C8 !important;">                <div class="content">
                    <h2 class="fw-bold mb-0">
                       {{ $exceptions_count }}
                    </h2>
                <div>{{ trans('menu.exceptions') }}</div>
                </div>
                <div class="icon">
                    <span class="bi bi-x-octagon"></span>
                </div>
                <a href="/exception/index" class="more stretched-link"> {{ trans('common.more_info') }} <span class="bi bi-arrow-right"></span></a>
            </div>
        </div>

    </div>
</div>
</div>
<div class="row">
    <div class="col-lg-7 col-md-12 mt-3">
        <div class="card">
    <div class="card-header"><i class="bi bi-bar-chart"></i> {{ trans('cruds.welcome.control_planning') }}</div>
    <div class="card-body">
            <div class="d-flex justify-content-center align-items-center" style="height: 510px;">
                <canvas id="canvas-status" style="max-height: 510px;"  class="chartjs-render-monitor"></canvas>
            </div>
        </div>
</div>
    </div>
    <div class="col-lg-5 col-md-12 mt-3">
        <div class="card">
    <div class="card-header"><i class="bi bi-pie-chart"></i> {{ trans('cruds.welcome.control_status') }}</div>
    <div class="card-body">
            <div class="d-flex justify-content-center align-items-center" style="height: 510px;">
                <canvas id="canvas-doughnut" style="max-height: 510px;"  class="chartjs-render-monitor"></canvas>
            </div>
        </div>
</div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
    <div class="card-header"><i class="bi bi-alarm"></i> {{ trans('cruds.welcome.next_controls') }}</div>
    <div class="card-body">
                <table
                    class="table table-striped table-hover table-bordered"
                    >
                    <thead>
                        <tr>
                            <th>{{ trans('cruds.measure.fields.clauses') }}</th>
                            <th>{{ trans('cruds.measure.fields.name') }}</th>
                            <th>{{ trans('cruds.measure.fields.scope') }}</th>
                            <th>{{ trans('cruds.measure.fields.score') }}</th>
                            <th>{{ trans('cruds.measure.fields.realisation_date') }}</th>
                            <th>{{ trans('cruds.measure.fields.plan_date') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($controls_todo as $control)
                        <tr>
                            <td class="wrap-column">
                                @if($control->measures->count() > 3)
                                    @php
                                        $clauses = $control->measures->pluck('clause');
                                        // Grouper par 3 et joindre avec retour à la ligne
                                        $formattedClauses = $clauses->chunk(3)->map(function($chunk) {
                                            return $chunk->implode(', ');
                                        })->implode('<br>');
                                    @endphp
                                    <span data-bs-placement="top" data-bs-html="true" title="{{ $formattedClauses }}" data-bs-toggle="tooltip"
                                        style="cursor: help; border-bottom: 1px dotted #999;"
                                    >
                                        @foreach($control->measures->take(2) as $measure)
                                        <a id="{{ $measure['clause'] }}" href="/alice/show/{{ $measure['id'] }}">
                                            {{ $measure['clause'] }}
                                        </a>
                                        @if (!$loop->last)
                                        ,
                                        @endif
                                        @endforeach
                                        , ...
                                    </span>
                                @else
                                    @foreach($control->measures as $measure)
                                    <a id="{{ $measure['clause'] }}" href="/alice/show/{{ $measure['id'] }}">
                                        {{ $measure['clause'] }}
                                    </a>
                                    @if (!$loop->last)
                                    ,
                                    @endif
                                    @endforeach
                                @endif
                            </td>

                            <td>{{ $control->name }}</td>
                            <td>
                                <a href="/bob/index?domain=0&attribute=none&scope={{ urlencode($control->scope) }}&status=0&period=99">
                                    {{ $control->scope }}
                                </a>
                            </td>
                            <td class="text-center">
                                <a href="/bob/show/{{ $control->prev_id }}" style="text-decoration: none;">
                                    @if ($control->score==1)
                                        &#128545;
                                    @elseif ($control->score==2)
                                        &#128528;
                                    @elseif ($control->score==3)
                                        <span style="filter: sepia(1) saturate(5) hue-rotate(70deg)">&#128512;</span>
                                    @else
                                        &#9675; <!-- &#9899; -->
                                    @endif
                                </a>
                            </td>
                            <td><a href="/bob/show/{{$control->prev_id}}">{{ $control->prev_date }}</a></td>
                            <td>
                                <a id="{{ $control->plan_date }}" href="/bob/show/{{ $control->id }}">
                                    <span style="font-weight: bold; color: {{ today()->lte($control->plan_date) ? 'green' : 'red' }}">{{ $control->plan_date }}</span>
                                </a>
                                @if ($control->status===1)
                                    &nbsp;<a href="/bob/make/{{ $control->id }}"><i class="bi bi-hourglass-split"></i></a>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                <br><br><br><br><br>
            </div>
</div>
        </div>
    </div>

<!------------------------------------------------------------------------------------->
<script>
document.addEventListener("DOMContentLoaded", function () {
    var barChartData = {
        labels: [
        <?php
        for ($i = -12; $i < 12; $i++) {
            $now = \Carbon\Carbon::now();
            echo '"';
            echo $now->startOfMonth()->addMonth($i)->format("m/Y");
            echo '",';
        }
        ?>
        ],
        datasets: [
            {
                backgroundColor: "#3AB87A",
                borderColor: "#3AB87A",
                stack: 'Stack 0',
                data: [
                    <?php
                    for ($i = -12; $i < 12; $i++) {
                        $count = 0;
                        $first = \Carbon\Carbon::today()->startOfMonth()->addMonth($i);
                        $second = \Carbon\Carbon::today()->startOfMonth()->addMonth($i)->endOfMonth();
                        ?>
                        @foreach ($controls as $control)
                            <?php
                            if (($control->score == 3) && ($control->status != 1) && ($control->realisation_date != null) && (\Carbon\Carbon::parse($control->plan_date)->between($first, $second))) { $count++; }
                            ?>
                        @endforeach
                        {{ $count }},
                    <?php } ?>
                ]
            },
            {
                backgroundColor: "#E8731A",
                borderColor: "#E8731A",
                stack: 'Stack 0',
                data: [
                    <?php
                    for ($i = -12; $i < 12; $i++) {
                        $count = 0;
                        $first = \Carbon\Carbon::today()->startOfMonth()->addMonth($i);
                        $second = \Carbon\Carbon::today()->startOfMonth()->addMonth($i)->endOfMonth();
                        ?>
                        @foreach ($controls as $control)
                            <?php
                            if (($control->score == 2) && ($control->status != 1) && ($control->realisation_date != null) && (\Carbon\Carbon::parse($control->plan_date)->between($first, $second))) { $count++; }
                            ?>
                        @endforeach
                        {{ $count }},
                    <?php } ?>
                ]
            },
            {
                backgroundColor: "#D94F45",
                borderColor: "#D94F45",
                stack: 'Stack 0',
                data: [
                    <?php
                    for ($i = -12; $i < 12; $i++) {
                        $count = 0;
                        $first = \Carbon\Carbon::today()->startOfMonth()->addMonth($i);
                        $second = \Carbon\Carbon::today()->startOfMonth()->addMonth($i)->endOfMonth();
                        ?>
                        @foreach ($controls as $control)
                            <?php
                            if (($control->score == 1) && ($control->status != 1) && ($control->realisation_date != null) && (\Carbon\Carbon::parse($control->plan_date)->between($first, $second))) { $count++; }
                            ?>
                        @endforeach
                        {{ $count }},
                    <?php } ?>
                ]
            },
            {
                backgroundColor: "#7EC8F0", // bleu clair : en attente de validation
                borderColor: "#7EC8F0",
                stack: 'Stack 0',
                data: [
                    <?php
                    for ($i = -12; $i < 12; $i++) {
                        $count = 0;
                        $first = \Carbon\Carbon::today()->startOfMonth()->addMonth($i);
                        $second = \Carbon\Carbon::today()->startOfMonth()->addMonth($i)->endOfMonth();
                        ?>
                        @foreach ($controls as $control)
                            <?php
                            if (($control->status == 1) && (\Carbon\Carbon::parse($control->plan_date)->between($first, $second))) { $count++; }
                            ?>
                        @endforeach
                        {{ $count }},
                    <?php } ?>
                ]
            },
            {
                backgroundColor: "rgba(128, 128, 128, 0.3)", // gris avec transparence
                borderColor: "gray",
                stack: 'Stack 0',
                data: [
                    <?php
                    for ($i = -12; $i < 12; $i++) {
                        $count = 0;
                        $first = \Carbon\Carbon::today()->startOfMonth()->addMonth($i);
                        $second = \Carbon\Carbon::today()->startOfMonth()->addMonth($i)->endOfMonth();
                        ?>
                        @foreach ($controls as $control)
                            <?php
                            if (($control->realisation_date == null) && (\Carbon\Carbon::parse($control->plan_date)->between($first, $second))) {
                                $count++;
                            }
                            ?>
                        @endforeach
                        {{ $count }},
                    <?php } ?>
                ]
            },
        ]
    };

    var ctx = document.getElementById('canvas-status').getContext('2d');
    window.myBar = new Chart(ctx, {
        type: 'bar',
        data: barChartData,
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false,
                },
                title: {
                    display: false,
                },
                datalabels: {
                    color: 'white',
                    anchor: 'center',
                    align: 'center',
                    font: {
                        weight: 'bold'
                    },
                    formatter: (value) => value > 0 ? value : ''
                }
            },
            onHover: (event, chartElement) => {
                event.native.target.style.cursor = chartElement.length ? 'pointer' : 'default';
            },
            onClick: (event, elements) => {
                const activePoints = window.myBar.getElementsAtEventForMode(event.native, 'nearest', { intersect: true }, true);
                if (activePoints.length) {
                    const firstPoint = activePoints[0];
                    window.location.href = "/bob/index?domain=0&clause=none&attribute=none&scope=none&status=0&period=" + (firstPoint.index - 12);
                }
            }
        },
    });

    var options = {
        responsive: true,
        plugins: {
            legend: {
                display: true,
                position: 'bottom',
            },
            title: {
                display: false,
            },
            datalabels: {
                color: 'white',
                anchor: 'center',
                align: 'center',
                font: {
                    weight: 'bold'
                },
                formatter: (value) => value > 0 ? value : ''
            }
        }
    };

    var ctx2 = document.getElementById('canvas-doughnut').getContext('2d');

    var marksData = {
        labels: [
            "{{ trans('common.fail') }}",
            "{{ trans('common.alert') }}",
            "{{ trans('common.success') }}",
            @if ($can_validate)
            "{{ trans('common.to_validate') }}",
            @endif
            "{{ trans('common.unknown') }}"
        ],
        datasets: [
            {
                backgroundColor: [
                    '#D94F45', '#E8731A', '#3AB87A', @if ($can_validate) '#7EC8F0', @endif 'gray'
                ],
                borderWidth: 0,
                data: [
                    <?php $count=0; ?>
                    @foreach($active_controls as $c)
                        <?php if ($c->score=="1") { $count++; } ?>
                    @endforeach
                    {{ $count }},
                    <?php $count=0; ?>
                    @foreach($active_controls as $c)
                        <?php if ($c->score=="2") { $count++; } ?>
                    @endforeach
                    {{ $count }},
                    <?php $count=0; ?>
                    @foreach($active_controls as $c)
                        <?php if ($c->score=="3") { $count++; } ?>
                    @endforeach
                    {{ $count }},
                    @if ($can_validate)
                    {{ $controls_to_validate }},
                    @endif
                    {{ $controls_never_made }}
                ]
            }
        ]
    };

    var radarChart = new Chart(ctx2, {
        type: 'doughnut',
        data: marksData,
        options: options
    });
});
</script>
@endsection
