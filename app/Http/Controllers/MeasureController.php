<?php

namespace App\Http\Controllers;

use App\Exports\MeasuresExport;
use App\Models\Action;
use App\Models\Control;
use App\Models\Domain;
use App\Models\Measure;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpWord\TemplateProcessor as PhpWordTemplateProcessor;

class MeasureController extends Controller
{
    /**
     * Display a listing of measures (audit instances).
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        // Not for API
        abort_if(Auth::user()->isAPI(), Response::HTTP_FORBIDDEN, '403 Forbidden');

        // Domain filter
        $domain = $request->get('domain');
        if ($domain !== null) {
            $domain = intval($domain);
            if ($domain === 0) {
                $request->session()->forget('domain');
                $domain = null;
            } else {
                $request->session()->put('domain', $domain);
            }
        } else {
            $domain = $request->session()->get('domain');
        }

        // Clause filter
        $clause = $request->get('clause');
        if ($clause !== null) {
            if ($clause === 'none') {
                $request->session()->forget('clause');
                $clause = null;
            } else {
                $request->session()->put('clause', $clause);
            }
        } else {
            $clause = $request->session()->get('clause');
        }

        // Scope filter
        $scope = $request->get('scope');
        if ($scope !== null) {
            if ($scope === 'none') {
                $request->session()->forget('scope');
                $scope = null;
            } else {
                $request->session()->put('scope', $scope);
            }
        } else {
            $scope = $request->session()->get('scope');
        }

        // Period filter
        $period = $request->get('period');
        if ($period !== null) {
            $period = intval($period);
            $request->session()->put('period', $period);
        } else {
            $period = $request->session()->get('period');
            if ($period === null) {
                $request->session()->put('period', 99);
            }
        }

        // Status filter
        $status = $request->get('status');
        if ($status !== null) {
            $request->session()->put('status', $status);
        } else {
            $status = $request->session()->get('status');
            if ($status === null) {
                $status = '2';
            }
        }

        // Late filter
        $late = $request->get('late');
        if ($late !== null) {
            $request->session()->put('status', '2');
            $status = '2';
        }

        $domains = Domain::All();

        $clauses = DB::table('controls')
            ->when($domain !== null, fn ($q) => $q->where('domain_id', $domain))
            ->whereNotNull('clause')
            ->where('clause', '!=', '')
            ->distinct()
            ->orderBy('clause')
            ->pluck('clause');

        $domainTitle = $request->input('domain_title');
        if ($domainTitle !== null) {
            $domainId = Domain::where('title', $domainTitle)->value('id');
            if ($domainId) {
                $request->session()->put('domain', $domainId);
            }
        }

        $scopes = DB::table('measures')
            ->whereNotNull('scope')
            ->where('scope', '<>', '');
        if (!Auth::user()->seesAllData()) {
            $scopes = $scopes
                ->leftJoin('measure_user', 'measures.id', '=', 'measure_user.measure_id')
                ->leftJoin('measure_user_group', 'measures.id', '=', 'measure_user_group.measure_id')
                ->leftJoin('user_user_group', 'measure_user_group.user_group_id', '=', 'user_user_group.user_group_id')
                ->where(function ($query) {
                    $query->where('measure_user.user_id', '=', Auth::user()->id)
                        ->orWhere('user_user_group.user_id', '=', Auth::user()->id);
                });
        }
        $scopes = $scopes
            ->whereIn('status', [0, 1])
            ->distinct()
            ->orderBy('scope')
            ->pluck('scope');

        // Build measures query
        $measures = DB::table('measures as m1')
            ->leftjoin('measures as m2', 'm1.next_id', '=', 'm2.id')
            ->leftjoin(
                'control_measure',
                'control_measure.measure_id',
                '=',
                'm1.id'
            )
            ->leftjoin('controls', 'control_measure.control_id', '=', 'controls.id')
            ->leftjoin('domains', 'controls.domain_id', '=', 'domains.id');

        // Filter to the user's own measures, unless they see all data
        if (!Auth::user()->seesAllData()) {
            $measures = $measures
                ->leftJoin('measure_user', 'm1.id', '=', 'measure_user.measure_id')
                ->leftJoin('measure_user_group', 'm1.id', '=', 'measure_user_group.measure_id')
                ->leftJoin('user_user_group', 'measure_user_group.user_group_id', '=', 'user_user_group.user_group_id')
                ->where(function ($query) {
                    $query->where('measure_user.user_id', '=', Auth::user()->id)
                        ->orWhere('user_user_group.user_id', '=', Auth::user()->id);
                });
        }

        // Filter on domain
        if ($domain !== null && $domain !== 0) {
            $measures = $measures->where('controls.domain_id', '=', $domain);
        }

        // Filter on clause
        if ($clause !== null) {
            $measures = $measures->where('clause', '=', $clause);
        }

        // Filter on scope
        if ($scope !== null) {
            $measures = $measures->where('m1.scope', '=', $scope);
        }

        // Filter on period
        if ($period !== null && $period !== 99) {
            $measures = $measures
                ->where(
                    'm1.plan_date',
                    '>=',
                    (new Carbon('first day of this month'))
                        ->addMonths($period)
                        ->format('Y-m-d')
                )
                ->where(
                    'm1.plan_date',
                    '<',
                    (new Carbon('first day of next month'))
                        ->addMonths($period)
                        ->format('Y-m-d')
                );
        }

        // Filter on status
        if ($late !== null) {
            $measures = $measures
                ->where('m1.plan_date', '<', Carbon::today()->format('Y-m-d'))
                ->whereIn('m1.status', [0, 1]);
        } elseif ($status === '1') {
            if (Auth::user()->role === 5) {
                $measures = $measures->whereIn('m1.status', [1, 2]);
            } else {
                $measures = $measures->where('m1.status', 2);
            }
        } elseif ($status === '2') {
            if (Auth::user()->role === 5) {
                $measures = $measures->where('m1.status', 0);
            } else {
                $measures = $measures->whereIn('m1.status', [0, 1]);
            }
        }

        // Join actions
        $measures = $measures->leftjoin('actions', 'actions.measure_id', '=', 'm1.id');

        $measures = $measures
            ->select([
                'm1.id',
                'm1.name',
                'm1.scope',
                'm1.attributes',
                'm1.note',
                'm1.periodicity',
                'm1.plan_date',
                'm1.realisation_date',
                'm1.score as score',
                'm1.status',
                'actions.id as action_id',
                'm2.id as next_id',
                'm2.plan_date as next_date',
            ])
            ->orderBy('m1.id')
            ->distinct()
            ->get();

        // Fetch controls for all measures in one query
        $measureControls = DB::table('control_measure')
            ->select(['measure_id', 'control_id', 'clause'])
            ->leftjoin('controls', 'controls.id', '=', 'control_id')
            ->whereIn('measure_id', $measures->pluck('id'))
            ->orderBy('clause')
            ->get();

        // Group controls by measure_id
        $controlsByMeasureId = $measureControls->groupBy('measure_id');

        foreach ($measures as $measure) {
            $measure->controls = $controlsByMeasureId
                ->get($measure->id, collect())
                ->map(function ($measureControl) {
                    return [
                        'id'     => $measureControl->control_id,
                        'clause' => $measureControl->clause,
                    ];
                });
        }

        return view('measures.index')
            ->with('measures', $measures)
            ->with('clauses', $clauses)
            ->with('scopes', $scopes)
            ->with('domains', $domains);
    }

    /**
     * Show the form for creating a new measure (audit instance).
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        abort_if(
            (Auth::user()->role !== 1) && (Auth::user()->role !== 2),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden'
        );

        $all_controls = DB::table('controls')
            ->select('id', 'clause', 'name')
            ->orderBy('clause')
            ->get();

        $scopes = DB::table('measures')
            ->whereNotNull('scope')
            ->where('scope', '<>', '')
            ->whereIn('status', [0, 1])
            ->distinct()
            ->orderBy('scope')
            ->pluck('scope');

        $values = [];
        $attributes = DB::table('attributes')
            ->select('values')
            ->union(DB::table('controls')
                ->select(DB::raw('attributes as value')))
            ->get();
        foreach ($attributes as $key) {
            foreach (explode(' ', $key->values) as $value) {
                if (strlen($value) > 0) {
                    array_push($values, $value);
                }
            }
        }
        sort($values);
        $values = array_unique($values);

        $users = DB::table('users')
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $groups = DB::table('user_groups')
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $owners = collect();
        foreach ($users as $user) {
            $owners->put('USR_' . $user->id, $user->name);
        }
        foreach ($groups as $group) {
            $owners->put('GRP_' . $group->id, $group->name);
        }

        return view('measures.create')
            ->with('scopes', $scopes)
            ->with('all_controls', $all_controls)
            ->with('attributes', $values)
            ->with('owners', $owners);
    }

    /**
     * Store a newly created measure (audit instance).
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        abort_if(
            (Auth::user()->role !== 1) && (Auth::user()->role !== 2),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden'
        );

        $this->validate(
            $request,
            [
                'name'        => 'required|min:3|max:255',
                'scope'       => 'max:32',
                'objective'   => 'required',
                'plan_date'   => 'required',
                'periodicity' => 'required|integer|in:-1,0,1,3,6,12',
            ]
        );

        $measure = new Measure();
        $measure->name        = request('name');
        $measure->scope       = request('scope');
        $measure->objective   = request('objective');
        $measure->attributes  = request('attributes') !== null ? implode(' ', request('attributes')) : null;
        $measure->input       = request('input');
        $measure->model       = request('model');
        $measure->plan_date   = request('plan_date');
        $measure->action_plan = request('action_plan');
        $measure->periodicity = request('periodicity');
        $measure->save();

        $users = collect();
        foreach ($request->input('owners', []) as $owner) {
            if (str_starts_with($owner, 'USR_')) {
                $users->push(intval(substr($owner, 4)));
            }
        }
        $measure->users()->sync($users);

        $groups = collect();
        foreach ($request->input('owners', []) as $owner) {
            if (str_starts_with($owner, 'GRP_')) {
                $groups->push(intval(substr($owner, 4)));
            }
        }
        $measure->groups()->sync($groups);

        $measure->controls()->sync($request->input('controls', []));

        return redirect('/bob/index');
    }

    /**
     * Display a control
     *
     * @return \Illuminate\View\View
     */
    public function show(int $id)
    {
        abort_if(
            ! Measure::isViewableBy(Auth::user(), $id),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden'
        );

        $measure = Measure::find($id);

        abort_if($measure === null, Response::HTTP_NOT_FOUND, '404 Not Found');

        if ($measure->next_id !== null) {
            $next_measure = DB::table('measures')
                ->select('id', 'plan_date')
                ->where('id', '=', $measure->next_id)
                ->first();
        } else {
            $next_measure = null;
        }

        $prev_measure = DB::table('measures')
            ->select('id', 'plan_date')
            ->where('next_id', '=', $id)
            ->first();

        $documents = DB::table('documents')->where('measure_id', $id)->get();

        return view('measures.show')
            ->with('measure', $measure)
            ->with('next_id', $next_measure !== null ? $next_measure->id : null)
            ->with(
                'next_date',
                $next_measure !== null ? $next_measure->plan_date : null
            )
            ->with('prev_id', $prev_measure !== null ? $prev_measure->id : null)
            ->with(
                'prev_date',
                $prev_measure !== null ? $prev_measure->plan_date : null
            )
            ->with('documents', $documents);
    }

