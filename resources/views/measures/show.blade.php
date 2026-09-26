@extends("layout")

@section('title', $measure->name)

@section("content")
<div data-role="panel" data-title-caption="{{ trans('cruds.measure.title_singular') }}" data-collapsible="false" data-title-icon="<span class='mif-paste'></span>">

	<div class="grid">
		<div class="row">
			<div class="cell-lg-1 cell-md-2">
				<strong>{{ trans("cruds.measure.fields.clauses") }}</strong>
			</div>
			<div class="cell-lg-7 cell-md-9">
				@foreach($measure->controls as $control)
					<a href="/alice/show/{{ $control->id }}">{{ $control->clause }}</a> - {{ $control->name }}
					@if(!$loop->last)
					<br>
					@endif
				@endforeach
			</div>
		</div>
		<div class="row">
			<div class="cell-lg-1 cell-md-2">
				<strong>{{ trans("cruds.measure.fields.name") }}</strong>
			</div>
			@if ($measure->scope===null)
			<div class="cell-lg-6 cell-md-9">
				{{ $measure->name }}
			</div>
			@else
			<div class="cell-lg-4 cell-md-5">
				{{ $measure->name }}
			</div>
			<div class="cell-lg-1 cell-md-2" align="right">
				<strong>{{ trans("cruds.measure.fields.scope") }}</strong>
			</div>
			<div class="cell-lg-1 cell-md-2">
				<a href="/bob/index?scope={{ $measure->scope }}">
				{{ $measure->scope }}
				</a>
			</div>
			@endif
		</div>
		<div class="row">
			<div class="cell-lg-1 cell-md-2">
				<strong>{{ trans("cruds.measure.fields.objective") }}</strong>
			</div>
			<div class="cell-lg-7 cell-md-9">
				{!! \Parsedown::instance()->text($measure->objective) !!}
			</div>
		</div>

		@if ($measure->attributes!=null)
		<div class="row">
			<div class="cell-lg-1 cell-md-2">
				<strong>{{ trans("cruds.measure.fields.attributes") }}</strong>
			</div>
			<div class="cell-lg-7 cell-md-9">
				{{ $measure->attributes }}
			</div>
		</div>
		@endif

		<div class="row">
			<div class="cell-lg-1 cell-md-2">
				<strong>{{ trans("cruds.measure.fields.input") }}</strong>
			</div>
			<div class="cell-lg-7 cell-md-9">
				{!! \Parsedown::instance()->text($measure->input) !!}
			</div>
		</div>

		<div class="row">
			<div class="cell-lg-1 cell-md-2">
				<strong>{{ trans("cruds.measure.fields.model") }}</strong>
			</div>
			<div class="cell-lg-7 cell-md-9">
				<pre>{!! $measure->model !!}</pre>
			</div>
		</div>

		<div class="row">
			<div class="cell-lg-1 cell-md-2">
				<strong>{{ trans("cruds.measure.fields.plan_date") }}</strong>
			</div>
			<div class="cell-lg-1 cell-md-2 no-wrap">
				{{ $measure->plan_date }}
			</div>

			<div class="cell-lg-1 cell-md-2 text-right">
				<strong>{{ trans("cruds.measure.fields.realisation_date") }}</strong>
			</div>
			<div class="cell-lg-1 cell-md-2 no-wrap">
				{{ $measure->realisation_date }}
			</div>

			<div class="cell-lg-1 cell-md-2 text-right">
				<strong>{{ trans("common.previous") }}</strong>
				<br>
				<strong>{{ trans("common.next") }}</strong>
			</div>
			<div class="cell-lg-1 cell-md-2 no-wrap">
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
				<div class="cell-lg-1 cell-md-2">
					<strong>{{ trans("cruds.measure.fields.observations") }}</strong>
				</div>
				<div class="cell-lg-5 cell-md-9">
					<pre>{!! $measure->observations !!}</pre>
				</div>
			</div>
		@endif

		@if ($documents->isNotEmpty())
			<div class="row">
				<div class="cell-lg-1 cell-md-2">
					<strong>{{ trans("cruds.measure.fields.evidence") }}</strong>
				</div>
				<div class="cell-lg-5 cell-md-8">
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
			<div class="cell-lg-1 cell-md-2">
				<strong>{{ trans("cruds.measure.fields.note") }}</strong>
			</div>
			<div class="cell-2">
				{{ fmod($measure->note, 1) == 0 ? intval($measure->note) : $measure->note }}
			</div>
		</div>


		<div class="row">
			<div class="cell-lg-1 cell-md-2">
				<strong>{{ trans("cruds.measure.fields.indicator") }}</strong>
			</div>
			<div class="cell-lg-6 cell-md-8">
				<pre>{{ $measure->indicator }}</pre>
			</div>
		</div>

		@if ($measure->score!==null)
			<div class="row">
				<div class="cell-lg-1 cell-md-2">
					<strong>{{ trans("cruds.measure.fields.score") }}</strong>
				</div>
				<div class="cell-lg-6 cell-md-8">
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
				<div class="cell-lg-1 cell-md-2">
					<strong>{{ trans("cruds.measure.fields.action_plan") }}</strong>
				</div>
				<div class="cell-lg-6 cell-md-8">
					{!! \Parsedown::instance()->text($measure->action_plan) !!}
				</div>
			</div>
		@endif

		<div class="row">
			<div class="cell-lg-1 cell-md-2">
				<strong>{{ trans('cruds.measure.fields.periodicity') }}</strong>
			</div>
			<div class="cell-lg-6 cell-md-8">
				@if ($measure->periodicity==0) {{ trans("common.once") }} @endif
				@if ($measure->periodicity==-1) {{ trans("common.weekly") }} @endif
				@if ($measure->periodicity==1) {{ trans("common.monthly") }} @endif
				@if ($measure->periodicity==3) {{ trans("common.quarterly") }} @endif
				@if ($measure->periodicity==6) {{ trans("common.biannually") }} @endif
				@if ($measure->periodicity==12) {{ trans("common.annually") }} @endif
			</div>
		</div>

		<div class="row">
			<div class="cell-lg-1 cell-md-2">
				<strong>{{ trans('cruds.measure.fields.owners') }}</strong>
			</div>
			<div class="cell-lg-6 cell-md-8">
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
			<div class="cell-12">

				@if ($measure->canMake())
					<a href="/bob/make/{{ $measure->id }}" class="button success">
						<span class="mif-assignment"></span>
						&nbsp;
						{{ trans("common.make") }}
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
					<a href="/bob/make/{{ $measure->id }}" class="button success">
						<span class="mif-assignment"></span>
						&nbsp;
						{{ trans("common.validate") }}
					</a>
					&nbsp;
				@endif
				@if (($measure->status===0)||($measure->status===1))
					@if ((Auth::User()->role===1)||(Auth::User()->role===2))
						<a href="/bob/plan/{{ $measure->id }}" class="button info">
							<span class="mif-calendar"></span>
							&nbsp;
							{{ trans("common.plan") }}
						</a>
						&nbsp;
					@endif
				@endif
				@if (Auth::User()->role==1)
				<a href="/bob/edit/{{ $measure->id }}" class="button primary">
					<span class="mif-pencil"></span>
					&nbsp;
					{{ trans("common.edit") }}
				</a>
				&nbsp;
				<a href="/bob/clone/{{ $measure->id }}" class="button warning">
					<span class="mif-add-lib"></span>
					&nbsp;
					{{ trans('common.clone') }}
				</a>
				&nbsp;
				<form action="/bob/delete/{{ $measure->id }}" onSubmit="if(!confirm('{{ trans('common.confirm') }}')){return false;}" class="d-inline">
					<button class="button alert">
						<span class="mif-fire"></span>
						&nbsp;
						{{ trans("common.delete") }}
					</button>
				</form>
				&nbsp;
				<a class="button" href="/logs/history/bob/{{ $measure->id }}">
					<span class="mif-log-file"></span>
					&nbsp;
					{{ trans("common.history") }}
				</a>
				&nbsp;
				@endif
				<a class="button" href="/bob/index">
					<span class="mif-cancel"></span>
					&nbsp;
					{{ trans("common.cancel") }}
				</a>
			</div>
		</div>
	</div>

</div>
@endsection
