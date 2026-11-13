@extends("layout")

@section("content")
<div class="row">
    <div class="col-lg-6 col-md-12">

        <div class="card">
    <div class="card-header">@include('partials.help', ['page' => 'measures.history'])<i class="bi bi-bar-chart"></i> {{ trans('cruds.measure.history') }}</div>
    <div class="card-body">

            <canvas id="canvas-status" width="600" height="300px" class="chartjs-render-monitor">
            </canvas>

            <div class="row">
                <div class="col-md-12 col-lg-6">
            <table class="table table-sm table-bordered">
                <?php
                    if (Request::get('date')==null)
                        $start_day=Carbon\Carbon::now()->floorMonth();
                    else
                        $start_day=Carbon\Carbon::createFromFormat("m/Y", Request::get('date'))->floorMonth();
                    $delta = today()->floorMonth()->diffInMonths($start_day);
                    if ($start_day<today())
                        $delta = -$delta;
                ?>
                <tbody>
                    @for ($i=-12;$i<0;$i++)
                    <?php
                        $first = today()->floorMonth()->addMonth($i+$delta);
                        $second = \Carbon\Carbon::today()->floorMonth()->addMonth($i+$delta)->endOfMonth();
                    ?>
                    <tr>
                        <td align="center">
                        <a href="/bob/index?period={{$i+$delta}}&domain=0&scope=none&status=0">
                          <?php
	                     echo $first->format("m/Y");
                          ?>
                        </a>
                        </td>
                        <td align="center">
                            <?php $count=0; ?>
                            @foreach ($measures as $measure)
                                <?php
                                if (($measure->score!=null) &&
                                    (\Carbon\Carbon::parse($measure->realisation_date)->between($first, $second))
                                )
                                {
                                        $count++;
                                }
                                ?>
                            @endforeach
                            {{ $count }}
                            /
                            <?php $count=0; ?>
                            @foreach ($measures as $measure)
                                <?php
                                if (
                                    (
                                        ($measure->score==null)&&
                                        (\Carbon\Carbon::parse($measure->plan_date)->between($first, $second))
                                    )||
                                    (
                                        ($measure->score!=null)&&
                                        (\Carbon\Carbon::parse($measure->realisation_date)->between($first, $second))
                                    )
                                )
                                {
                                        $count++;
                                }
                                ?>
                            @endforeach
                            {{ $count }}
                        </td>
                        <td  align="center">
                            <?php $count=0; ?>
                            @foreach ($measures as $measure)
                                <?php
                                if (($measure->score==1) &&
                                    (\Carbon\Carbon::parse($measure->realisation_date)->between($first, $second))
                                ) {
                                        $count++;
                                }
                                ?>
                            @endforeach
                            <span style="color:#D94F45">
                            @if ($count>0) {{ $count }}
                                &nbsp; &#9679;
                            @else
                                &nbsp; &nbsp; &nbsp;
                            @endif
                            </span>
                        </td>
                        <td  align="center">
                            <?php $count=0; ?>
                            @foreach ($measures as $measure)
                                <?php
                                if (($measure->score==2) &&
                                    (\Carbon\Carbon::parse($measure->realisation_date)->between($first, $second))
                                ) {
                                        $count++;
                                }
                                ?>
                            @endforeach
                            <span style="color:#E8731A">
                            @if ($count>0) {{ $count }}
                                &nbsp; &#9679;
                            @else
                                &nbsp; &nbsp; &nbsp;
                            @endif
                            </span>
                        </td>
                        <td align="center">
                            <?php $count=0; ?>
                            @foreach ($measures as $measure)
                                <?php
                                if (($measure->score==3) &&
                                    (\Carbon\Carbon::parse($measure->realisation_date)->between($first, $second))
                                ) {
                                        $count++;
                                }
                                ?>
                            @endforeach
                            <span style="color:#3AB87A">
                            @if ($count>0) {{ $count }} &nbsp; &#9679;
                            @else
                            &nbsp; &nbsp; &nbsp;
                            @endif
                            </span>
                        </td>
                    </tr>
                @endfor
                </tbody>
            </table>
        </div>

    <!----------------------------------------------------------->

    <div class="col-md-12 col-lg-6">
    <table class="table table-sm table-bordered">
        <tbody>
        <?php
        for ($i=0;$i<12;$i++) {
            $first = \Carbon\Carbon::today()->day(1)->addMonth($i+$delta);
            $second = \Carbon\Carbon::today()->day(1)->addMonth($i+$delta)->endOfMonth();
            ?>
            <tr>
                <td align="center">
                <a href="/bob/index?period={{$i+$delta}}&domain=0&scope=none&status=0">
                <?php
	             echo $first->format("m/Y");
                ?>
                </a>
                </td>
                <td align="center">
                <?php $count=0; ?>
                    @foreach ($measures as $measure)
                    <?php
                    if (($measure->score!=null) &&
                        (\Carbon\Carbon::parse($measure->realisation_date)->between($first, $second))
                    ) {
                            $count++;
                    }
                    ?>
                    @endforeach
                    {{ $count }}
                    /
                    <?php $count=0; ?>
                    @foreach ($measures as $measure)
                        <?php
                        if (
                            (
                                ($measure->score==null) &&
                                (\Carbon\Carbon::parse($measure->plan_date)->between($first, $second))
                            )||
                            (
                                ($measure->score!=null) &&
                                (\Carbon\Carbon::parse($measure->realisation_date)->between($first, $second))
                            )
                        ) {
                                $count++;
                        }
                        ?>
                    @endforeach
                    {{ $count }}
                </td>
                <td  align="center">
                    <?php $count=0; ?>
                    @foreach ($measures as $measure)
                        <?php
                        if (($measure->score==1)
                            && (\Carbon\Carbon::parse($measure->realisation_date)->between($first, $second))
                        ) {
                                $count++;
                        }
                        ?>
                    @endforeach
                    <span style="color:#D94F45">
                    @if ($count>0) {{ $count }} &nbsp; &#9679;
                    @else
                    &nbsp; &nbsp; &nbsp;
                    @endif
                    </span>
                </td>
                <td  align="center">
                    <?php $count=0; ?>
                    @foreach ($measures as $measure)
                        <?php
                        if (
                            ($measure->score==2) &&
                            (\Carbon\Carbon::parse($measure->realisation_date)->between($first, $second))
                            )
                        {
                                $count++;
                        }
                        ?>
                    @endforeach
                    <span style="color:#E8731A">
                    @if ($count>0) {{ $count }} &nbsp; &#9679;
                    @else
                    &nbsp; &nbsp; &nbsp;
                    @endif
                    </span>
                </td>
                <td align="center">
                    <?php $count=0; ?>
                    @foreach ($measures as $measure)
                        <?php
                        if (($measure->score==3) &&
                            (\Carbon\Carbon::parse($measure->realisation_date)->between($first, $second))
                        ) {
                                $count++;
                        }
                        ?>
                    @endforeach
                    <span style="color:#3AB87A">
                    @if ($count>0) {{ $count }} &nbsp; &#9679;
                    @else
                    &nbsp; &nbsp; &nbsp;
                    @endif
                    </span>
                </td>
            </tr>
        <?php  } ?>
        </tbody>
    </table>


