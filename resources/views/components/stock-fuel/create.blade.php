<?php

use Livewire\Component;
use App\Models\FuelStock;
use App\Models\FuelTransaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

new class extends Component
{
    public $rows = [];

    public function mount()
    {
        $this->rows = [
            $this->emptyRow(),
        ];
    }

    public function emptyRow()
    {
        return [
            'fuel_stock_id' => '',
            'transaction_type' => 'stock_in',
            'quantity' => 0,
            'note' => '',
        ];
    }

    public function addRow()
    {
        $this->rows[] = $this->emptyRow();
    }

    public function removeRow($index)
    {
        if (count($this->rows) <= 1) {
            return;
        }

        unset($this->rows[$index]);
        $this->rows = array_values($this->rows);
    }

    public function getTotalStockInProperty()
    {
        return collect($this->rows)->sum(function ($row) {
            return ($row['transaction_type'] ?? '') === 'stock_in'
                ? (float) ($row['quantity'] ?? 0)
                : 0;
        });
    }

    public function getTotalStockOutProperty()
    {
        return collect($this->rows)->sum(function ($row) {
            return ($row['transaction_type'] ?? '') === 'stock_out'
                ? (float) ($row['quantity'] ?? 0)
                : 0;
        });
    }

    public function getNetTotalProperty()
    {
        return $this->totalStockIn - $this->totalStockOut;
    }

    public function save()
    {
        $this->validate([
            'rows' => 'required|array|min:1',
            'rows.*.fuel_stock_id' => 'nullable|exists:fuel_stocks,id',
            'rows.*.transaction_type' => 'required|in:stock_in,stock_out',
            'rows.*.quantity' => 'required|numeric|min:0.01',
            'rows.*.note' => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () {
            foreach ($this->rows as $row) {
                $quantity = (float) $row['quantity'];

                if (!empty($row['fuel_stock_id'])) {
                    $stock = FuelStock::findOrFail($row['fuel_stock_id']);
                } else {
                    $stock = FuelStock::create([
                        'name' => 'Main Diesel Stock',
                        'opening_stock' => 0,
                        'current_stock' => 0,
                        'minimum_alert' => 0,
                        'status' => 'active',
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id(),
                    ]);
                }

                if ($row['transaction_type'] === 'stock_out' && $stock->current_stock < $quantity) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'rows' => 'Not enough fuel stock for ' . $stock->name,
                    ]);
                }

                if ($row['transaction_type'] === 'stock_in') {
                    $stock->current_stock = (float) $stock->current_stock + $quantity;
                } else {
                    $stock->current_stock = (float) $stock->current_stock - $quantity;
                }

                $stock->updated_by = Auth::id();
                $stock->save();

                FuelTransaction::create([
                    'fuel_stock_id' => $stock->id,
                    'transaction_date' => now()->format('Y-m-d'),
                    'type' => $row['transaction_type'],
                    'quantity' => $quantity,
                    'balance_after' => $stock->current_stock,
                    'reference_no' => 'FUEL-' . now()->format('YmdHis') . '-' . $stock->id,
                    'note' => $row['note'] ?: null,
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ]);
            }
        });

        session()->flash('success', 'Fuel stock saved successfully.');

        return redirect()->route('stock-fuel.index');
    }

    public function with()
    {
        return [
            'fuelStocks' => FuelStock::where('status', 'active')
    ->orderBy('name')
    ->get(),
        ];
    }
};

?>

