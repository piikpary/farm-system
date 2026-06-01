<?php

use Livewire\Component;
use App\Models\FuelStock;
use App\Models\FuelTransaction;
use App\Models\SidebarMenuSetting;
new class extends Component
{
    public function getTotalOpeningStockProperty()
    {
        return FuelStock::sum('opening_stock');
    }

    public function getTotalCurrentStockProperty()
    {
        return FuelStock::sum('current_stock');
    }
    public function getShowEditFuelProperty()
{
    return (bool) SidebarMenuSetting::where('menu_key', 'stock_fuel_edit')
        ->value('is_visible');
}

    public function getTotalStockInProperty()
    {
        return FuelTransaction::where('type', 'stock_in')->sum('quantity');
    }

    public function getTotalStockOutProperty()
    {
        return FuelTransaction::whereIn('type', [
            'stock_out',
            'refill_to_tractor',
        ])->sum('quantity');
    }

    public function with()
    {
        return [
            'fuelStocks' => FuelStock::latest()->get(),
        ];
    }
};

?>

<div class="page">
    @include('components.shared-style')
    @include('components.toast-alert')

    <style>
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
            padding: 14px 10px;
            border-bottom: 1px solid #eef2f7;
            vertical-align: middle;
            white-space: nowrap;
        }

        .fuel-total-row {
            background: #f8fafc;
            font-weight: 900;
            border-top: 2px solid #d1d5db;
        }

        .fuel-total-row td {
            border-bottom: 0;
        }

        .total-label {
            text-align: right;
            font-weight: 900;
        }

        .stock-in {
            color: #15803d;
            font-weight: 900;
        }

        .stock-out {
            color: #dc2626;
            font-weight: 900;
        }

        .current-stock {
            color: #166534;
            font-weight: 900;
        }
    </style>

    <div class="page-header">
        <div>
            <h1 class="page-title">Stock Fuel</h1>
            <p class="page-subtitle">Control diesel stock balance, stock in, stock out, and fuel usage.</p>
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

            <a href="{{ route('stock-fuel.create') }}" class="btn">
                Add / Adjust Fuel
            </a>

            <a href="{{ route('stock-fuel.history') }}" class="btn gray">
                Fuel History
            </a>
        </div>
    </div>

    <div class="panel">
        <h2 class="panel-title">Fuel Stock List</h2>

        <div class="excel-table-wrap">
            <table class="excel-table">
                <thead>
                    <tr>
                        <th>Stock Name</th>
                        <th>Opening Stock</th>
                        <th>Current Stock</th>
                        <th>Total Stock In</th>
                        <th>Total Stock Out</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($fuelStocks as $stock)
                        @php
                            $stockIn = \App\Models\FuelTransaction::where('fuel_stock_id', $stock->id)
                                ->where('type', 'stock_in')
                                ->sum('quantity');

                            $stockOut = \App\Models\FuelTransaction::where('fuel_stock_id', $stock->id)
                                ->whereIn('type', ['stock_out', 'refill_to_tractor'])
                                ->sum('quantity');
                        @endphp

                        <tr>
                            <td>{{ $stock->name }}</td>

                            <td>{{ number_format((float) $stock->opening_stock, 2) }} L</td>

                            <td class="current-stock">
                                {{ number_format((float) $stock->current_stock, 2) }} L
                            </td>

                            <td class="stock-in">
                                {{ number_format((float) $stockIn, 2) }} L
                            </td>

                            <td class="stock-out">
                                {{ number_format((float) $stockOut, 2) }} L
                            </td>

                            <td>
                                <span class="status {{ $stock->status }}">
                                    {{ ucfirst($stock->status) }}
                                </span>
                            </td>

                            <td>
                                @if($this->showEditFuel && auth()->user()->hasPermission('stock_fuel.edit'))
                                    <a href="{{ route('stock-fuel.edit', $stock->id) }}" class="mini">
                                        Edit
                                    </a>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align:center;color:#64748b;font-weight:800;">
                                No fuel stock found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>

                <tfoot>
                    <tr class="fuel-total-row">
                        <td class="total-label">Total</td>

                        <td>
                            {{ number_format((float) $this->totalOpeningStock, 2) }} L
                        </td>

                        <td class="current-stock">
                            {{ number_format((float) $this->totalCurrentStock, 2) }} L
                        </td>

                        <td class="stock-in">
                            {{ number_format((float) $this->totalStockIn, 2) }} L
                        </td>

                        <td class="stock-out">
                            {{ number_format((float) $this->totalStockOut, 2) }} L
                        </td>

                        <td>-</td>
                        <td>-</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>