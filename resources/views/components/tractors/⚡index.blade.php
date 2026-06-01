<?php

use Livewire\Component;
use App\Models\Tractor;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

new class extends Component
{
    public $search = '';
    public $rows = [];
    public $editingId = null;

    public $editRow = [
        'tractor_no' => '',
        'name' => '',
        'model' => '',
        'plate_no' => '',
        'fuel_capacity' => '',
        'status' => 'active',
    ];

    public function addRow()
    {
        $this->rows[] = $this->emptyRow();
    }

    public function emptyRow()
    {
        return [
            'tractor_no' => '',
            'name' => '',
            'model' => '',
            'plate_no' => '',
            'fuel_capacity' => '',
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
        if (!auth()->user()->hasPermission('tractors.create')) {
            abort(403, 'Permission denied.');
        }

        if (!isset($this->rows[$index])) {
            return;
        }

        $this->validate([
            "rows.$index.tractor_no" => [
                'required',
                'string',
                'max:100',
                Rule::unique('tractors', 'tractor_no'),
            ],
            "rows.$index.name" => 'required|string|max:150',
            "rows.$index.model" => 'required|string|max:150',
            "rows.$index.plate_no" => 'required|string|max:100',
            "rows.$index.fuel_capacity" => 'required|numeric|min:0',
            "rows.$index.status" => 'required|in:active,inactive',
        ]);

        $row = $this->rows[$index];

        Tractor::create([
            'tractor_no' => $row['tractor_no'],
            'name' => $row['name'],
            'model' => $row['model'],
            'plate_no' => $row['plate_no'],
            'fuel_capacity' => (float) $row['fuel_capacity'],
            'current_meter' => 0,
            'status' => $row['status'],
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        unset($this->rows[$index]);
        $this->rows = array_values($this->rows);

        session()->flash('success', 'Tractor saved successfully.');
    }

    public function edit($id)
    {
        if (!auth()->user()->hasPermission('tractors.edit')) {
            abort(403, 'Permission denied.');
        }

        $tractor = Tractor::findOrFail($id);

        $this->editingId = $tractor->id;

        $this->editRow = [
            'tractor_no' => $tractor->tractor_no,
            'name' => $tractor->name,
            'model' => $tractor->model,
            'plate_no' => $tractor->plate_no,
            'fuel_capacity' => $tractor->fuel_capacity,
            'status' => $tractor->status,
        ];
    }

    public function cancelEdit()
    {
        $this->editingId = null;

        $this->editRow = [
            'tractor_no' => '',
            'name' => '',
            'model' => '',
            'plate_no' => '',
            'fuel_capacity' => '',
            'status' => 'active',
        ];
    }

    public function updateRow()
    {
        if (!auth()->user()->hasPermission('tractors.edit')) {
            abort(403, 'Permission denied.');
        }

        $tractor = Tractor::findOrFail($this->editingId);

        $this->validate([
            'editRow.tractor_no' => [
                'required',
                'string',
                'max:100',
                Rule::unique('tractors', 'tractor_no')->ignore($tractor->id),
            ],
            'editRow.name' => 'required|string|max:150',
            'editRow.model' => 'required|string|max:150',
            'editRow.plate_no' => 'required|string|max:100',
            'editRow.fuel_capacity' => 'required|numeric|min:0',
            'editRow.status' => 'required|in:active,inactive',
        ]);

        $tractor->update([
            'tractor_no' => $this->editRow['tractor_no'],
            'name' => $this->editRow['name'],
            'model' => $this->editRow['model'],
            'plate_no' => $this->editRow['plate_no'],
            'fuel_capacity' => (float) $this->editRow['fuel_capacity'],
            'status' => $this->editRow['status'],
            'updated_by' => Auth::id(),
        ]);

        $this->cancelEdit();

        session()->flash('success', 'Tractor updated successfully.');
    }

    public function delete($id)
    {
        if (!auth()->user()->hasPermission('tractors.delete')) {
            abort(403, 'Permission denied.');
        }

        Tractor::findOrFail($id)->delete();

        session()->flash('success', 'Tractor deleted successfully.');
    }

    public function getTractorsProperty()
    {
        return Tractor::query()
            ->when($this->search, function ($q) {
                $q->where(function ($query) {
                    $query->where('tractor_no', 'like', '%' . $this->search . '%')
                        ->orWhere('name', 'like', '%' . $this->search . '%')
                        ->orWhere('model', 'like', '%' . $this->search . '%')
                        ->orWhere('plate_no', 'like', '%' . $this->search . '%')
                        ->orWhere('status', 'like', '%' . $this->search . '%');
                });
            })
            ->orderBy('tractor_no')
            ->get();
    }

    public function getTotalFuelCapacityProperty()
    {
        return $this->tractors->sum(function ($tractor) {
            return (float) ($tractor->fuel_capacity ?? 0);
        });
    }
};

?>

<div class="page">
    @include('components.shared-style')
    @include('components.toast-alert')

    <style>
        .master-toolbar { display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; margin-bottom:14px; }
        .filter-box { display:flex; align-items:center; gap:10px; flex:1; max-width:480px; }
        .filter-box input { width:100%; height:44px; border:1px solid #d1d5db; border-radius:12px; padding:10px 14px; font-weight:700; background:#fff; }

        .master-table-wrap { overflow-x:auto; border:1px solid #e5e7eb; border-radius:16px; }
        .master-table { width:100%; min-width:1180px; border-collapse:collapse; background:#fff; }
        .master-table th { background:#f8fafc; color:#0f172a; font-size:12px; font-weight:900; text-transform:uppercase; padding:12px 10px; border-bottom:1px solid #e5e7eb; white-space:nowrap; }
        .master-table td { padding:10px; border-bottom:1px solid #eef2f7; vertical-align:middle; white-space:nowrap; }

        .master-table input,
        .master-table select {
            width:100%;
            min-width:140px;
            height:44px;
            padding:9px 10px;
            border:1px solid #d1d5db;
            border-radius:10px;
            font-size:13px;
            background:#fff;
            font-weight:700;
        }

        .row-no { width:45px; min-width:45px; text-align:center; font-weight:900; color:#64748b; }
        .new-row { background:#f0fdf4; }
        .new-row td { border-bottom:1px solid #bbf7d0; }
        .table-actions { display:flex; gap:6px; align-items:center; flex-wrap:wrap; }

        .total-row { background:#f8fafc; font-weight:900; border-top:2px solid #d1d5db; }
        .total-row td { border-bottom:0; padding:14px 10px; color:#0f172a; }
        .total-label { text-align:right; font-weight:900; }

        .plus-cell {
            width:34px;
            height:34px;
            border:none;
            border-radius:10px;
            background:#16a34a;
            color:#fff;
            font-size:20px;
            font-weight:900;
            cursor:pointer;
            display:inline-flex;
            align-items:center;
            justify-content:center;
        }

        .plus-cell:hover { background:#15803d; }
        .danger-plus { background:#dc2626; }
        .danger-plus:hover { background:#b91c1c; }

        .error { display:block; color:#dc2626; font-size:12px; margin-top:4px; font-weight:700; }
    </style>

    <div class="page-header">
        <div>
            <h1 class="page-title">Tractors</h1>
            <p class="page-subtitle">Manage tractor and machine information.</p>
        </div>

        <div class="page-actions">
            {{-- <div class="language-switcher">
                <a href="{{ route('language.switch', 'en') }}" class="lang-btn {{ app()->getLocale() === 'en' ? 'active' : '' }}">EN</a>
                <a href="{{ route('language.switch', 'km') }}" class="lang-btn {{ app()->getLocale() === 'km' ? 'active' : '' }}">ខ្មែរ</a>
            </div> --}}

            <a href="{{ route('dashboard') }}" class="btn gray">Dashboard</a>
        </div>
    </div>

    <div class="panel">
        <div class="master-toolbar">
            <div class="filter-box">
                <input type="text" wire:model.live="search" placeholder="Filter tractor no, name, model, plate, status">
            </div>
        </div>

        <div class="master-table-wrap">
            <table class="master-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Tractor No *</th>
                        <th>Name</th>
                        <th>Model</th>
                        <th>Plate No</th>
                        <th>Fuel Capacity</th>
                        <th>Status</th>
                        <th width="190">Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($this->tractors as $tractor)
                        @if($editingId === $tractor->id)
                            <tr class="new-row">
                                <td class="row-no">{{ $loop->iteration }}</td>

                                <td>
                                    <input type="text" wire:model.live="editRow.tractor_no">
                                    @error('editRow.tractor_no') <small class="error">{{ $message }}</small> @enderror
                                </td>

                                <td>
                                    <input type="text" wire:model.live="editRow.name">
                                    @error('editRow.name') <small class="error">{{ $message }}</small> @enderror
                                </td>

                                <td>
                                    <input type="text" wire:model.live="editRow.model">
                                    @error('editRow.model') <small class="error">{{ $message }}</small> @enderror
                                </td>

                                <td>
                                    <input type="text" wire:model.live="editRow.plate_no">
                                    @error('editRow.plate_no') <small class="error">{{ $message }}</small> @enderror
                                </td>

                                <td>
                                    <input type="number" step="0.01" wire:model.live="editRow.fuel_capacity">
                                    @error('editRow.fuel_capacity') <small class="error">{{ $message }}</small> @enderror
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
                                <td>{{ $tractor->tractor_no }}</td>
                                <td>{{ $tractor->name ?? '-' }}</td>
                                <td>{{ $tractor->model ?? '-' }}</td>
                                <td>{{ $tractor->plate_no ?? '-' }}</td>
                                <td>{{ number_format((float) $tractor->fuel_capacity, 2) }}</td>
                                <td><span class="status {{ $tractor->status }}">{{ ucfirst($tractor->status) }}</span></td>
                                <td>
                                    <div class="table-actions">
                                        @if(auth()->user()->hasPermission('tractors.edit'))
                                            <button type="button" wire:click="edit({{ $tractor->id }})" class="mini">Edit</button>
                                        @endif

                                        @if(auth()->user()->hasPermission('tractors.delete'))
                                            <button type="button" wire:click="delete({{ $tractor->id }})" class="mini danger" onclick="return confirm('Delete this tractor?')">Delete</button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        @if(count($rows) === 0)
                            <tr>
                                <td colspan="8" class="empty">No tractor found.</td>
                            </tr>
                        @endif
                    @endforelse

                    @foreach($rows as $index => $row)
                        <tr class="new-row">
                            <td class="row-no">
                                <button type="button" wire:click="removeRow({{ $index }})" class="plus-cell danger-plus" title="Remove row">×</button>
                            </td>

                            <td>
                                <input type="text" wire:model.live="rows.{{ $index }}.tractor_no" placeholder="T-01">
                                @error("rows.$index.tractor_no") <small class="error">{{ $message }}</small> @enderror
                            </td>

                            <td>
                                <input type="text" wire:model.live="rows.{{ $index }}.name" placeholder="Tractor name">
                                @error("rows.$index.name") <small class="error">{{ $message }}</small> @enderror
                            </td>

                            <td>
                                <input type="text" wire:model.live="rows.{{ $index }}.model" placeholder="Kubota">
                                @error("rows.$index.model") <small class="error">{{ $message }}</small> @enderror
                            </td>

                            <td>
                                <input type="text" wire:model.live="rows.{{ $index }}.plate_no" placeholder="6454">
                                @error("rows.$index.plate_no") <small class="error">{{ $message }}</small> @enderror
                            </td>

                            <td>
                                <input type="number" step="0.01" wire:model.live="rows.{{ $index }}.fuel_capacity" placeholder="10">
                                @error("rows.$index.fuel_capacity") <small class="error">{{ $message }}</small> @enderror
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
                            @if(auth()->user()->hasPermission('tractors.create'))
                                <button type="button" wire:click="addRow" class="plus-cell" title="Add row">+</button>
                            @else
                                -
                            @endif
                        </td>

                        <td colspan="4" class="total-label">Total</td>
                        <td>{{ number_format((float) $this->totalFuelCapacity, 2) }}</td>
                        <td>-</td>
                        <td>-</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>