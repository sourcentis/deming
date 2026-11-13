@extends("layout")

@section("content")
<div class="card">
    <div class="card-header">@include('partials.help', ['page' => 'groups'])<i class="bi bi-people"></i> {{ trans('cruds.group.show') }}</div>
    <div class="card-body">

	<div class="container-fluid px-0">

    	<div class="row">
    		<div class="col-lg-1 col-md-2">
                <strong>{{ trans('cruds.group.fields.name') }}</strong>
	    	</div>
            <div class="col-lg-11 col-md-10">
                {{ $group->name }}
    		</div>
	    </div>

    	<div class="row">
    		<div class="col-lg-1 col-md-2">
                <strong>{{ trans('cruds.group.fields.type') }}</strong>
	    	</div>
            <div class="col-lg-11 col-md-10">
                @include('groups.type_badge')
    		</div>
	    </div>

    	<div class="row">
    		<div class="col-lg-1 col-md-2">
                <strong>{{ trans('cruds.group.fields.description') }}</strong>
	    	</div>
			<div class="col-lg-11 col-md-10">
                {{ $group->description }}
    		</div>
	    </div>

    	<div class="row">
    		<div class="col-lg-1 col-md-2">
                <strong>{{ trans('cruds.group.fields.users') }}</strong>
	    	</div>
        </div>
    	<div class="row">
			<div class="col-lg-12 col-md-12">
			    <table data-order="[]" data-page-length="25" id="group-users-table" class="table table-striped table-hover table-bordered datatable">
			        <thead>
			            <tr>
                            <th>{{ trans("cruds.user.fields.name") }}</th>
                            <th>{{ trans("cruds.user.fields.email") }}</th>
                            <th>{{ trans("cruds.user.fields.role") }}</th>
			            </tr>
			        </thead>
			        <tbody>
                @foreach($group->users as $user)
    				<tr>
			            <td>
                            <a id="{{ $user->name }}" href="/users/{{$user->id}}">
                                {{ $user->name }}
                            </a>
                        </td>
			            <td>
                            {{ $user->email }}
			            </td>
                        <td>
        		    		{{ $user->role==1 ? trans('cruds.user.roles.admin') : "" }}
        		    		{{ $user->role==2 ? trans('cruds.user.roles.user') : "" }}
                            {{ $user->role==5 ? trans('cruds.user.roles.auditee') : "" }}
        		    		{{ $user->role==3 ? trans('cruds.user.roles.auditor') : "" }}
        		    		{{ $user->role==4 ? trans('cruds.user.roles.api') : "" }}
                        </td>
	    			</tr>
    			@endforeach
    			</table>
    		</div>
    	</div>


    	<div class="row">
            <div class="col-md-4 col-lg-2">
                <strong>{{ trans('cruds.group.fields.controls') }}</strong>
	    	</div>
        </div>
    	<div class="row">
			<div class="col-lg-12 col-md-12">
			    <table data-order="[]" data-page-length="25" id="group-measures-table" class="table table-striped table-hover table-bordered datatable">
			        <thead>
			            <tr>
                            <th>{{ trans("cruds.measure.fields.clauses") }}</th>
                            <th>{{ trans("cruds.measure.fields.name") }}</th>
                            <th>{{ trans("cruds.measure.fields.scope") }}</th>
                            <th>{{ trans("cruds.measure.fields.planned") }}</th>
			            </tr>
			        </thead>
			        <tbody>
                @foreach($group->measures as $measure)
    				<tr>
			            <td>
                            @foreach($measure->controls as $control)
                            <a id="{{ $control['clause'] }}" href="/alice/show/{{ $control['id'] }}">
                                {{ $control['clause'] }}
                            </a>
                            @if (!$loop->last)
                            ,
                            @endif
                            @endforeach
                        </td>
			            <td>
                            {{ $measure->name }}
                        </td>
			            <td>
                            {{ $measure->scope }}
			            </td>
                        <td>
                            <a id="{{ $measure->plan_date }}" href="/bob/show/{{$measure->id}}">
			                <b>
			                    @if( strtotime($measure->plan_date) >= strtotime('now') )
			                        <font color="green">{{ $measure->plan_date }}</font>
			                    @else
			                        <font color="red">{{ $measure->plan_date }}</font>
			                    @endif
			                </b>
                            </a>
                        </td>
	    			</tr>
    			@endforeach
    			</table>
    		</div>
    	</div>

    	<div class="row">
    		<div class="col">
	    	</div>
        </div>


    </div>

	<div class="row">
        <div class="col-lg-6 col-md-10">
        <form class="d-inline" action="/groups/{{ $group->id }}/edit">
			@if (Auth::User()->role==1)
            <button class="btn btn-primary">
	            <span class="bi bi-pencil-square"></span> {{ trans('common.edit') }}
	    	</button>
	    </form>
	    	&nbsp;
	    	@endif
			@if (Auth::User()->role==1)
            <form action="/groups/{{ $group->id }}" method="post" data-confirm="{{ trans('common.confirm') }}" class="d-inline">
               {{ method_field('delete') }}
               @csrf
	            <button class="btn btn-danger" type="submit">
					<span class="bi bi-trash"></span> {{ trans('common.delete') }}
	            </button>
	        </form>
	        &nbsp;
	        @endif
			<a class="btn btn-light border" href="/groups">
				<span class="bi bi-x-circle"></span> {{ trans('common.cancel') }}
            </a>
        </div>
    </div>
	</div>
</div>
</div>
@endsection
