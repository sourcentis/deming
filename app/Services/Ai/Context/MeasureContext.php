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
            'name' => $measure->name,
            'scope' => $measure->scope,
            'security_measures' => $measure->controls()->orderBy('clause')->get()
                ->map(fn ($c) => ['clause' => $c->clause, 'name' => $c->name])->all(),
            'objective' => $this->text($measure->objective),
            'attributes' => $this->text($measure->getAttribute('attributes')),
            'input' => $this->text($measure->input),
            'model' => $this->text($measure->model),
            'indicator' => $this->text($measure->indicator),
            'periodicity' => $this->periodicity($measure->periodicity),
            'status' => self::MEASURE_STATUS[$measure->status] ?? $measure->status,
            'planned_date' => $this->date($measure->plan_date),
            'completion_date' => $this->date($measure->realisation_date),
            'late' => $this->measureIsLate($measure),
            'score' => $this->score($measure->score),
            'note' => $measure->note,
            'observations' => $this->text($measure->observations),
            'action_plan' => $this->text($measure->action_plan),
            'documents' => [
                'count' => $documents->count(),
                'files' => $documents->all(),
            ],
            'actions' => Action::query()->where('measure_id', $measure->id)->orderBy('due_date')->get()
                ->map(fn (Action $a) => $this->actionSummary($a))->all(),
            'history' => $history->map(fn (Measure $m) => [
                'completion_date' => $this->date($m->realisation_date),
                'score' => $this->score($m->score),
                'observations' => $this->text($m->observations, self::SUMMARY_LIMIT),
                'action_plan' => $this->text($m->action_plan, self::SUMMARY_LIMIT),
            ])->all(),
        ];
    }
}
