@extends("layout")

@section("content")
<div class="card">
    <div class="card-header">@include('partials.help', ['page' => 'actions.index'])<i class="bi bi-list-check"></i> {{ trans('cruds.action.index') }}</div>
    <div class="card-body">

<div class="container-fluid px-0">
    <div class="row">
        <div class="col-md-4 col-lg-2">
            <select class="form-select select2" id='type' name="type">
                <option value="">-- {{ trans("cruds.action.fields.choose_type")}} --</option>
                <option value="1" {{ (Session::get('type')==1) ? 'selected' : '' }}>{{ trans('cruds.action.types.major') }}</option>
                <option value="2" {{ (Session::get('type')==2) ? 'selected' : '' }}>{{ trans('cruds.action.types.minor') }}</option>
                <option value="3" {{ (Session::get('type')==3) ? 'selected' : '' }}>{{ trans('cruds.action.types.observation') }}</option>
                <option value="4" {{ (Session::get('type')==4) ? 'selected' : '' }}>{{ trans('cruds.action.types.opportunity') }}</option>
            </select>
        </div>
        <div class="col-md-10 col-lg-5 mt-2">
             <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="status" value="0" id="status0" {{ (Session::get("status")=="0") ? 'checked' : '' }}/><label class="form-check-label" for="status0">{{ trans("cruds.action.fields.status_open") }}</label></div>
             <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="status" value="1" id="status1" {{ (Session::get("status")=="1") ? 'checked' : '' }}/><label class="form-check-label" for="status1">{{ trans("cruds.action.fields.status_closed") }}</label></div>
             <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="status" value="2" id="status2" {{ (Session::get("status")=="2") ? 'checked' : '' }}/><label class="form-check-label" for="status2">{{ trans("cruds.action.fields.status_all") }}</label></div>
        </div>
        <div class="col-md-4 col-lg-2">
            <select class="form-select select2" id='scope' name="scope">
                <option value="">-- {{ trans("cruds.action.fields.choose_scope")}} --</option>
                @foreach ($scopes as $scope)
                    <option {{ (Session::get('scope')==$scope) ? 'selected' : '' }}>
                        {{ $scope }}
                    </option>
                @endforeach
            </select>
        </div>
		<div class="col-auto ms-auto">
		@if ((Auth::User()->role==1)||(Auth::User()->role==2))
            <a class="btn btn-primary" href='/action/create'>
	            <span class="bi bi-plus-lg"></span> {{ trans('common.new') }}
            </a>
        @endif
		</div>
    </div>
</div>

<script>
    window.addEventListener('load', function(){

        var select = document.getElementById('type');
        select.addEventListener('change', function(){
            window.location = '/actions?type=' + this.value;
        }, false);

        var select = document.getElementById('scope');
        select.addEventListener('change', function(){
            window.location = '/actions?scope=' + this.value;
        }, false);

        select = document.getElementById('status0');
        select.addEventListener('change', function(){
            window.location = '/actions?status=0';
        }, false);

        select = document.getElementById('status1');
        select.addEventListener('change', function(){
            window.location = '/actions?status=1';
        }, false);

        select = document.getElementById('status2');
        select.addEventListener('change', function(){
            window.location = '/actions?status=2';
        }, false);
    }, false);