<!------->

</div>

       </div>
    </div>
</div>

</div>

    <div class="col-lg-6 col-md-12">
        <div class="card">
    <div class="card-header"><i class="bi bi-calendar3"></i> {{ trans('cruds.measure.calendar') }}</div>
    <div class="card-body">

            <?php
            if (Request::get('date')!==null)
                $calendar = new \App\Calendar(Request::get('date'));
            else
                $calendar = new \App\Calendar(\Carbon\Carbon::now()->format('m/Y'));

            foreach ($measures as $measure) {
                if (($measure->score===null) && ($measure->plan_date!==null)) {
                    if ($measure->observations===null)
                        $calendar->addEvent($measure->controls->implode(', '), $measure->plan_date, 1, 'grey', $measure->id);
                    else
                        $calendar->addEvent($measure->controls->implode(', '), $measure->plan_date, 1, 'lblue', $measure->id);
                    }
                else if (($measure->score===1) && ($measure->realisation_date!==null)) {
                        $calendar->addEvent($measure->controls->implode(', '), $measure->realisation_date, 1, 'red', $measure->id);
                        }
                else if (($measure->score===2) && ($measure->realisation_date!==null)) {
                        $calendar->addEvent($measure->controls->implode(', '), $measure->realisation_date, 1, 'orange', $measure->id);
                        }
                else if (($measure->score===3) && ($measure->realisation_date!==null)) {
                        $calendar->addEvent($measure->controls->implode(', '), $measure->realisation_date, 1, 'green', $measure->id);
                    }
                }
            echo $calendar;
            ?>
        </div>
