<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * SHA-256 of the prompts shipped with Deming (trimmed, LF line endings).
     * The prompts, the response format and the model now belong to the AI gateway.
     */
    private const DEFAULT_PROMPTS = [
        'ai.prompt_control' => '8c095519f24f71c9560fd026bee7c89317969bf7290da5cfb542755471e7e2b8',
        'ai.prompt_measure' => '25c586283018dbc0fb620b0e056bfb24e2bca184ecb9d629a34e47574cff7907',
        'ai.prompt_action' => '9a65bf72c2fec3757721833388347777bd743778f75e07f2d031a7ae9aa4ba7d',
        'ai.prompt_risk' => 'd451258affa5c615562916e5aa94ce0e83df3d8c27d94943e5d549eea779354c',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $rows = DB::table('configuration')->whereIn('name', array_keys(self::DEFAULT_PROMPTS))->get(['name', 'value']);
        foreach ($rows as $row) {
            $value = trim(str_replace("\r\n", "\n", (string) $row->value));
            if ($value !== '' && ! hash_equals(self::DEFAULT_PROMPTS[$row->name], hash('sha256', $value))) {
                // The name only, never the content of the prompt
                Log::warning('AI: custom prompt removed, prompts are now managed by the AI gateway', ['name' => $row->name]);
            }
        }

        DB::table('configuration')
            ->whereIn('name', [...array_keys(self::DEFAULT_PROMPTS), 'ai.model'])
            ->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Nothing to restore: the prompts and the model are managed by the AI gateway
    }
};
