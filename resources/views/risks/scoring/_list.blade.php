{{-- "Classification" tab of the settings page --}}
    <div class="container-fluid px-0 mb-2">
        <div class="row">
            <div class="col-lg-12 text-md-end">
                @include('partials.help', ['page' => 'risks.scoring_index'])
                <button class="btn btn-primary" onclick="location.href='/risk/scoring/create'">
                    <span class="bi bi-plus-lg"></span>
                    {{ trans('common.new') }}
                </button>
            </div>
        </div>
    </div>

    <table class="table table-striped table-hover border table-bordered">
        <thead>
            <tr>
                <th width="4%"  align="center">{{ trans("common.active") }}</th>
                <th width="25%">{{ trans("cruds.risk_scoring.fields.name") }}</th>
                <th width="18%">{{ trans("cruds.risk_scoring.fields.formula") }}</th>
                <th width="28%">{{ trans("cruds.risk_scoring.fields.levels") }}</th>
                <th width="15%">{{ trans("cruds.risk_scoring.fields.thresholds") }}</th>
                <th width="10%"></th>
            </tr>
        </thead>
        <tbody>
        @forelse ($configs as $config)
        <tr>
            {{-- Indicateur actif --}}
            <td align="center">
                @if ($config->is_active)
                    <span class="bi bi-check-lg text-success" style="font-size:1.2rem"
                          title="{{ trans('common.active') }}"></span>
                @endif
            </td>

            {{-- Nom --}}
            <td>
                <b><a href="/risk/scoring/{{ $config->id }}/edit">{{ $config->name }}</a></b>
                @if ($config->is_active)
                    &nbsp;<span class="badge text-bg-primary">{{ trans('common.active') }}</span>
                @endif
            </td>

            {{-- Formule --}}
            <td>{{ $formulas[$config->formula]['label'] ?? $config->formula }}</td>

            {{-- Résumé des niveaux --}}
            <td>
                @if ($config->usesLikelihood())
                    <small>
                        <b>{{ trans('cruds.risk.fields.exposure') }} :</b>
                        {{ count($config->exposure_levels ?? []) }} {{ trans('cruds.risk_scoring.fields.levels') }}
                    </small>
                    <br>
                    <small>
                        <b>{{ trans('cruds.risk.fields.vulnerability') }} :</b>
                        {{ count($config->vulnerability_levels ?? []) }} {{ trans('cruds.risk_scoring.fields.levels') }}
                    </small>
                @else
                    <small>
                        <b>{{ trans('cruds.risk.fields.probability') }} :</b>
                        {{ count($config->probability_levels ?? []) }} {{ trans('cruds.risk_scoring.fields.levels') }}
                    </small>
                @endif
                <br>
                <small>
                    <b>{{ trans('cruds.risk.fields.impact') }} :</b>
                    {{ count($config->impact_levels ?? []) }} {{ trans('cruds.risk_scoring.fields.levels') }}
                </small>
            </td>

            {{-- Seuils --}}
            <td>
                @foreach ($config->risk_thresholds as $i => $t)
                    @php $prevMax = $i > 0 ? ($config->risk_thresholds[$i-1]['max'] ?? null) : null; @endphp
                        <span data-bs-placement="top" title="{{ $t['max'] !== null
                                  ? 'Score ≤ ' . $t['max']
                                  : 'Score > ' . ($prevMax ?? 0) }}" data-bs-toggle="tooltip" class="badge"
                              style="
                                background:{{ $t['color'] ?? '#cccccc' }};
                                color:{{ contrast_color($t['color'] ?? '#cccccc') }};
padding:2px 8px;
                            font-size:1rem;

                                "
                              >
                            {{ $t['label'] ?? $t['level'] ?? '?' }}
                        </span>
                @endforeach
            </td>

            {{-- Actions --}}
            <td align="center" style="white-space:nowrap">
                <a href="/risk/scoring/{{ $config->id }}/edit" class="btn btn-sm btn-primary"
                   title="{{ trans('common.edit') }}">
                    <span class="bi bi-pencil-square"></span>
                </a>

                @unless ($config->is_active)
                    &nbsp;
                    {{-- Activation : POST avec CSRF --}}
                    <form action="/risk/scoring/{{ $config->id }}/activate"
                          method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-success"
                                title="{{ trans('cruds.risk_scoring.activate') }}">
                            <span class="bi bi-check-lg"></span>
                        </button>
                    </form>
                    &nbsp;
                    {{-- Suppression : GET, même pattern que /bob/delete/{id} --}}
                    <form action="/risk/scoring/{{ $config->id }}/delete"
                          class="d-inline"
                          onsubmit="if(!confirm('{{ trans('common.confirm') }}')){return false;}">
                        <button type="submit" class="btn btn-sm btn-danger"
                                title="{{ trans('common.delete') }}">
                            <span class="bi bi-trash"></span>
                        </button>
                    </form>
                @endunless
            </td>
        </tr>
        @empty
            <tr>
                <td colspan="6" class="text-center text-muted">
                    {{ trans('cruds.risk_scoring.empty') }}
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>
