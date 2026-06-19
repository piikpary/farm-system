<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('task_categories', function (Blueprint $table) {
            $table->foreignId('task_category_group_id')
                ->nullable()
                ->after('id')
                ->constrained('task_category_groups')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('task_categories', function (Blueprint $table) {
            $table->dropForeign(['task_category_group_id']);
            $table->dropColumn('task_category_group_id');
        });
    }
};