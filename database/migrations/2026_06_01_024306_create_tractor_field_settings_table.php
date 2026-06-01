<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tractor_field_settings', function (Blueprint $table) {
            $table->id();
            $table->string('field_key')->unique();
            $table->string('field_label');
            $table->boolean('is_visible')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tractor_field_settings');
    }
};