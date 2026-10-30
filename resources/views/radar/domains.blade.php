@extends("layout")

@section("content")
<form action="/radar/domains">
    <div class="card">
    <div class="card-header">@include('partials.help', ['page' => 'radar.domains'])<i class="bi bi-bar-chart"></i> {{ trans('cruds.domain.radar') }}</div>
    <div class="card-body">

    <div class="row">
        <div class="col-md-9">
            <div class="row">
                <div class="col-md-4 col-lg-3 col-xl-2">
                    <strong>{{ trans("cruds.domain.fields.framework") }}</strong>
                    <select class="form-select select2" name="framework" id="framework">
                        <option value=""></option>
                        @foreach ($frameworks as $framework)
                        <option
                            @if (Session::get("framework")==$framework->title)
                                selected
                            @endif >
                            {{ $framework->title }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 col-lg-3 col-xl-2">
                    <strong>{{ trans("cruds.measure.fields.scope") }}</strong>
                    <select class="form-select select2" name="scope" id="scope">
                        <option value=""></option>
                        @foreach ($scopes as $key => $value)
                        <option
                            @if (Session::get("scope")==$value->scope)
                                selected
                            @endif >
                            {{ $value->scope }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="d-none d-xl-block col-xl-6">
                </div>
                <div class="col-md-4 col-lg-3 col-xl-2">
                    <strong>{{ trans("cruds.measure.groupBy") }}</strong>
                    <select class="form-select select2" name="group" id="group">
                        <option value="0" {{ Session::get("group")==="0" ? "selected" : "" }}>{{ trans("cruds.welcome.measures") }}</option>
                        <option value="1" {{ Session::get("group")==="1" ? "selected" : "" }}>{{ trans("cruds.welcome.controls") }}</option>
                    </select>
                </div>
            </div>

            <div class="mt-2">
                <div class="card">
    <div class="card-header"><i class="bi bi-bar-chart"></i> {{ trans("cruds.measure.status_date") }} {{date('d/m/Y')}}</div>
    <div class="card-body">
                    <div class="p-8">
                        <canvas id="canvas-status" class="chartjs-render-monitor"></canvas>
                    </div>
                </div>
</div>
            </div>
        </div>
    </div>
    <div class="mt-2">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>{{ trans("cruds.measure.fields.domain") }}</th>
                    <th>{{ trans("cruds.measure.fields.clause") }}</th>
                    <th>{{ trans("cruds.measure.fields.name") }}</th>
                    <th>{{ trans("cruds.measure.fields.scope") }}</th>
                    <th>{{ trans("cruds.measure.fields.score") }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($active_controls as $control)
                <tr>
                    <td>{{ $control->title }}</td>
                    <td>
                        <a href="/alice/show/{{ $control->measure_id }}">
                            {{ $control->clause }}
                        </a>
                    </td>
                    <td>
                        <a href="/bob/show/{{ $control->control_id }}">{{ $control->name }}</a>
                    </td>
                    <td>{{ $control->scope }}</td>
                    <td>
                        @if ($control->score==1)
                            &#128545;
                        @elseif ($control->score==2)
                            &#128528;
                        @elseif ($control->score==3)
                            <span style="filter: sepia(1) saturate(5) hue-rotate(70deg)">&#128512;</span>
                        @else
                            &#9675; <!-- &#9899; -->
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</form>

<script>
document.addEventListener("DOMContentLoaded", function () {
    var barChartData = {
        labels : [
            @foreach ($domains as $domain)
                '{{ $domain->title }}'{{ $loop->last ? '' : ',' }}
            @endforeach
        ],
        datasets: [{
            backgroundColor: '#60a917',
            borderColor: '#60a917',
            stack: 'Stack 0',
            data: [
                @foreach ($domains as $domain)
                    <?php $count = 0; ?>
                    @foreach($active_controls as $c)
                      <?php if ($c->score == 3 && $c->title == $domain->title) {
                          $count++;
                      } ?>
                    @endforeach
                    {{ $count }}{{ $loop->last ? '' : ',' }}
                @endforeach
            ]
        }, {
            backgroundColor: '#fa6800',
            borderColor: '#fa6800',
            borderWidth: 1,
            stack: 'Stack 0',
            data: [
                @foreach ($domains as $domain)
                    <?php $count = 0; ?>
                    @foreach($active_controls as $c)
                      <?php if ($c->score == 2 && $c->title == $domain->title) {
                          $count++;
                      } ?>
                    @endforeach
                    {{ $count }}{{ $loop->last ? '' : ',' }}
                @endforeach
            ]
        }, {
            backgroundColor: '#ce352c',
            borderColor: '#ce352c',
            stack: 'Stack 0',
            data: [
                @foreach ($domains as $domain)
                    <?php $count = 0; ?>
                    @foreach($active_controls as $c)
                      <?php if ($c->score == 1 && $c->title == $domain->title) {
                          $count++;
                      } ?>
                    @endforeach
                    {{ $count }}{{ $loop->last ? '' : ',' }}
                @endforeach
            ]
        }, {
            label: 'Gris',
            backgroundColor: 'rgba(128,128,128,1)', // 100% opaque
            borderColor: '#000000',
            borderWidth: 1,
            stack: 'Stack 0',
            data: [
                @foreach ($domains as $domain)
                    <?php $count = 0; ?>
                    @foreach($controls_never_made as $c)
                      <?php if ($c->domain_id == $domain->id) {
                          $count++;
                      } ?>
                    @endforeach
                    {{ $count }}{{ $loop->last ? '' : ',' }}
                @endforeach
            ]
        }]
    };

    var ctx1 = document.getElementById('canvas-status').getContext('2d');
    window.myBar = new Chart(ctx1, {
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
            scales: {
                y: {
                    ticks: {
                        reverse: false,
                        stepSize: 10
                    }
                }
            }
        }
    });

    document.getElementById('canvas-status').onclick = function(evt) {
        const points = window.myBar.getElementsAtEventForMode(evt, 'nearest', { intersect: true }, true);
        if (points.length) {
            const firstPoint = points[0];
            const label = barChartData.labels[firstPoint.index];
            const value = barChartData.datasets[firstPoint.datasetIndex].data[firstPoint.index];
            window.location.href = "/bob/index?attribute=none&status=2&period=99&domain_title=" + encodeURIComponent(label);
        }
    };

    window.addEventListener('load', function() {
        ['scope', 'framework', 'group'].forEach(function(id) {
            var select = document.getElementById(id);
            if (select) {
                select.addEventListener('change', function() {
                    // Cleared field: "none" removes the filter kept in session
                    window.location = '/radar/domains?' + id + '=' + encodeURIComponent(this.value || 'none');
                }, false);
            }
        });
    });
});
</script>

</div>
</div>
@endsection
