@extends("layout")

@section("content")
<div class="card">
    <div class="card-header">@include('partials.help', ['page' => 'radar.attributes'])<i class="bi bi-pie-chart"></i> {{ trans('cruds.measure.radar') }}</div>
    <div class="card-body">

<div class="container-fluid px-0">
    <div class="row">
        <div class="col-12">

    @foreach($attributes as $attribute)

    <div class="row">
        <div class="col-md-12 col-lg-10">
        </div>


    </div>
    <div class="row">
        <div class="col-md-12 col-lg-6">
            <table class="table table-bordered">
                  <thead>
                  <tr>
                    <th width="80%">{{ $attribute->name }}</th>
                    <th width="20%">{{ trans("cruds.measure.fields.note") }}</th>
                  </tr>
                  </thead>
                  <tbody>
                  @foreach(explode(" ",$attribute->values) as $value)
                    @if(strlen($value)>0)
                        <?php $score1=0; $score2=0; $score3=0; ?>
                        @foreach($controls as $control)
                            @if (str_contains($control->attributes.' ', $value.' '))
                                @if ($control->score==1)
                                    <?php $score1++; ?>
                                @elseif ($control->score==2)
                                    <?php $score2++; ?>
                                @elseif ($control->score==3)
                                    <?php $score3++; ?>
                                @endif
                            @endif
                        @endforeach
                    <tr>
                        <td><a href="/bob/index?domain=0&period=99&status=1&attribute={{ urlencode($value) }}">{{ $value }}</a></td>
                        <td>
                            <font color="30FF30"><?php echo $score3; ?></font> -
                            <font color="ff5733"><?php echo $score2; ?></font> -
                            <font color="FF1010"><?php echo $score1; ?></font>
                        </td>
                    </tr>
                    @endif
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="col-md-12 col-lg-6">
            <canvas id="canvas-radar-{{ $attribute->id }}" width="100" height="100"></canvas>
        </div>
    </div>
    @endforeach
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

@foreach($attributes as $attribute)

const ctx_{{ $attribute->id }} = document.getElementById('canvas-radar-{{ $attribute->id }}').getContext('2d');

const marksData_{{ $attribute->id }} = {
    labels: [
        @foreach(explode(" ", $attribute->values) as $value)
            @if(strlen($value) > 0)
                "{{ $value }}"{{ $loop->last ? '' : ',' }}
            @endif
        @endforeach
    ],
    datasets: [
        {
            backgroundColor: 'rgba(0,123,255,0.9)', // blue
            borderColor: 'rgba(0,123,255,1)',
            pointBackgroundColor: 'rgba(0,123,255,1)',
            data: [
                @foreach(explode(" ", $attribute->values) as $value)
                    @if(strlen($value) > 0)
                        <?php $score1 = 0; $score2 = 0; $score3 = 0; $total = 0; ?>
                        @foreach($controls as $control)
                            @if (str_contains($control->attributes.' ', $value.' '))
                                @if ($control->score == 1)
                                    <?php $score1++; ?>
                                @elseif ($control->score == 2)
                                    <?php $score2++; ?>
                                @elseif ($control->score == 3)
                                    <?php $score3++; ?>
                                @endif
                                <?php $total++; ?>
                            @endif
                        @endforeach
                        {{ $total == 0 ? 0 : 2.5 * $score3 / $total }}
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
                @foreach (explode(" ", $attribute->values) as $value)
                    @if (strlen($value) > 0)
                        2{{ $loop->last ? '' : ',' }}
                    @endif
                @endforeach
            ]
        },
        {
            backgroundColor: 'rgba(255,165,0,0.3)', // orange
            borderColor: 'rgba(255,165,0,1)',
            pointBackgroundColor: 'rgba(255,165,0,1)',
            data: [
                @foreach (explode(" ", $attribute->values) as $value)
                    @if (strlen($value) > 0)
                        2.5{{ $loop->last ? '' : ',' }}
                    @endif
                @endforeach
            ]
        },
        {
            backgroundColor: 'rgba(0,128,0,0.3)', // green
            borderColor: 'rgba(0,128,0,1)',
            pointBackgroundColor: 'rgba(0,128,0,1)',
            data: [
                @foreach (explode(" ", $attribute->values) as $value)
                    @if (strlen($value) > 0)
                        3{{ $loop->last ? '' : ',' }}
                    @endif
                @endforeach
            ]
        },
        {
            backgroundColor: 'rgba(0,0,0,1)', // black
            data: [
                @foreach (explode(" ", $attribute->values) as $value)
                    @if (strlen($value) > 0)
                        0{{ $loop->last ? '' : ',' }}
                    @endif
                @endforeach
            ]
        }
    ]
};

const radarChart_{{ $attribute->id }} = new Chart(ctx_{{ $attribute->id }}, {
    type: 'radar',
    data: marksData_{{ $attribute->id }},
    options: options
});

@endforeach
});
</script>

@endsection