    /**
     * Show the form for editing a measure (audit instance).
     *
     * @return \Illuminate\View\View
     */
    public function edit(int $id)
    {
        abort_if(
            ! Auth::user()->isAdmin(),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden'
        );

        $measure = Measure::find($id);

        abort_if($measure === null, Response::HTTP_NOT_FOUND, '404 Not Found');

        $documents = DB::table('documents')->where('measure_id', $id)->get();

        $ids = DB::table('measures')
            ->orderBy('id')
            ->pluck('id');

        $all_controls = DB::table('controls')
            ->select('id', 'clause', 'name')
            ->orderBy('clause')
            ->get();

        $users = DB::table('users')
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $groups = DB::table('user_groups')
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $owners = collect();
        foreach ($users as $user) {
            $owners->put('USR_' . $user->id, $user->name);
        }
        foreach ($groups as $group) {
            $owners->put('GRP_' . $group->id, $group->name);
        }

        $controls = DB::table('control_measure')
            ->select('control_id')
            ->where('measure_id', $id)
            ->pluck('control_id')
            ->toArray();

        $scopes = DB::table('measures')
            ->select('scope')
            ->whereNotNull('scope')
            ->where('scope', '<>', '')
            ->whereIn('status', [0, 1])
            ->distinct()
            ->orderBy('scope')
            ->pluck('scope')
            ->toArray();

        $values = [];
        $attributes = DB::table('attributes')->select('values')
            ->union(DB::table('controls')
                ->select(DB::raw('attributes as value')))
            ->get();
        foreach ($attributes as $key) {
            foreach (explode(' ', $key->values) as $value) {
                if (strlen($value) > 0) {
                    array_push($values, $value);
                }
            }
        }
        sort($values);
        $values = array_unique($values);

        return view('measures.edit')
            ->with('measure', $measure)
            ->with('documents', $documents)
            ->with('scopes', $scopes)
            ->with('all_controls', $all_controls)
            ->with('controls', $controls)
            ->with('ids', $ids)
            ->with('attributes', $values)
            ->with('owners', $owners);
    }

