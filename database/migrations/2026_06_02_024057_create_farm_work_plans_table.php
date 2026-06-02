<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('farm_work_plans', function (Blueprint $table) {
            $table->id();

            $table->date('plan_date');

            $table->foreignId('task_category_id')
                ->nullable()
                ->constrained('task_categories')
                ->nullOnDelete();

            $table->date('plan_start')->nullable();
            $table->date('plan_end')->nullable();

            $table->json('zone_block_ids')->nullable();

            $table->decimal('plan_area', 12, 2)->default(0.00);
            $table->decimal('request_l_per_hectare', 12, 2)->default(0.00);
            $table->decimal('request_liters', 12, 2)->default(0.00);

            $table->string('status', 50)->default('in_progress');

            $table->text('note')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index('plan_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('farm_work_plans');
    }
};