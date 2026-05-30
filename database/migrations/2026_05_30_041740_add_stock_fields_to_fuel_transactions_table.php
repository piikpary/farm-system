<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fuel_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('fuel_transactions', 'fuel_stock_id')) {
                $table->foreignId('fuel_stock_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('fuel_stocks')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('fuel_transactions', 'balance_after')) {
                $table->decimal('balance_after', 12, 2)
                    ->default(0)
                    ->after('quantity');
            }
        });
    }

    public function down(): void
    {
        Schema::table('fuel_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('fuel_transactions', 'fuel_stock_id')) {
                $table->dropConstrainedForeignId('fuel_stock_id');
            }

            if (Schema::hasColumn('fuel_transactions', 'balance_after')) {
                $table->dropColumn('balance_after');
            }
        });
    }
};