    /**
     * Clone a measure (audit instance).
     *
     * @return \Illuminate\View\View
     */
    public function clone(Request $request)
    {
        abort_if(
            !Auth::user()->isAdmin() && !Auth::user()->isUser(),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden'
        );

        $all_controls = DB::table('controls')
            ->select('id', 'clause', 'name')
            ->orderBy('id')
            ->get();

        $scopes = DB::table('measures')
            ->select('scope')
            ->whereNotNull('scope')
            ->where('scope', '<>', '')
            ->whereIn('status', [0, 1])
            ->distinct()
            ->orderBy('scope')
            ->pluck('scope');

        $values = [];
        $attributes = DB::table('attributes')->select('values')
            ->union(DB::table('controls')
                ->select(DB::raw('attributes as value')))
            ->get();
        foreach ($attributes as $key) {
            foreach (explode(' ', $key->values) as $value) {
                if (strlen($value) > 0) {
                    array_push($values, $value);
                }
            }
        }
        sort($values);
        $values = array_unique($values);

        $users = DB::table('users')
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $groups = DB::table('user_groups')
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $owners = collect();
        foreach ($users as $user) {
            $owners->put('USR_' . $user->id, $user->name);
        }
        foreach ($groups as $group) {
            $owners->put('GRP_' . $group->id, $group->name);
        }

        $measure = Measure::query()->find($request->id);

        abort_if($measure === null, Response::HTTP_NOT_FOUND, '404 Not Found');

        $request->merge(
            $measure->only(
                [
                    'name', 'scope', 'objective',
                    'input', 'periodicity', 'model', 'action_plan',
                    'plan_date',
                ]
            )
        );
        $request->merge(['controls' => $measure->controls()->pluck('id')->toArray()]);
        $request->merge(['attributes' => explode(' ', $measure->attributes)]);

        $items = [];
        foreach ($measure->users as $user) {
            array_push($items, 'USR_' . $user->id);
        }
        foreach ($measure->groups as $group) {
            array_push($items, 'GRP_' . $group->id);
        }
        $request->merge(['owners' => $items]);

        $request->flash();

        return view('measures.create')
            ->with('scopes', $scopes)
            ->with('all_controls', $all_controls)
            ->with('attributes', $values)
            ->with('owners', $owners);
    }

