@extends("layout")

@section('title', $measure->name)

@section("content")
<div class="card">
    <div class="card-header">@include('partials.help', ['page' => 'measures.plan'])<i class="bi bi-clipboard-check"></i> {{ trans('cruds.measure.plan') }}</div>
    <div class="card-body">

    @include('partials.errors')

    <form method="POST" action="/bob/plan">
    @csrf

    <input type="hidden" name="id" value="{{ $measure->id }}"/>

    <div class="container-fluid px-0">
    	<div class="row">
    		<div class="col-lg-1 col-md-2">
        		<strong>{{ trans("cruds.measure.fields.clauses") }}</strong>
        	</div>
    		<div class="col-lg-11 col-md-10">
                @foreach($measure->controls as $control)
                    <a href="/alice/show/{{ $control->id }}">{{ $control->clause }}</a> - {{ $control->name }}
                    @if(!$loop->last)
                    <br>
                    @endif
                @endforeach
            </div>
        </div>

    	<div class="row">
    		<div class="col-lg-1 col-md-2">
        		<strong>{{ trans('cruds.measure.fields.name') }}</strong>
        	</div>
    		<div class="col-lg-4 col-md-5">
        		{{ $measure->name }}
    		</div>
            @if ($measure->scope!==null)
    		<div class="col-lg-1 col-md-2 text-md-end">
        		<strong>{{ trans("cruds.measure.fields.scope") }}</strong>
        	</div>
    		<div class="col-lg-1 col-md-2">
                <a href="/bob/index?scope={{ $measure->scope }}">
    			{{ $measure->scope }}
                </a>
    		</div>
            @endif
    	</div>
    	<div class="row">
    		<div class="col-lg-1 col-md-2">
        		<strong>{{ trans('cruds.measure.fields.objective') }}</strong>
        	</div>
            <div class="col-lg-11 col-md-10">
                {!! \Parsedown::instance()->text($measure->objective) !!}
    		</div>
    	</div>

    	<div class="row">
    		<div class="col-lg-1 col-md-2">
    			<strong>{{ trans('cruds.measure.fields.plan_date') }}</strong>
        	</div>
    		<div class="col-lg-2 col-md-3">
    			<input class="form-control" type="date" name="plan_date" value="{{
    			\Carbon\Carbon
    			::createFromFormat('Y-m-d',$measure->plan_date)
    			->format('Y-m-d')
    			}}"
                />
    		</div>
    	</div>

    	<div class="row">
    		<div class="col-lg-1 col-md-2">
        		<strong>{{ trans('cruds.measure.fields.periodicity') }}</strong>
        	</div>
    		<div class="col-lg-2 col-md-3">
    			<select class="form-select select2" name="periodicity">
    			    <option value="0" {{ $measure->periodicity==0 ? "selected" : ""}}>{{ trans('common.once') }}</option>
    			    <option value="-1" {{ $measure->periodicity==-1 ? "selected" : ""}}>{{ trans('common.weekly') }}</option>
    			    <option value="1" {{ $measure->periodicity==1 ? "selected" : ""}}>{{ trans('common.monthly') }}</option>
    			    <option value="3" {{ $measure->periodicity==3 ? "selected" : ""}}>{{ trans('common.quarterly') }}</option>
    			    <option value="6" {{ $measure->periodicity==6 ? "selected" : ""}}>{{ trans('common.biannually') }}</option>
    			    <option value="12" {{ $measure->periodicity==12 ? "selected" : ""}}>{{ trans('common.annually') }}</option>
    			 </select>
    		</div>
    	</div>

    	<div class="row">
    		<div class="col-lg-1 col-md-2">
    			<strong>{{ trans('cruds.measure.fields.owners') }}</strong>
        	</div>
            <div class="col-lg-11 col-md-10">
                <select class="form-select select2" name="owners[]" id="owners" multiple>
                    @foreach($owners as $id => $name)
                        <option
                            value="{{ $id }}"
                            {{ (in_array($id, old('owners', []))) ||
                                (
                                    (str_starts_with($id,'USR_') && $measure->users->contains(intval(substr($id, 4)))) ||
                                    (str_starts_with($id,'GRP_') && $measure->groups->contains(intval(substr($id, 4))))
                                )
                                ? 'selected' : '' }}>
                        {{ $name }}
                        </option>
                    @endforeach
                </select>
    		</div>
    	</div>

    	<div class="row">
    		<div class="col-12">
    		@if (Auth::User()->role !== 3)
    			<button type="submit" class="btn btn-success">
    				<span class="bi bi-calendar3"></span> {{ trans("common.plan") }}
    			</button>
    			</form>
    			&nbsp;
    		@endif
    		@if (Auth::User()->role !== 3)
                <form action="/bob/unplan" method="POST" data-confirm="{{ trans('common.confirm') }}" class="d-inline">
    				@csrf
    				<input type="hidden" name="id" value="{{ $measure->id }}"/>
    	            <button class="btn btn-danger" type="submit">
    					<span class="bi bi-trash"></span> {{ trans("common.unplan") }}
    				</button>
    			</form>
    			&nbsp;
    		@endif
            <a href="/bob/show/{{$measure->id}}" class="btn btn-light border">
				<span class="bi bi-x-circle"></span> {{ trans("common.cancel") }}
            </a>
    		</div>
    	</div>
    </div>
    </form>
</div>
</div>
@endsection
