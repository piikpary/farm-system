<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('task_category_groups', function (Blueprint $table) {
            $table->enum('group_type', ['planning', 'harvesting'])
                ->default('planning')
                ->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('task_category_groups', function (Blueprint $table) {
            $table->dropColumn('group_type');
        });
    }
};