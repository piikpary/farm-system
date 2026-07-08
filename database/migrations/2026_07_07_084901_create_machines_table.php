<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('machines', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('machine_no')->nullable();
            $table->string('brand')->nullable();
            $table->string('description')->nullable();
            $table->string('status')->default('Active');
            $table->timestamps();

            $table->index('name');
            $table->index('machine_no');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('machines');
    }
};