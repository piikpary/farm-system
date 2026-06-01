<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('farm_work_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('farm_work_logs', 'gps_distance_meters')) {
                $table->decimal('gps_distance_meters', 12, 2)
                    ->default(0)
                    ->after('working_area');
            }

            if (!Schema::hasColumn('farm_work_logs', 'estimated_plowed_area')) {
                $table->decimal('estimated_plowed_area', 12, 4)
                    ->default(0)
                    ->after('gps_distance_meters')
                    ->comment('Estimated plowed area in hectare');
            }

            if (!Schema::hasColumn('farm_work_logs', 'gps_progress_percent')) {
                $table->decimal('gps_progress_percent', 8, 2)
                    ->default(0)
                    ->after('estimated_plowed_area');
            }
        });
    }

    public function down(): void
    {
        Schema::table('farm_work_logs', function (Blueprint $table) {
            if (Schema::hasColumn('farm_work_logs', 'gps_progress_percent')) {
                $table->dropColumn('gps_progress_percent');
            }

            if (Schema::hasColumn('farm_work_logs', 'estimated_plowed_area')) {
                $table->dropColumn('estimated_plowed_area');
            }

            if (Schema::hasColumn('farm_work_logs', 'gps_distance_meters')) {
                $table->dropColumn('gps_distance_meters');
            }
        });
    }
};