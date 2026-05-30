<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sidebar_menu_settings', function (Blueprint $table) {
            $table->id();

            $table->string('menu_key')->unique();
            $table->string('menu_label');
            $table->string('menu_group')->nullable();
            $table->boolean('is_visible')->default(false);
            $table->integer('sort_order')->default(0);

            $table->timestamps();
        });

        DB::table('sidebar_menu_settings')->insert([
            [
                'menu_key' => 'tractors',
                'menu_label' => 'Tractors',
                'menu_group' => 'master_data',
                'is_visible' => false,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'menu_key' => 'drivers',
                'menu_label' => 'Drivers',
                'menu_group' => 'master_data',
                'is_visible' => false,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'menu_key' => 'zones',
                'menu_label' => 'Zones',
                'menu_group' => 'master_data',
                'is_visible' => false,
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'menu_key' => 'task_categories',
                'menu_label' => 'Task Categories',
                'menu_group' => 'master_data',
                'is_visible' => false,
                'sort_order' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('sidebar_menu_settings');
    }
};