</div>
    </div>
</div>
</div>

</div>
</div>
</div>
</div>

<!------------------------------------------------------------------------------------->

<script @nonce>
    var barChartData = {
        labels : [
        <?php
        for ($i=-12;$i<12;$i++) {
            $now = \Carbon\Carbon::now();
            echo '"';
            echo $now->startOfMonth()->addMonth($i+$delta)->format("m/Y");
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
                for ($i=-12; $i<12; $i++) {
                    $count=0;
                    $first = \Carbon\Carbon::today()->day(1)->addMonth($i+$delta);
                    $second = \Carbon\Carbon::today()->day(1)->addMonth($i+$delta)->endOfMonth();
                    ?>
                @foreach ($measures as $measure)
                    <?php
                    if (($measure->score==3) &&
                        ($measure->realisation_date!=null) &&
                        (\Carbon\Carbon::parse($measure->realisation_date)->between($first, $second))
                    ) { $count++;
                    }
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
                for ($i=-12; $i<12; $i++) {
                    $count=0;
                    $first = \Carbon\Carbon::today()->day(1)->addMonth($i+$delta);
                    $second = \Carbon\Carbon::today()->day(1)->addMonth($i+$delta)->endOfMonth();
                    ?>
                @foreach ($measures as $measure)
                    <?php
                    if (($measure->score==2) &&
                        ($measure->realisation_date!=null) &&
                        (\Carbon\Carbon::parse($measure->realisation_date)->between($first, $second))
                    ) { $count++;
                    }
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
                for ($i=-12; $i<12; $i++) {
                    $count=0;
                    $first = \Carbon\Carbon::today()->day(1)->addMonth($i+$delta);
                    $second = \Carbon\Carbon::today()->day(1)->addMonth($i+$delta)->endOfMonth();
                    ?>
                @foreach ($measures as $measure)
                    <?php
                    if (($measure->score==1) &&
                        ($measure->realisation_date!=null) &&
                        (\Carbon\Carbon::parse($measure->realisation_date)->between($first, $second))
                    ) {
                        $count++;
                    }
                    ?>
                @endforeach
                {{ $count }},
                <?php } ?>
            ]
        },
        {
            backgroundColor: "rgba(128,128,128,0.3)",
            borderColor: "#808080",
            stack: 'Stack 0',
            data: [
                <?php
                for ($i=-12; $i<12; $i++) {
                    $count=0;
                    $first = \Carbon\Carbon::today()->day(1)->addMonth($i+$delta);
                    $second = \Carbon\Carbon::today()->day(1)->addMonth($i+$delta)->endOfMonth();
                    ?>
                @foreach ($measures as $measure)
                    <?php
                    if (($measure->score==null)
                        && ($measure->plan_date!=null)
                        && (\Carbon\Carbon::parse($measure->plan_date)->between($first, $second))
                    ) {
                            $count++;
                    }
                    ?>
                @endforeach
                {{ $count }},
                <?php } ?>
            ]
        }
        ]
    };

    window.addEventListener('load', function() {
        var ctx = document.getElementById('canvas-status').getContext('2d');
        window.myBar = new Chart(ctx, {
            type: 'bar',
            data: barChartData,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    title: {
                        display: false
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
                onClick: (event, elements, chart) => {
                    const points = chart.getElementsAtEventForMode(event.native, 'nearest', { intersect: true }, true);
                    if (points.length) {
                        const firstPoint = points[0];
                        const label = chart.data.labels[firstPoint.index];
                        window.location.href = "/bob/history?date=" + label;
                    }
                },
                scales: {
                    x: {
                        stacked: true
                    },
                    y: {
                        stacked: true
                    }
                }
            }
        });

        document.getElementById('date').addEventListener('change', function() {
            window.location = '/bob/history?date=' + this.value;
        });
    });
</script>

@endsection