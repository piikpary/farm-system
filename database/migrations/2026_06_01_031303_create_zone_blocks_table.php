<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zone_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zone_id')->constrained('zones')->cascadeOnDelete();

            $table->string('block_code')->unique();
            $table->string('name')->nullable();
            $table->decimal('area', 12, 2)->default(0);
            $table->string('soil_type')->nullable();

            $table->decimal('center_lat', 12, 8)->nullable();
            $table->decimal('center_lng', 12, 8)->nullable();
            $table->longText('polygon_coordinates')->nullable();
            $table->text('location_note')->nullable();

            $table->enum('status', ['active', 'inactive'])->default('active');

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zone_blocks');
    }
};