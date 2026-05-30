<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('farm_work_logs', function (Blueprint $table) {
            $table->id();

            $table->date('work_date');

            $table->foreignId('tractor_id')->constrained('tractors')->restrictOnDelete();
            $table->foreignId('driver_id')->constrained('drivers')->restrictOnDelete();
            $table->foreignId('zone_id')->constrained('zones')->restrictOnDelete();
            $table->foreignId('task_category_id')->constrained('task_categories')->restrictOnDelete();

            $table->decimal('working_duration', 12, 2)->default(0);
            $table->decimal('working_area', 12, 2)->default(0);

            $table->decimal('diesel_start', 12, 2)->default(0);
            $table->decimal('diesel_refill', 12, 2)->default(0);
            $table->decimal('diesel_end', 12, 2)->default(0);

            $table->decimal('diesel_consumed', 12, 2)->default(0);
            $table->decimal('diesel_per_hectare', 12, 2)->default(0);
            $table->decimal('hectare_per_hour', 12, 2)->default(0);

            $table->decimal('request_fuel_per_hectare', 12, 2)->default(0);
            $table->decimal('request_fuel', 12, 2)->default(0);
            $table->decimal('variance_fuel', 12, 2)->default(0);

            $table->text('note')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index('work_date');
            $table->index(['tractor_id', 'work_date']);
            $table->index(['driver_id', 'work_date']);
            $table->index(['zone_id', 'work_date']);
            $table->index(['task_category_id', 'work_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('farm_work_logs');
    }
};