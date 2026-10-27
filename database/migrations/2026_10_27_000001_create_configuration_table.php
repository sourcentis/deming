<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Generic key/value configuration store (name convention: "domain.key")
        Schema::create('configuration', function (Blueprint $table) {
            $table->id();
            $table->string('name', 191)->unique();
            $table->longText('value')->nullable();
            $table->timestamps();
        });

        // Only the AI switch is seeded; other values fall back to defaults defined in code
        DB::table('configuration')->insert([
            'name' => 'ai.enabled',
            'value' => '0',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('configuration');
    }
};
