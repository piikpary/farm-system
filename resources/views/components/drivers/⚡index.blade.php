<?php

use Livewire\Component;
use App\Models\Driver;
use Illuminate\Support\Facades\Auth;

new class extends Component
{
    public $search = '';
    public $rows = [];
    public $editingId = null;

    public $editRow = [
        'name' => '',
        'phone' => '',
        'id_card_no' => '',
        'address' => '',
        'status' => 'active',
    ];

    public function addRow()
    {
        $this->rows[] = $this->emptyRow();
    }

    public function emptyRow()
    {
        return [
            'name' => '',
            'phone' => '',
            'id_card_no' => '',
            'address' => '',
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
        if (!auth()->user()->hasPermission('drivers.create')) {
            abort(403, 'Permission denied.');
        }

        if (!isset($this->rows[$index])) {
            return;
        }

        $this->validate([
            "rows.$index.name" => 'required|string|max:150',
            "rows.$index.phone" => 'nullable|string|max:50',
            "rows.$index.id_card_no" => 'nullable|string|max:100',
            "rows.$index.address" => 'nullable|string|max:1000',
            "rows.$index.status" => 'required|in:active,inactive',
        ]);

        $row = $this->rows[$index];

        Driver::create([
            'name' => $row['name'],
            'phone' => $row['phone'] ?: null,
            'id_card_no' => $row['id_card_no'] ?: null,
            'address' => $row['address'] ?: null,
            'status' => $row['status'],
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        unset($this->rows[$index]);
        $this->rows = array_values($this->rows);

        session()->flash('success', 'Driver saved successfully.');
    }

    public function edit($id)
    {
        if (!auth()->user()->hasPermission('drivers.edit')) {
            abort(403, 'Permission denied.');
        }

        $driver = Driver::findOrFail($id);

        $this->editingId = $driver->id;

        $this->editRow = [
            'name' => $driver->name,
            'phone' => $driver->phone,
            'id_card_no' => $driver->id_card_no,
            'address' => $driver->address,
            'status' => $driver->status,
        ];
    }

    public function cancelEdit()
    {
        $this->editingId = null;

        $this->editRow = [
            'name' => '',
            'phone' => '',
            'id_card_no' => '',
            'address' => '',
            'status' => 'active',
        ];
    }

    public function updateRow()
    {
        if (!auth()->user()->hasPermission('drivers.edit')) {
            abort(403, 'Permission denied.');
        }

        $driver = Driver::findOrFail($this->editingId);

        $this->validate([
            'editRow.name' => 'required|string|max:150',
            'editRow.phone' => 'nullable|string|max:50',
            'editRow.id_card_no' => 'nullable|string|max:100',
            'editRow.address' => 'nullable|string|max:1000',
            'editRow.status' => 'required|in:active,inactive',
        ]);

        $driver->update([
            'name' => $this->editRow['name'],
            'phone' => $this->editRow['phone'] ?: null,
            'id_card_no' => $this->editRow['id_card_no'] ?: null,
            'address' => $this->editRow['address'] ?: null,
            'status' => $this->editRow['status'],
            'updated_by' => Auth::id(),
        ]);

        $this->cancelEdit();

        session()->flash('success', 'Driver updated successfully.');
    }

    public function delete($id)
    {
        if (!auth()->user()->hasPermission('drivers.delete')) {
            abort(403, 'Permission denied.');
        }

        Driver::findOrFail($id)->delete();

        session()->flash('success', 'Driver deleted successfully.');
    }

    public function getDriversProperty()
    {
        return Driver::query()
            ->when($this->search, function ($q) {
                $q->where(function ($query) {
                    $query->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('phone', 'like', '%' . $this->search . '%')
                        ->orWhere('id_card_no', 'like', '%' . $this->search . '%')
                        ->orWhere('address', 'like', '%' . $this->search . '%')
                        ->orWhere('status', 'like', '%' . $this->search . '%');
                });
            })
            ->orderBy('name')
            ->get();
    }

    public function getTotalDriversProperty()
    {
        return $this->drivers->count();
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

        .wide-input { min-width:240px !important; }
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
            <h1 class="page-title">{{ __('pages.drivers') }}</h1>
            <p class="page-subtitle">List of tractor drivers.</p>
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
                <input type="text" wire:model.live="search" placeholder="Filter name, phone, ID card, address, status">
            </div>
        </div>

        <div class="master-table-wrap">
            <table class="master-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name *</th>
                        <th>Phone</th>
                        <th>ID Card</th>
                        <th>Address</th>
                        <th>Status</th>
                        <th width="190">Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($this->drivers as $driver)
                        @if($editingId === $driver->id)
                            <tr class="new-row">
                                <td class="row-no">{{ $loop->iteration }}</td>

                                <td>
                                    <input type="text" wire:model.live="editRow.name">
                                    @error('editRow.name') <small class="error">{{ $message }}</small> @enderror
                                </td>

                                <td>
                                    <input type="text" wire:model.live="editRow.phone">
                                    @error('editRow.phone') <small class="error">{{ $message }}</small> @enderror
                                </td>

                                <td>
                                    <input type="text" wire:model.live="editRow.id_card_no">
                                    @error('editRow.id_card_no') <small class="error">{{ $message }}</small> @enderror
                                </td>

                                <td>
                                    <input type="text" class="wide-input" wire:model.live="editRow.address">
                                    @error('editRow.address') <small class="error">{{ $message }}</small> @enderror
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
                                <td>{{ $driver->name }}</td>
                                <td>{{ $driver->phone ?? '-' }}</td>
                                <td>{{ $driver->id_card_no ?? '-' }}</td>
                                <td>{{ $driver->address ?? '-' }}</td>
                                <td><span class="status {{ $driver->status }}">{{ ucfirst($driver->status) }}</span></td>
                                <td>
                                    <div class="table-actions">
                                        @if(auth()->user()->hasPermission('drivers.edit'))
                                            <button type="button" wire:click="edit({{ $driver->id }})" class="mini">Edit</button>
                                        @endif

                                        @if(auth()->user()->hasPermission('drivers.delete'))
                                            <button type="button" wire:click="delete({{ $driver->id }})" class="mini danger" onclick="return confirm('Delete this driver?')">Delete</button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        @if(count($rows) === 0)
                            <tr>
                                <td colspan="7" class="empty">No driver found.</td>
                            </tr>
                        @endif
                    @endforelse

                    @foreach($rows as $index => $row)
                        <tr class="new-row">
                            <td class="row-no">
                                <button type="button" wire:click="removeRow({{ $index }})" class="plus-cell danger-plus" title="Remove row">×</button>
                            </td>

                            <td>
                                <input type="text" wire:model.live="rows.{{ $index }}.name" placeholder="Driver name">
                                @error("rows.$index.name") <small class="error">{{ $message }}</small> @enderror
                            </td>

                            <td>
                                <input type="text" wire:model.live="rows.{{ $index }}.phone" placeholder="Phone number">
                                @error("rows.$index.phone") <small class="error">{{ $message }}</small> @enderror
                            </td>

                            <td>
                                <input type="text" wire:model.live="rows.{{ $index }}.id_card_no" placeholder="ID card number">
                                @error("rows.$index.id_card_no") <small class="error">{{ $message }}</small> @enderror
                            </td>

                            <td>
                                <input type="text" class="wide-input" wire:model.live="rows.{{ $index }}.address" placeholder="Address">
                                @error("rows.$index.address") <small class="error">{{ $message }}</small> @enderror
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
                            @if(auth()->user()->hasPermission('drivers.create'))
                                <button type="button" wire:click="addRow" class="plus-cell" title="Add row">+</button>
                            @else
                                -
                            @endif
                        </td>

                        <td colspan="4" class="total-label">{{ __('pages.total_drivers') }}</td>
                        <td>{{ number_format((int) $this->totalDrivers) }}</td>
                        <td>-</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>