<div class="page">
    @include('components.shared-style')
    @include('components.toast-alert')

    <style>
        .excel-toolbar {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: center;
            margin-bottom: 14px;
            flex-wrap: wrap;
        }

        .excel-table-wrap {
            overflow-x: auto;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
        }

        .excel-table {
            min-width: 980px;
            width: 100%;
            border-collapse: collapse;
            background: #ffffff;
        }

        .excel-table th {
            background: #f8fafc;
            color: #0f172a;
            font-size: 12px;
            font-weight: 900;
            text-transform: uppercase;
            padding: 12px 10px;
            border-bottom: 1px solid #e5e7eb;
            white-space: nowrap;
        }

        .excel-table td {
            padding: 8px;
            border-bottom: 1px solid #eef2f7;
            vertical-align: middle;
        }

        .excel-table input,
        .excel-table select {
            width: 100%;
            min-width: 150px;
            height: 44px;
            padding: 9px 10px;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            font-size: 13px;
            background: #fff;
            font-weight: 700;
        }

        .row-no {
            width: 45px;
            min-width: 45px;
            text-align: center;
            font-weight: 900;
            color: #64748b;
        }

        .excel-actions {
            display: flex;
            gap: 6px;
        }

        .fuel-total-row {
            background: #f8fafc;
            font-weight: 900;
            border-top: 2px solid #d1d5db;
        }

        .fuel-total-row td {
            padding: 14px 10px;
            color: #0f172a;
            white-space: nowrap;
            border-bottom: 0;
        }

        .total-label {
            text-align: right;
        }

        .stock-in {
            color: #15803d;
            font-weight: 900;
        }

        .stock-out {
            color: #dc2626;
            font-weight: 900;
        }

        .net-total {
            color: #0f172a;
            font-weight: 900;
        }
    </style>

    <div class="page-header">
        <div>
            <h1 class="page-title">Add / Adjust Fuel Stock</h1>
            <p class="page-subtitle">Input fuel stock in or stock out by Excel-style row.</p>
        </div>

        <div class="page-actions">
            <div class="language-switcher">
                <a href="{{ route('language.switch', 'en') }}"
                   class="lang-btn {{ app()->getLocale() === 'en' ? 'active' : '' }}">
                    EN
                </a>

                <a href="{{ route('language.switch', 'km') }}"
                   class="lang-btn {{ app()->getLocale() === 'km' ? 'active' : '' }}">
                    ខ្មែរ
                </a>
            </div>

            <a href="{{ route('stock-fuel.index') }}" class="btn gray">
                {{ __('pages.back') }}
            </a>
        </div>
    </div>

    <div class="panel">
        <div class="excel-toolbar">
            <button type="button" wire:click="addRow" class="btn light">
                + Add Row
            </button>
        </div>

        <div class="excel-table-wrap">
            <table class="excel-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Select Stock</th>
                        <th>Transaction Type</th>
                        <th>Fuel Quantity</th>
                        <th>Note</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($rows as $index => $row)
                        <tr>
                            <td class="row-no">{{ $index + 1 }}</td>

                            <td>
                                <select wire:model.live="rows.{{ $index }}.fuel_stock_id">
                                    <option value="">Create / Use Main Diesel Stock</option>
                                    @foreach($fuelStocks as $stock)
                                        <option value="{{ $stock->id }}">
                                           {{ $stock->name }} - {{ number_format($stock->current_stock, 2) }} L
                                        </option>
                                    @endforeach
                                </select>

                                @error("rows.$index.fuel_stock_id")
                                    <small>{{ $message }}</small>
                                @enderror
                            </td>

                            <td>
                                <select wire:model.live="rows.{{ $index }}.transaction_type">
                                    <option value="stock_in">Stock In</option>
                                    <option value="stock_out">Stock Out</option>
                                </select>

                                @error("rows.$index.transaction_type")
                                    <small>{{ $message }}</small>
                                @enderror
                            </td>

                            <td>
                                <input type="number"
                                       step="0.01"
                                       wire:model.live="rows.{{ $index }}.quantity">

                                @error("rows.$index.quantity")
                                    <small>{{ $message }}</small>
                                @enderror
                            </td>

                            <td>
                                <input type="text"
                                       wire:model.live="rows.{{ $index }}.note"
                                       placeholder="Reason or note">
                            </td>

                            <td>
                                <div class="excel-actions">
                                    <button type="button"
                                            wire:click="removeRow({{ $index }})"
                                            class="mini danger">
                                        Remove
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>

                <tfoot>
                    <tr class="fuel-total-row">
                        <td colspan="3" class="total-label">Total</td>

                        <td>
                            <span class="net-total">
                                {{ number_format((float) $this->netTotal, 2) }} L
                            </span>
                        </td>

                        <td>
                            In:
                            <span class="stock-in">
                                {{ number_format((float) $this->totalStockIn, 2) }} L
                            </span>
                            /
                            Out:
                            <span class="stock-out">
                                {{ number_format((float) $this->totalStockOut, 2) }} L
                            </span>
                        </td>

                        <td>-</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="btn-row">
            <button type="button" wire:click="save" class="btn">
                Save Fuel Stock
            </button>

            <a href="{{ route('stock-fuel.index') }}" class="btn gray">
                Cancel
            </a>
        </div>
    </div>
</div>