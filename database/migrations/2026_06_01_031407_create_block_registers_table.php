<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('block_registers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('zone_block_id')->constrained('zone_blocks')->cascadeOnDelete();
            $table->string('variety')->nullable();

            $table->date('planting_date')->nullable();
            $table->foreignId('planting_cycle_type_id')->nullable()->constrained('planting_cycle_types')->nullOnDelete();
            $table->date('expected_harvest_date')->nullable();

            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->text('note')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('block_registers');
    }
};