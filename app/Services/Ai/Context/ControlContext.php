<?php

namespace App\Services\Ai\Context;

use App\Models\Action;
use App\Models\Control;
use App\Models\ControlMapping;
use App\Models\Exception;
use App\Models\Measure;
use Illuminate\Database\Eloquent\Model;

/**
 * Context of a security measure (model Control, "mesure" in the UI).
 */
class ControlContext extends ContextBuilder
{
    private const EXCEPTION_STATUS = [0 => 'brouillon', 1 => 'soumise', 2 => 'approuvee', 3 => 'rejetee', 4 => 'expiree'];

    private const MAPPING_LIMIT = 10;

    /** @param  Control  $object */
    public function build(Model $object): array
    {
        // Toute modification ici doit être reportée dans docs/ai*.md#privacy
        $control = $object;

        $measures = Measure::query()
            ->join('control_measure', 'control_measure.measure_id', '=', 'measures.id')
            ->where('control_measure.control_id', $control->id)
            ->select('measures.*');

        $done = (clone $measures)
            ->whereNotNull('measures.realisation_date')
            ->orderByDesc('measures.realisation_date')
            ->orderByDesc('measures.id')
            ->limit(self::HISTORY_LIMIT)
            ->get();

        $planned = (clone $measures)
            ->whereNull('measures.realisation_date')
            ->orderBy('measures.plan_date')
            ->limit(self::HISTORY_LIMIT)
            ->get();

        $latest = $planned->first() ?? $done->first();

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
            'derniers_controles' => $done->map(fn (Measure $m) => [
                'date_realisation' => $this->date($m->realisation_date),
                'score' => $this->score($m->score),
                'observations' => $this->text($m->observations, self::SUMMARY_LIMIT),
                'plan_action' => $this->text($m->action_plan, self::SUMMARY_LIMIT),
            ])->all(),
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
