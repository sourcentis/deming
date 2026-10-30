<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // controls = security measures, measures = controls (UI naming is inverted)
    private const TABLES = ['controls', 'measures', 'actions', 'risks'];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Last AI analysis of each object: validated JSON, date, model and SHA-256 of the inputs
        foreach (self::TABLES as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->longText('ai_analysis')->nullable();
                $table->timestamp('ai_analysis_at')->nullable();
                $table->string('ai_analysis_model')->nullable();
                $table->char('ai_analysis_hash', 64)->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (self::TABLES as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->dropColumn(['ai_analysis', 'ai_analysis_at', 'ai_analysis_model', 'ai_analysis_hash']);
            });
        }
    }
};
