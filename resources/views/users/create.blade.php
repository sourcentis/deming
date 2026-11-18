@extends("layout")

@section("content")
<div class="card">
    <div class="card-header">@include('partials.help', ['page' => 'users'])<i class="bi bi-person"></i> {{ trans('cruds.user.add') }}</div>
    <div class="card-body">

    @include('partials.errors')

	<form method="POST" action="/users">
	@csrf
		<div class="container-fluid px-0">
	    	<div class="row">
	    		<div class="col-md-2 col-lg-1">
					<label>{{ trans('cruds.user.fields.login') }}</label>
		    	</div>
	    		<div class="col-md-10 col-lg-11">
					<input type="text" class="{{ $errors->has('login') ? 'is-invalid' : ''}} form-control" name="login" value="{{ old('login') }}" size='40'>
				</div>
			</div>
	    	<div class="row">
	    		<div class="col-md-2 col-lg-1">
					<label>{{ trans('cruds.user.fields.name') }}</label>
		    	</div>
	    		<div class="col-md-10 col-lg-11">
					<input type="text" class="{{ $errors->has('name') ? 'is-invalid' : ''}} form-control" name="name" value="{{ old('name') }}" size='40'>
				</div>
			</div>
	    	<div class="row">
	    		<div class="col-md-2 col-lg-1">
					<label>{{ trans('cruds.user.fields.title') }}</label>
				</div>
	    		<div class="col-md-10 col-lg-11">
					<input type="text" class="{{ $errors->has('title') ? 'is-invalid' : ''}} form-control" name="title" value="{{ old('title') }}" size='80'>
				</div>
			</div>
	    	<div class="row">
	    		<div class="col-md-2 col-lg-1">
					<label>{{ trans('cruds.user.fields.language') }}</label>
				</div>
	    		<div class="col-md-2 col-lg-2 col-xl-1">
	    			<select class="form-select select2" name='language'>
	    				<option {{ old('language',Auth::user()->language)=='de' ? 'selected' : ''}} >de</option>
	    				<option {{ old('language',Auth::user()->language)=='en' ? 'selected' : ''}} >en</option>
	    				<option {{ old('language',Auth::user()->language)=='es' ? 'selected' : ''}} >es</option>
	    				<option {{ old('language',Auth::user()->language)=='fr' ? 'selected' : ''}} >fr</option>
	    			</select>
				</div>
			</div>
	    	<div class="row">
	    		<div class="col-md-2 col-lg-1">
					<label>{{ trans('cruds.user.fields.role') }}</label>
				</div>
	    		<div class="col-md-6 col-lg-3">
                    <select class="form-select select2" name="role">
					    <option value="1" {{ old('role')==1 ? "selected" : "" }}>{{ trans('cruds.user.roles.admin') }}</option>
					    <option value="2" {{ old('role')==2 ? "selected" : "" }}>{{ trans('cruds.user.roles.user') }}</option>
                        <option value="5" {{ old('role')==5 ? "selected" : "" }}>{{ trans('cruds.user.roles.auditee') }}</option>
					    <option value="3" {{ old('role')==3 ? "selected" : "" }}>{{ trans('cruds.user.roles.auditor') }}</option>
					    <option value="4" {{ old('role')==4 ? "selected" : "" }}>{{ trans('cruds.user.roles.api') }}</option>
					    <option value="0" {{ old('role')==0 ? "selected" : "" }}>{{ trans('cruds.user.roles.disabled') }}</option>
					</select>
				</div>
			</div>
	    	<div class="row">
	    		<div class="col-md-2 col-lg-1">
					<label class="form-label" for="email">{{ trans('cruds.user.fields.email') }}</label>
				</div>
	    		<div class="col-md-10 col-lg-11">
					<input type="text" name="email" class="{{ $errors->has('email') ? 'is-invalid' : ''}} form-control" value="{{ old('email') }}" size="120">
				</div>
			</div>

	        @if (! Config::get('app.ldap_enabled'))
	    	<div class="row">
	    		<div class="col-md-2 col-lg-1">
					<label class="form-label" for="password">{{ trans('cruds.user.fields.password') }}</label>
				</div>
	    		<div class="col-md-10 col-lg-11">
					<input type="password" class="form-control" name="password1"/>
					<input type="password" class="form-control" name="password2"/>
				</div>
			</div>
			@endif
		</div>

		<div class="row mt-4">
    		<div class="col-md-8 col-lg-4">
				<button type="submit" class="btn btn-success">
					<span class="bi bi-floppy"></span> {{ trans('common.save') }}
				</button>
				<a class="btn btn-light border" href="/users">
					<span class="bi bi-x-circle"></span> {{ trans('common.cancel') }}
				</a>
			</div>
		</div>
	</form>
</div>
</div>
@endsection
