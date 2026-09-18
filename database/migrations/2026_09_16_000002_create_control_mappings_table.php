<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('control_mappings', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('source_control_id');
            $table->unsignedInteger('target_control_id');
            $table->enum('mapping_type', [
                'equivalent',
                'covers',
                'covered_by',
                'partial',
                'supports',
                'related',
            ]);
            $table->enum('coverage', [
                'full',
                'high',
                'medium',
                'low',
                'none',
            ])->nullable();
            $table->decimal('confidence', 5, 2)->nullable();
            $table->text('rationale')->nullable();
            $table->string('source_reference')->nullable();
            $table->string('source_url', 2048)->nullable();
            $table->boolean('validated')->default(false);
            $table->unsignedBigInteger('validated_by')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->timestamps();

            $table->foreign('source_control_id')
                ->references('id')
                ->on('controls')
                ->cascadeOnDelete();
            $table->foreign('target_control_id')
                ->references('id')
                ->on('controls')
                ->cascadeOnDelete();
            $table->foreign('validated_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->unique(
                ['source_control_id', 'target_control_id'],
                'control_mappings_source_target_unique'
            );
            $table->index('target_control_id', 'control_mappings_target_control_index');
            $table->index('mapping_type');
            $table->index('coverage');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('control_mappings');
    }
};