</script>

    <div class="row mt-3">

        <table data-order="[[0,&quot;asc&quot;]]" data-page-length="100" id="actions" class="table table-striped table-hover table-bordered datatable">
	    <thead>
	    <tr>
            <th>{{ trans('cruds.action.fields.reference') }}</th>
            <th>{{ trans('cruds.action.fields.type') }}</th>
            <th class="dt-hidden">{{ trans('cruds.action.fields.clauses') }}</th>
            <th>{{ trans('cruds.action.fields.risks') }}</th>
            <th>{{ trans('cruds.action.fields.status') }}</th>
            <th>{{ trans('cruds.action.fields.progress') }}</th>
            <th>{{ trans('cruds.action.fields.name') }}</th>
            <th class="dt-hidden">{{ trans('cruds.action.fields.remediation') }}</th>
			<th>{{ trans('cruds.action.fields.scope') }}</th>
            <th>{{ trans('cruds.action.fields.owners') }}</th>
            <th>{{ trans('cruds.action.fields.due_date') }}</th>
	    </tr>
	    </thead>
	    <tbody>
	@foreach($actions as $action)
        <tr data-href="/action/show/{{ $action->id }}">
            <td>
                <b id="{{ $action->reference }}" style="font-size: 0.8em;">
                    {{ $action->reference==null ? ("ACT-".$action->id) : $action->reference }}
                </b>
            </td>
            <td>
                @if ($action->type==1)
                    <div class="text-danger fw-bold" style="font-size: 0.8em;" title="{{ trans('cruds.action.types.major') }}">{{ trans('cruds.action.types_short.major') }}</div>
                @elseif ($action->type==2)
                    <div class="text-orange fw-bold" style="font-size: 0.8em;" title="{{ trans('cruds.action.types.minor') }}">{{ trans('cruds.action.types_short.minor') }}</div>
                @elseif ($action->type==3)
                    <div class="text-warning fw-bold" style="font-size: 0.8em;" title="{{ trans('cruds.action.types.observation') }}">{{ trans('cruds.action.types_short.observation') }}</div>
                @elseif ($action->type==4)
                    <div class="text-success fw-bold" style="font-size: 0.8em;" title="{{ trans('cruds.action.types.opportunity') }}">{{ trans('cruds.action.types_short.opportunity') }}</div>
                @endif
            </td>
            <td>
                @foreach($action->controls as $control)
                    <a href="/alice/show/{{ $control->id }}">{{ $control->clause }}</a>{{ $loop->last ? '' : ',' }}
                @endforeach
            </td>
            <td>
                @foreach($action->risks as $risk)
                    <a href="/risk/show/{{ $risk->id }}">{{ $risk->name }}</a>{{ $loop->last ? '' : ',' }}
                @endforeach
            </td>
            <td id="{{ $action->status }}">
                @if ($action->status==0)
                {{ trans('cruds.action.fields.status_open') }}
                @elseif ($action->status==1)
                {{ trans('cruds.action.fields.status_closed') }}
                @elseif ($action->status==2)
                {{ trans('cruds.action.fields.status_rejected') }}
                @else
                {{ $action->status }}
                @endif
            </td>
            <td style="min-width: 130px" data-order="{{ (int) $action->progress }}">
                <div class="d-flex align-items-center gap-2">
                    <div class="progress flex-grow-1" role="progressbar"
                         aria-label="{{ trans('cruds.action.fields.progress') }}"
                         aria-valuenow="{{ (int) $action->progress }}" aria-valuemin="0" aria-valuemax="100">
                        <div class="progress-bar {{ $action->progress < 25 ? 'bg-danger' : ($action->progress < 50 ? 'bg-warning' : 'bg-success') }}"
                             style="width: {{ (int) $action->progress }}%"></div>
                    </div>
                    <small class="text-nowrap">{{ (int) $action->progress }}%</small>
                </div>
            </td>
            <td class="align-top text-start">
                <b>{{ $action->name }}</b>
                {!! \Parsedown::instance()->text($action->cause) !!}
            </td>
            <td class="align-top text-start">
                {!! \Parsedown::instance()->text($action->remediation) !!}
            </td>
            <td>
                {{ $action->scope }}
            </td>
            <td>
                {{ $action->owners->implode(', ') }}
            </td>
            <td>
            @if ($action->due_date!==null)
                <b>
                @if (today()->lte($action->due_date))
                    <font color="green">{{ $action->due_date }}</font>
                @else
                    <font color="red">{{ $action->due_date }}</font>
                @endif
                </b>
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
