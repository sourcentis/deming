@extends("layout")

@section("content")
<style type="text/css">
form, table {
     display:inline;
     margin:0px;
     padding:0px;
}
</style>

<div class="card">
    <div class="card-header">@include('partials.help', ['page' => 'actions.edit'])<i class="bi bi-list-check"></i> {{ trans('cruds.action.edit') }}</div>
    <div class="card-body">

@include('partials.errors')

<form method="POST" action="/action/save">
	@csrf
	<input type="hidden" name="id" value="{{ $action->id }}"/>

	<div class="container-fluid px-0">
    	<div class="row">
    		<div class="col-md-2 col-lg-1">
                <strong>{{ trans("cruds.action.fields.reference") }}</strong>
	    	</div>
            <div class="col-md-2 col-lg-2 col-xl-1">
                <input class="form-control" type="text" name="reference" value="{{ $action->reference }}" maxlength="32">
            </div>
            <div class="col-md-2 col-lg-1 text-md-end">
                <strong>{{ trans("cruds.action.fields.type") }}</strong>
            </div>
            <div class="col-md-4 col-lg-2">
                <select class="form-select" name="type" data-type="select">
                    <option></option>
                    <option value="1" {{ ($action->type==1) ? 'selected' : '' }}>{{ trans('cruds.action.types.major') }}</option>
                    <option value="2" {{ ($action->type==2) ? 'selected' : '' }}>{{ trans('cruds.action.types.minor') }}</option>
                    <option value="3" {{ ($action->type==3) ? 'selected' : '' }}>{{ trans('cruds.action.types.observation') }}</option>
                    <option value="4" {{ ($action->type==4) ? 'selected' : '' }}>{{ trans('cruds.action.types.opportunity') }}</option>
                </select>
            </div>
            <div class="col-md-2 col-lg-1 text-md-end">
                <strong>{{ trans('cruds.action.fields.due_date') }}</strong>
            </div>
            <div class="col-md-2 col-lg-2 col-xl-1">
                <input class="form-control" type="date"
                name="due_date"
                value="{{$action->due_date}}"/>
			</div>
        </div>

		<div class="row">
    		<div class="col-md-2 col-lg-1">
                <strong>{{ trans('cruds.action.fields.clauses') }}</strong>
	    	</div>
            <div class="col-md-12 col-lg-11">
				<select class="form-select select2" name="measures[]" multiple>
					@foreach($all_measures as $measure)
					    <option value="{{ $measure->id }}"
                            {{ in_array($measure->id, old("measures", $measures)) ? "selected" : "" }}>
                                {{ $measure->clause }} - {{ $measure->name }}
                        </option>
				    @endforeach
				 </select>
			</div>
		</div>

		<div class="row">
    		<div class="col-md-2 col-lg-1">
                <strong>{{ trans('cruds.action.fields.risks') }}</strong>
	    	</div>
            <div class="col-md-12 col-lg-11">
				<select class="form-select select2" name="risks[]" multiple>
					@foreach($all_risks as $risk)
					    <option value="{{ $risk->id }}"
                            {{ in_array($risk->id, old("risks", $risks)) ? "selected" : "" }}>
                                {{ $risk->name }}
                        </option>
				    @endforeach
				 </select>
			</div>
		</div>

    	<div class="row">
    		<div class="col-md-2 col-lg-1">
                <strong>{{ trans("cruds.action.fields.name") }}</strong>
    		</div>
    		<div class="col-md-5 col-lg-6">
				<input class="form-control" type="text" name="name" value="{{ $action->name }}" maxlength="255">
			</div>
    		<div class="col-md-1 col-lg-1 text-md-end">
	    		<strong>{{ trans("cruds.action.fields.scope") }}</strong>
            </div>
    		<div class="col-md-4 col-lg-4">
                <input class="form-control" type="text" name="scope" autocomplete="on" maxlength="32"
                value="{{ $action->scope }}"/>
			</div>
		</div>

    	<div class="row">
    		<div class="col-md-2 col-lg-1">
                <strong>{{ trans('cruds.action.fields.cause') }}</strong>
	    	</div>
            <div class="col-md-12 col-lg-11">
            <textarea name="cause" class="easymde form-control" id="cause">{{ old('cause', $action->cause) }}</textarea>
			</div>
		</div>

	<div class="row">
	</div>

	<div class="row">
		<div class="col-md-2 col-lg-1">
            <strong>{{ trans('cruds.action.fields.remediation') }}</strong>
    	</div>
		<div class="col-md-12 col-lg-11">
            <textarea name="remediation" class="easymde form-control" id="remediation">{{ old('remediation', $action->remediation) }}</textarea>
		</div>
	</div>

	<div class="row">
		<div class="col-md-2 col-lg-1">
            <strong>{{ trans('cruds.action.fields.progress') }}</strong>
    	</div>
		<div class="col-md-12 col-lg-11">
            <div class="d-flex align-items-center gap-2">
                    <input class="form-range" value="{{ $action->progress }}" type="range" name="progress" min="0" max="100" step="1"
                           data-range-label/>
                    <span class="badge bg-primary">{{ (int) $action->progress }}%</span>
                </div>
        </div>
    </div>

	<div class="row">
        <div class="col-md-2 col-lg-1">
            <strong>{{ trans('cruds.action.fields.status') }}</strong>
        </div>
        <div class="col-md-4 col-lg-2">
            <select class="form-select select2" name="status" id="status">
                <option value="0" {{ $action->status==0 ? 'selected' : '' }}>{{ trans('cruds.action.fields.status_open') }}</option>
                <option value="1" {{ $action->status==1 ? 'selected' : '' }}>{{ trans('cruds.action.fields.status_closed') }}</option>
                <option value="2" {{ $action->status==2 ? 'selected' : '' }}>{{ trans('cruds.action.fields.status_rejected') }}</option>
            </select>
        </div>
        <div class="col-md-6 col-lg-3 text-md-end">
            <strong>{{ trans('cruds.action.fields.close_date') }}</strong>
        </div>
        <div class="col-md-2 col-lg-2 col-xl-1">
            <input class="form-control" type="date"
                name="close_date"
                value="{{$action->close_date}}"
                />
        </div>
    </div>

	<div class="row">
		<div class="col-md-2 col-lg-1">
            <strong>{{ trans('cruds.action.fields.justification') }}</strong>
    	</div>
		<div class="col-md-12 col-lg-11">
            <textarea name="justification" class="easymde form-control" id="justification">{{ old('justification', $action->justification) }}</textarea>
		</div>
	</div>

	<div class="row">
        <div class="col-md-2 col-lg-1">
            <strong>{{ trans('cruds.action.fields.owners') }}</strong>
        </div>
        <div class="col-md-12 col-lg-11">
            <select class="form-select select2" name="owners[]" id="owners" multiple>
                @foreach($users as $user)
                    <option value="{{ $user->id }}" {{ (in_array($user->id, old('owners', [])) || $action->owners->contains($user->id)) ? 'selected' : '' }}>{{ $user->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="row">
		<div class="col-md-2 col-lg-1">

        </div>
    </div>
	<div class="container-fluid px-0">
    	<div class="mt-2">
			<button type="submit" class="btn btn-success">
                <span class="bi bi-floppy"></span> {{ trans('common.save') }}
			</button>
			&nbsp;
            <a class="btn btn-light border" href="/actions">
    			<span class="bi bi-x-circle"></span> {{ trans("common.cancel") }}
            </a>
		</div>
	</div>
</div>
</form>
</div>
</div>
@endsection
