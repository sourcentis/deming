@extends("layout")

@section("content")
<form action="/radar/actions">
    <div class="card">
    <div class="card-header">@include('partials.help', ['page' => 'radar.actions'])<i class="bi bi-bar-chart"></i> {{ trans('cruds.action.radar') }}</div>
    <div class="card-body">

    <div class="row">
        <div class="col-md-9">
            <div class="row">
                <div class="col-auto">
                    <strong>Period</strong>
                    <div class="input-group">
                        <span class="input-group-text">Start</span>
                        <input class="form-control" type="date" id="start" name="start" value="{{ $start }}"/>
                    </div>
                </div>
                <div class="col-auto">
                    <br>
                    <div class="input-group">
                        <span class="input-group-text">End</span>
                        <input class="form-control" type="date" id="end" name="end" value="{{ $end }}"/>
                    </div>
                </div>
                <div class="col-md-8 col-lg-4">
                    <strong>Scope</strong>
                    <select class="form-select select2" id='scope' name="scope">
                        <option value="">-- {{ trans("cruds.action.fields.choose_scope")}} --</option>
                        @foreach ($scopes as $scope)
                            <option {{ (Session::get('scope')==$scope) ? 'selected' : '' }}>
                                {{ $scope }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mt-2">
                <div class="card">
    <div class="card-header"><i class="bi bi-bar-chart"></i> {{ trans("cruds.action.radar_date") }} {{ date('d/m/Y')}}</div>
    <div class="card-body">
                    <div class="p-8">
                        <canvas id="actionsChart" class="chartjs-render-monitor"></canvas>
                    </div>
                </div>
</div>
            </div>

            <div class="mt-2">
                <div class="row">
                    <div class="col-md-12">
                        <table data-order="[[0,&quot;asc&quot;]]" data-page-length="100" id="actions" data-title="Deming - {{ trans('cruds.action.radar') }} - {{ now()->format('d/m/Y') }}" class="table table-striped table-hover table-bordered datatable">
                            <thead>
                                <tr>
                                    <th>{{ trans('cruds.action.fields.reference') }}</th>
                                    <th>{{ trans('cruds.action.fields.type') }}</th>
                                    <th>{{ trans('cruds.action.fields.status') }}</th>
                                    <th>{{ trans('cruds.action.fields.name') }}</th>
                                    <th>{{ trans('cruds.action.fields.due_date') }}</th>
                                    <th>{{ trans('cruds.action.fields.progress') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                        @foreach($actions as $action)
                        <tr data-href="/action/show/{{ $action->id }}">
                            <td>
                                <b id="{{ $action->reference }}">{{ \App\Models\Action::displayReference($action->reference, $action->id) }}</b>
                            </td>
                            <td data-order="{{ $action->type }}">
                                @if ($action->type==1)
                                    <div class="text-danger fw-bold">{{ trans('cruds.action.types.major') }}</div>
                                @elseif ($action->type==2)
                                    <div class="text-orange fw-bold">{{ trans('cruds.action.types.minor') }}</div>
                                @elseif ($action->type==3)
                                    <div class="text-warning fw-bold">{{ trans('cruds.action.types.observation') }}</div>
                                @elseif ($action->type==4)
                                    <div class="text-success fw-bold">{{ trans('cruds.action.types.opportunity') }}</div>
                                @endif
                            </td>
                            <td id="{{ $action->status }}">
                                @if ($action->status==0)
                                {{ trans('cruds.action.fields.status_open') }}
                                @elseif ($action->status==1)
                                {{ trans('cruds.action.fields.status_closed') }}
                                @elseif ($action->status==2)
                                {{ trans('cruds.action.fields.status_rejected') }}
                                @else
                                {{ $action->status }}
                                @endif
                            </td>
                            <td>{{ $action->name }}</td>
                            <td>
                            @if ($action->due_date!==null)
                                <b>
                                @if (today()->lte($action->due_date))
                                    <font color="green">{{ $action->due_date }}</font>
                                @else
                                    <font color="red">{{ $action->due_date }}</font>
                                @endif
                                </b>
                            @endif
                            </td>
                            <td style="min-width: 130px" data-order="{{ (int) $action->progress }}">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" role="progressbar"
                                         aria-label="{{ trans('cruds.action.fields.progress') }}"
                                         aria-valuenow="{{ (int) $action->progress }}" aria-valuemin="0" aria-valuemax="100">
                                        <div class="progress-bar {{ $action->progress < 25 ? 'bg-danger' : ($action->progress < 50 ? 'bg-warning' : 'bg-success') }}"
                                             style="width: {{ (int) $action->progress }}%"></div>
                                    </div>
                                    <small class="text-nowrap">{{ (int) $action->progress }}%</small>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

</form>

<script @nonce>
document.addEventListener("DOMContentLoaded", function () {

    const chartData = @json($data);
    const labels = ['Major', 'Minor', 'Observation', 'Opportunity'];
    const colors = ['#ce352c', '#fa6800', '#D3D936', '#60a917'];

    const data = {
        labels: labels,
        datasets: [
            {
                label: 'Open',
                data: chartData.map(d => d.open),
                backgroundColor: chartData.map(d => colors[d.type - 1]),
                stack: 'Stack 0'
            },
            {
                label: 'Closed',
                data: chartData.map(d => d.closed),
                backgroundColor: '#bdbdbd',
                stack: 'Stack 0'
            }
        ]
    };

    const ctx = document.getElementById('actionsChart').getContext('2d');
    const myChart = new Chart(ctx, {
        type: 'bar',
        data: data,
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                title: { display: false },
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
            scales: {
                x: {
                    stacked: true
                },
                y: {
                    stacked: true,
                    beginAtZero: true
                }
            }
        }
    });

    window.addEventListener('load', function() {
        ['scope', 'start'].forEach(function(id) {
            var select = document.getElementById(id);
            if (select) {
                select.addEventListener('change', function() {
                    window.location = '/radar/actions?' + id + '=' + this.value;
                }, false);
            }
        });
    });

});
</script>

</div>
</div>
@endsection
