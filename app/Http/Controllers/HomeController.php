<?php

namespace App\Http\Controllers;

use App\Models\Exception;
use App\Models\Risk;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        abort_if(Auth::user()->isAPI(),Response::HTTP_FORBIDDEN, '403 Forbidden');

        // Fetch counts and data using optimized queries
        $activeDomainsCount = $this->getActiveDomainsCount();
        $controlsCount = $this->getControlsCount();
        $activeMeasuresCount = $this->getActiveMeasuresCount();
        $controlsMadeCount = $this->getControlsMadeCount();
        $controlsNeverMade = $this->getControlsNeverMade();
        $planedControlsThisMonthCount = $this->getPlanedControlsThisMonthCount();
        $lateControlsCount = $this->getLateControlsCount();
        $actionPlansCount = $this->getActionPlansCount();
        $risksCount = $this->getRisksCount();
        $exceptionsCount = $this->getExceptionsCount();

        $activeControls = $this->getActiveControls();
        $controlsTodo = $this->getControlsTodo();
        $expandedControls = $this->getExpandedControls();
        $controlsToValidate = $expandedControls->filter(fn ($control) => (int) $control->status === 1)->count();

        // Store counts in session
        $request->session()->put([
            'planed_controls_this_month_count' => $planedControlsThisMonthCount,
            'late_controls_count' => $lateControlsCount,
            'action_plans_count' => $actionPlansCount,
        ]);

        // Return view with data
        return view('welcome', [
            'active_domains_count' => $activeDomainsCount,
            'controls_count' => $controlsCount,
            'active_measures_count' => $activeMeasuresCount,
            'controls_made_count' => $controlsMadeCount,
            'controls_never_made' => $controlsNeverMade,
            'controls_to_validate' => $controlsToValidate,
            'can_validate' => Auth::user()->isAdmin() || Auth::user()->isUser(),
            'risks_count' => $risksCount,
            'exceptions_count' => $exceptionsCount,
            'active_controls' => $activeControls,
            'controls_todo' => $controlsTodo,
            'action_plans_count' => $actionPlansCount,
            'late_controls_count' => $lateControlsCount,
            'controls' => $expandedControls,
        ]);
    }

    private function getActiveDomainsCount()
    {
        $query = DB::table('measures')
            ->join('control_measure', 'measures.id', '=', 'measure_id')
            ->join('controls', 'control_measure.control_id', '=', 'controls.id');

        // Filtrer sauf si l'utilisateur voit toutes les données
        if (!Auth::user()->seesAllData()) {
            $userId = Auth::id();
            $query->where(function($q) use ($userId) {
                $q->whereExists(function($subQ) use ($userId) {
                    $subQ->select(DB::raw(1))
                        ->from('measure_user')
                        ->whereColumn('measure_user.measure_id', 'measures.id')
                        ->where('measure_user.user_id', $userId);
                })
                    ->orWhereExists(function($subQ) use ($userId) {
                        $subQ->select(DB::raw(1))
                            ->from('measure_user_group')
                            ->join('user_user_group', 'user_user_group.user_group_id', '=', 'measure_user_group.user_group_id')
                            ->whereColumn('measure_user_group.measure_id', 'measures.id')
                            ->where('user_user_group.user_id', $userId);
                    });
            });
        }

        return $query->whereIn('status', [0, 1])
            ->distinct('controls.domain_id')
            ->count('controls.domain_id');
    }
    private function getControlsCount()
    {
        $query = DB::table('controls');

        // Filtrer sauf si l'utilisateur voit toutes les données
        if (!Auth::user()->seesAllData()) {
            $userId = Auth::id();
            $query->whereExists(function($q) use ($userId) {
                $q->select(DB::raw(1))
                    ->from('control_measure')
                    ->whereColumn('control_measure.control_id', 'controls.id')
                    ->where(function($subQuery) use ($userId) {
                        $subQuery->whereExists(function($subQ) use ($userId) {
                            $subQ->select(DB::raw(1))
                                ->from('measure_user')
                                ->whereColumn('measure_user.measure_id', 'control_measure.measure_id')
                                ->where('measure_user.user_id', $userId);
                        })
                            ->orWhereExists(function($subQ) use ($userId) {
                                $subQ->select(DB::raw(1))
                                    ->from('measure_user_group')
                                    ->join('user_user_group', 'user_user_group.user_group_id', '=', 'measure_user_group.user_group_id')
                                    ->whereColumn('measure_user_group.measure_id', 'control_measure.measure_id')
                                    ->where('user_user_group.user_id', $userId);
                            });
                    });
            });
        }

        return $query->count();
    }

    private function getActiveMeasuresCount()
    {
        $query = DB::table('measures');

        // Filtrer sauf si l'utilisateur voit toutes les données
        if (!Auth::user()->seesAllData()) {
            $userId = Auth::id();
            $query->where(function($q) use ($userId) {
                $q->whereExists(function($subQ) use ($userId) {
                    $subQ->select(DB::raw(1))
                        ->from('measure_user')
                        ->whereColumn('measure_user.measure_id', 'measures.id')
                        ->where('measure_user.user_id', $userId);
                })
                    ->orWhereExists(function($subQ) use ($userId) {
                        $subQ->select(DB::raw(1))
                            ->from('measure_user_group')
                            ->join('user_user_group', 'user_user_group.user_group_id', '=', 'measure_user_group.user_group_id')
                            ->whereColumn('measure_user_group.measure_id', 'measures.id')
                            ->where('user_user_group.user_id', $userId);
                    });
            });
        }

        return $query->whereIn('status', [0, 1])
            ->count();
    }

    private function getControlsMadeCount()
    {
        $query = DB::table('measures');

        // Filtrer sauf si l'utilisateur voit toutes les données
        if (!Auth::user()->seesAllData()) {
            $userId = Auth::id();
            $query->where(function($q) use ($userId) {
                $q->whereExists(function($subQ) use ($userId) {
                    $subQ->select(DB::raw(1))
                        ->from('measure_user')
                        ->whereColumn('measure_user.measure_id', 'measures.id')
                        ->where('measure_user.user_id', $userId);
                })
                    ->orWhereExists(function($subQ) use ($userId) {
                        $subQ->select(DB::raw(1))
                            ->from('measure_user_group')
                            ->join('user_user_group', 'user_user_group.user_group_id', '=', 'measure_user_group.user_group_id')
                            ->whereColumn('measure_user_group.measure_id', 'measures.id')
                            ->where('user_user_group.user_id', $userId);
                    });
            })
            ->whereIn('status', [1, 2]);
        }
        else
            $query = $query->where('status', 2);
        return $query->count();
    }

    private function getControlsNeverMade()
    {
        $query = DB::table('measures as c1')
            ->leftJoin('measures as c2', 'c2.next_id', '=', 'c1.id');

        // Filtrer sauf si l'utilisateur voit toutes les données
        if (!Auth::user()->seesAllData()) {
            $userId = Auth::id();
            $query->where(function($q) use ($userId) {
                $q->whereExists(function($subQ) use ($userId) {
                    $subQ->select(DB::raw(1))
                        ->from('measure_user')
                        ->whereColumn('measure_user.measure_id', 'c1.id')
                        ->where('measure_user.user_id', $userId);
                })
                    ->orWhereExists(function($subQ) use ($userId) {
                        $subQ->select(DB::raw(1))
                            ->from('measure_user_group')
                            ->join('user_user_group', 'user_user_group.user_group_id', '=', 'measure_user_group.user_group_id')
                            ->whereColumn('measure_user_group.measure_id', 'c1.id')
                            ->where('user_user_group.user_id', $userId);
                    });
            });
        }

        return $query->whereNull('c1.realisation_date')
            ->whereNull('c2.id')
            ->count();
    }

    private function getPlanedControlsThisMonthCount()
    {
        $query = DB::table('measures');

        // Filtrer sauf si l'utilisateur voit toutes les données
        if (!Auth::user()->seesAllData()) {
            $userId = Auth::id();
            $query->where(function($q) use ($userId) {
                $q->whereExists(function($subQ) use ($userId) {
                    $subQ->select(DB::raw(1))
                        ->from('measure_user')
                        ->whereColumn('measure_user.measure_id', 'measures.id')
                        ->where('measure_user.user_id', $userId);
                })
                    ->orWhereExists(function($subQ) use ($userId) {
                        $subQ->select(DB::raw(1))
                            ->from('measure_user_group')
                            ->join('user_user_group', 'user_user_group.user_group_id', '=', 'measure_user_group.user_group_id')
                            ->whereColumn('measure_user_group.measure_id', 'measures.id')
                            ->where('user_user_group.user_id', $userId);
                    });
            });
        }

        return $query->whereNull('realisation_date')
            ->whereBetween('plan_date', [
                Carbon::now()->startOfMonth(),
                Carbon::now()->endOfMonth(),
            ])
            ->count();
    }


    private function getLateControlsCount()
    {
        $query = DB::table('measures');

        // Filtrer sauf si l'utilisateur voit toutes les données
        if (!Auth::user()->seesAllData()) {
            $userId = Auth::id();
            $query->where(function($q) use ($userId) {
                $q->whereExists(function($subQ) use ($userId) {
                    $subQ->select(DB::raw(1))
                        ->from('measure_user')
                        ->whereColumn('measure_user.measure_id', 'measures.id')
                        ->where('measure_user.user_id', $userId);
                })
                    ->orWhereExists(function($subQ) use ($userId) {
                        $subQ->select(DB::raw(1))
                            ->from('measure_user_group')
                            ->join('user_user_group', 'user_user_group.user_group_id', '=', 'measure_user_group.user_group_id')
                            ->whereColumn('measure_user_group.measure_id', 'measures.id')
                            ->where('user_user_group.user_id', $userId);
                    });
            });
        }

        return $query->whereNull('realisation_date')
            ->where('plan_date', '<', Carbon::today())
            ->count();
    }

    private function getActionPlansCount()
    {
        $query = DB::table('actions');

        // Filtrer sauf si l'utilisateur voit toutes les données
        if (!Auth::user()->seesAllData()) {
            $userId = Auth::id();
            $query->whereExists(function($q) use ($userId) {
                $q->select(DB::raw(1))
                    ->from('action_user')
                    ->whereColumn('action_user.action_id', 'actions.id')
                    ->where('action_user.user_id', $userId);
            });
        }

        return $query->where('status', 0)
            ->count();
    }

    private function getActiveControls()
    {
        $query = DB::table('measures as c1')
            ->select(['c1.id', 'controls.id', 'domains.title', 'c1.realisation_date', 'c1.score'])
            ->join('measures as c2', 'c2.id', '=', 'c1.next_id')
            ->join('control_measure', 'control_measure.measure_id', '=', 'c1.id')
            ->join('controls', 'control_measure.control_id', '=', 'controls.id')
            ->join('domains', 'domains.id', '=', 'controls.domain_id');

        // Filtrer sauf si l'utilisateur voit toutes les données
        if (!Auth::user()->seesAllData()) {
            $userId = Auth::id();
            $query->where(function($q) use ($userId) {
                $q->whereExists(function($subQ) use ($userId) {
                    $subQ->select(DB::raw(1))
                        ->from('measure_user')
                        ->whereColumn('measure_user.measure_id', 'c1.id')
                        ->where('measure_user.user_id', $userId);
                })
                    ->orWhereExists(function($subQ) use ($userId) {
                        $subQ->select(DB::raw(1))
                            ->from('measure_user_group')
                            ->join('user_user_group', 'user_user_group.user_group_id', '=', 'measure_user_group.user_group_id')
                            ->whereColumn('measure_user_group.measure_id', 'c1.id')
                            ->where('user_user_group.user_id', $userId);
                    });
            });
        }

        return $query->whereNull('c2.realisation_date')
            ->orderBy('c1.id')
            ->get();
    }
    private function getControlsTodo()
    {
        $query = DB::table('measures as c1')
            ->select([
                'c1.id', 'c1.name', 'c1.scope', 'c1.plan_date', 'c1.status',
                'c2.id as prev_id', 'c2.realisation_date as prev_date', 'c2.score as score',
            ])
            ->leftJoin('measures as c2', 'c1.id', '=', 'c2.next_id');

        // Filtrer sauf si l'utilisateur voit toutes les données
        if (!Auth::user()->seesAllData()) {
            $userId = Auth::id();
            $query->where(function($q) use ($userId) {
                $q->whereExists(function($subQ) use ($userId) {
                    $subQ->select(DB::raw(1))
                        ->from('measure_user')
                        ->whereColumn('measure_user.measure_id', 'c1.id')
                        ->where('measure_user.user_id', $userId);
                })
                    ->orWhereExists(function($subQ) use ($userId) {
                        $subQ->select(DB::raw(1))
                            ->from('measure_user_group')
                            ->join('user_user_group', 'user_user_group.user_group_id', '=', 'measure_user_group.user_group_id')
                            ->whereColumn('measure_user_group.measure_id', 'c1.id')
                            ->where('user_user_group.user_id', $userId);
                    });
            })
                // Uniquement les mesures à faire
                ->where('c1.status', '=', 0);
        }
        else
            // Pour les mesures à faire et à valider
            $query = $query->whereIn('c1.status', [0, 1]);

        $controlsTodo = $query
            ->where('c1.plan_date', '<', Carbon::today()->addDays(30))
            ->orderBy('c1.plan_date')
            ->get();

        // Fetch related security measures (controls) in a single query
        $controlMeasures = DB::table('control_measure')
            ->select(['measure_id', 'control_id', 'clause'])
            ->leftJoin('controls', 'controls.id', '=', 'control_id')
            ->whereIn('measure_id', $controlsTodo->pluck('id'))
            ->orderBy('clause')
            ->get()
            ->groupBy('measure_id');

        // Map over controlsTodo to add security measure (control) data
        $controlsTodo->map(function ($control) use ($controlMeasures) {
            $control->measures = $controlMeasures->get($control->id, collect())->map(function ($controlMeasure) {
                return [
                    'id' => $controlMeasure->control_id,
                    'clause' => $controlMeasure->clause,
                ];
            });
            return $control;
        });

        return $controlsTodo;
    }
    private function getExpandedControls()
    {
        $query = DB::table('measures')
            ->select('id', 'score', 'status', 'realisation_date', 'plan_date', 'periodicity');

        // Filtrer sauf si l'utilisateur voit toutes les données
        if (!Auth::user()->seesAllData()) {
            $userId = Auth::id();
            $query->where(function($q) use ($userId) {
                $q->whereExists(function($subQ) use ($userId) {
                    $subQ->select(DB::raw(1))
                        ->from('measure_user')
                        ->whereColumn('measure_user.measure_id', 'measures.id')
                        ->where('measure_user.user_id', $userId);
                })
                    ->orWhereExists(function($subQ) use ($userId) {
                        $subQ->select(DB::raw(1))
                            ->from('measure_user_group')
                            ->join('user_user_group', 'user_user_group.user_group_id', '=', 'measure_user_group.user_group_id')
                            ->whereColumn('measure_user_group.measure_id', 'measures.id')
                            ->where('user_user_group.user_id', $userId);
                    });
            });
        }

        $measures = $query->get();

        return $measures->flatMap(function ($measure) {
            $expanded = collect([$measure]);

            if ($measure->realisation_date === null) {
                if ($measure->periodicity === -1) {
                    for ($i = 1; $i <= 32; $i++) {
                        $repeatedMeasure = clone $measure;
                        $repeatedMeasure->id = null;
                        $repeatedMeasure->score = null;
                        $repeatedMeasure->observations = null;
                        $repeatedMeasure->realisation_date = null;
                        $repeatedMeasure->plan_date = Carbon::parse($measure->plan_date)->addDays($i * 7);
                        $expanded->push($repeatedMeasure);
                    }
                }
                else if ($measure->periodicity > 0 && $measure->periodicity <= 12) {
                    for ($i = 1; $i <= 12 / $measure->periodicity; $i++) {
                        $repeatedMeasure = clone $measure;
                        $repeatedMeasure->id = null;
                        $repeatedMeasure->score = null;
                        $repeatedMeasure->observations = null;
                        $repeatedMeasure->realisation_date = null;
                        $repeatedMeasure->plan_date = Carbon::parse($measure->plan_date)->addMonthsNoOverflow($i * $measure->periodicity);
                        $expanded->push($repeatedMeasure);
                    }
                }
            }
            return $expanded;
        });
    }

    private function getRisksCount() {
        $query = Risk::query();

        if (!Auth::user()->seesAllData()) {
            $query->ownedBy(Auth::user()->id);
        }

        return $query->count();
    }

    private function getExceptionsCount() {
        $query = Exception::query();


        return $query->count();
    }

}
