@extends("layout")

@section("content")
<div class="card">
    <div class="card-header">@include('partials.help', ['page' => 'radar.controls'])<i class="bi bi-pie-chart"></i> {{ trans('cruds.measure.radar') }}</div>
    <div class="card-body">

<div class="container-fluid px-0">
    <div class="row">
        <div class="col-12">

    <form action="/radar/alice">
        <div class="row">
            <div class="col-md-12 col-lg-10">
            </div>
            <div class="col-md-4 col-lg-2">
                {{ trans("cruds.measure.fields.scope") }}
                <select class="form-select select2" name="scope" id="scope">
                    @foreach ($scopes as $scope)
                    <option
                        @if (Session::get("scope")==$scope)
                            selected
                        @endif >
                        {{ $scope }}
                    </option>
                    @endforeach
                </select>
            </div>
        </div>
    </form>

    @foreach($domains as $domain)

    <div class="row">
        <div class="col-md-12 col-lg-10">
            <b>{{ $domain->title }} - {{ $domain->description }}</b>
        </div>
    </div>
    <div class="row">
        <div class="col-md-8 col-lg-4">
            <canvas id="canvas-radar-{{ $domain->id }}" width="100%"></canvas>
        </div>
        <div class="col-md-12 col-lg-8">
            <table class="table table-bordered">
                  <thead>
                  <tr>
                    <th>{{ trans("cruds.measure.fields.note") }}</th>
                    <th><center>#</center></th>
                    <th>{{ trans("cruds.measure.fields.name") }}</th>
                    <th>{{ trans("cruds.measure.fields.scope") }}</th>
                    <th>{{ trans("cruds.measure.fields.realisation_date") }}</th>
                    <th>{{ trans("cruds.measure.fields.next") }}</th>
                  </tr>
                  </thead>
                  <tbody>
            @foreach($measures as $measure)
                @if ($measure->domain_id == $domain->id)
                    <tr>
                        <td><center>
                    @if ($measure->score==1)
                        &#128545;
                    @elseif ($measure->score==2)
                        &#128528;
                    @elseif ($measure->score==3)
                        <span style="filter: sepia(1) saturate(5) hue-rotate(80deg)">&#128512;</span>
                    @else
                        &#9675;
                    @endif
                        </center></td>

                    <td><a href="/alice/show/{{ $measure->measure_id }}">{{ $measure->clause }}</a></td>
                    <td>{{ $measure->name }}</td>
                    <td>{{ $measure->scope }}</td>
                    <td><a href="/bob/show/{{ $measure->control_id }}">{{ $measure->realisation_date }}</a></td>
                    <td><a href="/bob/show/{{ $measure->next_id }}">{{ $measure->next_date }}</a></td>
                    </tr>
                @endif
            @endforeach
        </tbody>
            </table>
        </div>
    </div>
    @endforeach
</div>

</div>
</div>
</div>
</div>
</div>

<script @nonce>
document.addEventListener("DOMContentLoaded", function () {

const options = {
    responsive: true,
    plugins: {
        legend: {
            display: false,
        },
        title: {
            display: false
        },
        datalabels: {
            display: false
        }
    }
};

@foreach($domains as $domain)

const ctx_{{ $domain->id }} = document.getElementById('canvas-radar-{{ $domain->id }}').getContext('2d');

const marksData_{{ $domain->id }} = {
    labels: [
        @foreach ($measures as $m)
            @if ($m->domain_id == $domain->id)
                '{{ $m->clause }}'{{ $loop->last ? '' : ',' }}
            @endif
        @endforeach
    ],
    datasets: [
        {
            backgroundColor: 'rgba(0,123,255,0.9)', // blue
            borderColor: 'rgba(0,123,255,1)',
            pointBackgroundColor: 'rgba(0,123,255,1)',
            data: [
                @foreach ($measures as $m)
                    @if ($m->domain_id == $domain->id)
                        @if ($m->score == 1)
                            0.5
                        @elseif ($m->score == 2)
                            1.5
                        @elseif ($m->score == 3)
                            2.5
                        @else
                            0
                        @endif
                        {{ $loop->last ? '' : ',' }}
                    @endif
                @endforeach
            ]
        },
        {
            backgroundColor: 'rgba(255,0,0,0.3)', // red
            borderColor: 'rgba(255,0,0,1)',
            pointBackgroundColor: 'rgba(255,0,0,1)',
            data: [
                @foreach ($measures as $m)
                    @if ($m->domain_id == $domain->id)
                        1
                        {{ $loop->last ? '' : ',' }}
                    @endif
                @endforeach
            ]
        },
        {
            backgroundColor: 'rgba(255,165,0,0.3)', // orange
            borderColor: 'rgba(255,165,0,1)',
            pointBackgroundColor: 'rgba(255,165,0,1)',
            data: [
                @foreach ($measures as $m)
                    @if ($m->domain_id == $domain->id)
                        2
                        {{ $loop->last ? '' : ',' }}
                    @endif
                @endforeach
            ]
        },
        {
            backgroundColor: 'rgba(0,128,0,0.3)', // green
//            borderColor: 'rgba(128,128,128,1)',
//            pointBackgroundColor: 'rgba(0,128,0,1)',
            data: [
                @foreach ($measures as $m)
                    @if ($m->domain_id == $domain->id)
                        3
                        {{ $loop->last ? '' : ',' }}
                    @endif
                @endforeach
            ]
        },
        {
            backgroundColor: 'rgba(0,0,0,1)', // black
            data: [0, 0, 0, 0]
        }
    ]
};

const radarChart_{{ $domain->id }} = new Chart(ctx_{{ $domain->id }}, {
    type: 'radar',
    data: marksData_{{ $domain->id }},
    options: options
});

@endforeach

    window.addEventListener('load', function() {
        var select = document.getElementById('scope');
        select.addEventListener('change', function() {
            window.location = '/radar/alice?scope=' + this.value;
        }, false);
    });
});
</script>

@endsection
