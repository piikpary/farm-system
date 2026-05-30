<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_work_actions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('farm_work_log_id')
                ->constrained('farm_work_logs')
                ->cascadeOnDelete();

            $table->foreignId('driver_id')
                ->constrained('drivers')
                ->restrictOnDelete();

            $table->foreignId('tractor_id')
                ->nullable()
                ->constrained('tractors')
                ->nullOnDelete();

            $table->foreignId('zone_id')
                ->nullable()
                ->constrained('zones')
                ->nullOnDelete();

            $table->enum('action_type', [
                'start_work',
                'pause_work',
                'resume_work',
                'refill_diesel',
                'problem',
                'finish_work',
            ]);

            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();

            $table->text('note')->nullable();

            $table->timestamp('action_at');

            $table->timestamps();

            $table->index(['farm_work_log_id', 'action_at']);
            $table->index(['driver_id', 'action_at']);
            $table->index(['zone_id', 'action_at']);
            $table->index('action_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_work_actions');
    }
};