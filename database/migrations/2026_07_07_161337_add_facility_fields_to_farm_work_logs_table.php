<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('farm_work_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('farm_work_logs', 'machine_id')) {
                $table->unsignedBigInteger('machine_id')->nullable()->after('driver_id');
            }

            if (!Schema::hasColumn('farm_work_logs', 'location_id')) {
                $table->unsignedBigInteger('location_id')->nullable()->after('machine_id');
            }
        });

        DB::statement("ALTER TABLE farm_work_logs MODIFY tractor_id BIGINT UNSIGNED NULL");
        DB::statement("ALTER TABLE farm_work_logs MODIFY driver_id BIGINT UNSIGNED NULL");
        DB::statement("ALTER TABLE farm_work_logs MODIFY zone_id BIGINT UNSIGNED NULL");
        DB::statement("ALTER TABLE farm_work_logs MODIFY zone_block_id BIGINT UNSIGNED NULL");
    }

    public function down(): void
    {
        Schema::table('farm_work_logs', function (Blueprint $table) {
            if (Schema::hasColumn('farm_work_logs', 'machine_id')) {
                $table->dropColumn('machine_id');
            }

            if (Schema::hasColumn('farm_work_logs', 'location_id')) {
                $table->dropColumn('location_id');
            }
        });
    }
};