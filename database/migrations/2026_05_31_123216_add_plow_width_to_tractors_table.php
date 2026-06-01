<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tractors', function (Blueprint $table) {
            if (!Schema::hasColumn('tractors', 'plow_width')) {
                $table->decimal('plow_width', 8, 2)
                    ->default(0)
                    ->after('current_meter')
                    ->comment('Working implement width in meters');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tractors', function (Blueprint $table) {
            if (Schema::hasColumn('tractors', 'plow_width')) {
                $table->dropColumn('plow_width');
            }
        });
    }
};