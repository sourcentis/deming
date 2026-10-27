<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;

/**
 * Columns holding the last AI analysis of a model.
 *
 * They are hidden from serialization (REST API, audit log properties),
 * never fillable, and never copied when a model is replicated.
 */
trait HasAiAnalysis
{
    public const AI_COLUMNS = ['ai_analysis', 'ai_analysis_at', 'ai_analysis_model', 'ai_analysis_hash'];

    public function hasAiAnalysis(): bool
    {
        return $this->getAttribute('ai_analysis') !== null;
    }

    // Clear the stored analysis without touching updated_at nor the audit log
    public function clearAiAnalysis(): void
    {
        DB::table($this->getTable())
            ->where('id', $this->getKey())
            ->update(array_fill_keys(self::AI_COLUMNS, null));
    }
}
