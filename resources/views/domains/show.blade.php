@extends("layout")

@section("content")
<div class="card">
    <div class="card-header">@include('partials.help', ['page' => 'domains'])<i class="bi bi-bookshelf"></i> {{ trans('cruds.domain.show') }}</div>
    <div class="card-body">
    @include('partials.errors')
	<div class="container-fluid px-0">
    	<div class="row">
    		<div class="col-md-2 col-lg-1">
	    		<strong>{{ trans('cruds.domain.fields.framework') }}</strong>
	    	</div>
            <div class="col-md-12 col-lg-11">
	    		{{ $domain->framework }}
    		</div>
	    </div>

    	<div class="row">
    		<div class="col-md-2 col-lg-1">
	    		<strong>{{ trans('cruds.domain.fields.name') }}</strong>
	    	</div>
            <div class="col-md-12 col-lg-11">
	    		{{ $domain->title }}
    		</div>
	    </div>

    	<div class="row">
    		<div class="col-md-2 col-lg-1">
	    		<strong>{{ trans('cruds.domain.fields.description') }}</strong>
	    	</div>
            <div class="col-md-12 col-lg-11">
	    		{{ $domain->description }}
    		</div>
    	</div>
    </div>

	<div class="row">
		<div class="col-md-2 col-lg-1">
		</div>
	</div>

	<div class="row">
        <div class="col-md-12 col-lg-6">
    			@if (Auth::User()->role==1)
                <a href="/domains/{{ $domain->id }}/edit" class="btn btn-primary">
    		            <span class="bi bi-pencil-square"></span> {{ trans('common.edit') }}
                </a>
    			<form action="/domains/{{ $domain->id }}" class="d-inline" method="post" data-confirm="{{ trans('common.confirm') }}">
    	           {{ method_field('delete') }}
    	           @csrf
    		        <button class="btn btn-danger" type="submit">
    					<span class="bi bi-trash"></span> {{ trans('common.delete') }}
    		    	</button>
            	</form>
    			@endif
                <a href="/domains" class="btn btn-light border">
					<span class="bi bi-x-circle"></span> {{ trans('common.cancel') }}
                </a>
    	</div>
    </div>
</div>
</div>
@endsection
