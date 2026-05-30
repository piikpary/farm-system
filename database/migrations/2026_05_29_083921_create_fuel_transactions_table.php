<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fuel_transactions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('fuel_stock_id')->nullable()->constrained('fuel_stocks')->nullOnDelete();

            $table->date('transaction_date');

            $table->enum('type', [
                'stock_in',
                'stock_out',
                'refill_to_tractor',
                'adjustment',
            ]);

            $table->foreignId('tractor_id')->nullable()->constrained('tractors')->nullOnDelete();
            $table->foreignId('farm_work_log_id')->nullable()->constrained('farm_work_logs')->nullOnDelete();

            $table->decimal('quantity', 12, 2)->default(0);
            $table->decimal('balance_after', 12, 2)->default(0);

            $table->string('reference_no')->nullable();
            $table->text('note')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['transaction_date', 'type']);
            $table->index(['fuel_stock_id', 'transaction_date']);
            $table->index(['tractor_id', 'transaction_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fuel_transactions');
    }
};