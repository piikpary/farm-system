<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('task_category_groups', function (Blueprint $table) {
            $table->dropUnique('task_category_groups_name_unique');

            $table->unique(
                ['group_type', 'name'],
                'task_category_groups_type_name_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('task_category_groups', function (Blueprint $table) {
            $table->dropUnique(
                'task_category_groups_type_name_unique'
            );

            $table->unique(
                'name',
                'task_category_groups_name_unique'
            );
        });
    }
};