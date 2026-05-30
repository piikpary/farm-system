<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tractors', function (Blueprint $table) {
            $table->id();

            $table->string('tractor_no', 100)->unique();
            $table->string('name', 150)->nullable();
            $table->string('model', 150)->nullable();
            $table->string('plate_no', 100)->nullable();

            $table->decimal('fuel_capacity', 12, 2)->default(0);
            $table->decimal('current_meter', 12, 2)->default(0);

            $table->enum('status', ['active', 'inactive'])->default('active');

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tractors');
    }
};