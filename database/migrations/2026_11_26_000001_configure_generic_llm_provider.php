<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // controls = security measures, measures = controls (UI naming is inverted)
    private const TABLES = ['controls', 'measures', 'actions', 'risks'];

    private const AI_COLUMNS = ['ai_analysis', 'ai_analysis_at', 'ai_analysis_model', 'ai_analysis_hash', 'ai_analysis_version'];

    // Model of the Sourcentis AI gateway, the only provider supported until now
    private const GATEWAY_MODEL = 'sourcentis-standard';

    /**
     * Run the migrations.
     *
     * Deming now calls {base_url}/chat/completions of any OpenAI-compatible provider.
     * ai.base_url (already ending with /v1) and ai.api_key (already encrypted) keep
     * their names and values; only the model, so far chosen by the gateway, is added.
     */
    public function up(): void
    {
        $baseUrl = DB::table('configuration')->where('name', 'ai.base_url')->value('value');
        $hasModel = DB::table('configuration')->where('name', 'ai.model')->exists();

        if (is_string($baseUrl) && trim($baseUrl) !== '' && ! $hasModel) {
            DB::table('configuration')->insert([
                'name' => 'ai.model',
                'value' => self::GATEWAY_MODEL,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Saved analyses follow the former format (French keys), which the panel no longer reads
        foreach (self::TABLES as $table) {
            DB::table($table)
                ->whereNotNull('ai_analysis')
                ->update(array_fill_keys(self::AI_COLUMNS, null));
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Nothing to restore: the analyses are recomputed on demand, the model is kept
    }
};
