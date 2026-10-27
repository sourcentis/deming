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
            'nom' => $action->name,
            'type' => self::ACTION_TYPES[$action->type] ?? $action->type,
            'criticite' => $action->criticity,
            'statut' => self::ACTION_STATUS[$action->status] ?? $action->status,
            'perimetre' => $action->scope,
            'cause' => $this->text($action->cause),
            'remediation' => $this->text($action->remediation),
            'date_creation' => $this->date($action->creation_date),
            'date_echeance' => $this->date($action->due_date),
            'date_cloture' => $this->date($action->close_date),
            'avancement' => $action->progress,
            'justification' => $this->text($action->justification),
            'en_retard' => $this->actionIsLate($action),
            'nombre_responsables' => $action->owners()->count(),
            'mesures_liees' => $action->controls()->orderBy('clause')->get()
                ->map(fn ($c) => [
                    'clause' => $c->clause,
                    'nom' => $c->name,
                    'dernier_controle' => $this->lastControl($c->id),
                ])->all(),
            'risques_lies' => $action->risks()->get()
                ->map(fn ($r) => $this->riskSummary($r))->all(),
            'historique_progression' => array_map(fn (array $step) => [
                'date' => $step['date'],
                'avancement' => $step['progress'],
            ], $history),
        ];
    }
}
