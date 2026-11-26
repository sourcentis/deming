<?php

namespace App\Services\Ai\Context;

use App\Models\Action;
use App\Models\Control;
use App\Models\ControlMapping;
use App\Models\Exception;
use App\Models\Measure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Context of a security measure (model Control, "mesure" in the UI).
 */
class ControlContext extends ContextBuilder
{
    private const EXCEPTION_STATUS = [0 => 'draft', 1 => 'submitted', 2 => 'approved', 3 => 'rejected', 4 => 'expired'];

    private const MAPPING_LIMIT = 10;

    // Distinct controls sent, and results kept in the history of each one
    private const CONTROL_LIMIT = 15;

    private const CONTROL_HISTORY = 3;

    /** @param  Control  $object */
    public function build(Model $object): array
    {
        // Toute modification ici doit être reportée dans docs/ai*.md#privacy
        $control = $object;

        $measures = Measure::query()
            ->join('control_measure', 'control_measure.measure_id', '=', 'measures.id')
            ->where('control_measure.control_id', $control->id)
            ->select('measures.*');

        $planned = (clone $measures)
            ->whereNull('measures.realisation_date')
            ->orderBy('measures.plan_date')
            ->limit(self::HISTORY_LIMIT)
            ->get();

        $controls = $this->controls((clone $measures)->get());
        $latest = $planned->first() ?? $controls->first()['latest'] ?? null;

        $actions = Action::query()
            ->join('action_control', 'action_control.action_id', '=', 'actions.id')
            ->where('action_control.control_id', $control->id)
            ->where('actions.status', 0)
            ->orderBy('actions.due_date')
            ->select('actions.*')
            ->get();

        $mappings = $control->outgoingMappings()->with('targetControl.domain')->limit(self::MAPPING_LIMIT)->get()
            ->map(fn (ControlMapping $m) => $this->mapping($m, $m->targetControl))
            ->concat(
                $control->incomingMappings()->with('sourceControl.domain')->limit(self::MAPPING_LIMIT)->get()
                    ->map(fn (ControlMapping $m) => $this->mapping($m, $m->sourceControl))
            )
            ->take(self::MAPPING_LIMIT)
            ->values()
            ->all();

        return [
            'clause' => $control->clause,
            'name' => $control->name,
            'domain' => $control->domain === null ? null : [
                'framework' => $control->domain->framework,
                'title' => $control->domain->title,
            ],
            'objective' => $this->text($control->objective),
            'attributes' => $this->text($control->getAttribute('attributes')),
            'input' => $this->text($control->input),
            'model' => $this->text($control->model),
            'indicator' => $this->text($control->indicator),
            'action_plan' => $this->text($control->action_plan),
            'periodicity' => $this->periodicity($latest?->periodicity),
            'controls' => $controls->map(fn (array $c) => $this->controlSummary($c['latest'], $c['done']))->all(),
            'planned_controls' => $planned->map(fn (Measure $m) => [
                'planned_date' => $this->date($m->plan_date),
                'status' => self::MEASURE_STATUS[$m->status] ?? $m->status,
                'late' => $this->measureIsLate($m),
            ])->all(),
            'open_actions' => $actions->map(fn (Action $a) => $this->actionSummary($a))->all(),
            'related_risks' => $control->risks()->orderBy('name')->get()
                ->map(fn ($r) => $this->riskSummary($r))->all(),
            'exceptions' => Exception::query()->where('control_id', $control->id)->orderBy('start_date')->get()
                ->map(fn (Exception $e) => [
                    'name' => $e->name,
                    'status' => self::EXCEPTION_STATUS[$e->status] ?? $e->status,
                    'start_date' => $this->date($e->start_date),
                    'end_date' => $this->date($e->end_date),
                    'expired' => $e->is_expired,
                    'justification' => $this->text($e->justification, self::SUMMARY_LIMIT),
                    'compensating_controls' => $this->text($e->compensating_controls, self::SUMMARY_LIMIT),
                ])->all(),
            'mappings' => $mappings,
        ];
    }

    /**
     * Distinct controls of the measure: one per chain of realisations linked by next_id,
     * represented by its most recent instance (the planned one, or the last one made)
     * and the instances already made, most recent first.
     *
     * @param  Collection<int, Measure>  $measures
     * @return Collection<int, array{latest: Measure, done: list<Measure>}>
     */
    private function controls(Collection $measures): Collection
    {
        $byId = $measures->keyBy('id');
        $previous = $measures->filter(fn (Measure $m) => $m->next_id !== null)->keyBy('next_id');

        return $measures
            // Last instance of each chain: no next one among the measures of this security measure
            ->filter(fn (Measure $m) => $m->next_id === null || ! $byId->has($m->next_id))
            ->map(function (Measure $latest) use ($previous) {
                $done = [];
                $seen = [];
                for ($m = $latest; $m !== null && ! isset($seen[$m->id]); $m = $previous->get($m->id)) {
                    $seen[$m->id] = true;
                    if ($m->realisation_date !== null) {
                        $done[] = $m;
                    }
                }

                return ['latest' => $latest, 'done' => $done];
            })
            ->sortBy(fn (array $c) => [$c['latest']->name, $c['latest']->id])
            ->take(self::CONTROL_LIMIT)
            ->values();
    }

    /**
     * What a control checks, on which scope, and its results.
     *
     * @param  list<Measure>  $done
     * @return array<string, mixed>
     */
    private function controlSummary(Measure $latest, array $done): array
    {
        $last = $done[0] ?? null;
        $planned = $latest->realisation_date === null;

        return [
            'name' => $latest->name,
            'scope' => $this->text($latest->scope, self::SUMMARY_LIMIT),
            'objective' => $this->text($latest->objective, self::SUMMARY_LIMIT),
            'input' => $this->text($latest->input, self::SUMMARY_LIMIT),
            'model' => $this->text($latest->model, self::SUMMARY_LIMIT),
            'indicator' => $this->text($latest->indicator, self::SUMMARY_LIMIT),
            'periodicity' => $this->periodicity($latest->periodicity),
            'last_result' => $last === null ? null : [
                'date' => $this->date($last->realisation_date),
                'score' => $this->score($last->score),
                'observations' => $this->text($last->observations, self::SUMMARY_LIMIT),
            ],
            'history' => array_map(fn (Measure $m) => [
                'date' => $this->date($m->realisation_date),
                'score' => $this->score($m->score),
            ], array_slice($done, 0, self::CONTROL_HISTORY)),
            'next_date' => $planned ? $this->date($latest->plan_date) : null,
            'late' => $planned && $this->measureIsLate($latest),
        ];
    }

    /** @return array<string, mixed> */
    private function mapping(ControlMapping $mapping, ?Control $other): array
    {
        return [
            'framework' => $other?->domain?->framework,
            'clause' => $other?->clause,
            'name' => $other?->name,
            'type' => $mapping->mapping_type,
            'coverage' => $mapping->coverage,
        ];
    }
}
