<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('farm_work_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('farm_work_logs', 'work_status')) {
                $table->string('work_status')->default('pending')->after('work_date');
            }

            if (!Schema::hasColumn('farm_work_logs', 'started_at')) {
                $table->timestamp('started_at')->nullable()->after('work_status');
            }

            if (!Schema::hasColumn('farm_work_logs', 'finished_at')) {
                $table->timestamp('finished_at')->nullable()->after('started_at');
            }

            if (!Schema::hasColumn('farm_work_logs', 'gps_distance_meters')) {
                $table->decimal('gps_distance_meters', 12, 2)->default(0)->after('working_area');
            }

            if (!Schema::hasColumn('farm_work_logs', 'estimated_plowed_area')) {
                $table->decimal('estimated_plowed_area', 12, 4)->default(0)->after('gps_distance_meters');
            }

            if (!Schema::hasColumn('farm_work_logs', 'gps_progress_percent')) {
                $table->decimal('gps_progress_percent', 8, 2)->default(0)->after('estimated_plowed_area');
            }
        });
    }

    public function down(): void
    {
        Schema::table('farm_work_logs', function (Blueprint $table) {
            foreach ([
                'work_status',
                'started_at',
                'finished_at',
                'gps_distance_meters',
                'estimated_plowed_area',
                'gps_progress_percent',
            ] as $column) {
                if (Schema::hasColumn('farm_work_logs', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};