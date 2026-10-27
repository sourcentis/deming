<?php

namespace App\Http\Controllers;

// Models
use App\Exports\ActionsExport;
// Export
use App\Models\Action;
use App\Models\Risk;
// Laravel
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ActionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request $request
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        // Admin, user, auditor or auditee
        abort_if(Auth::user()->isAPI(),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden'
        );

        // Get type filter
        $type = $request->get('type');
        if ($type !== null) {
            $request->session()->put('type', $type);
        } elseif ($request->has('type')) {
            $request->session()->forget('type');
        } else {
            $type = $request->session()->get('type');
        }

        // Get status filter
        $status = $request->get('status');
        if ($status !== null) {
            $request->session()->put('status', $status);
        } elseif ($request->has('status')) {
            $request->session()->forget('status');
        } else {
            $status = $request->session()->get('status');
            if ($status === null) {
                $status = '0';
                $request->session()->put('status', $status);
            }
        }

        // Get scope filter
        $scope = $request->get('scope');
        if ($scope !== null) {
            $request->session()->put('ascope', $scope);
        } elseif ($request->has('scope')) {
            $request->session()->forget('ascope');
        } else {
            $scope = $request->session()->get('ascope');
        }

        // Build query
        $actions = DB::table('actions')
            ->leftjoin('measures', 'measure_id', '=', 'measures.id');

        // filter on type
        if (($type !== null) && (strlen($type) > 0)) {
            $actions = $actions->where('type', $type);
        }

        // filter on status
        if ($status === '0') {
            $actions = $actions->where('actions.status', 0);
        } elseif ($status === '1') {
            $actions = $actions->where('actions.status', 1);
        }

        // filter on scope
        if (($scope !== null) && (strlen($scope) > 0)) {
            $actions = $actions->where('actions.scope', $scope);
        }

        // filter to the current user's own actions, unless they see all data
        if (!Auth::user()->seesAllData()) {
            $userId = Auth::id();
            $actions = $actions->where(function($query) use ($userId) {
                // Actions assignées directement à l'utilisateur
                $query->whereExists(function($q) use ($userId) {
                    $q->select(DB::raw(1))
                        ->from('action_user')
                        ->whereColumn('action_user.action_id', 'actions.id')
                        ->where('action_user.user_id', $userId);
                })
                    // OU actions liées à des mesures assignées à l'utilisateur
                    ->orWhereExists(function($q) use ($userId) {
                        $q->select(DB::raw(1))
                            ->from('measure_user')
                            ->whereColumn('measure_user.measure_id', 'actions.measure_id')
                            ->where('measure_user.user_id', $userId);
                    })
                    // OU actions liées à des mesures assignées via un groupe
                    ->orWhereExists(function($q) use ($userId) {
                        $q->select(DB::raw(1))
                            ->from('measure_user_group')
                            ->join('user_user_group', 'user_user_group.user_group_id', '=', 'measure_user_group.user_group_id')
                            ->whereColumn('measure_user_group.measure_id', 'actions.measure_id')
                            ->where('user_user_group.user_id', $userId);
                    });
            });
        }

        // Query DB
        $actions = $actions->select(
            [
                'actions.id',
                'actions.reference',
                'actions.type',
                'actions.name',
                'actions.criticity',
                'actions.scope',
                'actions.cause',
                'actions.remediation',
                'actions.status',
                'actions.progress',
                'actions.due_date',
            ]
        )->get();

        // Fetch owners for all actions in one query
        $ownersByActionId = DB::table('action_user')
            ->join('users', 'users.id', '=', 'action_user.user_id')
            ->whereIn('action_user.action_id', $actions->pluck('id'))
            ->orderBy('users.name')
            ->get(['action_user.action_id', 'users.name'])
            ->groupBy('action_id');

        // Fetch clauses (controls) for all actions in one query
        $controlsByActionId = DB::table('action_control')
            ->join('controls', 'controls.id', '=', 'action_control.control_id')
            ->whereIn('action_control.action_id', $actions->pluck('id'))
            ->orderBy('controls.clause')
            ->get(['action_control.action_id', 'controls.id', 'controls.clause'])
            ->groupBy('action_id');

        // Fetch risks for all actions in one query
        $risksByActionId = DB::table('action_risk')
            ->join('risks', 'risks.id', '=', 'action_risk.risk_id')
            ->whereIn('action_risk.action_id', $actions->pluck('id'))
            ->whereNull('risks.deleted_at')
            ->orderBy('risks.name')
            ->get(['action_risk.action_id', 'risks.id', 'risks.name'])
            ->groupBy('action_id');

        foreach ($actions as $action) {
            $action->owners = $ownersByActionId
                ->get($action->id, collect())
                ->pluck('name');
            $action->controls = $controlsByActionId
                ->get($action->id, collect());
            $action->risks = $risksByActionId
                ->get($action->id, collect());
        }

        // Get scopes
        $scopes = DB::table('actions')
            ->select('scope')
            ->whereNotNull('scope')
            ->where('status', '<>', 3)
            ->distinct()
            ->orderBy('scope')
            ->pluck('scope')
            ->toArray();

        // return
        return view('actions.index')
            ->with('scopes', $scopes)
            ->with('actions', $actions);
    }
    /**
     * Save an action plan
     *
     * @param  \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function save(Request $request)
    {
        abort_if(
            ! ((Auth::user()->role === 1) || (Auth::user()->role === 2)),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden'
        );

        $this->validate(
            $request,
            [
                'name' => 'required|min:3|max:255',
            ]
        );

        // Get the action plan
        $id = (int) $request->get('id');
        $action = Action::find($id);

        // Action not found
        abort_if($action === null, Response::HTTP_NOT_FOUND, '404 Not Found');

        // Update fields
        $action->reference = request('reference');
        $action->type = request('type');
        $action->due_date = request('due_date');
        $action->name = request('name');
        $action->progress = request('progress');
        $action->scope = request('scope');
        $action->cause = request('cause');
        $action->remediation = request('remediation');
        $action->status = request('status');
        if ($action->status === 0) {
            $action->close_date = null;
        } else {
            $action->close_date = request('close_date');
        }
        $action->justification = request('justification');
        $action->update();

        // Sync measures
        $action->controls()->sync($request->input('measures', []));

        // Sync risks
        $action->risks()->sync($request->input('risks', []));

        // Sync owners
        $action->owners()->sync($request->input('owners', []));

        return redirect("/action/show/{$id}");
    }

    /**
     * Update an action plan
     *
     * @param  \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        abort_if(
            ! ((Auth::user()->role === 1) || (Auth::user()->role === 2)),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden'
        );

        // Get the action plan
        $id = (int) $request->get('id');
        $action = Action::find($id);

        // Action not found
        abort_if($action === null, Response::HTTP_NOT_FOUND, '404 Not Found');

        // Update fields
        $action->remediation = request('remediation');
        $action->progress = request('progress');
        $action->update();

        return redirect('/actions');
    }

    /**
     * Display the action.
     *
     * @param  int $id
     *
     * @return \Illuminate\View\View
     */
    public function show(int $id)
    {
        abort_if(
            ! Action::isViewableBy(Auth::user(), $id),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden'
        );

        // Get the action
        $action = Action::find($id);

        // Control not found
        abort_if($action === null, Response::HTTP_NOT_FOUND, '404 Not Found');

        // Return
        return view('actions.show')
            ->with('action', $action);
    }

    /**
     * Edit the action.
     *
     * @param  int $id
     *
     * @return \Illuminate\View\View
     */
    public function edit(int $id)
    {
        abort_if(
            ! ((Auth::user()->role === 1) ||
            (Auth::user()->role === 2) ||
            (Auth::user()->role === 3)),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden'
        );

        // Get the action
        $action = Action::query()->find($id);

        // Control not found
        abort_if($action === null, Response::HTTP_NOT_FOUND, '404 Not Found');

        // Get all scopes
        $scopes = DB::table('actions')
            ->select('scope')
            ->whereNotNull('scope')
            ->where('status', '<>', 2)
            ->distinct()
            ->orderBy('scope')
            ->pluck('scope')
            ->toArray();

        // Get all security measures (controls)
        $all_measures = DB::table('controls')
            ->select('id', 'clause', 'name')
            ->orderBy('id')
            ->get();

        $measures = DB::table('action_control')
            ->select('control_id')
            ->where('action_id', $id)
            ->pluck('control_id')
            ->toArray();

        // Get all risks
        $all_risks = Risk::query()
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $risks = $action->risks()->pluck('risks.id')->toArray();

        // Get users
        $users = DB::table('users')
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        // Return
        return view('actions.edit')
            ->with('scopes', $scopes)
            ->with('all_measures', $all_measures)
            ->with('measures', $measures)
            ->with('all_risks', $all_risks)
            ->with('risks', $risks)
            ->with('users', $users)
            ->with('action', $action);
    }

    /**
     * Create an action.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        abort_if(
            ! ((Auth::user()->role === 1) ||
            (Auth::user()->role === 2)),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden'
        );

        // Get all scopes
        $scopes = DB::table('actions')
            ->select('scope')
            ->whereNotNull('scope')
            ->where('status', '<>', 2)
            ->distinct()
            ->orderBy('scope')
            ->pluck('scope')
            ->toArray();

        // Get all security measures (controls)
        $all_measures = DB::table('controls')
            ->select('id', 'clause', 'name')
            ->orderBy('id')
            ->get();

        // Get users
        $users = DB::table('users')
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        // Get all risks
        $all_risks = Risk::query()
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        // Return
        return view('actions.create')
            ->with('scopes', $scopes)
            ->with('all_measures', $all_measures)
            ->with('all_risks', $all_risks)
            ->with('users', $users);
    }

    /**
     * Store the action.
     *
     * @param  \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        abort_if(
            ! ((Auth::user()->role === 1) ||
            (Auth::user()->role === 2)),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden'
        );

        $action = new Action();

        // Set fields
        $action->reference = request('reference');
        $action->type = request('type');
        $action->due_date = request('due_date');
        $action->name = request('name');
        $action->progress = request('progress');
        $action->scope = request('scope');
        $action->cause = request('cause');
        $action->remediation = request('remediation');
        // for Postegres
        $action->criticity = 0;
        $action->status = 0;

        // Save
        $action->save();

        // Sync measures
        $action->controls()->sync($request->input('measures', []));

        // Sync risks
        $action->risks()->sync($request->input('risks', []));

        // Sync owners
        $action->owners()->sync($request->input('owners', []));

        // Return
        return redirect('/actions');
    }

    /**
     * Show Close action.
     *
     * @param  int $id
     *
     * @return \Illuminate\View\View
     */
    public function close(int $id)
    {
        abort_if(
            ! ((Auth::user()->role === 1) ||
            (Auth::user()->role === 2) ||
            (Auth::user()->role === 3)),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden'
        );

        // Get the action
        $action = Action::find($id);

        // Control not found
        abort_if($action === null, Response::HTTP_NOT_FOUND, '404 Not Found');

        // Return
        return view('actions.close')
            ->with('action', $action);
    }

    /**
     * Show Close action.
     *
     * @param  \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function doClose(Request $request)
    {
        abort_if(
            ! ((Auth::user()->role === 1) ||
            (Auth::user()->role === 2) ||
            (Auth::user()->role === 3)),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden'
        );

        // Get the action
        $id = request('id');
        $action = Action::find($id);

        // Control not found
        abort_if($action === null, Response::HTTP_NOT_FOUND, '404 Not Found');

        // Get fields
        $action->status = request('status');
        $action->close_date = request('close_date');
        $action->justification = request('justification');

        // Save action
        $action->save();

        // Return
        return redirect('/actions');
    }

    public function export()
    {
        // For administrators and users only
        abort_if(
            Auth::user()->role !== 1 && Auth::user()->role !== 2,
            Response::HTTP_FORBIDDEN,
            '403 Forbidden'
        );

        return Excel::download(
            new ActionsExport(),
            trans('cruds.action.title') .
                '-' .
                now()->format('Y-m-d Hi') .
                '.xlsx'
        );
    }

    public function delete()
    {
        // For administrators and users only
        abort_if(
            Auth::user()->role !== 1 && Auth::user()->role !== 2,
            Response::HTTP_FORBIDDEN,
            '403 Forbidden'
        );

        // Get the action plan
        $id = (int) request('id');
        $action = Action::find($id);

        // Action not found
        abort_if($action === null, Response::HTTP_NOT_FOUND, '404 Not Found');

        // delete links
        DB::table('action_control')->where('action_id', $action->id)->delete();

        // relete links to owners
        $action->owners()->detach();

        // delete
        $action->delete();

        // Return
        return redirect('/actions');
    }

    /**
     * Display the actions chart
     *
     * @param  \Illuminate\Http\Request $request
     *
     * @return \Illuminate\View\View
     */
    public function chart(Request $request)
    {
        abort_if(
            ! ((Auth::user()->role === 1) ||
            (Auth::user()->role === 2)),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden'
        );

        // Start
        $start = $request->get('start');
        if ($start === null) {
            $start = Carbon::now()->startOfYear()->toDateString();
        }
        // End
        $end = $request->get('start');
        if ($end === null) {
            $end = Carbon::now()->today()->toDateString();
        }
        // Get scope
        $scope = $request->get('scope');
        if ($scope !== null) {
            $request->session()->put('scope', $scope);
        } else {
            if ($request->has('scope')) {
                $request->session()->forget('scope');
            } else {
                $scope = $request->session()->get('scope');
            }
        }

        // Get scopes
        $scopes = DB::table('actions')
            ->select('scope')
            ->whereNotNull('scope')
            ->where('status', '<>', 3)
            ->distinct()
            ->orderBy('scope')
            ->pluck('scope')
            ->toArray();

        // Get data
        $types = [1, 2, 3, 4];
        $data = [];

        foreach ($types as $type) {
            $count_open = Action::where('type', $type)
                ->where('status', 0)
                ->where(function ($query) use ($start) {
                    $query->whereDate('close_date', '>', $start)
                        ->orWhereNull('close_date');
                })
                ->where(function ($query) use ($end) {
                    $query->whereDate('close_date', '<', $end)
                        ->orWhereNull('close_date');
                })
                ->when(! is_null($scope), function ($query) use ($scope) {
                    $query->where('scope', $scope);
                })
                ->count();
            $count_closed = Action::where('type', $type)
                ->whereIn('status', [1, 2])
                ->where(function ($query) use ($start) {
                    $query->whereDate('close_date', '>', $start)
                        ->orWhereNull('close_date');
                })
                ->where(function ($query) use ($end) {
                    $query->whereDate('close_date', '<', $end)
                        ->orWhereNull('close_date');
                })
                ->when(! is_null($scope), function ($query) use ($scope) {
                    $query->where('scope', $scope);
                })
                ->count();

            $data[] = [
                'type' => $type,
                'open' => $count_open,
                'closed' => $count_closed,
            ];
        }
        // Get Actions in scrope
        $actions = Action
            ::where(function ($query) use ($start) {
                $query->whereDate('close_date', '>', $start)
                    ->orWhereNull('close_date');
            })
                ->where(function ($query) use ($end) {
                    $query->whereDate('close_date', '<', $end)
                        ->orWhereNull('close_date');
                })
                ->when(! is_null($scope), function ($query) use ($scope) {
                    $query->where('scope', $scope);
                })
                ->get();

        // Return
        return view('radar.actions')
            ->with('start', $start)
            ->with('end', $end)
            ->with('scopes', $scopes)
            ->with('actions', $actions)
            ->with('data', $data);
    }
}
