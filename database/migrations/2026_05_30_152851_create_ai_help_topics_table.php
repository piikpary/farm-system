<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_help_topics', function (Blueprint $table) {
            $table->id();
            $table->string('module'); // tractors, drivers, work_logs, stock_fuel
            $table->string('title');
            $table->text('keywords')->nullable();
            $table->longText('content');
            $table->string('status')->default('active');
            $table->timestamps();

            $table->index(['module', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_help_topics');
    }
};