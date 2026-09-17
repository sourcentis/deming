<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('frameworks', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('version')->default('');
            $table->string('publisher')->default('');
            $table->string('jurisdiction')->nullable();
            $table->string('source_url', 2048)->nullable();
            $table->string('status')->nullable();
            $table->date('publication_date')->nullable();
            $table->date('effective_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Preserve the existing Domain.framework semantics while making all
        // framework codes already present in an installation immediately usable.
        DB::table('domains')
            ->whereNotNull('framework')
            ->where('framework', '<>', '')
            ->select('framework')
            ->distinct()
            ->orderBy('framework')
            ->pluck('framework')
            ->each(function ($framework): void {
                $code = (string) $framework;

                if (trim($code) === '') {
                    return;
                }

                DB::table('frameworks')->insertOrIgnore([
                    'code' => $code,
                    'name' => $code,
                    'version' => '',
                    'publisher' => '',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('frameworks');
    }
};
