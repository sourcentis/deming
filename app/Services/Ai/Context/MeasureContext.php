<?php

namespace App\Services\Ai\Context;

use App\Models\Action;
use App\Models\Measure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Context of a control (model Measure, "contrôle" in the UI).
 */
class MeasureContext extends ContextBuilder
{
    /** @param  Measure  $object */
    public function build(Model $object): array
    {
        // Toute modification ici doit être reportée dans docs/ai*.md#privacy
        $measure = $object;

        $controlIds = DB::table('control_measure')->where('measure_id', $measure->id)->pluck('control_id');

        // Previous controls made on the same security measure(s)
        $history = Measure::query()
            ->whereIn('id', DB::table('control_measure')->whereIn('control_id', $controlIds)->select('measure_id'))
            ->where('id', '<>', $measure->id)
            ->whereNotNull('realisation_date')
            ->orderByDesc('realisation_date')
            ->orderByDesc('id')
            ->limit(self::HISTORY_LIMIT)
            ->get();

        $documents = DB::table('documents')->where('measure_id', $measure->id)->orderBy('filename')->pluck('filename');

        return [
            'nom' => $measure->name,
            'perimetre' => $measure->scope,
            'mesures' => $measure->controls()->orderBy('clause')->get()
                ->map(fn ($c) => ['clause' => $c->clause, 'nom' => $c->name])->all(),
            'objectif' => $this->text($measure->objective),
            'attributs' => $this->text($measure->getAttribute('attributes')),
            'donnees_entree' => $this->text($measure->input),
            'modele' => $this->text($measure->model),
            'indicateur' => $this->text($measure->indicator),
            'periodicite' => $this->periodicity($measure->periodicity),
            'statut' => self::MEASURE_STATUS[$measure->status] ?? $measure->status,
            'date_planifiee' => $this->date($measure->plan_date),
            'date_realisation' => $this->date($measure->realisation_date),
            'en_retard' => $this->measureIsLate($measure),
            'score' => $this->score($measure->score),
            'note' => $measure->note,
            'observations' => $this->text($measure->observations),
            'plan_action' => $this->text($measure->action_plan),
            'documents' => [
                'nombre' => $documents->count(),
                'fichiers' => $documents->all(),
            ],
            'actions' => Action::query()->where('measure_id', $measure->id)->orderBy('due_date')->get()
                ->map(fn (Action $a) => $this->actionSummary($a))->all(),
            'historique' => $history->map(fn (Measure $m) => [
                'date_realisation' => $this->date($m->realisation_date),
                'score' => $this->score($m->score),
                'observations' => $this->text($m->observations, self::SUMMARY_LIMIT),
                'plan_action' => $this->text($m->action_plan, self::SUMMARY_LIMIT),
            ])->all(),
        ];
    }
}
