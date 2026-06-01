<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('farm_work_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('farm_work_logs', 'zone_block_id')) {
                $table->foreignId('zone_block_id')
                    ->nullable()
                    ->after('zone_id')
                    ->constrained('zone_blocks')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('farm_work_logs', function (Blueprint $table) {
            if (Schema::hasColumn('farm_work_logs', 'zone_block_id')) {
                $table->dropConstrainedForeignId('zone_block_id');
            }
        });
    }
};