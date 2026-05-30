<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_gps_tracks', function (Blueprint $table) {
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

            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);
            $table->decimal('speed', 8, 2)->nullable();
            $table->decimal('accuracy', 8, 2)->nullable();

            $table->timestamp('tracked_at');

            $table->timestamps();

            $table->index(['driver_id', 'tracked_at']);
            $table->index(['farm_work_log_id', 'tracked_at']);
            $table->index(['zone_id', 'tracked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_gps_tracks');
    }
};