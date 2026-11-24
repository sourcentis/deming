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
    private const EXCEPTION_STATUS = [0 => 'brouillon', 1 => 'soumise', 2 => 'approuvee', 3 => 'rejetee', 4 => 'expiree'];

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
            'nom' => $control->name,
            'domaine' => $control->domain === null ? null : [
                'referentiel' => $control->domain->framework,
                'titre' => $control->domain->title,
            ],
            'objectif' => $this->text($control->objective),
            'attributs' => $this->text($control->getAttribute('attributes')),
            'donnees_entree' => $this->text($control->input),
            'modele' => $this->text($control->model),
            'indicateur' => $this->text($control->indicator),
            'plan_action' => $this->text($control->action_plan),
            'periodicite' => $this->periodicity($latest?->periodicity),
            'controles' => $controls->map(fn (array $c) => $this->controlSummary($c['latest'], $c['done']))->all(),
            'controles_planifies' => $planned->map(fn (Measure $m) => [
                'date_planifiee' => $this->date($m->plan_date),
                'statut' => self::MEASURE_STATUS[$m->status] ?? $m->status,
                'en_retard' => $this->measureIsLate($m),
            ])->all(),
            'actions_ouvertes' => $actions->map(fn (Action $a) => $this->actionSummary($a))->all(),
            'risques_lies' => $control->risks()->orderBy('name')->get()
                ->map(fn ($r) => $this->riskSummary($r))->all(),
            'exceptions' => Exception::query()->where('control_id', $control->id)->orderBy('start_date')->get()
                ->map(fn (Exception $e) => [
                    'nom' => $e->name,
                    'statut' => self::EXCEPTION_STATUS[$e->status] ?? $e->status,
                    'date_debut' => $this->date($e->start_date),
                    'date_fin' => $this->date($e->end_date),
                    'expiree' => $e->is_expired,
                    'justification' => $this->text($e->justification, self::SUMMARY_LIMIT),
                    'mesures_compensatoires' => $this->text($e->compensating_controls, self::SUMMARY_LIMIT),
                ])->all(),
            'correspondances' => $mappings,
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
            'nom' => $latest->name,
            'perimetre' => $this->text($latest->scope, self::SUMMARY_LIMIT),
            'objectif' => $this->text($latest->objective, self::SUMMARY_LIMIT),
            'donnees_entree' => $this->text($latest->input, self::SUMMARY_LIMIT),
            'modele' => $this->text($latest->model, self::SUMMARY_LIMIT),
            'indicateur' => $this->text($latest->indicator, self::SUMMARY_LIMIT),
            'periodicite' => $this->periodicity($latest->periodicity),
            'dernier_resultat' => $last === null ? null : [
                'date' => $this->date($last->realisation_date),
                'score' => $this->score($last->score),
                'observations' => $this->text($last->observations, self::SUMMARY_LIMIT),
            ],
            'historique' => array_map(fn (Measure $m) => [
                'date' => $this->date($m->realisation_date),
                'score' => $this->score($m->score),
            ], array_slice($done, 0, self::CONTROL_HISTORY)),
            'prochaine_date' => $planned ? $this->date($latest->plan_date) : null,
            'en_retard' => $planned && $this->measureIsLate($latest),
        ];
    }

    /** @return array<string, mixed> */
    private function mapping(ControlMapping $mapping, ?Control $other): array
    {
        return [
            'referentiel' => $other?->domain?->framework,
            'clause' => $other?->clause,
            'nom' => $other?->name,
            'type' => $mapping->mapping_type,
            'couverture' => $mapping->coverage,
        ];
    }
}
