@extends("layout")

@section("content")
    @include('partials.errors')

    <div class="card">
    <div class="card-header">@include('partials.help', ['page' => 'users'])<i class="bi bi-person"></i> {{ trans('cruds.user.show') }}</div>
    <div class="card-body">
		<div class="container-fluid px-0">
	    	<div class="row">
	    		<div class="col-md-2 col-lg-1">
		    		<strong>{{ trans('cruds.user.fields.login') }}</strong>
		    	</div>
                <div class="col-md-12 col-lg-11">
		    		{{ $user->login }}
	    		</div>
		    </div>

	    	<div class="row">
	    		<div class="col-md-2 col-lg-1">
		    		<strong>{{ trans('cruds.user.fields.name') }}</strong>
		    	</div>
                <div class="col-md-12 col-lg-11">
		    		{{ $user->name }}
	    		</div>
		    </div>

	    	<div class="row">
	    		<div class="col-md-2 col-lg-1">
		    		<strong>{{ trans('cruds.user.fields.title') }}</strong>
		    	</div>
                <div class="col-md-12 col-lg-11">
		    		{{ $user->title }}
	    		</div>
		    </div>

	    	<div class="row">
	    		<div class="col-md-2 col-lg-1">
		    		<strong>{{ trans('cruds.user.fields.language') }}</strong>
		    	</div>
                <div class="col-md-12 col-lg-11">
		    		{{ $user->language }}
	    		</div>
		    </div>

	    	<div class="row">
	    		<div class="col-md-2 col-lg-1">
		    		<strong>{{ trans('cruds.user.fields.role') }}</strong>
		    	</div>
                <div class="col-md-12 col-lg-11">
		    		{{ $user->role==1 ? trans('cruds.user.roles.admin') : "" }}
		    		{{ $user->role==2 ? trans('cruds.user.roles.user') : "" }}
                    {{ $user->role==5 ? trans('cruds.user.roles.auditee') : "" }}
		    		{{ $user->role==3 ? trans('cruds.user.roles.auditor') : "" }}
		    		{{ $user->role==4 ? trans('cruds.user.roles.api') : "" }}
		    		{{ $user->role==0 ? trans('cruds.user.roles.disabled') : "" }}
	    		</div>
		    </div>

	    	<div class="row">
	    		<div class="col-md-2 col-lg-1">
		    		<strong>{{ trans('cruds.user.fields.email') }}</strong>
		    	</div>
                <div class="col-md-12 col-lg-11">
		    		{{ $user->email }}
	    		</div>
	    	</div>

	    	<div class="row">
                <div class="col-md-2 col-lg-1">
                    <strong>{{ trans('cruds.user.fields.groups') }}</strong>
		    	</div>

	    		<div class="col-md-12 col-lg-11">
                    @foreach($user->groups as $group)
                        <a href='/groups/{{ $group->id }}'> {{ $group->name }}</a>
                        @if (!$loop->last)
                        ,
                        @endif
                    @endforeach
	    		</div>
	    	</div>

	    	<div class="row">
	    		<div class="col-md-4 col-lg-2">
		    		<strong>{{ trans('cruds.user.fields.controls') }}</strong>
		    	</div>
		    </div>
	    	<div class="row">
	    		<div class="col-md-12 col-lg-8">
				    <table data-order="[[3,&quot;asc&quot;]]" data-page-length="25" id="user-controls-table" class="table table-striped table-hover table-bordered datatable">
				        <thead>
				            <tr>
				                <th>{{ trans("cruds.measure.fields.clauses") }}</th>
				                <th>{{ trans("cruds.measure.fields.name") }}</th>
                                <th>{{ trans("cruds.measure.fields.scope") }}</th>
				                <th>{{ trans("cruds.measure.fields.planned") }}</th>
				                <th>{{ trans("cruds.user.fields.groups") }}</th>
				            </tr>
				        </thead>
				        <tbody>
	    			@foreach($assignments as $assignment)
	    				@php($measure = $assignment['measure'])
	    				<tr>
				            <td>
                                @foreach($measure->controls as $control)
                                    <a href="/alice/show/{{ $control->id }}">{{ $control->clause }}</a>
                                    @if(!$loop->last)
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
				                <a id="{{ $measure->plan_date }}" href="/bob/show/{{ $measure->id }}">
				                <b>
				                    @if( strtotime($measure->plan_date) >= strtotime('now') )
				                        <font color="green">{{ $measure->plan_date }}</font>
				                    @else
				                        <font color="red">{{ $measure->plan_date }}</font>
				                    @endif
				                </b>
				                </a>
		    				</td>
		    				<td>
                                @foreach($assignment['groups'] as $group)
                                    <a href="/groups/{{ $group->id }}">{{ $group->name }}</a>
                                    @if(!$loop->last)
                                    ,
                                    @endif
                                @endforeach
		    				</td>
		    			</tr>
	    			@endforeach
				        </tbody>
	    			</table>
	    		</div>
	    	</div>
	    </div>

    	<div class="row">
            <div class="col-md-12 col-lg-6">
			@if ((Auth::User()->role==1)||($user->id==Auth::User()->id))
		    <form>
		    	<button class="btn btn-primary" onclick='this.form.action="/users/{{ $user->id }}/edit"'>
		            <span class="bi bi-pencil"></span> {{ trans('common.edit') }}
		    	</button>
		    </form>
            @endif
			@if (Auth::User()->role==1)
			<form action="/users/{{ $user->id }}" method="post" onSubmit="if(!confirm('{{ trans('common.confirm') }}')){return false;}">
               {{ method_field('delete') }}
               @csrf
	            <button class="btn btn-danger" type="submit">
					<span class="bi bi-trash"></span> {{ trans('common.delete') }}
	            </button>
	        </form>
	        @endif
			@if (Auth::User()->role==1)
			<a class="btn btn-light border" href="/logs/history/user/{{ $user->id }}">
				<span class="bi bi-journal-text"></span> {{ trans('common.history') }}
			</a>
			@endif
            <a class="btn btn-light border" href="/users">
				<span class="bi bi-x-circle"></span> {{ trans('common.cancel') }}
            </a>
		</div>
	</div>
</div>
</div>
@endsection
