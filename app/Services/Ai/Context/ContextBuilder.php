<?php

namespace App\Services\Ai\Context;

use App\Models\Action;
use App\Models\Measure;
use App\Models\Risk;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Builds the JSON context sent to the AI agent for one analysed object.
 *
 * Data minimization rules shared by every builder:
 * - no user name, login or e-mail (owners are reduced to a count or a boolean);
 * - no document content (only the number and the file names);
 * - no internal identifiers;
 * - related objects are summarized, histories limited to 5 items;
 * - dates in ISO format; time-dependent facts as booleans only (never a
 *   number of days), so that the context hash does not change every day.
 *
 * Keys are in French, the language of the default prompts.
 */
abstract class ContextBuilder
{
    protected const HISTORY_LIMIT = 5;

    // Max length of the main object's text fields, and of related objects' summaries
    protected const TEXT_LIMIT = 4000;
    protected const SUMMARY_LIMIT = 300;

    // Control (Measure) status
    protected const MEASURE_STATUS = [0 => 'a_faire', 1 => 'propose', 2 => 'realise'];

    // Control (Measure) score
    protected const SCORES = [1 => 'rouge (objectif non atteint)', 2 => 'orange (partiellement atteint)', 3 => 'vert (atteint)'];

    protected const ACTION_STATUS = [0 => 'ouverte', 1 => 'cloturee', 2 => 'rejetee'];
    protected const ACTION_TYPES = [1 => 'majeure', 2 => 'mineure', 3 => 'observation', 4 => 'opportunite'];

    /** @return array<string, mixed> */
    abstract public function build(Model $object): array;

    protected function date(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Carbon::parse($value)->toDateString();
    }

    protected function text(?string $value, int $limit = self::TEXT_LIMIT): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return Str::limit(trim($value), $limit);
    }

    protected function periodicity(?int $months): ?string
    {
        return match ($months) {
            null => null,
            0 => 'unique',
            -1 => 'hebdomadaire',
            1 => 'mensuelle',
            3 => 'trimestrielle',
            6 => 'semestrielle',
            12 => 'annuelle',
            default => $months . ' mois',
        };
    }

    protected function score(?int $score): ?string
    {
        return $score === null ? null : (self::SCORES[$score] ?? (string) $score);
    }

    // Planned date passed and control not made yet
    protected function measureIsLate(Measure $measure): bool
    {
        return $measure->realisation_date === null
            && Carbon::parse($measure->plan_date)->isBefore(Carbon::today());
    }

    // Due date passed and action still open
    protected function actionIsLate(Action $action): bool
    {
        return (int) $action->status === 0
            && $action->due_date !== null
            && Carbon::parse($action->due_date)->isBefore(Carbon::today());
    }

    /**
     * Last control made for a security measure.
     *
     * @return array<string, mixed>|null
     */
    protected function lastControl(int $controlId): ?array
    {
        $measure = Measure::query()
            ->join('control_measure', 'control_measure.measure_id', '=', 'measures.id')
            ->where('control_measure.control_id', $controlId)
            ->whereNotNull('measures.realisation_date')
            ->orderByDesc('measures.realisation_date')
            ->orderByDesc('measures.id')
            ->select('measures.realisation_date', 'measures.score')
            ->first();

        if ($measure === null) {
            return null;
        }

        return [
            'date' => $this->date($measure->realisation_date),
            'score' => $this->score($measure->score),
        ];
    }

    /** @return array<string, mixed> */
    protected function riskSummary(Risk $risk): array
    {
        return [
            'nom' => $risk->name,
            'niveau' => $risk->risk_level,
            'niveau_libelle' => $risk->risk_level_label,
            'statut' => $risk->status,
        ];
    }

    /** @return array<string, mixed> */
    protected function actionSummary(Action $action): array
    {
        return [
            'reference' => $action->reference,
            'nom' => $action->name,
            'type' => self::ACTION_TYPES[$action->type] ?? $action->type,
            'criticite' => $action->criticity,
            'statut' => self::ACTION_STATUS[$action->status] ?? $action->status,
            'avancement' => $action->progress,
            'echeance' => $this->date($action->due_date),
            'en_retard' => $this->actionIsLate($action),
        ];
    }
}
