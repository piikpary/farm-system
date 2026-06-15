<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('farm_work_plan_activities', function (Blueprint $table) {
            $table->id();

            $table->foreignId('farm_work_plan_id')
                ->constrained('farm_work_plans')
                ->cascadeOnDelete();

            $table->foreignId('task_category_id')
                ->constrained('task_categories')
                ->restrictOnDelete();

            $table->decimal('fuel_per_hectare', 12, 2)->default(0);

            $table->timestamps();

            $table->unique(
                ['farm_work_plan_id', 'task_category_id'],
                'work_plan_activity_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('farm_work_plan_activities');
    }
};