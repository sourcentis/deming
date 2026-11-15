@extends("layout")

@section("content")
    <div class="card">
    <div class="card-header">@include('partials.help', ['page' => 'measures.index'])<i class="bi bi-clipboard-check"></i> {{ trans("cruds.measure.list")}}</div>
    <div class="card-body">
    <div class="container-fluid px-0 mb-2">
        <div class="row align-items-center">
            <div class="col-lg-2 col-md-6">
                <select class="form-select select2" id='domain' name="domain_id">
                    <option value="0">-- {{ trans("cruds.measure.fields.choose_domain")}} --</option>
                    @foreach ($domains as $domain)
                        <option value="{{ $domain->id }}"
                            @if (intval(Session::get("domain"))==$domain->id)
                                selected
                            @endif >
                            {{ $domain->title }} - {{ $domain->description }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-lg-2 col-md-6">
                <select class="form-select select2" id='clause' name="clause">
                    <option value="none">-- {{ trans("cruds.measure.fields.choose_clause")}} --</option>
                    @foreach ($clauses as $clause)
                        <option
                            @if (Session::get("clause")==trim($clause))
                                selected
                            @endif >
                            {{ $clause }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-lg-2 col-md-6">
                <select class="form-select select2" id='scope' name="scope">
                    <option value="none">-- {{ trans("cruds.measure.fields.choose_scope")}} --</option>
                    @foreach ($scopes as $scope)
                        <option
                            @if (Session::get("scope")==$scope)
                                selected
                            @endif >
                            {{ $scope }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-lg-2 col-md-6">
                <select class="form-select select2" id='cur_period' name="period">
                    <option value="99"
                        @if (Session::get("period")==="99")
                            selected
                        @endif
                    >-- {{ trans("cruds.measure.fields.choose_period") }} --</option>
                        @for ($i = -12; $i < 12; $i++)
                            <option value="{{ $i }}"
                            @if ((Session::get("period"))==strval($i))
                                selected
                            @endif
                            >
                            {{ now()->day(1)->addMonth($i)->format("M Y") }}
                            </option>
                        @endfor
                    </select>
                </div>
            <div class="col-lg col-md-6 mt-2 mt-lg-0">
                 <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="status" value="0" id="status0" {{ (Session::get("status")=="0") ? 'checked' : '' }}><label class="form-check-label" for="status0">{{ trans("cruds.measure.fields.status_all") }}</label></div>
                 <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="status" value="1" id="status1" {{ (Session::get("status")=="1") ? 'checked' : '' }}><label class="form-check-label" for="status1">{{ trans("cruds.measure.fields.status_done") }}</label></div>
                 <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="status" value="2" id="status2" {{ (Session::get("status")=="2") ? 'checked' : '' }}><label class="form-check-label" for="status2">{{ trans("cruds.measure.fields.status_todo") }}</label></div>
            </div>
            <div class="col-auto ms-auto">
			@if ((Auth::User()->role==1)||(Auth::User()->role==2))
				<button class="btn btn-primary" data-href="/bob/create">
		            <span class="bi bi-plus-lg"></span>
					{{ trans('common.new') }}
               </button>
            @endif
			</div>
        </div>
    </div>

    <table data-order="[[5,&quot;asc&quot;]]" data-page-length="100"
        id="controls"
        class="table table-striped table-hover table-bordered datatable">
        <thead>
            <tr>
                <th>{{ trans("cruds.measure.fields.clauses") }}</th>
                <th data-priority="1">{{ trans("cruds.measure.fields.name") }}</th>
                <th>{{ trans("cruds.measure.fields.scope") }}</th>
                <th class="dt-hidden">{{ trans("cruds.measure.fields.attributes") }}</th>
                <th>{{ trans("cruds.measure.fields.score") }}</th>
                <th data-priority="2">{{ trans("cruds.measure.fields.planned") }}</th>
                <th>{{ trans("cruds.measure.fields.realized") }}</th>
                <th>{{ trans("cruds.measure.fields.next") }}</th>
                <th class="dt-hidden">{{ trans("cruds.measure.fields.note") }}</th>
                <th class="dt-hidden">{{ trans("cruds.measure.fields.periodicity") }}</th>
            </tr>
        </thead>
        <tbody>
        @foreach($measures as $measure)
        <tr>
            <td class="wrap-column">
                @if($measure->controls->count() > 3)
                    @php
                        $clauses = $measure->controls->pluck('clause');
                        // Grouper par 3 et joindre avec retour à la ligne
                        $formattedClauses = $clauses->chunk(3)->map(function($chunk) {
                            return $chunk->implode(', ');
                        })->implode('<br>');
                    @endphp
                    <span data-bs-placement="top" data-bs-html="true" title="{{ $formattedClauses }}" data-bs-toggle="tooltip"
                        style="cursor: help; border-bottom: 1px dotted #999;"
                    >
                        @foreach($measure->controls->take(2) as $control)
                        <a id="{{ $control['clause'] }}" href="/alice/show/{{ $control['id'] }}">
                            {{ $control['clause'] }}
                        </a>
                        @if (!$loop->last)
                        ,
                        @endif
                        @endforeach
                        , ...
                    </span>
                @else
                    @foreach($measure->controls as $control)
                    <a id="{{ $control['clause'] }}" href="/alice/show/{{ $control['id'] }}">
                        {{ $control['clause'] }}
                    </a>
                    @if (!$loop->last)
                    ,
                    @endif
                    @endforeach
                @endif
            </td>

            <td>
                    {{ $measure->name }}
            </td>
            <td>
                    {{ $measure->scope }}
            </td>
            <td>{{ $measure->attributes }}</td>
            <td>
                <center id="{{ $measure->score }}">
                    @if ($measure->action_id!=null)
                        <a href="/action/show/{{ $measure->action_id }}" class="no-underline">
                    @endif
                    @if ($measure->score==1)
                        &#128545;
                    @elseif ($measure->score==2)
                        &#128528;
                    @elseif ($measure->score==3)
                        <span style="filter: sepia(1) saturate(5) hue-rotate(70deg)">&#128512;</span>
                    @else
                        &#9675; <!-- &#9899; -->
                    @endif
                    @if ($measure->action_id!=null)
                    </a>
                    @endif
                </center>
            </td>
            <td>
                <!-- format in red when month passed -->
                @if (($measure->status === 0)||($measure->status === 1))
                <a id="{{ $measure->plan_date }}" href="/bob/show/{{$measure->id}}" style="white-space: nowrap;">
                <b> @if (today()->lte($measure->plan_date))
                        <font color="green">{{ $measure->plan_date }}</font>
                    @else
                        <font color="red">{{ $measure->plan_date }}</font>
                    @endif
                </b>
                </a>
                @else
                    {{ $measure->plan_date }}
                @endif
            </td>
            <td>
                <b id="{{ $measure->realisation_date }}">
                    <a href="/bob/show/{{$measure->id}}" style="white-space: nowrap;">
                        {{ $measure->realisation_date }}
                    </a>
                    @if ( ($measure->status===1 )&& ((Auth::User()->role===1)||(Auth::User()->role===2)))
                        &nbsp;
                        <a href="/bob/make/{{ $measure->id }}"><i class="bi bi-hourglass-split"></i></a>
                    @endif
                </b>
            </td>
            <td>
                <b id="{{ $measure->next_date }}">
                    @if ($measure->next_id!=null)
                    <a href="/bob/show/{{$measure->next_id}}" style="white-space: nowrap;">
                        {{ $measure->next_date }}
                    </a>
                    @endif
                </b>
            </td>
            <td class="text-end" data-order="{{ $measure->note ?? -1 }}">
                @if ($measure->note !== null)
                    {{ fmod($measure->note, 1) == 0 ? intval($measure->note) : $measure->note }}
                @endif
            </td>
            <td data-order="{{ $measure->periodicity }}">
                @switch($measure->periodicity)
                    @case(0) {{ trans('common.once') }} @break
                    @case(-1) {{ trans('common.weekly') }} @break
                    @case(1) {{ trans('common.monthly') }} @break
                    @case(3) {{ trans('common.quarterly') }} @break
                    @case(6) {{ trans('common.biannually') }} @break
                    @case(12) {{ trans('common.annually') }} @break
                    @default {{ $measure->periodicity }}
                @endswitch
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
</div>
</div>
<script @nonce>
document.addEventListener("DOMContentLoaded", () => {
  const getParam = (k) => new URLSearchParams(location.search).get(k) ?? '';
  const table = initDataTable(document.getElementById('controls'));

  // --- Applique la recherche passée dans l'URL
  const searchValue = getParam('search');
  if (searchValue) {
    table.search(searchValue).draw();
  }

  // --- Snapshot de tous les filtres (UI -> fallback URL)
  function snapshotFilters() {
    return {
      domain : document.getElementById('domain')?.value ?? getParam('domain'),
      clause : (document.getElementById('clause')?.value ?? getParam('clause')).trim(),
      scope  : (document.getElementById('scope')?.value  ?? getParam('scope')).trim(),
      period : document.getElementById('cur_period')?.value ?? getParam('period'),
      status : (document.getElementById('status0')?.checked ? '0' :
               document.getElementById('status1')?.checked ? '1' :
               document.getElementById('status2')?.checked ? '2' : getParam('status')),
      search : table.search()
    };
  }

  // --- Navigation: toujours poster TOUTES les valeurs
  function navigateWithAll(patch={}) {
    const next = new URL(location.href);
    next.pathname = '/bob/index';

    const all = { ...snapshotFilters(), ...patch };
    for (const [k,v] of Object.entries(all)) {
      if (v == null || String(v) === '') next.searchParams.delete(k);
      else next.searchParams.set(k, String(v));
    }
    if (next.toString() !== location.href) location.assign(next.toString());
  }

  const bindChange = (id, key, coerce = v => v) => {
    const el = document.getElementById(id);
    if (!el) return;
    el.addEventListener('change', () => navigateWithAll({ [key]: coerce(el.value) }));
  };

  bindChange('domain', 'domain', v => String(v));
  bindChange('clause', 'clause', v => String(v).trim());
  bindChange('scope',  'scope',  v => String(v).trim());
  bindChange('cur_period', 'period', v => String(v));

  // Radios status
  ['0', '1', '2'].forEach(value => {
    document.getElementById('status' + value)
      ?.addEventListener('change', () => navigateWithAll({ status: value }));
  });
});
</script>
@endsection
