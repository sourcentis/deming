@extends("layout")

@section("content")
<div data-role="panel" data-title-caption="{{ trans('cruds.action.index') }}" data-collapsible="false" data-title-icon="<span class='mif-pending-actions'></span>">

<div class="grid">
    <div class="row">
        <div class="cell-2">
            <select id='type' name="type" data-role="select">
                <option value="">-- {{ trans("cruds.action.fields.choose_type")}} --</option>
                <option value="1" {{ (Session::get('type')==1) ? 'selected' : '' }}>{{ trans('cruds.action.types.major') }}</option>
                <option value="2" {{ (Session::get('type')==2) ? 'selected' : '' }}>{{ trans('cruds.action.types.minor') }}</option>
                <option value="3" {{ (Session::get('type')==3) ? 'selected' : '' }}>{{ trans('cruds.action.types.observation') }}</option>
                <option value="4" {{ (Session::get('type')==4) ? 'selected' : '' }}>{{ trans('cruds.action.types.opportunity') }}</option>
            </select>
        </div>
        <div class="cell-5 mt-2">
             <input type="radio" data-role="radio" data-append="{{ trans("cruds.action.fields.status_open") }}" value="0" id="status0" {{ (Session::get("status")=="0") ? 'checked' : '' }}/>
             <input type="radio" data-role="radio" data-append="{{ trans("cruds.action.fields.status_closed") }}" value="1" id="status1" {{ (Session::get("status")=="1") ? 'checked' : '' }}/>
             <input type="radio" data-role="radio" data-append="{{ trans("cruds.action.fields.status_all") }}" value="2" id="status2" {{ (Session::get("status")=="2") ? 'checked' : '' }}/>
        </div>
        <div class="cell-2">
        </div>
        <div class="cell-2">
            <select id='scope' name="scope" data-role="select">
                <option value="">-- {{ trans("cruds.action.fields.choose_scope")}} --</option>
                @foreach ($scopes as $scope)
                    <option {{ (Session::get('scope')==$scope) ? 'selected' : '' }}>
                        {{ $scope }}
                    </option>
                @endforeach
            </select>
        </div>
		<div class="cell-1" align="right">
		@if ((Auth::User()->role==1)||(Auth::User()->role==2))
            <a class="button primary" href='/action/create'>
	            <span class="mif-add-lib"></span>
	            &nbsp;
				{{ trans('common.new') }}
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

        <table id="actions" class="table striped row-hover cell-border"
	       data-role="table"
	       data-rows="100"
	       data-show-activity="true"
	       data-rownum="false"
	       data-check="false"
	       data-check-style="1">
	    <thead>
	    <tr>
            <th class="sortable-column sort-asc"><span style="font-size: 0.8em;">{{ trans('cruds.action.fields.reference') }}</span></th>
            <th class="sortable-column sort-asc"><span style="font-size: 0.8em;">{{ trans('cruds.action.fields.type') }}</span></th>
            <th class="sortable-column sort-asc">{{ trans('cruds.action.fields.status') }}</th>
            <th class="sortable-column sort-asc">{{ trans('cruds.action.fields.progress') }}</th>
            <th class="sortable-column sort-asc">{{ trans('cruds.action.fields.name') }}</th>
			<th class="sortable-column sort-asc">{{ trans('cruds.action.fields.scope') }}</th>
            <th class="sortable-column sort-asc">{{ trans('cruds.action.fields.due_date') }}</th>
	    </tr>
	    </thead>
	    <tbody>
	@foreach($actions as $action)
        <tr>
            <td>
                <b id="{{ $action->reference }}" style="font-size: 0.8em;">
                    <a href="/action/show/{{ $action->id }}">{{ $action->reference==null ? ("ACT-".$action->id) : $action->reference }}</a>
                </b>
            </td>
            <td>
                @if ($action->type==1)
                    <div class="fg-red text-bold" style="font-size: 0.8em;" title="{{ trans('cruds.action.types.major') }}">{{ trans('cruds.action.types_short.major') }}</div>
                @elseif ($action->type==2)
                    <div class="fg-orange text-bold" style="font-size: 0.8em;" title="{{ trans('cruds.action.types.minor') }}">{{ trans('cruds.action.types_short.minor') }}</div>
                @elseif ($action->type==3)
                    <div class="fg-yellow text-bold" style="font-size: 0.8em;" title="{{ trans('cruds.action.types.observation') }}">{{ trans('cruds.action.types_short.observation') }}</div>
                @elseif ($action->type==4)
                    <div class="fg-green text-bold" style="font-size: 0.8em;" title="{{ trans('cruds.action.types.opportunity') }}">{{ trans('cruds.action.types_short.opportunity') }}</div>
                @endif
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
            <td width="40">
                <div data-role="donut" data-value="{{ $action->progress }}"
                {{ $action->progress }}
                @if ($action->progress<25)
                    class="donut-red"
                @elseif ($action->progress<50)
                    class="donut-orange"
                @else
                    class="donut-green"
                @endif
                    >
                </div>
            </td>
            <td >
                <b>{{ $action->name }}</b>
                {!! \Parsedown::instance()->text($action->cause) !!}
            </td>
            <td>
                {{ $action->scope }}
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
@endsection
