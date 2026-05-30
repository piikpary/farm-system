<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('zones', function (Blueprint $table) {
            if (!Schema::hasColumn('zones', 'center_lat')) {
                $table->decimal('center_lat', 10, 7)->nullable()->after('total_area');
            }

            if (!Schema::hasColumn('zones', 'center_lng')) {
                $table->decimal('center_lng', 10, 7)->nullable()->after('center_lat');
            }

            if (!Schema::hasColumn('zones', 'polygon_coordinates')) {
                $table->json('polygon_coordinates')->nullable()->after('center_lng');
            }
        });
    }

    public function down(): void
    {
        Schema::table('zones', function (Blueprint $table) {
            if (Schema::hasColumn('zones', 'polygon_coordinates')) {
                $table->dropColumn('polygon_coordinates');
            }

            if (Schema::hasColumn('zones', 'center_lng')) {
                $table->dropColumn('center_lng');
            }

            if (Schema::hasColumn('zones', 'center_lat')) {
                $table->dropColumn('center_lat');
            }
        });
    }
};