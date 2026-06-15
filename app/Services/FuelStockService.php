<?php

namespace App\Services;

use App\Models\FuelStock;
use App\Models\FuelTransaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Exception;

class FuelStockService
{
    public static function deductFuelFifo(float $quantity, ?int $farmWorkLogId = null, ?int $tractorId = null, ?string $note = null): void
    {
        if ($quantity <= 0) {
            return;
        }

        DB::transaction(function () use ($quantity, $farmWorkLogId, $tractorId, $note) {
            $remainingQty = $quantity;

            $totalAvailable = FuelStock::where('status', 'active')
                ->where('current_stock', '>', 0)
                ->sum('current_stock');

            if ($totalAvailable < $quantity) {
                throw new Exception('Not enough stock fuel. Current stock: ' . number_format((float) $totalAvailable, 2) . ' L');
            }

            $stocks = FuelStock::where('status', 'active')
                ->where('current_stock', '>', 0)
                ->oldest('id')
                ->lockForUpdate()
                ->get();

            foreach ($stocks as $stock) {
                if ($remainingQty <= 0) {
                    break;
                }

                $beforeStock = (float) $stock->current_stock;
                $deductQty = min($beforeStock, $remainingQty);
                $afterStock = $beforeStock - $deductQty;

                $stock->update([
                    'current_stock' => $afterStock,
                    'updated_by' => Auth::id(),
                ]);

                FuelTransaction::create([
                    'fuel_stock_id' => $stock->id,
                    'tractor_id' => $tractorId,
                    'farm_work_log_id' => $farmWorkLogId,
                    'type' => 'stock_out',
                    'quantity' => $deductQty,
                    'balance_after' => $afterStock,
                    'reference_no' => 'FIFO-OUT-' . $stock->id . '-' . now()->format('YmdHis'),
                    'transaction_date' => now()->toDateString(),
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                    'note' => $note ?: 'Fuel deducted by FIFO from work log',
                ]);

                $remainingQty -= $deductQty;
            }
        });
    }

    public static function returnFuel(float $quantity, ?int $farmWorkLogId = null, ?int $tractorId = null, ?string $note = null): void
    {
        if ($quantity <= 0) {
            return;
        }

        DB::transaction(function () use ($quantity, $farmWorkLogId, $tractorId, $note) {
            $stock = FuelStock::where('status', 'active')
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if (! $stock) {
                throw new Exception('No active fuel stock found.');
            }

            $newStock = (float) $stock->current_stock + $quantity;

            $stock->update([
                'current_stock' => $newStock,
                'updated_by' => Auth::id(),
            ]);

            FuelTransaction::create([
                'fuel_stock_id' => $stock->id,
                'tractor_id' => $tractorId,
                'farm_work_log_id' => $farmWorkLogId,
                'type' => 'adjustment',
                'quantity' => $quantity,
                'balance_after' => $newStock,
                'reference_no' => 'RETURN-FUEL-' . $stock->id . '-' . now()->format('YmdHis'),
                'transaction_date' => now()->toDateString(),
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
                'note' => $note ?: 'Fuel returned from work log adjustment',
            ]);
        });
    }
}