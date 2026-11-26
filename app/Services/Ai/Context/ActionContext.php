<?php

namespace App\Services\Ai\Context;

use App\Models\Action;
use Illuminate\Database\Eloquent\Model;

/**
 * Context of an action plan (model Action).
 */
class ActionContext extends ContextBuilder
{
    /** @param  Action  $object */
    public function build(Model $object): array
    {
        // Toute modification ici doit être reportée dans docs/ai*.md#privacy
        $action = $object;

        // Progress history without author, last 5 steps
        $history = array_slice($action->history(), -self::HISTORY_LIMIT);

        return [
            'reference' => $action->reference,
            'name' => $action->name,
            'type' => self::ACTION_TYPES[$action->type] ?? $action->type,
            'criticity' => $action->criticity,
            'status' => self::ACTION_STATUS[$action->status] ?? $action->status,
            'scope' => $action->scope,
            'cause' => $this->text($action->cause),
            'remediation' => $this->text($action->remediation),
            'creation_date' => $this->date($action->creation_date),
            'due_date' => $this->date($action->due_date),
            'close_date' => $this->date($action->close_date),
            'progress' => $action->progress,
            'justification' => $this->text($action->justification),
            'late' => $this->actionIsLate($action),
            'owner_count' => $action->owners()->count(),
            'security_measures' => $action->controls()->orderBy('clause')->get()
                ->map(fn ($c) => [
                    'clause' => $c->clause,
                    'name' => $c->name,
                    'last_control' => $this->lastControl($c->id),
                ])->all(),
            'related_risks' => $action->risks()->get()
                ->map(fn ($r) => $this->riskSummary($r))->all(),
            'progress_history' => array_map(fn (array $step) => [
                'date' => $step['date'],
                'progress' => $step['progress'],
            ], $history),
        ];
    }
}
