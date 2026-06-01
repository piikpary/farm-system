<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tractors', function (Blueprint $table) {
            if (!Schema::hasColumn('tractors', 'iot_device_id')) {
                $table->string('iot_device_id')->nullable()->unique()->after('tractor_no');
            }

            if (!Schema::hasColumn('tractors', 'plow_width')) {
                $table->decimal('plow_width', 8, 2)->default(0)->after('current_meter');
            }

            if (!Schema::hasColumn('tractors', 'last_lat')) {
                $table->decimal('last_lat', 10, 7)->nullable()->after('plow_width');
            }

            if (!Schema::hasColumn('tractors', 'last_lng')) {
                $table->decimal('last_lng', 10, 7)->nullable()->after('last_lat');
            }

            if (!Schema::hasColumn('tractors', 'last_seen_at')) {
                $table->timestamp('last_seen_at')->nullable()->after('last_lng');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tractors', function (Blueprint $table) {
            if (Schema::hasColumn('tractors', 'iot_device_id')) {
                $table->dropUnique(['iot_device_id']);
                $table->dropColumn('iot_device_id');
            }

            if (Schema::hasColumn('tractors', 'plow_width')) {
                $table->dropColumn('plow_width');
            }

            if (Schema::hasColumn('tractors', 'last_lat')) {
                $table->dropColumn('last_lat');
            }

            if (Schema::hasColumn('tractors', 'last_lng')) {
                $table->dropColumn('last_lng');
            }

            if (Schema::hasColumn('tractors', 'last_seen_at')) {
                $table->dropColumn('last_seen_at');
            }
        });
    }
};