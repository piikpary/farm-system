<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('farm_work_logs', function (Blueprint $table) {
            $table->foreignId('farm_work_plan_id')
                ->nullable()
                ->after('id')
                ->constrained('farm_work_plans')
                ->nullOnDelete();

            $table->index('farm_work_plan_id');
        });
    }

    public function down(): void
    {
        Schema::table('farm_work_logs', function (Blueprint $table) {
            $table->dropForeign(['farm_work_plan_id']);
            $table->dropIndex(['farm_work_plan_id']);
            $table->dropColumn('farm_work_plan_id');
        });
    }
};