    /**
     * Remove a measure (audit instance).
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(int $id)
    {
        abort_if(
            Auth::user()->role !== 1,
            Response::HTTP_FORBIDDEN,
            '403 Forbidden'
        );

        $measure = Measure::find($id);

        abort_if($measure === null, Response::HTTP_NOT_FOUND, '404 Not Found');

        foreach ($measure->documents as $document) {
            \Log::debug(storage_path('docs/' . $document->id));
            unlink(storage_path('docs/' . $document->id));
        }
        $measure->documents()->delete();

        Measure::where('next_id', $measure->id)
            ->update(['next_id' => $measure->next_id]);

        DB::Table('control_measure')
            ->where('measure_id', '=', $measure->id)
            ->delete();

        DB::Table('measure_user_group')
            ->where('measure_id', '=', $measure->id)
            ->delete();

        DB::Table('actions')
            ->where('measure_id', $measure->id)
            ->update(['measure_id' => null]);

        $measure->delete();

        return redirect('/bob/index');
    }

    public function history()
    {
        abort_if(in_array(Auth::user()->role, [4, 5]), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $measuresData = DB::table('measures')
            ->select(
                'measures.id',
                'measures.score',
                'measures.observations',
                'measures.realisation_date',
                'measures.plan_date',
                'measures.periodicity',
                'controls.clause'
            )
            ->leftJoin('control_measure', 'measures.id', '=', 'control_measure.measure_id')
            ->join('controls', 'control_measure.control_id', '=', 'controls.id')
            ->get();

        $measures = $measuresData->groupBy('id')->map(function ($measureGroup) {
            $measure = $measureGroup->first();
            $measure->controls = $measureGroup->pluck('clause')->filter()->values();
            return $measure;
        })->values();

        $expandedMeasures = collect();
        foreach ($measures as $measure) {
            $expandedMeasures->push($measure);

            if ($measure->realisation_date === null) {
                if ($measure->periodicity === -1) {
                    for ($i = 1; $i <= 52; $i++) {
                        $repeated = clone $measure;
                        $repeated->id = null;
                        $repeated->score = null;
                        $repeated->observations = null;
                        $repeated->realisation_date = null;
                        $repeated->plan_date = Carbon::parse($measure->plan_date)->addDays($i * 7);
                        $expandedMeasures->push($repeated);
                    }
                } elseif (($measure->periodicity > 0) && ($measure->periodicity <= 12)) {
                    for ($i = 1; $i <= 12 / $measure->periodicity; $i++) {
                        $repeated = clone $measure;
                        $repeated->id = null;
                        $repeated->score = null;
                        $repeated->observations = null;
                        $repeated->realisation_date = null;
                        $repeated->plan_date = Carbon::parse($measure->plan_date)->addMonthsNoOverflow($i * $measure->periodicity);
                        $expandedMeasures->push($repeated);
                    }
                }
            }
        }

        return view('measures.history')
            ->with('measures', $expandedMeasures);
    }

    /**
     * Radar by domain.
     */
    public function domains(Request $request)
    {
        abort_if(
            Auth::user()->role === 4 || Auth::user()->role === 5,
            Response::HTTP_FORBIDDEN,
            '403 Forbidden'
        );

        $scope = $request->get('scope');
        if ($scope !== null) {
            if ($scope === 'none') {
                $scope = null;
                $request->session()->forget('scope');
            } else {
                $request->session()->put('scope', $scope);
            }
        } else {
            $scope = $request->session()->get('scope');
        }

        $framework = $request->get('framework');
        if ($framework !== null) {
            if ($framework === 'none') {
                $framework = null;
                $request->session()->forget('framework');
            } else {
                $request->session()->put('framework', $framework);
            }
        } else {
            $framework = $request->session()->get('framework');
        }

        $group = $request->get('group');
        if ($group !== null) {
            $request->session()->put('group', $group);
        } else {
            $group = $request->session()->get('group');
        }

        $domains = DB::table('domains')
            ->select(DB::raw('distinct domains.id, domains.title'))
            ->join('controls', 'domains.id', '=', 'controls.domain_id')
            ->join(
                'control_measure',
                'control_measure.control_id',
                '=',
                'controls.id'
            )
            ->join('measures', 'control_measure.measure_id', '=', 'measures.id')
            ->whereIn('measures.status', [0, 1]);

        if ($framework !== null) {
            $domains = $domains->where('framework', '=', $framework);
        }

        $domains = $domains->orderBy('domains.title')->get();

        $frameworks = DB::table('domains')
            ->select(DB::raw('distinct domains.framework as title'))
            ->join('controls', 'domains.id', '=', 'controls.domain_id')
            ->join(
                'control_measure',
                'control_measure.control_id',
                '=',
                'controls.id'
            )
            ->join('measures', 'control_measure.measure_id', '=', 'measures.id')
            ->whereIn('measures.status', [0, 1])
            ->orderBy('domains.framework')
            ->get();

        $scopes = DB::table('measures')
            ->select('scope')
            ->whereIn('status', [0, 1])
            ->whereNotNull('scope')
            ->distinct()
            ->orderBy('scope')
            ->get();

        $measures_never_made = DB::table('measures as m1')
            ->select('controls.domain_id')
            ->join(
                'control_measure',
                'm1.id',
                '=',
                'control_measure.measure_id'
            )
            ->join('controls', 'controls.id', '=', 'control_measure.control_id')
            ->leftJoin('measures as m2', 'm2.next_id', '=', 'm1.id')
            ->whereIn('m1.status', [0, 1])
            ->whereNull('m2.id')
            ->get();

        $active_measures = DB::table('measures as m1');

        if ($group === '1') {
            $active_measures = $active_measures->select([
                'domains.title',
                'controls.id as measure_id',
                'controls.clause as clause',
                'm1.id as control_id',
                'm1.name as name',
                'm1.scope as scope',
                DB::raw('min(m1.score) as score'),
            ]);
        } else {
            $active_measures = $active_measures->select([
                'domains.title',
                'controls.id as measure_id',
                'controls.clause as clause',
                'm1.id as control_id',
                'm1.name as name',
                'm1.scope as scope',
                'm1.score as score',
            ]);
        }

        $active_measures = $active_measures
            ->join('measures as m2', 'm2.id', '=', 'm1.next_id')
            ->join(
                'control_measure',
                'control_measure.measure_id',
                '=',
                'm1.id'
            )
            ->join('controls', 'control_measure.control_id', '=', 'controls.id')
            ->join('domains', 'domains.id', '=', 'controls.domain_id')
            ->whereIn('m2.status', [0, 1]);

        if ($framework !== null) {
            $active_measures = $active_measures->where('domains.framework', '=', $framework);
        }

        if ($scope !== null) {
            $active_measures = $active_measures->where('m1.scope', '=', $scope);
        }

        if ($group === '1') {
            $active_measures = $active_measures->groupBy([
                'domains.title',
                'controls.id',
                'controls.clause',
            ]);
        }

        $active_measures = $active_measures
            ->orderBy('domains.title')
            ->orderBy('clause')
            ->get();

        return view('radar.domains')
            ->with('frameworks', $frameworks)
            ->with('domains', $domains)
            ->with('scopes', $scopes)
            ->with('active_controls', $active_measures)
            ->with('controls_never_made', $measures_never_made);
    }

