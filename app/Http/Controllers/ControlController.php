<?php

namespace App\Http\Controllers;

use App\Exports\ControlsExport;
use App\Models\Measure;
use App\Models\Control;
use App\Models\Domain;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class ControlController extends Controller
{
    /**
     * Display a listing of controls (security measures).
     */
    public function index(Request $request): View
    {
        // Not for API
        abort_if(Auth::user()->isAPI(),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden'
        );

        $domains = Domain::All();

        $domain = $request->get('domain');
        if ($domain !== null) {
            if ($domain === '0') {
                $request->session()->forget('domain');
                $domain = null;
            }
        } else {
            $domain = $request->session()->get('domain');
        }

        $controls = DB::table('controls')
            ->select(
                [
                    'controls.id',
                    'controls.domain_id',
                    'controls.clause',
                    'controls.name',
                    'domains.title',
                ]
            )
            ->join('domains', 'domains.id', '=', 'controls.domain_id');

        // Filter controls to those linked to the user's own measures, unless they see all data
        if (!Auth::user()->seesAllData()) {
            $userId = Auth::id();

            $controls->whereExists(function ($query) use ($userId) {
                $query->select(DB::raw(1))
                    ->from('control_measure')
                    ->whereColumn('control_measure.control_id', 'controls.id')
                    ->where(function ($subQuery) use ($userId) {
                        $subQuery->whereExists(function ($q) use ($userId) {
                            $q->select(DB::raw(1))
                                ->from('measure_user')
                                ->whereColumn('measure_user.measure_id', 'control_measure.measure_id')
                                ->where('measure_user.user_id', $userId);
                        })
                            ->orWhereExists(function ($q) use ($userId) {
                                $q->select(DB::raw(1))
                                    ->from('measure_user_group')
                                    ->join('user_user_group', 'user_user_group.user_group_id', '=', 'measure_user_group.user_group_id')
                                    ->whereColumn('measure_user_group.measure_id', 'control_measure.measure_id')
                                    ->where('user_user_group.user_id', $userId);
                            });
                    });
            });

            $controls->addSelect(
                ['control_count' => DB::table('measures')
                    ->selectRaw('count(*) as measures_count')
                    ->leftjoin('control_measure', 'control_measure.control_id', 'controls.id')
                    ->whereColumn('control_measure.measure_id', 'measures.id')
                    ->whereIn('measures.status', [0, 1])
                    ->where(function ($q) use ($userId) {
                        $q->whereExists(function ($subQ) use ($userId) {
                            $subQ->select(DB::raw(1))
                                ->from('measure_user')
                                ->whereColumn('measure_user.measure_id', 'measures.id')
                                ->where('measure_user.user_id', $userId);
                        })
                            ->orWhereExists(function ($subQ) use ($userId) {
                                $subQ->select(DB::raw(1))
                                    ->from('measure_user_group')
                                    ->join('user_user_group', 'user_user_group.user_group_id', '=', 'measure_user_group.user_group_id')
                                    ->whereColumn('measure_user_group.measure_id', 'measures.id')
                                    ->where('user_user_group.user_id', $userId);
                            });
                    }),
                ]
            );
        } else {
            $controls->addSelect(
                ['control_count' => DB::table('measures')
                    ->selectRaw('count(*) as measures_count')
                    ->leftjoin('control_measure', 'control_measure.control_id', 'controls.id')
                    ->whereColumn('control_measure.measure_id', 'measures.id')
                    ->whereIn('measures.status', [0, 1]),
                ]
            );
        }

        if ($domain !== null) {
            $controls->where('controls.domain_id', $domain);
            $request->session()->put('domain', $domain);
        }

        $controls = $controls->orderBy('clause')->get();

        return view('controls.index')
            ->with('controls', $controls)
            ->with('domains', $domains);
    }

    /**
     * Show the form for creating a new control (security measure).
     */
    public function create()
    {
        abort_if(!Auth::user()->isAdmin() && !Auth::user()->isUser(),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden'
        );

        $domains = Domain::All();

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

        $control = null;

        return view('controls.create', compact('control', 'values', 'domains'));
    }

    /**
     * Store a newly created control (security measure).
     */
    public function store(Request $request)
    {
        abort_if(!Auth::user()->isAdmin() && !Auth::user()->isUser(),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden'
        );

        $this->validate(
            $request,
            [
                'domain_id' => 'required',
                'clause'    => 'required|min:3|max:30|unique:controls,clause',
                'name'      => 'required|min:5|max:255',
                'objective' => 'required',
            ]
        );

        $control = new Control();
        $control->domain_id   = request('domain_id');
        $control->clause      = request('clause');
        $control->name        = request('name');
        $control->attributes  = request('attributes') !== null ? implode(' ', request('attributes')) : null;
        $control->objective   = request('objective');
        $control->input       = request('input');
        $control->model       = request('model');
        $control->indicator   = request('indicator');
        $control->action_plan = request('action_plan');
        $control->save();

        $request->session()->put('domain', $control->domain_id);

        return redirect('/alice/index');
    }

    /**
     * Display a control (security measure).
     */
    public function show(int $id)
    {
        abort_if(Auth::user()->isAPI(),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden'
        );

        // Must have an assigned measure on this control, unless the user sees all data
        abort_if(
            (!Auth::user()->seesAllData()) &&
            ! DB::table('measures')
                ->join('control_measure', 'control_measure.measure_id', '=', 'measures.id')
                ->join('measure_user', 'measure_user.measure_id', '=', 'measures.id')
                ->where('control_measure.control_id', $id)
                ->where('measure_user.user_id', Auth::user()->id)
                ->exists(),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden'
        );

        $control = Control::find($id);

        abort_if($control === null, Response::HTTP_NOT_FOUND, '404 Not Found');

        $control->load([
            'outgoingMappings.targetControl.domain',
            'incomingMappings.sourceControl.domain',
        ]);

        $measures = DB::table('measures')
            ->select('measures.id', 'measures.name', 'measures.scope', 'score', 'measures.status', 'realisation_date', 'plan_date')
            ->join('control_measure', 'control_measure.measure_id', '=', 'measures.id')
            ->leftjoin('actions', 'actions.measure_id', '=', 'measures.id')
            ->where('control_measure.control_id', $id)
            ->get();

        return view('controls.show')
            ->with('control', $control)
            ->with('measures', $measures);
    }

    /**
     * Show the form for editing a control (security measure).
     */
    public function edit(int $id)
    {
        abort_if(!Auth::user()->isAdmin() && !Auth::user()->isUser(),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden'
        );

        $control = Control::find($id);

        abort_if($control === null, Response::HTTP_NOT_FOUND, '404 Not Found');

        $domains = Domain::All();

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

        return view('controls.edit', compact('control', 'values', 'domains'));
    }

    /**
     * Clone a control (security measure).
     */
    public function clone(int $id)
    {
        abort_if(!Auth::user()->isAdmin() && !Auth::user()->isUser(),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden'
        );

        $control = Control::find($id);

        abort_if($control === null, Response::HTTP_NOT_FOUND, '404 Not Found');

        $domains = Domain::all();

        $values = [];
        $attributes = DB::table('attributes')->select('values')
            ->union(DB::table('controls')
                ->select(DB::raw('attributes as value')))
            ->get();

        foreach ($attributes as $attribute) {
            foreach (explode(' ', $attribute->values) as $value) {
                if (strlen($value) > 0) {
                    $values[] = $value;
                }
            }
        }

        $values = array_unique($values);
        sort($values);

        $selectedAttributes = array_filter(
            explode(' ', $control->attributes ?? ''),
            fn ($val) => strlen($val) > 0
        );

        return view(
            'controls.create',
            compact('control', 'values', 'domains', 'selectedAttributes')
        );
    }

    /**
     * Update a control (security measure).
     */
    public function update(Request $request)
    {
        abort_if(!Auth::user()->isAdmin() && !Auth::user()->isUser(),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden'
        );

        $this->validate(
            $request,
            [
                'domain_id' => 'required',
                'clause'    => 'required|min:3|max:30',
                'name'      => 'required|min:5',
                'objective' => 'required',
            ]
        );

        $control = Control::query()->find($request->id);

        abort_if($control === null, Response::HTTP_NOT_FOUND, '404 Not Found');

        $control->domain_id   = request('domain_id');
        $control->clause      = request('clause');
        $control->name        = request('name');
        $control->attributes  = request('attributes') !== null ? implode(' ', request('attributes')) : null;
        $control->objective   = request('objective');
        $control->input       = request('input');
        $control->model       = request('model');
        $control->indicator   = request('indicator');
        $control->action_plan = request('action_plan');
        $control->update();

        return redirect('/alice/show/' . $control->id);
    }

    /**
     * Delete a control (security measure).
     */
    public function destroy(Request $request)
    {
        abort_if(
            (Auth::user()->role === 3) ||
            (Auth::user()->role === 4) ||
            (Auth::user()->role === 5),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden'
        );

        abort_if(
            ! DB::table('controls')->where('id', $request->id)->exists(),
            Response::HTTP_NOT_FOUND,
            '404 Not Found'
        );

        // Has associated measures?
        if (DB::table('controls')
            ->where('id', $request->id)
            ->join('control_measure', 'controls.id', 'control_measure.control_id')
            ->exists()) {
            return back()
                ->withErrors(['msg' => 'There are measures associated with this control !'])
                ->withInput();
        }

        Control::destroy($request->id);

        return redirect('/alice/index');
    }

    /**
     * Plan a new measure on a control.
     */
    public function plan(Request $request): View
    {
        abort_if(
            (Auth::user()->role === 3) ||
            (Auth::user()->role === 4) ||
            (Auth::user()->role === 5),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden'
        );

        $control = Control::find($request->id);

        abort_if($control === null, Response::HTTP_NOT_FOUND, '404 Not Found');

        $all_controls = DB::table('controls')
            ->select('id', 'clause', 'name')
            ->orderBy('id')
            ->get();

        $controls = [$request->id];

        $scopes = Measure::query()
            ->whereNotNull('scope')
            ->where('scope', '!=', '')
            ->whereIn('status', [0, 1])
            ->distinct()
            ->orderBy('scope')
            ->pluck('scope')
            ->toArray();

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

        $values = [];
        $attributes = DB::table('attributes')
            ->select('values')
            ->get();
        foreach ($attributes as $attribute) {
            foreach (explode(' ', $attribute->values) as $value) {
                if (strlen($value) > 0) {
                    array_push($values, $value);
                }
            }
            sort($values);
            $values = array_unique($values);
        }

        return view(
            'controls.plan',
            compact(
                'control',
                'all_controls',
                'controls',
                'scopes',
                'owners',
                'values'
            )
        );
    }

    /**
     * Activate a control by creating a new measure instance.
     */
    public function activate(Request $request): RedirectResponse
    {
        abort_if(
            (Auth::user()->role === 3) ||
            (Auth::user()->role === 4) ||
            (Auth::user()->role === 5),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden'
        );

        $this->validate(
            $request,
            [
                'plan_date'   => 'required',
                'periodicity' => 'required|integer|in:-1,0,1,3,6,12',
                'controls'    => 'array|min:1',
            ]
        );

        $measure = new Measure();
        $measure->name        = $request->get('name');
        $measure->scope       = $request->get('scope');
        $measure->attributes  = request('attributes') !== null ? implode(' ', request('attributes')) : null;
        $measure->objective   = $request->get('objective');
        $measure->input       = $request->get('input');
        $measure->model       = $request->get('model');
        $measure->indicator   = $request->get('indicator');
        $measure->action_plan = $request->get('action_plan');
        $measure->periodicity = $request->get('periodicity');
        $measure->plan_date   = $request->get('plan_date');
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

        return redirect('/alice/index');
    }

    /**
     * Disable a control (delete its active measure).
     */
    public function disable(Request $request)
    {
        abort_if(
            (Auth::user()->role === 3) ||
            (Auth::user()->role === 4) ||
            (Auth::user()->role === 5),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden'
        );

        $measure_id = DB::table('measures')
            ->select('id')
            ->join('control_measure', 'control_measure.measure_id', '=', 'measures.id')
            ->where('control_measure.control_id', $request->id)
            ->whereIn('measures.status', [0, 1])
            ->first()
            ?->id;

        if ($measure_id !== null) {
            Measure::where('next_id', $measure_id)->update(['next_id' => null]);
            Measure::where('id', $measure_id)->delete();
        }

        return redirect('/alice/index');
    }

    /**
     * Export all controls in xlsx.
     */
    public function export()
    {
        abort_if(
            (Auth::user()->role === 3) ||
            (Auth::user()->role === 4) ||
            (Auth::user()->role === 5),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden'
        );

        return Excel::download(new ControlsExport(), trans('cruds.control.title') . '-' . now()->format('Y-m-d Hi') . '.xlsx');
    }
}
