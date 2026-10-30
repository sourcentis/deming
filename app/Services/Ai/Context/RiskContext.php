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
            'nom' => $risk->name,
            'description' => $this->text($risk->description),
            'probabilite' => $this->level($config, 'probability', $risk->probability),
            'commentaire_probabilite' => $this->text($risk->probability_comment),
            'impact' => $this->level($config, 'impact', $risk->impact),
            'commentaire_impact' => $this->text($risk->impact_comment),
            'exposition' => $this->level($config, 'exposure', $risk->exposure),
            'vulnerabilite' => $this->level($config, 'vulnerability', $risk->vulnerability),
            'risque_residuel' => $risk->residual_risk,
            'methode_scoring' => [
                'nom' => $config->name,
                'formule' => $config->formula,
                'seuils' => array_map(fn (array $t) => [
                    'niveau' => $t['level'] ?? null,
                    'libelle' => $t['label'] ?? null,
                    'max' => $t['max'] ?? null,
                ], $config->risk_thresholds ?? []),
            ],
            'score' => $risk->risk_score,
            'niveau' => $risk->risk_level,
            'niveau_libelle' => $risk->risk_level_label,
            'statut' => $risk->status,
            'statut_libelle' => Risk::statusLabel((string) $risk->status),
            'commentaire_statut' => $this->text($risk->status_comment),
            'frequence_revue_mois' => $risk->review_frequency,
            'prochaine_revue' => $this->date($risk->next_review_at),
            'revue_en_retard' => $risk->is_overdue,
            'a_un_proprietaire' => $risk->owner_id !== null,
            'necessite_mesures' => $risk->requiresControls(),
            'necessite_actions' => $risk->requiresActions(),
            'mesures_liees' => $risk->controls()->orderBy('clause')->get()
                ->map(fn ($c) => [
                    'clause' => $c->clause,
                    'nom' => $c->name,
                    'dernier_controle' => $this->lastControl($c->id),
                ])->all(),
            'actions_liees' => $risk->actions()->orderBy('due_date')->get()
                ->map(fn (Action $a) => [
                    'reference' => $a->reference,
                    'nom' => $a->name,
                    'criticite' => $a->criticity,
                    'avancement' => $a->progress,
                    'echeance' => $this->date($a->due_date),
                    'cloturee' => (int) $a->status !== 0,
                    'en_retard' => $this->actionIsLate($a),
                ])->all(),
        ];
    }

    /** @return array<string, mixed>|null */
    private function level(RiskScoringConfig $config, string $field, ?int $value): ?array
    {
        if ($value === null) {
            return null;
        }

        return ['valeur' => $value, 'libelle' => $config->levelLabel($field, $value)];
    }
}
