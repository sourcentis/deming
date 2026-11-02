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
        // task_version of the AI gateway that produced the stored analysis
        foreach (self::TABLES as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->string('ai_analysis_version', 32)->nullable();
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
                $table->dropColumn('ai_analysis_version');
            });
        }
    }
};
