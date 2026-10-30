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
    <div class="card-header">@include('partials.help', ['page' => 'actions.show'])<i class="bi bi-list-check"></i> {{ trans('cruds.action.show') }}</div>
    <div class="card-body">
    @include('partials.ai-button', ['type' => 'action', 'object' => $action])

    @include('partials.errors')

    <form method="POST" action="/action/update">
		@csrf
		<input type="hidden" name="id" value="{{ $action->id }}"/>

		<div class="container-fluid px-0">
        	<div class="row">
        		<div class="col-md-2 col-lg-1">
                    <strong>{{ trans("cruds.action.fields.reference") }}</strong>
    	    	</div>
                <div class="col-md-4 col-lg-2">
                    {{ $action->reference==null ? ("ACT-".$action->id) : $action->reference }}
                </div>
                <div class="col-md-2 col-lg-1 text-md-end">
                    <strong>{{ trans("cruds.action.fields.type") }}</strong>
    	    	</div>
                <div class="col-md-2 col-lg-1">
                    @if ($action->type==1)
                    <p class="text-danger fw-bold">
                    {{ trans('cruds.action.types.major') }}
                    </p>
                    @elseif ($action->type==2)
                    <p class="text-orange fw-bold">
                    {{ trans('cruds.action.types.minor') }}
                    </p>
                    @elseif ($action->type==3)
                    <p class="text-warning fw-bold">
                    {{ trans('cruds.action.types.observation') }}
                    </p>
                    @elseif ($action->type==4)
                    <p class="text-success fw-bold">
                    {{ trans('cruds.action.types.opportunity') }}
                    </p>
                    @endif
                </div>
                <div class="col-md-2 col-lg-2 text-md-end">
                    <strong>{{ trans('cruds.action.fields.due_date') }}</strong>
    	    	</div>
                <div class="col-md-4 col-lg-2">
                    {{$action->due_date}}
    			</div>
            </div>

            @if ($action->controls->isNotEmpty())
        	<div class="row">
        		<div class="col-md-2 col-lg-1">
                    <strong>{{ trans("cruds.action.fields.clauses") }}</strong>
    	    	</div>
        		<div class="col-md-10 col-lg-11">
                    @foreach($action->controls as $control)
                        <a href="/alice/show/{{ $control->id }}">{{ $control->clause }}</a> - {{ $control->name }}
                        @if(!$loop->last)
                        <br>
                        @endif
                    @endforeach
                </div>
            </div>
            @endif

            @if ($action->risks->isNotEmpty())
        	<div class="row">
        		<div class="col-md-2 col-lg-1">
                    <strong>{{ trans("cruds.action.fields.risks") }}</strong>
    	    	</div>
        		<div class="col-md-10 col-lg-11">
                    @foreach($action->risks as $risk)
                        <a href="/risk/show/{{ $risk->id }}">{{ $risk->name }}</a>
                        @if(!$loop->last)
                        <br>
                        @endif
                    @endforeach
                </div>
            </div>
            @endif

	    	<div class="row">
	    		<div class="col-md-2 col-lg-1">
		    		<strong>{{ trans('cruds.action.fields.name') }}</strong>
		    	</div>
                <div class="col-md-10 col-lg-5">
                    @if ($action->control_id!==null)
                    <a href="/bob/show/{{ $action->control_id }}">
					    {{ $action->name }}
                    </a>
                    @else
					    {{ $action->name }}
                    @endif
				</div>
                @if ($action->scope!==null)
                <div class="col-md-4 col-lg-2">
		    		<strong>{{ trans("cruds.measure.fields.scope") }}</strong>
		    		&nbsp;
	    			{{ $action->scope }}
	    		</div>
                @endif
			</div>

	    	<div class="row">
	    		<div class="col-md-2 col-lg-1">
                    <strong>{{ trans('cruds.action.fields.cause') }}</strong>
		    	</div>
				<div class="col-md-10 col-lg-11">
                    <pre>{{ $action->cause }}</pre>
				</div>
			</div>

    	<div class="row">
		</div>

    	<div class="row">
    		<div class="col-md-2 col-lg-1">
                <strong>{{ trans('cruds.action.fields.remediation') }}</strong>
	    	</div>
			<div class="col-md-12 col-lg-11">
                @if ($action->status==0)
                    <textarea name="remediation" class="easymde form-control" id="remediation">{{ old('remediation', $action->remediation) }}</textarea>
                @else
                    {!! \Parsedown::instance()->text($action->remediation) !!}
                @endif
			</div>
		</div>
        @if ($action->status==0)
    	<div class="row">
    		<div class="col-md-2 col-lg-1">
                <strong>{{ trans('cruds.action.fields.progress') }}</strong>
	    	</div>
			<div class="col-md-12 col-lg-11">
                <div class="d-flex align-items-center gap-2">
                    <input class="form-range" value="{{ $action->progress }}" type="range" name="progress" min="0" max="100" step="1"
                           oninput="this.nextElementSibling.textContent = this.value + '%'"/>
                    <span class="badge bg-primary">{{ (int) $action->progress }}%</span>
                </div>
            </div>
        </div>
        @endif

        @if ($action->status!=0)
    	<div class="row">
			<div class="col-md-2 col-lg-1">
                <strong>{{ trans('cruds.action.fields.status') }}</strong>
	    	</div>
            <div class="col-md-8 col-lg-4">
                @if ($action->status==0)
                    {{ trans('cruds.action.fields.status_open') }}
                @elseif ($action->status==1)
                    {{ trans('cruds.action.fields.status_closed') }}
                @elseif ($action->status==2)
                    {{ trans('cruds.action.fields.status_rejected') }}
                @else
                    {{ $action->status }}
                @endif

	    	</div>
			<div class="col-md-2 col-lg-1">
                <strong>{{ trans('cruds.action.fields.close_date') }}</strong>
	    	</div>
			<div class="col-md-2 col-lg-1">
                {{ $action->close_date}}
            </div>
        </div>
    	<div class="row">
    		<div class="col-md-2 col-lg-1">
                <strong>{{ trans('cruds.action.fields.justification') }}</strong>
	    	</div>
			<div class="col-md-12 col-lg-11">
                {!! \Parsedown::instance()->text($action->justification) !!}
			</div>
		</div>
        @endif

    	<div class="row">
			<div class="col-md-2 col-lg-1">
                <strong>{{ trans('cruds.action.fields.owners') }}</strong>
	    	</div>
            <div class="col-md-12 col-lg-11">
                @foreach($action->owners as $owner)
					{{ $owner->name }}
                    @if ($action->owners->last()!=$owner)
					,
					@endif
				@endforeach
			</div>
	    </div>

        <div class="row">
    		<div class="col-md-2 col-lg-1">

            </div>
        </div>
		<div class="container-fluid px-0">
	    	<div class="mt-2">
                @if ($action->status==0)
				<button type="submit" class="btn btn-success">
                    <span class="bi bi-floppy"></span> {{ trans('common.save') }}
				</button>
	            &nbsp;
                    <a class="btn btn-info" href="/action/close/{{ $action->id }}">
                        <span class="bi bi-check-lg"></span> {{ trans("common.close") }}
                    </a>
	            &nbsp;
                @endif
                <a class="btn btn-primary" href="/action/edit/{{ $action->id }}">
    		            <span class="bi bi-pencil"></span> {{ trans('common.edit') }}
                </a>
				&nbsp;
                <button class="btn btn-danger" type="submit"
                    onclick="if (!confirm('{{ trans('common.confirm') }}')) return false; this.form.action='/action/delete';">
                    <span class="bi bi-trash"></span> {{ trans('common.delete') }}
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
