@extends("layout")

@section("content")
<form action="/radar/domains">
    <div class="card">
    <div class="card-header">@include('partials.help', ['page' => 'radar.measures'])<i class="bi bi-speedometer"></i> {{ trans("cruds.welcome.dashboard") }}</div>
    <div class="card-body">

        <div class="row">
            <div class="col-md-4 col-lg-2">
                <strong>{{ trans("cruds.measure.fields.clause") }}</strong>
                <select class="form-select select2" name="clauses" id="clauses">
                    <option></option>
                    @foreach ($clauses as $clause)
                    <option value="{{ trim($clause) }}"
                        @if (request()->get('clause') == trim($clause)) selected @endif>
                        {{ $clause }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 col-lg-2">
            @if (($scopes!=null) && ($scopes->count()>0))
                <strong>{{ trans("cruds.measure.fields.scope") }}</strong>
                <select class="form-select select2" name="scopes" id="scopes">
                    <option></option>
                    @foreach ($scopes as $scope)
                    <option value="{{ trim($scope) }}"
                        @if (request()->get('scope') == trim($scope)) selected @endif>
                        {{ $scope }}
                    </option>
                    @endforeach
                </select>
            @endif
            </div>
        </div>
    </div>
</div>

    @php
    // One chart per scope, in alphabetical order; controls without scope come last
    $groups = [];
    foreach ($controls as $control) {
        $groups[$control->id] = $control->measures
            ->groupBy(fn ($measure) => (string) $measure->scope)
            ->sortKeysUsing(fn ($a, $b) => ($a === '') <=> ($b === '') ?: strnatcasecmp($a, $b))
            ->values();
    }
    @endphp

    @foreach($controls as $control)
    <div class="card mt-2">
    <div class="card-header"><i class="bi bi-graph-up"></i> {{ $control->name }}</div>
    <div class="card-body">
        @foreach($groups[$control->id] as $index => $measures)
        @if ($groups[$control->id]->count() > 1)
        <h6 class="fw-bold {{ $loop->first ? '' : 'mt-4' }}">{{ $measures->first()->scope ?? '—' }}</h6>
        @endif
        <div class="p-7" style="height: 300px;">
            <canvas id="scoreChart-{{ $control->id }}-{{ $index }}"></canvas>
        </div>

        <div>
            <div style="overflow-x: auto;">
                <table class="table table-bordered table-striped" style="width: max-content;">
                    <tbody>
                        <tr>
                            <td class="fw-bold">{{ trans("cruds.measure.fields.realisation_date") }}</td>
                            @foreach($measures as $measure)
                            <td><a href="/bob/show/{{ $measure->id }}">{{ $measure->realisation_date }}</a></td>
                            @endforeach
                        </tr>
                        <tr>
                            <td class="fw-bold">{{ trans("cruds.measure.fields.score") }}</td>
                            @foreach($measures as $measure)
                            <td class="text-center"
                                {!! $measure->score == 1 ? 'style="background-color: #ce352c;"' : '' !!}
                                {!! $measure->score == 2 ? 'style="background-color: #fa6800;"' : '' !!}
                                {!! $measure->score == 3 ? 'style="background-color: #60a917;"' : '' !!}>
                            {{ $measure->note }}
                            </td>
                            @endforeach
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        @endforeach
    </div>
</div>

    @endforeach

</form>

<script>
window.addEventListener('DOMContentLoaded', function() {
    var clauseSelect = document.getElementById('clauses');
    clauseSelect.addEventListener('change', function() {
        const clauseSelectOption = clauseSelect.options[clauseSelect.selectedIndex];
        window.location = '/radar/bob?clause=' + encodeURIComponent(clauseSelectOption.value);
    }, false);

    var scopeSelect = document.getElementById('scopes');
    if (scopeSelect!=null)
        scopeSelect.addEventListener('change', function() {
            const clauseSelectOption = clauseSelect.options[clauseSelect.selectedIndex];
            const scopeSelectOption = scopeSelect.options[scopeSelect.selectedIndex];
            window.location = '/radar/bob?clause=' +
                encodeURIComponent(clauseSelectOption.value) +
                '&scope=' + encodeURIComponent(scopeSelectOption.value);
        }, false);


@foreach($controls as $control)
@foreach($groups[$control->id] as $index => $measures)
{
    const labels = @json($measures->pluck('realisation_date'));
    const data = @json($measures->pluck('note'));

    const ctx = document.getElementById('scoreChart-{{$control->id}}-{{$index}}').getContext('2d');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                borderColor: 'rgba(0,123,255,1)',
                backgroundColor: 'rgba(0,123,255,0.1)',
                fill: true,
                tension: 0.2, // Remplace lineTension
                pointRadius: 4,
                pointHoverRadius: 6,
                pointBackgroundColor: 'rgba(0,123,255,1)'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                title: {
                    display: false
                },
                datalabels: {
                    display: false
                }
            },
            scales: {
                x: {
                    type: 'time',
                    time: {
                        unit: 'day',
                        tooltipFormat: 'yyyy-MM-dd'
                    },
                    ticks: {
                        autoSkip: true
                    }
                },
                y: {
                    ticks: {
                        beginAtZero: true
                    }
                }
            },
            interaction: {
                mode: 'index',
                intersect: false
            }
        }
    });
}
@endforeach
@endforeach
});
</script>

@endsection
