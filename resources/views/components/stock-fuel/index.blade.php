<?php

use Livewire\Component;
use App\Models\FuelStock;
use App\Models\FuelTransaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

new class extends Component
{
    public $search = '';
    public $rows = [];

    public $editingId = null;

    public $editRow = [
        'opening_stock' => '',
        'current_stock' => '',
        'total_stock_in' => '',
        'total_stock_out' => '',
        'status' => 'active',
    ];

    public function addRow()
    {
        $this->rows[] = $this->emptyRow();
    }

    public function emptyRow()
    {
        return [
            'opening_stock' => '',
            'current_stock' => '',
            'total_stock_in' => '',
            'total_stock_out' => '',
            'status' => 'active',
        ];
    }

    public function removeRow($index)
    {
        if (!isset($this->rows[$index])) {
            return;
        }

        unset($this->rows[$index]);
        $this->rows = array_values($this->rows);
    }

    public function saveRow($index)
    {
        if (!auth()->user()->hasPermission('stock_fuel.create')) {
            abort(403, 'Permission denied.');
        }

        if (!isset($this->rows[$index])) {
            return;
        }

        $this->validate([
            "rows.$index.opening_stock" => 'nullable|numeric|min:0',
            "rows.$index.current_stock" => 'nullable|numeric|min:0',
            "rows.$index.total_stock_in" => 'nullable|numeric|min:0',
            "rows.$index.total_stock_out" => 'nullable|numeric|min:0',
            "rows.$index.status" => 'required|in:active,inactive',
        ]);

        $row = $this->rows[$index];

        try {
            DB::transaction(function () use ($row) {
                $openingStock = (float) ($row['opening_stock'] ?: 0);
                $currentStock = (float) ($row['current_stock'] ?: 0);
                $totalStockIn = (float) ($row['total_stock_in'] ?: 0);
                $totalStockOut = (float) ($row['total_stock_out'] ?: 0);

                if ($currentStock <= 0 && $openingStock > 0) {
                    $currentStock = $openingStock;
                }

                if ($totalStockIn <= 0 && $currentStock > 0) {
                    $totalStockIn = $currentStock;
                }

                $fuelStock = FuelStock::create([
                    'opening_stock' => $openingStock,
                    'current_stock' => $currentStock,
                    'total_stock_in' => $totalStockIn,
                    'total_stock_out' => $totalStockOut,
                    'status' => $row['status'],
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ]);

                if ($currentStock > 0) {
                    FuelTransaction::create([
                        'fuel_stock_id' => $fuelStock->id,
                        'tractor_id' => null,
                        'farm_work_log_id' => null,
                        'type' => 'stock_in',
                        'quantity' => $currentStock,
                        'balance_after' => $currentStock,
                        'reference_no' => 'STOCK-IN-' . $fuelStock->id . '-' . now()->format('YmdHis'),
                        'transaction_date' => now()->toDateString(),
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id(),
                        'note' => 'Initial fuel stock created',
                    ]);
                }
            });

            unset($this->rows[$index]);
            $this->rows = array_values($this->rows);

            session()->flash('success', 'Fuel stock saved successfully and history created.');
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function edit($id)
    {
        if (!auth()->user()->hasPermission('stock_fuel.edit')) {
            abort(403, 'Permission denied.');
        }

        $fuelStock = FuelStock::findOrFail($id);

        $this->editingId = $fuelStock->id;

        $this->editRow = [
            'opening_stock' => $fuelStock->opening_stock,
            'current_stock' => $fuelStock->current_stock,
            'total_stock_in' => $fuelStock->total_stock_in,
            'total_stock_out' => $fuelStock->total_stock_out,
            'status' => $fuelStock->status,
        ];
    }

    public function cancelEdit()
    {
        $this->editingId = null;

        $this->editRow = [
            'opening_stock' => '',
            'current_stock' => '',
            'total_stock_in' => '',
            'total_stock_out' => '',
            'status' => 'active',
        ];
    }

    public function updateRow()
    {
        if (!auth()->user()->hasPermission('stock_fuel.edit')) {
            abort(403, 'Permission denied.');
        }

        $this->validate([
            'editRow.opening_stock' => 'nullable|numeric|min:0',
            'editRow.current_stock' => 'nullable|numeric|min:0',
            'editRow.total_stock_in' => 'nullable|numeric|min:0',
            'editRow.total_stock_out' => 'nullable|numeric|min:0',
            'editRow.status' => 'required|in:active,inactive',
        ]);

        try {
            DB::transaction(function () {
                $fuelStock = FuelStock::lockForUpdate()->findOrFail($this->editingId);

                $oldCurrentStock = (float) $fuelStock->current_stock;

                $newOpeningStock = (float) ($this->editRow['opening_stock'] ?: 0);
                $newCurrentStock = (float) ($this->editRow['current_stock'] ?: 0);
                $newTotalStockIn = (float) ($this->editRow['total_stock_in'] ?: 0);
                $newTotalStockOut = (float) ($this->editRow['total_stock_out'] ?: 0);

                $fuelStock->update([
                    'opening_stock' => $newOpeningStock,
                    'current_stock' => $newCurrentStock,
                    'total_stock_in' => $newTotalStockIn,
                    'total_stock_out' => $newTotalStockOut,
                    'status' => $this->editRow['status'],
                    'updated_by' => Auth::id(),
                ]);

                $difference = $newCurrentStock - $oldCurrentStock;

                if ($difference != 0) {
                    FuelTransaction::create([
                        'fuel_stock_id' => $fuelStock->id,
                        'tractor_id' => null,
                        'farm_work_log_id' => null,
                        'type' => 'adjustment',
                        'quantity' => abs($difference),
                        'balance_after' => $newCurrentStock,
                        'reference_no' => 'ADJUST-STOCK-' . $fuelStock->id . '-' . now()->format('YmdHis'),
                        'transaction_date' => now()->toDateString(),
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id(),
                        'note' => $difference > 0
                            ? 'Fuel stock adjusted increase'
                            : 'Fuel stock adjusted decrease',
                    ]);
                }
            });

            $this->cancelEdit();

            session()->flash('success', 'Fuel stock updated successfully and history adjusted.');
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function delete($id)
    {
        if (!auth()->user()->hasPermission('stock_fuel.delete')) {
            abort(403, 'Permission denied.');
        }

        FuelStock::findOrFail($id)->delete();

        session()->flash('success', 'Fuel stock deleted successfully.');
    }

    public function getFuelStocksProperty()
    {
        return FuelStock::query()
            ->when($this->search, function ($q) {
                $q->where(function ($query) {
                    $query->where('status', 'like', '%' . $this->search . '%')
                        ->orWhere('opening_stock', 'like', '%' . $this->search . '%')
                        ->orWhere('current_stock', 'like', '%' . $this->search . '%')
                        ->orWhere('total_stock_in', 'like', '%' . $this->search . '%')
                        ->orWhere('total_stock_out', 'like', '%' . $this->search . '%');
                });
            })
            ->latest()
            ->get();
    }

    public function getTotalOpeningStockProperty()
    {
        return $this->fuelStocks->sum(fn ($stock) => (float) ($stock->opening_stock ?? 0));
    }

    public function getTotalCurrentStockProperty()
    {
        return $this->fuelStocks->sum(fn ($stock) => (float) ($stock->current_stock ?? 0));
    }

    public function getTotalStockInProperty()
    {
        return $this->fuelStocks->sum(fn ($stock) => (float) ($stock->total_stock_in ?? 0));
    }

    public function getTotalStockOutProperty()
    {
        return $this->fuelStocks->sum(fn ($stock) => (float) ($stock->total_stock_out ?? 0));
    }
};

?>

<div class="page">
    @include('components.shared-style')
    @include('components.toast-alert')

    <style>
        .master-toolbar { display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; margin-bottom:14px; }
        .filter-box { display:flex; align-items:center; gap:10px; flex:1; max-width:480px; }
        .filter-box input { width:100%; height:44px; border:1px solid #d1d5db; border-radius:12px; padding:10px 14px; font-weight:700; background:#ffffff; }
        .master-table-wrap { overflow-x:auto; border:1px solid #e5e7eb; border-radius:16px; }
        .master-table { width:100%; min-width:1150px; border-collapse:collapse; background:#ffffff; }
        .master-table th { background:#f8fafc; color:#0f172a; font-size:12px; font-weight:900; text-transform:uppercase; padding:12px 10px; border-bottom:1px solid #e5e7eb; white-space:nowrap; }
        .master-table td { padding:10px; border-bottom:1px solid #eef2f7; vertical-align:middle; white-space:nowrap; }
        .master-table input, .master-table select { width:100%; min-width:140px; height:44px; padding:9px 10px; border:1px solid #d1d5db; border-radius:10px; font-size:13px; background:#ffffff; font-weight:700; }
        .row-no { width:45px; min-width:45px; text-align:center; font-weight:900; color:#64748b; }
        .new-row { background:#f0fdf4; }
        .new-row td { border-bottom:1px solid #bbf7d0; }
        .table-actions { display:flex; gap:6px; align-items:center; flex-wrap:wrap; }
        .total-row { background:#f8fafc; font-weight:900; border-top:2px solid #d1d5db; }
        .total-row td { border-bottom:0; padding:14px 10px; color:#0f172a; }
        .total-label { text-align:right; font-weight:900; }
        .plus-cell { width:34px; height:34px; border:none; border-radius:10px; background:#16a34a; color:#ffffff; font-size:20px; font-weight:900; cursor:pointer; display:inline-flex; align-items:center; justify-content:center; }
        .plus-cell:hover { background:#15803d; }
        .danger-plus { background:#dc2626; }
        .danger-plus:hover { background:#b91c1c; }
        .green-number { color:#166534; font-weight:900; }
        .red-number { color:#dc2626; font-weight:900; }
        .error { display:block; color:#dc2626; font-size:12px; margin-top:4px; font-weight:700; }
    </style>

    <div class="page-header">
        <div>
            <h1 class="page-title">Stock Fuel</h1>
            <p class="page-subtitle">Control diesel stock balance, stock in, stock out, and fuel usage.</p>
        </div>

        <div class="page-actions">
            <a href="{{ route('stock-fuel.history') }}" class="btn gray">
                Fuel History
            </a>
        </div>
    </div>

    <div class="panel">
        <div class="master-toolbar">
            <div class="filter-box">
                <input type="text"
                       wire:model.live="search"
                       placeholder="Filter stock fuel by status or quantity">
            </div>
        </div>

        <div class="master-table-wrap">
            <table class="master-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Opening Stock</th>
                        <th>Current Stock</th>
                        <th>Total Stock In</th>
                        <th>Total Stock Out</th>
                        <th>Status</th>
                        <th width="190">Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($this->fuelStocks as $stock)
                        @if($editingId === $stock->id)
                            <tr class="new-row">
                                <td class="row-no">{{ $loop->iteration }}</td>

                                <td>
                                    <input type="number" step="0.01" wire:model.live="editRow.opening_stock">
                                    @error('editRow.opening_stock') <small class="error">{{ $message }}</small> @enderror
                                </td>

                                <td>
                                    <input type="number" step="0.01" wire:model.live="editRow.current_stock">
                                    @error('editRow.current_stock') <small class="error">{{ $message }}</small> @enderror
                                </td>

                                <td>
                                    <input type="number" step="0.01" wire:model.live="editRow.total_stock_in">
                                    @error('editRow.total_stock_in') <small class="error">{{ $message }}</small> @enderror
                                </td>

                                <td>
                                    <input type="number" step="0.01" wire:model.live="editRow.total_stock_out">
                                    @error('editRow.total_stock_out') <small class="error">{{ $message }}</small> @enderror
                                </td>

                                <td>
                                    <select wire:model.live="editRow.status">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                    @error('editRow.status') <small class="error">{{ $message }}</small> @enderror
                                </td>

                                <td>
                                    <div class="table-actions">
                                        <button type="button" wire:click="updateRow" class="mini">Save</button>
                                        <button type="button" wire:click="cancelEdit" class="mini danger">Cancel</button>
                                    </div>
                                </td>
                            </tr>
                        @else
                            <tr>
                                <td class="row-no">{{ $loop->iteration }}</td>
                                <td>{{ number_format((float) $stock->opening_stock, 2) }} L</td>
                                <td class="green-number">{{ number_format((float) $stock->current_stock, 2) }} L</td>
                                <td class="green-number">{{ number_format((float) $stock->total_stock_in, 2) }} L</td>
                                <td class="red-number">{{ number_format((float) $stock->total_stock_out, 2) }} L</td>
                                <td><span class="status {{ $stock->status }}">{{ ucfirst($stock->status) }}</span></td>
                                <td>
                                    <div class="table-actions">
                                        @if(auth()->user()->hasPermission('stock_fuel.edit'))
                                            <button type="button" wire:click="edit({{ $stock->id }})" class="mini">Edit</button>
                                        @endif

                                        @if(auth()->user()->hasPermission('stock_fuel.delete'))
                                            <button type="button"
                                                    wire:click="delete({{ $stock->id }})"
                                                    class="mini danger"
                                                    onclick="return confirm('Delete this fuel stock?')">
                                                Delete
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        @if(count($rows) === 0)
                            <tr>
                                <td colspan="7" class="empty">No fuel stock found.</td>
                            </tr>
                        @endif
                    @endforelse

                    @foreach($rows as $index => $row)
                        <tr class="new-row">
                            <td class="row-no">
                                <button type="button"
                                        wire:click="removeRow({{ $index }})"
                                        class="plus-cell danger-plus"
                                        title="Remove row">
                                    ×
                                </button>
                            </td>

                            <td>
                                <input type="number" step="0.01" wire:model.live="rows.{{ $index }}.opening_stock" placeholder="0">
                                @error("rows.$index.opening_stock") <small class="error">{{ $message }}</small> @enderror
                            </td>

                            <td>
                                <input type="number" step="0.01" wire:model.live="rows.{{ $index }}.current_stock" placeholder="0">
                                @error("rows.$index.current_stock") <small class="error">{{ $message }}</small> @enderror
                            </td>

                            <td>
                                <input type="number" step="0.01" wire:model.live="rows.{{ $index }}.total_stock_in" placeholder="0">
                                @error("rows.$index.total_stock_in") <small class="error">{{ $message }}</small> @enderror
                            </td>

                            <td>
                                <input type="number" step="0.01" wire:model.live="rows.{{ $index }}.total_stock_out" placeholder="0">
                                @error("rows.$index.total_stock_out") <small class="error">{{ $message }}</small> @enderror
                            </td>

                            <td>
                                <select wire:model.live="rows.{{ $index }}.status">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                                @error("rows.$index.status") <small class="error">{{ $message }}</small> @enderror
                            </td>

                            <td>
                                <div class="table-actions">
                                    <button type="button" wire:click="saveRow({{ $index }})" class="mini">Save</button>
                                    <button type="button" wire:click="removeRow({{ $index }})" class="mini danger">Remove</button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>

                <tfoot>
                    <tr class="total-row">
                        <td>
                            @if(auth()->user()->hasPermission('stock_fuel.create'))
                                <button type="button" wire:click="addRow" class="plus-cell" title="Add row">+</button>
                            @else
                                -
                            @endif
                        </td>

                        <td class="total-label">Total</td>
                        <td>{{ number_format((float) $this->totalCurrentStock, 2) }} L</td>
                        <td class="green-number">{{ number_format((float) $this->totalStockIn, 2) }} L</td>
                        <td class="red-number">{{ number_format((float) $this->totalStockOut, 2) }} L</td>
                        <td>-</td>
                        <td>-</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>