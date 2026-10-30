@extends("layout")

@section('title', $control->name)

@section("content")
<div class="card">
    <div class="card-header">@include('partials.help', ['page' => 'controls.show'])<i class="bi bi-book"></i> {{ trans('cruds.control.show') }}</div>
    <div class="card-body">
    @include('partials.ai-button', ['type' => 'control', 'object' => $control])

    @include('partials.errors')

    <div class="container-fluid px-0">
    	<div class="row">
    		<div class="col-lg-1 col-md-2">
        		<strong>{{ trans('cruds.control.fields.domain') }}</strong>
        	</div>
            <div class="col-lg-11 col-md-10">
    			<a href="/domains/{{$control->domain_id}}">
    			{{ $control->domain->title ?? ""}}
    			</a>
    			-
    			{{ $control->domain->description ?? "" }}
    		</div>
        </div>

    	<div class="row">
    		<div class="col-lg-1 col-md-2">
        		<strong>{{ trans('cruds.control.fields.clause') }}</strong> - <strong>{{ trans('cruds.control.fields.name') }}</strong>
        	</div>
            <div class="col-lg-11 col-md-10">
    			{{ $control->clause }} - {{ $control->name }}
    		</div>
        </div>

    	<div class="row">
    		<div class="col-lg-1 col-md-2">
        		<strong>{{ trans('cruds.control.fields.objective') }}</strong>
        	</div>
            <div class="col-lg-11 col-md-10">
                {!! \Parsedown::instance()->text($control->objective) !!}
    		</div>
        </div>

    	@if ($control->attributes!=null)
    	<div class="row">
    		<div class="col-lg-1 col-md-2">
        		<strong>{{ trans("cruds.control.fields.attributes") }}</strong>
        	</div>
            <div class="col-lg-11 col-md-10">
        		{{ $control->attributes }}
    		</div>
    	</div>
    	@endif

    	<div class="row">
    		<div class="col-lg-1 col-md-2">
        		<strong>{{ trans('cruds.control.fields.input') }}</strong>
        	</div>
            <div class="col-lg-11 col-md-10">
                {!! \Parsedown::instance()->text($control->input) !!}
    		</div>
        </div>

    	<div class="row">
    		<div class="col-lg-1 col-md-2">
        		<strong>{{ trans('cruds.control.fields.model') }}</strong>
        	</div>
            <div class="col-lg-11 col-md-10">
    			<pre>{{ $control->model }}</pre>
    		</div>
        </div>


    	<div class="row">
    		<div class="col-lg-1 col-md-2">
        		<strong>{{ trans('cruds.control.fields.indicator') }}</strong>
        	</div>
    		<div class="col-lg-11 col-md-10">
    			<pre>{{ $control->indicator }}</pre>
    		</div>
        </div>

    	<div class="row">
    		<div class="col-lg-1 col-md-2">
        		<strong>{{ trans('cruds.control.fields.action_plan') }}</strong>
        	</div>
    		<div class="col-lg-11 col-md-10">
                {!! \Parsedown::instance()->text($control->action_plan) !!}
    		</div>
        </div>

    	<div class="row">
    		<div class="col-lg-1 col-md-2">
    		</div>
    	</div>

    	<div class="row">
            <div class="col-lg-8 col-md-12" >
    		@if (Auth::User()->role === 1)
                <a class="btn btn-info" href="/alice/plan/{{ $control->id }}">
    	            <span class="bi bi-calendar3"></span> {{ trans('common.plan') }}
                </a>
    	    &nbsp;
                <a class="btn btn-primary" href="/alice/{{ $control->id }}/edit">
    	            <span class="bi bi-pencil"></span> {{ trans('common.edit') }}
                </a>
    	    &nbsp;
                <a class="btn btn-warning" href="/alice/clone/{{ $control->id }}">
    	            <span class="bi bi-plus-lg"></span> {{ trans('common.clone') }}
                </a>
    	    &nbsp;
    		<form action="/alice/delete/{{ $control->id }}" class="d-inline" method="POST" onSubmit="if(!confirm('{{ trans('common.confirm') }}')){return false;}">
                @csrf
    			<button class="btn btn-danger" type="submit">
    				<span class="bi bi-trash"></span> {{ trans('common.delete') }}
    			</button>
            </form>
    	    &nbsp;
    	    @endif
    		@if (Auth::User()->role === 5)
                <a href="/bob/index" class="btn btn-light border">
        			<span class="bi bi-x-circle"></span> {{ trans('common.cancel') }}
                </a>
                @else
                <a href="/alice/index" class="btn btn-light border">
        			<span class="bi bi-x-circle"></span> {{ trans('common.cancel') }}
                </a>
                @endif
            </div>
        </div>
    </div>
</div>
</div>
<div>
    <br>
