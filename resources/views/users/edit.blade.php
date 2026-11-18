@extends("layout")

@section("content")
    <div class="card">
    <div class="card-header">@include('partials.help', ['page' => 'users'])<i class="bi bi-person"></i> {{ trans('cruds.user.edit') }}</div>
    <div class="card-body">

        @include('partials.errors')

		<form method="POST" action="/users/{{ $user->id }}">
		@method("PATCH")
		@csrf
		<div class="container-fluid px-0">

	    	<div class="row">
	    		<div class="col-md-2 col-lg-1">
					<label>{{ trans('cruds.user.fields.login') }}</label>
				</div>
	    		<div class="col-md-10 col-lg-11">
                    <input type="text" class="{{ $errors->has('login') ? 'is-invalid' : ''}} form-control" name="login" value="{{ old('login', $user->login) }}" size='40'>
				</div>
			</div>


	    	<div class="row">
	    		<div class="col-md-2 col-lg-1">
					<label>{{ trans('cruds.user.fields.name') }}</label>
				</div>
	    		<div class="col-md-10 col-lg-11">
                    <input type="text" class="{{ $errors->has('name') ? 'is-invalid' : ''}} form-control" name="name" value="{{ old('name', $user->name) }}" size='80'>
				</div>
			</div>

	    	<div class="row">
	    		<div class="col-md-2 col-lg-1">
					<label>{{ trans('cruds.user.fields.title') }}</label>
				</div>
	    		<div class="col-md-10 col-lg-11">
                    <input type="text" class="{{ $errors->has('title') ? 'is-invalid' : ''}} form-control" name="title" value="{{ old('title', $user->title) }}" size='80'>
				</div>
			</div>

	    	<div class="row">
	    		<div class="col-md-2 col-lg-1">
					<label>{{ trans('cruds.user.fields.language') }}</label>
				</div>
	    		<div class="col-md-2 col-lg-2 col-xl-1">
	    			<select class="form-select select2" name='language'>
	    				<option {{ (old('language',$user->language) =='de') ? 'selected' : ''}} >de</option>
	    				<option {{ (old('language',$user->language) =='en') ? 'selected' : ''}} >en</option>
	    				<option {{ (old('language',$user->language) =='es') ? 'selected' : ''}} >es</option>
                        <option {{ (old('language',$user->language) =='fr') ? 'selected' : ''}} >fr</option>
	    			</select>
				</div>
			</div>
			@if (Auth::User()->role==1)
	    	<div class="row">
	    		<div class="col-md-2 col-lg-1">
					<label>{{ trans('cruds.user.fields.role') }}</label>
				</div>
	    		<div class="col-md-6 col-lg-3">
                    <select class="form-select select2" name="role">
					    <option value="1" {{ $user->role==1 ? "selected" : "" }}>{{ trans('cruds.user.roles.admin') }}</option>
					    <option value="2" {{ $user->role==2 ? "selected" : "" }}>{{ trans('cruds.user.roles.user') }}</option>
                        <option value="5" {{ $user->role==5 ? "selected" : "" }}>{{ trans('cruds.user.roles.auditee') }}</option>
					    <option value="3" {{ $user->role==3 ? "selected" : "" }}>{{ trans('cruds.user.roles.auditor') }}</option>
					    <option value="4" {{ $user->role==4 ? "selected" : "" }}>{{ trans('cruds.user.roles.api') }}</option>
					    <option value="0" {{ $user->role==0 ? "selected" : "" }}>{{ trans('cruds.user.roles.disabled') }}</option>
					</select>
				</div>
			</div>
			@endif
	    	<div class="row">
	    		<div class="col-md-2 col-lg-1">
					<label class="form-label" for="description">{{ trans('cruds.user.fields.email') }}</label>
				</div>
	    		<div class="col-md-10 col-lg-11">
					<input type="text" name="email" class="{{ $errors->has('email') ? 'is-invalid' : ''}} form-control" value="{{ old('email', $user->email) }}" size="120">
				</div>
			</div>

	        @if (! Config::get('app.ldap_enabled'))
	    	<div class="row">
	    		<div class="col-md-2 col-lg-1">
					<label class="form-label" for="description">{{ trans('cruds.user.fields.password') }}</label>
				</div>
	    		<div class="col-md-10 col-lg-11">
					<input type="password" class="form-control" name="password1" autocomplete="new-password"/>
					<input type="password" class="form-control" name="password2" autocomplete="new-password"/>
				</div>
			</div>
			@endif

        @if (Auth::User()->isAdmin() || Auth::User()->isUser())
    	<div class="row">
    		<div class="col-md-4 col-lg-2">
	    		<strong>{{ trans('cruds.user.fields.controls') }}</strong>
	    	</div>
	    </div>

    	<div class="row">
    		<div class="col-12">
	            <select class="form-select select2" name="controls[]" id="controls" multiple>
	                @foreach($measures as $measure)
                       <option value="{{ $measure->id }}" {{ (in_array($measure->id, old('controls', [])) || ($user->measures->contains($measure->id))) ? 'selected' : '' }}>
                       {{ $measure->name }}
                       </option>
	                @endforeach
	            </select>
    		</div>
    	</div>
        @endif
    	<div class="row">
    		<div class="col-md-2 col-lg-1">
    		</div>
    	</div>
		</div>
			@if ((Auth::User()->role==1)||(Auth::User()->id===$user->id))
				<button type="submit" class="btn btn-success">
                    <span class="bi bi-floppy"></span> {{ trans('common.save') }}
				</button>
		        &nbsp;
			@endif
			</form>
			<form action="/users/{{ $user->id }}">
	    		<button type="submit" class="btn btn-light border">
	    			<span class="bi bi-x-circle"></span> {{ trans('common.cancel') }}</button>
			</form>
		</div>
</div>
	</div>
@endsection
