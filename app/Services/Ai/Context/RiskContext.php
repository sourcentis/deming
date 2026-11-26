<?php

namespace App\Services\Ai\Context;

use App\Models\Action;
use App\Models\Risk;
use App\Models\RiskScoringConfig;
use App\Services\RiskScoringService;
use Illuminate\Database\Eloquent\Model;

/**
 * Context of a risk (model Risk).
 */
class RiskContext extends ContextBuilder
{
    /** @param  Risk  $object */
    public function build(Model $object): array
    {
        // Toute modification ici doit être reportée dans docs/ai*.md#privacy
        $risk = $object;
        $config = app(RiskScoringService::class)->config();

        return [
            'name' => $risk->name,
            'description' => $this->text($risk->description),
            'probability' => $this->level($config, 'probability', $risk->probability),
            'probability_comment' => $this->text($risk->probability_comment),
            'impact' => $this->level($config, 'impact', $risk->impact),
            'impact_comment' => $this->text($risk->impact_comment),
            'exposure' => $this->level($config, 'exposure', $risk->exposure),
            'vulnerability' => $this->level($config, 'vulnerability', $risk->vulnerability),
            'residual_risk' => $risk->residual_risk,
            'scoring_method' => [
                'name' => $config->name,
                'formula' => $config->formula,
                'thresholds' => array_map(fn (array $t) => [
                    'level' => $t['level'] ?? null,
                    'label' => $t['label'] ?? null,
                    'max' => $t['max'] ?? null,
                ], $config->risk_thresholds ?? []),
            ],
            'score' => $risk->risk_score,
            'level' => $risk->risk_level,
            'level_label' => $risk->risk_level_label,
            'status' => $risk->status,
            'status_label' => Risk::statusLabel((string) $risk->status),
            'status_comment' => $this->text($risk->status_comment),
            'review_frequency_months' => $risk->review_frequency,
            'next_review' => $this->date($risk->next_review_at),
            'review_overdue' => $risk->is_overdue,
            'has_owner' => $risk->owner_id !== null,
            'requires_security_measures' => $risk->requiresControls(),
            'requires_actions' => $risk->requiresActions(),
            'security_measures' => $risk->controls()->orderBy('clause')->get()
                ->map(fn ($c) => [
                    'clause' => $c->clause,
                    'name' => $c->name,
                    'last_control' => $this->lastControl($c->id),
                ])->all(),
            'action_plans' => $risk->actions()->orderBy('due_date')->get()
                ->map(fn (Action $a) => [
                    'reference' => $a->reference,
                    'name' => $a->name,
                    'criticity' => $a->criticity,
                    'progress' => $a->progress,
                    'due_date' => $this->date($a->due_date),
                    'closed' => (int) $a->status !== 0,
                    'late' => $this->actionIsLate($a),
                ])->all(),
        ];
    }

    /** @return array<string, mixed>|null */
    private function level(RiskScoringConfig $config, string $field, ?int $value): ?array
    {
        if ($value === null) {
            return null;
        }

        return ['value' => $value, 'label' => $config->levelLabel($field, $value)];
    }
}
