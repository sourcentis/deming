@extends("layout")

@section('title', $control->name)

@section("content")
<div data-role="panel" data-title-caption="{{ trans('cruds.control.show') }}" data-collapsible="false" data-title-icon="<span class='mif-books'></span>">

    @include('partials.errors')

    <div class="grid">
    	<div class="row">
    		<div class="cell-lg-1 cell-md-2">
        		<strong>{{ trans('cruds.control.fields.domain') }}</strong>
        	</div>
            <div class="cell-lg-7 cell-md-9">
    			<a href="/domains/{{$control->domain_id}}">
    			{{ $control->domain->title ?? ""}}
    			</a>
    			-
    			{{ $control->domain->description ?? "" }}
    		</div>
        </div>

    	<div class="row">
    		<div class="cell-lg-1 cell-md-2">
        		<strong>{{ trans('cruds.control.fields.clause') }}</strong> - <strong>{{ trans('cruds.control.fields.name') }}</strong>
        	</div>
            <div class="cell-lg-7 cell-md-9">
    			{{ $control->clause }} - {{ $control->name }}
    		</div>
        </div>

    	<div class="row">
    		<div class="cell-lg-1 cell-md-2">
        		<strong>{{ trans('cruds.control.fields.objective') }}</strong>
        	</div>
            <div class="cell-lg-7 cell-md-9">
                {!! \Parsedown::instance()->text($control->objective) !!}
    		</div>
        </div>

    	@if ($control->attributes!=null)
    	<div class="row">
    		<div class="cell-lg-1 cell-md-2">
        		<strong>{{ trans("cruds.control.fields.attributes") }}</strong>
        	</div>
            <div class="cell-lg-7 cell-md-9">
        		{{ $control->attributes }}
    		</div>
    	</div>
    	@endif

    	<div class="row">
    		<div class="cell-lg-1 cell-md-2">
        		<strong>{{ trans('cruds.control.fields.input') }}</strong>
        	</div>
            <div class="cell-lg-7 cell-md-9">
                {!! \Parsedown::instance()->text($control->input) !!}
    		</div>
        </div>

    	<div class="row">
    		<div class="cell-lg-1 cell-md-2">
        		<strong>{{ trans('cruds.control.fields.model') }}</strong>
        	</div>
            <div class="cell-lg-7 cell-md-9">
    			<pre>{{ $control->model }}</pre>
    		</div>
        </div>


    	<div class="row">
    		<div class="cell-lg-1 cell-md-2">
        		<strong>{{ trans('cruds.control.fields.indicator') }}</strong>
        	</div>
    		<div class="cell-lg-6 cell-md-9">
    			<pre>{{ $control->indicator }}</pre>
    		</div>
        </div>

    	<div class="row">
    		<div class="cell-lg-1 cell-md-2">
        		<strong>{{ trans('cruds.control.fields.action_plan') }}</strong>
        	</div>
    		<div class="cell-lg-6 cell-md-9">
                {!! \Parsedown::instance()->text($control->action_plan) !!}
    		</div>
        </div>

    	<div class="row">
    		<div class="cell-lg-1 cell-md-2">
    		</div>
    	</div>

    	<div class="row">
            <div class="cell-lg-8 cell-md-12" >
    		@if (Auth::User()->role === 1)
                <a class="button info" href="/alice/plan/{{ $control->id }}">
    	            <span class="mif-calendar"></span>
    	            &nbsp;
    		    	{{ trans('common.plan') }}
                </a>
    	    &nbsp;
                <a class="button primary" href="/alice/{{ $control->id }}/edit">
    	            <span class="mif-pencil"></span>
    	            &nbsp;
    		    	{{ trans('common.edit') }}
                </a>
    	    &nbsp;
                <a class="button warning" href="/alice/clone/{{ $control->id }}">
    	            <span class="mif-add-lib"></span>
    	            &nbsp;
    		    	{{ trans('common.clone') }}
                </a>
    	    &nbsp;
    		<form action="/alice/delete/{{ $control->id }}" class="d-inline" method="POST" onSubmit="if(!confirm('{{ trans('common.confirm') }}')){return false;}">
                @csrf
    			<button class="button alert" type="submit">
    				<span class="mif-fire"></span>
    				&nbsp;
    			    {{ trans('common.delete') }}
    			</button>
            </form>
    	    &nbsp;
    	    @endif
    		@if (Auth::User()->role === 5)
                <a href="/bob/index" class="button">
        			<span class="mif-cancel"></span>
        			&nbsp;
        	    	{{ trans('common.cancel') }}
                </a>
                @else
                <a href="/alice/index" class="button">
        			<span class="mif-cancel"></span>
        			&nbsp;
        	    	{{ trans('common.cancel') }}
                </a>
                @endif
            </div>
        </div>
    </div>
</div>
<div>
    <br>
</div>
<div data-role="panel" data-title-caption="{{ trans('cruds.measure.title') }}" data-collapsible="false" data-title-icon="<span class='mif-paste'></span>">

	<div>
		<table id="controls" class="table striped row-hover cell-border"
				data-role="table"
				data-show-search="false"
				data-show-pagination="false"
				data-show-rows-steps="false"
				>
			<thead>
				<tr class="row-hover">
					<th class="sortable-column sort-asc" width="65%">{{ trans("cruds.welcome.controls") }}</th>
					<th class="sortable-column sort-asc" width="65%">{{ trans("cruds.measure.fields.scope") }}</th>
					<th class="sortable-column sort-asc" width="5%">{{ trans("cruds.measure.fields.score") }}</th>
					<th class="sortable-column sort-asc" width="15%">{{ trans("cruds.measure.fields.plan_date") }}</th>
					<th class="sortable-column sort-asc" width="15%">{{ trans("cruds.measure.fields.realisation_date") }}</th>
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
@if($control->outgoingMappings->isNotEmpty() || $control->incomingMappings->isNotEmpty())
<div>
    <br>
</div>
<div data-role="panel"
     data-title-caption="{{ trans('cruds.crosswalk.control_mappings') }}"
     data-collapsible="false"
     data-title-icon="<span class='mif-shuffle'></span>">
    <div class="overflow-auto">
        <table id="control-crosswalk" class="table striped row-hover cell-border"
               data-role="table" data-show-search="false" data-show-pagination="false">
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
@endif
@endsection