    public function measures(Request $request)
    {
        abort_if(
            !Auth::user()->isAdmin() && !Auth::user()->isUser(),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden'
        );

        $domains = DB::table('domains')
            ->select(DB::raw('distinct domains.id, domains.title, domains.description'))
            ->join('controls', 'domains.id', '=', 'controls.domain_id')
            ->join('control_measure', 'controls.id', '=', 'control_measure.control_id')
            ->join('measures', 'control_measure.measure_id', '=', 'measures.id')
            ->whereIn('measures.status', [0, 1])
            ->orderBy('domains.title')
            ->get();

        $scopes = DB::table('measures')
            ->select('scope')
            ->whereIn('status', [0, 1])
            ->distinct()
            ->orderBy('scope')
            ->pluck('scope');

        $cur_scope = $request->get('scope');
        if ($cur_scope !== null) {
            $request->session()->put('scope', $cur_scope);
        } else {
            $request->session()->forget('scope');
        }

        $measures = DB::table('measures as m1')
            ->select(
                DB::raw('
                    m1.id AS control_id,
                    m1.name,
                    controls.clause,
                    m1.scope as scope,
                    control_measure.control_id as measure_id,
                    controls.domain_id,
                    m1.plan_date,
                    m1.realisation_date,
                    m1.score as score,
                    m2.plan_date as next_date,
                    m2.id AS next_id')
            )
            ->join('measures as m2', 'm1.next_id', '=', 'm2.id')
            ->join('control_measure', 'control_measure.measure_id', '=', 'm2.id')
            ->join('controls', 'control_measure.control_id', '=', 'controls.id')
            ->whereIn('m2.status', [0, 1]);

        if ($cur_scope !== null) {
            $measures = $measures->where('m1.scope', '=', $cur_scope);
        }
        $measures = $measures->orderBy('clause')->orderBy('scope')->get();

        return view('/radar/controls')
            ->with('scopes', $scopes)
            ->with('measures', $measures)
            ->with('domains', $domains);
    }

    public function attributes()
    {
        abort_if(
            !Auth::user()->isAdmin() && !Auth::user()->isUser(),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden'
        );

        $attributes = DB::table('attributes')->orderBy('name')->get();

        $measures = DB::table('measures as m1')
            ->select([
                'm2.id',
                'm2.name',
                'm2.attributes',
                'm2.realisation_date',
                'm2.score',
            ])
            ->join('measures as m2', 'm1.id', '=', 'm2.next_id')
            ->where('m1.status', '=', 0)
            ->orderBy('m2.id')
            ->get();

        $controlMeasures = DB::table('control_measure')
            ->select(['measure_id', 'control_id', 'clause'])
            ->leftjoin('controls', 'controls.id', '=', 'control_id')
            ->whereIn('measure_id', $measures->pluck('id'))
            ->orderBy('clause')
            ->get();

        $controlsByMeasureId = $controlMeasures->groupBy('measure_id');

        foreach ($measures as $measure) {
            $measure->controls = $controlsByMeasureId
                ->get($measure->id, collect())
                ->map(function ($mc) {
                    return [
                        'id'     => $mc->control_id,
                        'clause' => $mc->clause,
                    ];
                });
        }

        return view('radar.attributes')
            ->with('attributes', $attributes)
            ->with('controls', $measures);
    }

    /**
     * Show a measure for planning.
     *
     * @return \Illuminate\View\View
     */
    public function plan(int $id)
    {
        abort_if(
            !Auth::user()->isAdmin() && !Auth::user()->isUser(),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden'
        );

        $measure = Measure::find($id);

        abort_if($measure === null, Response::HTTP_NOT_FOUND, '404 Not Found');

        $years = [];
        $cur_year = Carbon::now()->year;
        for ($i = 0; $i <= 3; $i++) {
            $years[$i] = $cur_year + $i;
        }

        $months = [];
        for ($month = 1; $month <= 12; $month++) {
            $months[$month] = $month;
        }

        $users = DB::table('users')
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $groups = DB::table('user_groups')
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $owners = collect();
        foreach ($users as $user) {
            $owners->put('USR_' . $user->id, $user->name);
        }
        foreach ($groups as $group) {
            $owners->put('GRP_' . $group->id, $group->name);
        }

        $all_controls = DB::table('controls')
            ->select('id', 'clause', 'name')
            ->orderBy('id')
            ->get();

        $controls = DB::table('control_measure')
            ->select('control_id')
            ->where('measure_id', $id)
            ->pluck('control_id');

        $scopes = DB::table('measures')
            ->select('scope')
            ->whereIn('status', [0, 1])
            ->distinct()
            ->orderBy('scope')
            ->pluck('scope');

        return view('measures.plan', compact('measure'))
            ->with('years', $years)
            ->with('day', date('d', strtotime($measure->plan_date)))
            ->with('month', date('m', strtotime($measure->plan_date)))
            ->with('year', date('Y', strtotime($measure->plan_date)))
            ->with('all_controls', $all_controls)
            ->with('controls', $controls)
            ->with('scopes', $scopes)
            ->with('owners', $owners);
    }

    /**
     * Unplan a measure.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function unplan(Request $request)
    {
        abort_if(
            !Auth::user()->isAdmin() && !Auth::user()->isUser(),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden'
        );

        $measure = Measure
            ::whereIn('status', [0, 1])
            ->where('id', '=', $request->id)
            ->first();

        abort_if($measure === null, Response::HTTP_NOT_FOUND, '404 Not Found');

        $prev_measure = Measure::where('next_id', $measure->id)->first();
        if ($prev_measure !== null) {
            $prev_measure->next_id = null;
            $prev_measure->update();
        }

        $measure->users()->detach();
        $measure->groups()->detach();
        $measure->controls()->detach();

        foreach ($measure->documents as $document) {
            \Log::debug(storage_path('docs/' . $document->id));
            unlink(storage_path('docs/' . $document->id));
        }
        $measure->documents()->delete();

        $measure->delete();

        return redirect('/alice/index');
    }

    /**
     * Save a measure for planning.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function doPlan(Request $request)
    {
        abort_if(
            Auth::user()->role !== 1 && Auth::user()->role !== 2,
            Response::HTTP_FORBIDDEN,
            '403 Forbidden'
        );

        $this->validate($request, [
            'plan_date'   => 'required',
            'periodicity' => 'required|integer|in:-1,0,1,3,6,12',
        ]);

        $measure = Measure::find($request->id);

        abort_if($measure === null, Response::HTTP_NOT_FOUND, '404 Not Found');

        if ($measure->status === 2) {
            return back()
                ->withErrors(['msg' => trans('cruds.control.error.made')])
                ->withInput();
        }

        $measure->plan_date   = $request->plan_date;
        $measure->periodicity = $request->periodicity;
        $measure->save();

        $users = collect();
        foreach ($request->input('owners', []) as $owner) {
            if (str_starts_with($owner, 'USR_')) {
                $users->push(intval(substr($owner, 4)));
            }
        }
        $measure->users()->sync($users);

        $groups = collect();
        foreach ($request->input('owners', []) as $owner) {
            if (str_starts_with($owner, 'GRP_')) {
                $groups->push(intval(substr($owner, 4)));
            }
        }
        $measure->groups()->sync($groups);

        return redirect('/bob/show/' . $request->id);
    }

    public function make(Request $request)
    {
        $id = (int) request('id');

        $measure = Measure::find($id);

        abort_if($measure === null, Response::HTTP_NOT_FOUND, '404 Not Found');

        abort_if(
            ! ($measure->canMake() || $measure->canValidate()),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden'
        );

        $documents = DB::table('documents')->where('measure_id', $id)->get();

        $request->session()->put('control', $id);

        if ($measure->periodicity === 0) {
            $next_date = null;
        } else {
            if ($measure->periodicity === -1) {
                $next_date = Carbon::createFromFormat('Y-m-d', $measure->plan_date)
                    ->addDays(7)
                    ->format('Y-m-d');
            } else {
                $next_date = Carbon::createFromFormat('Y-m-d', $measure->plan_date)
                    ->addMonthsNoOverflow($measure->periodicity)
                    ->format('Y-m-d');
            }
        }

        return view('measures.make')
            ->with('measure', $measure)
            ->with('documents', $documents)
            ->with('next_date', $next_date);
    }

    /**
     * Execute a measure.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function doMake(Request $request)
    {
        abort_if(
            Auth::user()->role === 4,
            Response::HTTP_FORBIDDEN,
            '403 Forbidden'
        );

        $id = (int) request('id');

        $measure = Measure::find($id);

        abort_if($measure === null, Response::HTTP_NOT_FOUND, '404 Not Found');

        abort_if(! $measure->canMake(), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $this->validate($request, [
            'note' => 'nullable|numeric',
        ]);

        if ((request('score') === null) || (request('score') === 0)) {
            return back()
                ->withErrors(['score' => 'score not set'])
                ->withInput();
        }

        $measure->observations    = request('observations');
        $measure->note            = request('note');
        $measure->score           = request('score');
        $measure->realisation_date = request('realisation_date');

        if (Auth::user()->role === 1 || Auth::user()->role === 2) {
            $measure->plan_date   = request('plan_date');
            $measure->action_plan = request('action_plan');

            if ($request->has('add_action_plan')) {
                $action = new Action();
                $action->name         = $measure->name;
                $action->scope        = $measure->scope;
                $action->status       = 0;
                $action->cause        = $measure->observations;
                $action->remediation  = $measure->action_plan;
                $action->due_date     = request('next_date');
                $action->measure_id   = $measure->id;
                $action->save();

                $controls = DB::table('control_measure')
                    ->select('control_id')
                    ->where('measure_id', $measure->id)
                    ->pluck('control_id');
                $action->controls()->sync($controls);

                $owners = DB::table('measure_user')
                    ->select('user_id')
                    ->where('measure_id', $measure->id)
                    ->pluck('user_id');
                $action->owners()->sync($owners);
            }
        } else {
            $measure->realisation_date = date('Y-m-d', strtotime('today'));
        }

        if (Auth::user()->role === 5) {
            $measure->status = 1;
        } else {
            $measure->status = 2;

            if (($measure->next_id === null) && ($measure->periodicity !== 0)) {
                $new_measure = $measure->replicate(Measure::AI_COLUMNS);
                $new_measure->observations    = null;
                $new_measure->realisation_date = null;
                $new_measure->note            = null;
                $new_measure->score           = null;
                $new_measure->status          = 0;
                if (Auth::user()->isAdmin() || Auth::user()->isUser()) {
                    $new_measure->plan_date = request('next_date');
                } else {
                    if ($measure->periodicity === -1) {
                        $new_measure->plan_date = Carbon::parse($measure->plan_date)
                            ->addDays(7)
                            ->toDateString();
                    } else {
                        $new_measure->plan_date = Carbon::parse($measure->plan_date)
                            ->addMonths($measure->periodicity)
                            ->toDateString();
                    }
                }
                $new_measure->save();

                $new_measure->users()->sync($measure->users()->pluck('id'));
                $new_measure->groups()->sync($measure->groups()->pluck('id'));
                $new_measure->controls()->sync($measure->controls()->pluck('id'));

                $measure->next_id = $new_measure->id;
            }
        }

        $measure->update();

        return redirect('/bob/index');
    }

    /**
     * Save a measure (edit in admin mode).
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function save(Request $request)
    {
        abort_if(
            ! Auth::user()->isAdmin(),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden'
        );

        $measure = Measure::query()->find($request->id);

        abort_if($measure === null, Response::HTTP_NOT_FOUND, '404 Not Found');

        $this->validate(
            $request,
            [
                'name'        => 'required|min:3|max:255',
                'scope'       => 'max:32',
                'objective'   => 'required',
                'plan_date'   => 'required',
                'periodicity' => 'required|integer|in:-1,0,1,3,6,12',
                'note'        => 'nullable|numeric',
            ]
        );

        $measure->name             = request('name');
        $measure->scope            = request('scope');
        $measure->objective        = request('objective');
        $measure->attributes       = request('attributes') !== null ? implode(' ', request('attributes')) : null;
        $measure->input            = request('input');
        $measure->model            = request('model');
        $measure->plan_date        = request('plan_date');
        $measure->realisation_date = request('realisation_date');
        $measure->observations     = request('observations');
        $measure->note             = request('note');
        $measure->indicator        = request('indicator');
        $measure->score            = request('score');
        $measure->action_plan      = request('action_plan');
        $measure->periodicity      = request('periodicity');
        $measure->status           = request('status');
        $measure->next_id          = request('next_id');

        $users = collect();
        foreach ($request->input('owners', []) as $owner) {
            if (str_starts_with($owner, 'USR_')) {
                $users->push(intval(substr($owner, 4)));
            }
        }
        $measure->users()->sync($users);

        $groups = collect();
        foreach ($request->input('owners', []) as $owner) {
            if (str_starts_with($owner, 'GRP_')) {
                $groups->push(intval(substr($owner, 4)));
            }
        }
        $measure->groups()->sync($groups);

        $measure->controls()->sync($request->input('controls', []));

        $measure->save();

        return redirect('/bob/show/' . $request->id);
    }

    /**
     * Draft a measure (save without completing).
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function draft(Request $request)
    {
        abort_if(
            Auth::user()->isAPI(),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden'
        );

        $id = (int) $request->get('id');

        $measure = Measure::find($id);

        abort_if($measure === null, Response::HTTP_NOT_FOUND, '404 Not Found');

        abort_if(! $measure->canMake(), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if ($measure->status === 2) {
            return back()
                ->withErrors(['msg' => trans('cruds.control.error.made')])
                ->withInput();
        }

        $this->validate($request, [
            'note' => 'nullable|numeric',
        ]);

        $measure->observations = request('observations');
        $measure->note         = request('note');
        $measure->score        = request('score') === 0 ? null : request('score');

        if (Auth::user()->isAdmin() || Auth::user()->isUser()) {
            $measure->plan_date   = request('plan_date');
            $measure->action_plan = request('action_plan');
        }
        $measure->save();

        return redirect('/bob/show/' . $id);
    }

    /**
     * Reject a proposed measure.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function reject(Request $request)
    {
        abort_if(
            ! (Auth::user()->isAdmin() || Auth::user()->isUser()),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden'
        );

        $id = (int) $request->get('id');

        $measure = Measure::find($id);

        abort_if($measure === null, Response::HTTP_NOT_FOUND, '404 Not Found');

        if ($measure->status === 2) {
            return back()
                ->withErrors(['msg' => trans('cruds.control.error.made')])
                ->withInput();
        }

        $this->validate($request, [
            'note' => 'nullable|numeric',
        ]);

        $measure->observations = request('observations');
        $measure->note         = request('note');
        $measure->score        = request('score');
        $measure->plan_date    = request('plan_date');
        $measure->action_plan  = request('action_plan');
        $measure->status       = 0;

        $measure->save();

        return redirect('/bob/index');
    }

    /**
     * Accept a proposed measure.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function accept(Request $request)
    {
        abort_if(
            ! (Auth::user()->role === 1 || Auth::user()->role === 2),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden'
        );

        $id = (int) $request->get('id');

        $this->validate($request, [
            'note' => 'nullable|numeric',
        ]);

        if ((request('score') === null) || (request('score') === 0)) {
            return back()
                ->withErrors(['score' => 'score not set'])
                ->withInput();
        }

        $measure = Measure::find($id);

        abort_if($measure === null, Response::HTTP_NOT_FOUND, '404 Not Found');

        if ($measure->status === 2) {
            return back()
                ->withErrors(['msg' => trans('cruds.control.error.made')])
                ->withInput();
        }

        $measure->observations     = request('observations');
        $measure->note             = request('note');
        $measure->score            = request('score');
        $measure->realisation_date = request('realisation_date');
        $measure->plan_date        = request('plan_date');
        $measure->action_plan      = request('action_plan');

        if ($measure->periodicity !== 0) {
            $new_measure                   = $measure->replicate(Measure::AI_COLUMNS);
            $new_measure->status           = 0;
            $new_measure->observations     = null;
            $new_measure->realisation_date = null;
            $new_measure->note             = null;
            $new_measure->score            = null;
            $new_measure->plan_date        = request('next_date');
            $new_measure->save();

            $new_measure->controls()->sync($measure->controls()->pluck('id')->toArray());
            $new_measure->users()->sync($measure->users()->pluck('id')->toArray());
            $new_measure->groups()->sync($measure->groups()->pluck('id')->toArray());

            $measure->next_id = $new_measure->id;
        }
        $measure->status = 2;

        $measure->update();

        return redirect('/bob/index');
    }

    public function export()
    {
        abort_if(
            !Auth::user()->isAdmin() && !Auth::user()->isUser(),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden'
        );

        return Excel::download(
            new MeasuresExport(),
            trans('cruds.measure.title') .
            '-' .
            now()->format('Y-m-d Hi') .
            '.xlsx'
        );
    }

    public function tempo(Request $request)
    {
        abort_if(
            ! Auth::user()->isAdmin() && !Auth::user()->isUser(),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden'
        );

        if ($request->clause !== null) {
            if ($request->scope !== null) {
                $controls = Control::query()
                    ->where('clause', '=', $request->clause)
                    ->whereHas('measures', function ($q) {
                        $q->where('measures.status', '=', 2);
                    })
                    ->whereHas('measures', function ($q) use ($request) {
                        $q->where('measures.scope', '=', $request->scope);
                    })
                    ->with(['measures' => function ($q) use ($request) {
                        $q->where('measures.scope', '=', $request->scope);
                    }])
                    ->get();
            } else {
                $controls = Control::query()
                    ->with('measures')
                    ->where('clause', '=', $request->clause)
                    ->whereHas('measures', function ($q) {
                        $q->where('measures.status', '=', 2);
                    })
                    ->get();
            }
            $scopes = DB::Table('measures')
                ->select('scope')
                ->join('control_measure', 'measures.id', '=', 'control_measure.measure_id')
                ->join('controls', 'control_measure.control_id', '=', 'controls.id')
                ->where('controls.clause', '=', $request->clause)
                ->whereNotNull('measures.scope')
                ->distinct()
                ->orderby('measures.scope')
                ->pluck('measures.scope');
        } else {
            $controls = collect();
            $scopes   = collect();
        }

        $clauses = DB::Table('controls')
            ->select('clause')
            ->join('control_measure', 'controls.id', '=', 'control_measure.control_id')
            ->join('measures', 'control_measure.measure_id', '=', 'measures.id')
            ->where('measures.status', '=', 2)
            ->distinct()
            ->orderby('clause')
            ->pluck('clause');

        return view('radar.measures')
            ->with('clauses', $clauses)
            ->with('scopes', $scopes)
            ->with('controls', $controls);
    }

    public function template(Request $request)
    {
        abort_if(
            Auth::user()->isAPI(),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden'
        );

        $id = (int) $request->id;

        $measure = Measure::find($id);

        abort_if($measure === null, Response::HTTP_NOT_FOUND, '404 Not Found');

        abort_if(! $measure->canMake(), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $template_filename = storage_path('app/models/control_.docx');
        if (! file_exists($template_filename)) {
            $template_filename = storage_path('app/models/control_' . Auth::user()->language . '.docx');
            if (! file_exists($template_filename)) {
                $template_filename = storage_path('app/models/control_en.docx');
            }
        }

        $templateProcessor = new PhpWordTemplateProcessor($template_filename);

        $clauses = $measure->controls()
            ->pluck('controls.clause')
            ->implode(', ');

        $templateProcessor->setValue('ref', $clauses);
        $templateProcessor->setValue('name', $measure->name);
        $templateProcessor->setValue('scope', $measure->scope);
        $templateProcessor->setValue('attributes', $measure->attributes);

        $templateProcessor->setComplexValue('objective', self::string2Textrun($measure->objective));
        $templateProcessor->setComplexValue('input', self::string2Textrun($measure->input));
        $templateProcessor->setComplexValue('model', self::string2Textrun($measure->model));
        $templateProcessor->setComplexValue('observations', self::string2Textrun(urldecode($request->observations)));
        $templateProcessor->setValue('date', Carbon::today()->format('d/m/Y'));

        $filepath = storage_path(
            'templates/measure-' .
            $measure->id .
            '-' .
            now()->format('Ymd') .
            '.docx'
        );

        $templateProcessor->saveAs($filepath);

        return response()->download($filepath);
    }

    private static function string2Textrun(?string $str)
    {
        if ($str === null) {
            return new \PhpOffice\PhpWord\Element\TextRun();
        }

        $textlines = explode("\n", $str);
        $textrun   = new \PhpOffice\PhpWord\Element\TextRun();

        // Le texte est échappé par PhpWord (voir AppServiceProvider)
        $textrun->addText(array_shift($textlines));

        foreach ($textlines as $line) {
            $textrun->addTextBreak();
            $textrun->addText($line);
        }

        return $textrun;
    }
}
