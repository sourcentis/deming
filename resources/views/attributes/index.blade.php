@extends("layout")

@section("content")
<div class="card">
    <div class="card-header">@include('partials.help', ['page' => 'attributes'])<i class="bi bi-tags"></i> {{ trans('cruds.attribute.index') }}</div>
    <div class="card-body">

    @include('partials.errors')

	<div class="container-fluid px-0">
		<div class="row">
			<div class="col-md-10 col-lg-5">
			</div>
			@if (Auth::User()->role==1)
			<div class="col-md-12 col-lg-7 text-md-end">
				<button class="btn btn-warning" data-href="/attribute/replace">
		            <span class="bi bi-pencil-square"></span>
					{{ trans('common.manage') }}
				</button>
				&nbsp;
				<button class="btn btn-primary" data-href="/attributes/create">
		            <span class="bi bi-plus-lg"></span> {{ trans('common.new') }}
				</button>
			</div>
			@endif
		</div>

		<div class="row">
			<div class="col-12">

		<table data-order="[]" data-searching="false" data-page-length="10"
			id="attributes"
			class="table table-striped table-hover table-bordered datatable">
		    <thead>
		    <tr>
				<th>{{ trans('cruds.attribute.fields.name') }}</th>
				<th>{{ trans('cruds.attribute.fields.values') }}</th>
		    </tr>
		    </thead>
		    <tbody>
		@foreach($attributes as $attribute)
			<tr>
				<td><a href="/attributes/{{ $attribute->id}}">{{ $attribute->name }}</a></td>
				<td>
					{{ strlen($attribute->values)>120 ? substr($attribute->values,0,120) . "..." : $attribute->values}}
				</td>
			</tr>
		@endforeach
			</tbody>
		</table>
		<br>
	</div>
</div>
</div>
</div>
@endsection
