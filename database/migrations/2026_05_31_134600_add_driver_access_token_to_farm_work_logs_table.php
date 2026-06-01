<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('farm_work_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('farm_work_logs', 'driver_access_token')) {
                $table->string('driver_access_token', 100)->nullable()->unique()->after('id');
            }
        });

        DB::table('farm_work_logs')
            ->whereNull('driver_access_token')
            ->orderBy('id')
            ->get()
            ->each(function ($log) {
                DB::table('farm_work_logs')
                    ->where('id', $log->id)
                    ->update([
                        'driver_access_token' => Str::random(64),
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('farm_work_logs', function (Blueprint $table) {
            if (Schema::hasColumn('farm_work_logs', 'driver_access_token')) {
                $table->dropUnique(['driver_access_token']);
                $table->dropColumn('driver_access_token');
            }
        });
    }
};