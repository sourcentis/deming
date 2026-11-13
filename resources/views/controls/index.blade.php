@extends("layout")

@section("content")
<div class="card">
    <div class="card-header">@include('partials.help', ['page' => 'controls.index'])<i class="bi bi-book"></i> {{ trans('cruds.control.index') }}</div>
    <div class="card-body">
		<div class="container-fluid px-0">
            <div class="row mb-3">
				<div class="col-md-10 col-lg-5">
					<select class="form-select select2" id='domain_id' name="domain_id">
					    <option value="0">-- {{ trans('cruds.domain.choose') }} --</option>
						@foreach ($domains as $domain)
					    	<option value="{{ $domain->id }}"
								@if (((int)Session::get("domain"))==$domain->id)
									selected
								@endif >
					    		{{ $domain->title }}
					    	</option>
					    @endforeach
					</select>
				</div>
				<div class="col-md-12 col-lg-7 text-md-end">
                    <a class="btn btn-primary" href="/alice/create">
			            <span class="bi bi-plus-lg"></span> {{ trans('common.new') }}
                    </a>
				</div>
			</div>

		<script @nonce>
			window.addEventListener('load', function(){
		    var select = document.getElementById('domain_id');

		    select.addEventListener('change', function(){
		        window.location = '/alice/index?domain=' + this.value;
		    }, false);
		}, false);
		</script>

		<div class="row">
			<div class="col">
            <table data-order="[[0,&quot;asc&quot;]]"
                id="measures"
                class="table table-striped table-hover table-bordered datatable">
		   <thead>
			    <tr>
					<th>{{ trans('cruds.control.fields.domain') }}</th>
					<th>{{ trans('cruds.control.fields.clause') }}</th>
					<th>{{ trans('cruds.control.fields.name') }}</th>
					<th class="dt-hidden">{{ trans('cruds.control.fields.attributes') }}</th>
					<th class="dt-center"># {{ trans('cruds.measure.title') }}</th>
					<th></th>
			    </tr>
		    </thead>
		    <tbody>
		@foreach($controls as $control)
			<tr>
				<td>
					<a id="{{ $control->title }}" href="/domains/{{$control->domain_id}}">
						{{ $control->title }}
					</a>
				</td>
				<td>
                    <a id="{{ $control->clause }}" href="/alice/show/{{ $control->id }}">
					@if (strlen($control->clause)==0)
						None
					@else
						{{ $control->clause }}
					@endif
					</a>
				</td>
				<td>{{ $control->name }}</td>
				<td>{{ $control->attributes }}</td>
				<td class="dt-center" id="{{ $control->control_count }}">
					@if ($control->control_count>0)
                    <a href="/bob/index?period=99&domain=0&scope=none&status=2&clause={{ htmlentities($control->clause) }}">
						{{ $control->control_count }}
					</a>
					@else
					0
					@endif
				</td>
				<td>
				@if (!Auth::User()->isAuditee())
                    <a href="/alice/plan/{{ $control->id }}" class="btn btn-info btn-sm">
			            <span class="bi bi-calendar3"></span> {{ trans('common.plan') }}
                    </a>
                @endif
				</td>
			</tr>
		@endforeach
		</tbody>
		</table>
	</div>
</div>
</div>
</div>
@endsection
