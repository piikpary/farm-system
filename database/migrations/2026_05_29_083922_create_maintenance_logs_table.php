<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tractor_id')->constrained('tractors')->restrictOnDelete();

            $table->date('maintenance_date');
            $table->string('maintenance_type', 150);

            $table->decimal('meter_reading', 12, 2)->nullable();
            $table->decimal('cost', 12, 2)->default(0);

            $table->text('description')->nullable();
            $table->date('next_maintenance_date')->nullable();

            $table->enum('status', ['pending', 'completed', 'cancelled'])->default('completed');

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index('maintenance_date');
            $table->index('next_maintenance_date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_logs');
    }
};