</div>
<div class="card">
    <div class="card-header"><i class="bi bi-clipboard-check"></i> {{ trans('cruds.measure.title') }}</div>
    <div class="card-body">

	<div>
		<table data-order="[[0,&quot;asc&quot;]]" data-paging="false" data-searching="false" data-info="false" data-buttons="false" id="controls" class="table table-striped table-hover table-bordered datatable"
				>
			<thead>
				<tr class="row-hover">
					<th>{{ trans("cruds.welcome.controls") }}</th>
					<th>{{ trans("cruds.measure.fields.scope") }}</th>
					<th>{{ trans("cruds.measure.fields.score") }}</th>
					<th>{{ trans("cruds.measure.fields.plan_date") }}</th>
					<th>{{ trans("cruds.measure.fields.realisation_date") }}</th>
				</tr>
			</thead>
			<tbody>
		@foreach($measures as $measure)
			<tr>
				<td>
					{{ $measure->name }}
				</td>
				<td>
					<a id="{{ $measure->scope }}" href="/bob/show/{{$measure->id}}">
						{{ $measure->scope }}
					</a>
				</td>
				<td>
					<center id="{{ $measure->score }}">
						@if ($measure->score==1)
							&#128545;
						@elseif ($measure->score==2)
							&#128528;
						@elseif ($measure->score==3)
							<span style="filter: sepia(1) saturate(5) hue-rotate(70deg)">&#128512;</span>
						@else
							&#9675; <!-- &#9899; -->
						@endif
					</center>
				</td>
				<td>
					<!-- format in red when month passed -->
					@if (($measure->status === 0)||($measure->status === 1))
					<a id="{{ $measure->plan_date }}" href="/bob/show/{{$measure->id}}">
					<b> @if (today()->lte($measure->plan_date))
							<font color="green">{{ $measure->plan_date }}</font>
						@else
							<font color="red">{{ $measure->plan_date }}</font>
						@endif
					</b>
					</a>
					@else
						{{ $measure->plan_date }}
					@endif
				</td>
				<td>
					<b id="{{ $measure->realisation_date }}">
						<a href="/bob/show/{{$measure->id}}">
							{{ $measure->realisation_date }}
						</a>
						@if ( ($measure->status===1 )&& ((Auth::User()->role===1)||(Auth::User()->role===2)))
							&nbsp;
							<a href="/bob/make/{{ $measure->id }}">&#8987;</a>
						@endif
					</b>
				</td>
			</tr>
		@endforeach
		</tbody>
		</table>
	</div>
	<div>
	</div>
</div>
</div>
@if($control->outgoingMappings->isNotEmpty() || $control->incomingMappings->isNotEmpty())
<div>
    <br>
</div>
<div class="card">
    <div class="card-header"><i class="bi bi-shuffle"></i> {{ trans('cruds.crosswalk.control_mappings') }}</div>
    <div class="card-body">
    <div class="overflow-auto">
        <table data-order="[]" data-paging="false" data-searching="false" data-info="false" data-buttons="false" id="control-crosswalk" class="table table-striped table-hover table-bordered datatable">
            <thead>
                <tr>
                    <th>{{ trans('cruds.crosswalk.corresponding_control') }}</th>
                    <th>{{ trans('cruds.crosswalk.fields.mapping_type') }}</th>
                    <th>{{ trans('cruds.crosswalk.fields.coverage') }}</th>
                    <th>{{ trans('cruds.crosswalk.stored_direction') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($control->outgoingMappings as $mapping)
                    <tr>
                        <td>
                            <strong>{{ $mapping->targetControl->domain->framework }}</strong> —
                            <a href="/alice/show/{{ $mapping->targetControl->id }}">
                                {{ trim($mapping->targetControl->clause) }} — {{ $mapping->targetControl->name }}
                            </a>
                        </td>
                        <td>
                            <a href="{{ route('crosswalk.show', $mapping) }}">
                                {{ trans('cruds.crosswalk.mapping_types.' . $mapping->mapping_type) }}
                            </a>
                        </td>
                        <td>{{ $mapping->coverage ? trans('cruds.crosswalk.coverage_levels.' . $mapping->coverage) : '–' }}</td>
                        <td>{{ trans('cruds.crosswalk.direct') }}</td>
                    </tr>
                @endforeach
                @foreach($control->incomingMappings as $mapping)
                    <tr>
                        <td>
                            <strong>{{ $mapping->sourceControl->domain->framework }}</strong> —
                            <a href="/alice/show/{{ $mapping->sourceControl->id }}">
                                {{ trim($mapping->sourceControl->clause) }} — {{ $mapping->sourceControl->name }}
                            </a>
                        </td>
                        <td>
                            <a href="{{ route('crosswalk.show', $mapping) }}">
                                {{ trans('cruds.crosswalk.mapping_types.' . \App\Models\ControlMapping::inverseMappingType($mapping->mapping_type)) }}
                            </a>
                        </td>
                        <td>{{ $mapping->coverage ? trans('cruds.crosswalk.coverage_levels.' . $mapping->coverage) : '–' }}</td>
                        <td>{{ trans('cruds.crosswalk.reversed') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
</div>
@endif
@endsection
