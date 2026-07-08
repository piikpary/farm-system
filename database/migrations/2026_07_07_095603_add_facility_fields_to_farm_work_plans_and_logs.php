<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('farm_work_plans', function (Blueprint $table) {
            if (!Schema::hasColumn('farm_work_plans', 'machine_id')) {
                $table->unsignedBigInteger('machine_id')->nullable()->after('task_category_id');
            }

            if (!Schema::hasColumn('farm_work_plans', 'location_id')) {
                $table->unsignedBigInteger('location_id')->nullable()->after('machine_id');
            }

            if (!Schema::hasColumn('farm_work_plans', 'requested_l_per_hour')) {
                $table->decimal('requested_l_per_hour', 12, 2)->nullable()->after('location_id');
            }

            if (!Schema::hasColumn('farm_work_plans', 'total_hour')) {
                $table->decimal('total_hour', 12, 2)->nullable()->after('requested_l_per_hour');
            }

            if (!Schema::hasColumn('farm_work_plans', 'total_liter')) {
                $table->decimal('total_liter', 12, 2)->nullable()->after('total_hour');
            }
        });

        Schema::table('farm_work_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('farm_work_logs', 'machine_id')) {
                $table->unsignedBigInteger('machine_id')->nullable()->after('task_category_id');
            }

            if (!Schema::hasColumn('farm_work_logs', 'location_id')) {
                $table->unsignedBigInteger('location_id')->nullable()->after('machine_id');
            }

            if (!Schema::hasColumn('farm_work_logs', 'consume_l_per_hour')) {
                $table->decimal('consume_l_per_hour', 12, 2)->nullable()->after('location_id');
            }

            if (!Schema::hasColumn('farm_work_logs', 'total_hour')) {
                $table->decimal('total_hour', 12, 2)->nullable()->after('consume_l_per_hour');
            }

            if (!Schema::hasColumn('farm_work_logs', 'total_consume_liter')) {
                $table->decimal('total_consume_liter', 12, 2)->nullable()->after('total_hour');
            }
        });
    }

    public function down(): void
    {
        Schema::table('farm_work_plans', function (Blueprint $table) {
            $drop = [];

            foreach ([
                'machine_id',
                'location_id',
                'requested_l_per_hour',
                'total_hour',
                'total_liter',
            ] as $column) {
                if (Schema::hasColumn('farm_work_plans', $column)) {
                    $drop[] = $column;
                }
            }

            if (!empty($drop)) {
                $table->dropColumn($drop);
            }
        });

        Schema::table('farm_work_logs', function (Blueprint $table) {
            $drop = [];

            foreach ([
                'machine_id',
                'location_id',
                'consume_l_per_hour',
                'total_hour',
                'total_consume_liter',
            ] as $column) {
                if (Schema::hasColumn('farm_work_logs', $column)) {
                    $drop[] = $column;
                }
            }

            if (!empty($drop)) {
                $table->dropColumn($drop);
            }
        });
    }
};