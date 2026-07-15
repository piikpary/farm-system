<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fuel_stocks', function (Blueprint $table) {
            $table->decimal('total_stock_in', 15, 2)
                ->default(0)
                ->after('current_stock');

            $table->decimal('total_stock_out', 15, 2)
                ->default(0)
                ->after('total_stock_in');
        });
    }

    public function down(): void
    {
        Schema::table('fuel_stocks', function (Blueprint $table) {
            $table->dropColumn([
                'total_stock_in',
                'total_stock_out',
            ]);
        });
    }
};