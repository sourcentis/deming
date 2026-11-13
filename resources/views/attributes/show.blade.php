@extends("layout")

@section("content")
<div class="card">
    <div class="card-header">@include('partials.help', ['page' => 'attributes'])<i class="bi bi-tags"></i> {{ trans('cruds.attribute.show') }}</div>
    <div class="card-body">
	<div class="container-fluid px-0">
    	<div class="row">
    		<div class="col-md-2 col-lg-1">
	    		<strong>{{ trans('cruds.attribute.fields.name') }}</strong>
	    	</div>
            <div class="col-md-12 col-lg-11">
	    		{{ $attribute->name }}
    		</div>
	    </div>

    	<div class="row">
    		<div class="col-md-2 col-lg-1">
	    		<strong>{{ trans('cruds.attribute.fields.values') }}</strong>
	    	</div>
    		<div class="col-md-12 col-lg-11">
	    		{{ $attribute->values }}
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
    	    <form action="/attributes/{{ $attribute->id }}/edit">
    	    	<button class="btn btn-primary" type="submit">
    	            <span class="bi bi-pencil"></span> {{ trans('common.edit') }}
    	    	</button>
    	    </form>
            &nbsp;
    		<form action="/attributes/{{ $attribute->id }}" method="post" data-confirm="{{ trans('common.confirm') }}">
               {{ method_field('delete') }}
               @csrf
    	        <button class="btn btn-danger" type="submit">
    				<span class="bi bi-trash"></span> {{ trans('common.delete') }}
    		    </button>
            </form>
    		&nbsp;
    		@endif
    		<form action="/attributes">
    	    	<button class="btn btn-light border" type="submit">
    				<span class="bi bi-x-circle"></span> {{ trans('common.cancel') }}
    	    	</button>
    		</form>
        </div>
	</div>
</div>
</div>
@endsection
