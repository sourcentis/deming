@extends("layout")

@section('title', $measure->name)

@section("content")
<div class="card">
    <div class="card-header">@include('partials.help', ['page' => 'measures.show'])<i class="bi bi-clipboard-check"></i> {{ trans('cruds.measure.title_singular') }}</div>
    <div class="card-body">
    @include('partials.ai-button', ['type' => 'measure', 'object' => $measure])

	<div class="container-fluid px-0">
		<div class="row">
			<div class="col-lg-1 col-md-2">
				<strong>{{ trans("cruds.measure.fields.clauses") }}</strong>
			</div>
			<div class="col-lg-11 col-md-10">
				@foreach($measure->controls as $control)
					<a href="/alice/show/{{ $control->id }}">{{ $control->clause }}</a> - {{ $control->name }}
					@if(!$loop->last)
					<br>
					@endif
				@endforeach
			</div>
		</div>
		<div class="row">
			<div class="col-lg-1 col-md-2">
				<strong>{{ trans("cruds.measure.fields.name") }}</strong>
			</div>
			@if ($measure->scope===null)
			<div class="col-lg-6 col-md-9">
				{{ $measure->name }}
			</div>
			@else
			<div class="col-lg-4 col-md-5">
				{{ $measure->name }}
			</div>
			<div class="col-lg-1 col-md-2 text-md-end">
				<strong>{{ trans("cruds.measure.fields.scope") }}</strong>
			</div>
			<div class="col-lg-1 col-md-2">
				<a href="/bob/index?scope={{ $measure->scope }}">
				{{ $measure->scope }}
				</a>
			</div>
			@endif
		</div>
		<div class="row">
			<div class="col-lg-1 col-md-2">
				<strong>{{ trans("cruds.measure.fields.objective") }}</strong>
			</div>
			<div class="col-lg-11 col-md-10">
				{!! \Parsedown::instance()->text($measure->objective) !!}
			</div>
		</div>

		@if ($measure->attributes!=null)
		<div class="row">
			<div class="col-lg-1 col-md-2">
				<strong>{{ trans("cruds.measure.fields.attributes") }}</strong>
			</div>
			<div class="col-lg-11 col-md-10">
				{{ $measure->attributes }}
			</div>
		</div>
		@endif

		<div class="row">
			<div class="col-lg-1 col-md-2">
				<strong>{{ trans("cruds.measure.fields.input") }}</strong>
			</div>
			<div class="col-lg-11 col-md-10">
				{!! \Parsedown::instance()->text($measure->input) !!}
			</div>
		</div>

		<div class="row">
			<div class="col-lg-1 col-md-2">
				<strong>{{ trans("cruds.measure.fields.model") }}</strong>
			</div>
			<div class="col-lg-11 col-md-10">
				<pre>{!! $measure->model !!}</pre>
			</div>
		</div>

		<div class="row">
			<div class="col-lg-1 col-md-2">
				<strong>{{ trans("cruds.measure.fields.plan_date") }}</strong>
			</div>
			<div class="col-lg-1 col-md-2 text-nowrap">
				{{ $measure->plan_date }}
			</div>

			<div class="col-lg-1 col-md-2 text-md-end">
				<strong>{{ trans("cruds.measure.fields.realisation_date") }}</strong>
			</div>
			<div class="col-lg-1 col-md-2 text-nowrap">
				{{ $measure->realisation_date }}
			</div>

			<div class="col-lg-1 col-md-2 text-md-end">
				<strong>{{ trans("common.previous") }}</strong>
				<br>
				<strong>{{ trans("common.next") }}</strong>
			</div>
			<div class="col-lg-1 col-md-2 text-nowrap">
				@if ($prev_id!=null)
					<a href="/bob/show/{{ $prev_id }}">
						{{ $prev_date }}
					</a>
				@else
					N/A
				@endif
				<br>
				@if ($next_id!=null)
					<a href="/bob/show/{{ $next_id }}">
						{{ $next_date }}
					</a>
				@else
					N/A
				@endif
			</div>
		</div>


		@if ($measure->observations!=null)
			<div class="row">
				<div class="col-lg-1 col-md-2">
					<strong>{{ trans("cruds.measure.fields.observations") }}</strong>
				</div>
				<div class="col-lg-11 col-md-10">
					<pre>{!! $measure->observations !!}</pre>
				</div>
			</div>
		@endif

		@if ($documents->isNotEmpty())
			<div class="row">
				<div class="col-lg-1 col-md-2">
					<strong>{{ trans("cruds.measure.fields.evidence") }}</strong>
				</div>
				<div class="col-lg-11 col-md-10">
					@foreach ($documents as $document)
						<a href="/doc/show/{{$document->id}}" target="_new">
							{{$document->filename}}
						</a>
						<br>
					@endforeach
				</div>
			</div>
		@endif

		<div class="row">
			<div class="col-lg-1 col-md-2">
				<strong>{{ trans("cruds.measure.fields.note") }}</strong>
			</div>
			<div class="col-md-4 col-lg-2">
				{{ fmod($measure->note, 1) == 0 ? intval($measure->note) : $measure->note }}
				@if (($measure->controls->count()===1) && (Auth::User()->isAdmin() || Auth::User()->isUser()))
					&nbsp;
					<a href="/radar/bob?{{ http_build_query(array_filter(['clause' => $measure->controls->first()->clause, 'scope' => $measure->scope], fn ($v) => $v !== null && $v !== '')) }}">
						<i class="bi bi-graph-up"></i>
					</a>
				@endif
			</div>
		</div>


		<div class="row">
			<div class="col-lg-1 col-md-2">
				<strong>{{ trans("cruds.measure.fields.indicator") }}</strong>
			</div>
			<div class="col-lg-11 col-md-10">
				<pre>{{ $measure->indicator }}</pre>
			</div>
		</div>

		@if ($measure->score!==null)
			<div class="row">
				<div class="col-lg-1 col-md-2">
					<strong>{{ trans("cruds.measure.fields.score") }}</strong>
				</div>
				<div class="col-lg-11 col-md-10">
					@if ($measure->score==1)
						&#128545;
					@elseif ($measure->score==2)
						&#128528;
					@elseif ($measure->score==3)
						<span style="filter: sepia(1) saturate(5) hue-rotate(80deg)">&#128512;</span>
					@else
						&#9899;
					@endif
					&nbsp; - &nbsp;
					@if ($measure->score==1)
						{{ trans("common.red") }}
					@elseif ($measure->score==2)
						{{ trans("common.orange") }}
					@elseif ($measure->score==3)
						{{ trans("common.green") }}
					@endif
				</div>
			</div>
		@endif

		@if (($measure->realisation_date!=null)&&($measure->score!=3))
			<div class="row">
				<div class="col-lg-1 col-md-2">
					<strong>{{ trans("cruds.measure.fields.action_plan") }}</strong>
				</div>
				<div class="col-lg-11 col-md-10">
					{!! \Parsedown::instance()->text($measure->action_plan) !!}
				</div>
			</div>
		@endif

		<div class="row">
			<div class="col-lg-1 col-md-2">
				<strong>{{ trans('cruds.measure.fields.periodicity') }}</strong>
			</div>
			<div class="col-lg-11 col-md-10">
				@if ($measure->periodicity==0) {{ trans("common.once") }} @endif
				@if ($measure->periodicity==-1) {{ trans("common.weekly") }} @endif
				@if ($measure->periodicity==1) {{ trans("common.monthly") }} @endif
				@if ($measure->periodicity==3) {{ trans("common.quarterly") }} @endif
				@if ($measure->periodicity==6) {{ trans("common.biannually") }} @endif
				@if ($measure->periodicity==12) {{ trans("common.annually") }} @endif
			</div>
		</div>

		<div class="row">
			<div class="col-lg-1 col-md-2">
				<strong>{{ trans('cruds.measure.fields.owners') }}</strong>
			</div>
			<div class="col-lg-11 col-md-10">
				@foreach($measure->groups as $group)
					{{ $group->name }}
					@if (!$loop->last)
					,
					@endif
				@endforeach
				@if (($measure->groups->count()>0)&&($measure->users->count()>0))
				,
				@endif
				@foreach($measure->users as $user)
					{{ $user->name }}
					@if (!$loop->last)
					,
					@endif
				@endforeach
			</div>
		</div>

		<div class="row">
			<div class="col-12">

				@if ($measure->canMake())
					<a href="/bob/make/{{ $measure->id }}" class="btn btn-success">
						<span class="bi bi-clipboard"></span> {{ trans("common.make") }}
					</a>
					&nbsp;
				@endif

				@if (
						($measure->status===1)
						&&
						(
							(Auth::User()->role===1) || (Auth::User()->role===2)
						)
					)
					<a href="/bob/make/{{ $measure->id }}" class="btn btn-success">
						<span class="bi bi-clipboard"></span> {{ trans("common.validate") }}
					</a>
					&nbsp;
				@endif
				@if (($measure->status===0)||($measure->status===1))
					@if ((Auth::User()->role===1)||(Auth::User()->role===2))
						<a href="/bob/plan/{{ $measure->id }}" class="btn btn-info">
							<span class="bi bi-calendar3"></span> {{ trans("common.plan") }}
						</a>
						&nbsp;
					@endif
				@endif
				@if (Auth::User()->role==1)
				<a href="/bob/edit/{{ $measure->id }}" class="btn btn-primary">
					<span class="bi bi-pencil"></span> {{ trans("common.edit") }}
				</a>
				&nbsp;
				<a href="/bob/clone/{{ $measure->id }}" class="btn btn-warning">
					<span class="bi bi-plus-lg"></span> {{ trans('common.clone') }}
				</a>
				&nbsp;
				<form action="/bob/delete/{{ $measure->id }}" onSubmit="if(!confirm('{{ trans('common.confirm') }}')){return false;}" class="d-inline">
					<button class="btn btn-danger">
						<span class="bi bi-trash"></span> {{ trans("common.delete") }}
					</button>
				</form>
				&nbsp;
				<a class="btn btn-light border" href="/logs/history/bob/{{ $measure->id }}">
					<span class="bi bi-journal-text"></span> {{ trans("common.history") }}
				</a>
				&nbsp;
				@endif
				<a class="btn btn-light border" href="/bob/index">
					<span class="bi bi-x-circle"></span> {{ trans("common.cancel") }}
				</a>
			</div>
		</div>
	</div>

</div>
</div>
@endsection
