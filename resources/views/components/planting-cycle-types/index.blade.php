<?php

use Livewire\Component;
use App\Models\PlantingCycleType;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

new class extends Component
{
    public $search = '';
    public $rows = [];

    public $editingId = null;

    public $editRow = [
        'code' => '',
        'name' => '',
        'description' => '',
        'status' => 'active',
    ];

    public function addRow()
    {
        $this->rows[] = $this->emptyRow();
    }

    public function emptyRow()
    {
        return [
            'code' => '',
            'name' => '',
            'description' => '',
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
        if (!auth()->user()->hasPermission('planting_cycle_types.create')) {
            abort(403, 'Permission denied.');
        }

        if (!isset($this->rows[$index])) {
            return;
        }

        $this->validate([
            "rows.$index.code" => [
                'required',
                'string',
                'max:50',
                Rule::unique('planting_cycle_types', 'code'),
            ],
            "rows.$index.name" => 'required|string|max:150',
            "rows.$index.description" => 'nullable|string|max:1000',
            "rows.$index.status" => 'required|in:active,inactive',
        ]);

        $row = $this->rows[$index];

        PlantingCycleType::create([
            'code' => $row['code'],
            'name' => $row['name'],
            'description' => $row['description'] ?: null,
            'status' => $row['status'],
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        unset($this->rows[$index]);
        $this->rows = array_values($this->rows);

        session()->flash('success', 'Cycle type saved successfully.');
    }

    public function edit($id)
    {
        if (!auth()->user()->hasPermission('planting_cycle_types.edit')) {
            abort(403, 'Permission denied.');
        }

        $cycleType = PlantingCycleType::findOrFail($id);

        $this->editingId = $cycleType->id;

        $this->editRow = [
            'code' => $cycleType->code,
            'name' => $cycleType->name,
            'description' => $cycleType->description,
            'status' => $cycleType->status,
        ];
    }

    public function cancelEdit()
    {
        $this->editingId = null;

        $this->editRow = [
            'code' => '',
            'name' => '',
            'description' => '',
            'status' => 'active',
        ];
    }

    public function updateRow()
    {
        if (!auth()->user()->hasPermission('planting_cycle_types.edit')) {
            abort(403, 'Permission denied.');
        }

        $cycleType = PlantingCycleType::findOrFail($this->editingId);

        $this->validate([
            'editRow.code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('planting_cycle_types', 'code')->ignore($cycleType->id),
            ],
            'editRow.name' => 'required|string|max:150',
            'editRow.description' => 'nullable|string|max:1000',
            'editRow.status' => 'required|in:active,inactive',
        ]);

        $cycleType->update([
            'code' => $this->editRow['code'],
            'name' => $this->editRow['name'],
            'description' => $this->editRow['description'] ?: null,
            'status' => $this->editRow['status'],
            'updated_by' => Auth::id(),
        ]);

        $this->cancelEdit();

        session()->flash('success', 'Cycle type updated successfully.');
    }

    public function delete($id)
    {
        if (!auth()->user()->hasPermission('planting_cycle_types.delete')) {
            abort(403, 'Permission denied.');
        }

        PlantingCycleType::findOrFail($id)->delete();

        session()->flash('success', 'Cycle type deleted successfully.');
    }

    public function getCycleTypesProperty()
    {
        return PlantingCycleType::query()
            ->when($this->search, function ($q) {
                $q->where(function ($query) {
                    $query->where('code', 'like', '%' . $this->search . '%')
                        ->orWhere('name', 'like', '%' . $this->search . '%')
                        ->orWhere('description', 'like', '%' . $this->search . '%')
                        ->orWhere('status', 'like', '%' . $this->search . '%');
                });
            })
            ->orderBy('code')
            ->get();
    }

    public function getTotalCycleTypesProperty()
    {
        return $this->cycleTypes->count();
    }
};

?>

<div class="page">
    @include('components.shared-style')
    @include('components.toast-alert')

    <style>
        .master-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 14px;
        }

        .filter-box {
            display: flex;
            align-items: center;
            gap: 10px;
            flex: 1;
            max-width: 480px;
        }

        .filter-box input {
            width: 100%;
            height: 44px;
            border: 1px solid #d1d5db;
            border-radius: 12px;
            padding: 10px 14px;
            font-weight: 700;
            background: #ffffff;
        }

        .master-table-wrap {
            overflow-x: auto;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
        }

        .master-table {
            width: 100%;
            min-width: 1000px;
            border-collapse: collapse;
            background: #ffffff;
        }

        .master-table th {
            background: #f8fafc;
            color: #0f172a;
            font-size: 12px;
            font-weight: 900;
            text-transform: uppercase;
            padding: 12px 10px;
            border-bottom: 1px solid #e5e7eb;
            white-space: nowrap;
        }

        .master-table td {
            padding: 10px;
            border-bottom: 1px solid #eef2f7;
            vertical-align: middle;
            white-space: nowrap;
        }

        .master-table input,
        .master-table select {
            width: 100%;
            min-width: 140px;
            height: 44px;
            padding: 9px 10px;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            font-size: 13px;
            background: #ffffff;
            font-weight: 700;
        }

        .wide-input {
            min-width: 320px !important;
        }

        .row-no {
            width: 45px;
            min-width: 45px;
            text-align: center;
            font-weight: 900;
            color: #64748b;
        }

        .new-row {
            background: #f0fdf4;
        }

        .new-row td {
            border-bottom: 1px solid #bbf7d0;
        }

        .table-actions {
            display: flex;
            gap: 6px;
            align-items: center;
            flex-wrap: wrap;
        }

        .total-row {
            background: #f8fafc;
            font-weight: 900;
            border-top: 2px solid #d1d5db;
        }

        .total-row td {
            border-bottom: 0;
            padding: 14px 10px;
            color: #0f172a;
        }

        .total-label {
            text-align: right;
            font-weight: 900;
        }

        .plus-cell {
            width: 34px;
            height: 34px;
            border: none;
            border-radius: 10px;
            background: #16a34a;
            color: #ffffff;
            font-size: 20px;
            font-weight: 900;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .plus-cell:hover {
            background: #15803d;
        }

        .danger-plus {
            background: #dc2626;
        }

        .danger-plus:hover {
            background: #b91c1c;
        }

        .error {
            display: block;
            color: #dc2626;
            font-size: 12px;
            margin-top: 4px;
            font-weight: 700;
        }
    </style>

    <div class="page-header">
        <div>
            <h1 class="page-title">Planting Cycle Types</h1>
            <p class="page-subtitle">Manage crop planting cycle master data.</p>
        </div>

        <div class="page-actions">
            {{-- <div class="language-switcher">
                <a href="{{ route('language.switch', 'en') }}"
                   class="lang-btn {{ app()->getLocale() === 'en' ? 'active' : '' }}">
                    EN
                </a>

                <a href="{{ route('language.switch', 'km') }}"
                   class="lang-btn {{ app()->getLocale() === 'km' ? 'active' : '' }}">
                    ខ្មែរ
                </a>
            </div> --}}

            <a href="{{ route('dashboard') }}" class="btn gray">
                Dashboard
            </a>
        </div>
    </div>

    <div class="panel">
        <div class="master-toolbar">
            <div class="filter-box">
                <input type="text"
                       wire:model.live="search"
                       placeholder="Filter code, cycle type, description, status">
            </div>
        </div>

        <div class="master-table-wrap">
            <table class="master-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Code *</th>
                        <th>Cycle Type *</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th width="190">Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($this->cycleTypes as $cycleType)
                        @if($editingId === $cycleType->id)
                            <tr class="new-row">
                                <td class="row-no">{{ $loop->iteration }}</td>

                                <td>
                                    <input type="text"
                                           wire:model.live="editRow.code"
                                           placeholder="PC">
                                    @error('editRow.code')
                                        <small class="error">{{ $message }}</small>
                                    @enderror
                                </td>

                                <td>
                                    <input type="text"
                                           wire:model.live="editRow.name"
                                           placeholder="Plant Cane">
                                    @error('editRow.name')
                                        <small class="error">{{ $message }}</small>
                                    @enderror
                                </td>

                                <td>
                                    <input type="text"
                                           class="wide-input"
                                           wire:model.live="editRow.description"
                                           placeholder="Description">
                                    @error('editRow.description')
                                        <small class="error">{{ $message }}</small>
                                    @enderror
                                </td>

                                <td>
                                    <select wire:model.live="editRow.status">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                    @error('editRow.status')
                                        <small class="error">{{ $message }}</small>
                                    @enderror
                                </td>

                                <td>
                                    <div class="table-actions">
                                        <button type="button"
                                                wire:click="updateRow"
                                                class="mini">
                                            Save
                                        </button>

                                        <button type="button"
                                                wire:click="cancelEdit"
                                                class="mini danger">
                                            Cancel
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @else
                            <tr>
                                <td class="row-no">{{ $loop->iteration }}</td>

                                <td>{{ $cycleType->code }}</td>

                                <td>{{ $cycleType->name }}</td>

                                <td>{{ $cycleType->description ?? '-' }}</td>

                                <td>
                                    <span class="status {{ $cycleType->status }}">
                                        {{ ucfirst($cycleType->status) }}
                                    </span>
                                </td>

                                <td>
                                    <div class="table-actions">
                                        @if(auth()->user()->hasPermission('planting_cycle_types.edit'))
                                            <button type="button"
                                                    wire:click="edit({{ $cycleType->id }})"
                                                    class="mini">
                                                Edit
                                            </button>
                                        @endif

                                        @if(auth()->user()->hasPermission('planting_cycle_types.delete'))
                                            <button type="button"
                                                    wire:click="delete({{ $cycleType->id }})"
                                                    class="mini danger"
                                                    onclick="return confirm('Delete this cycle type?')">
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
                                <td colspan="6" class="empty">
                                    No cycle type found.
                                </td>
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
                                <input type="text"
                                       wire:model.live="rows.{{ $index }}.code"
                                       placeholder="PC">
                                @error("rows.$index.code")
                                    <small class="error">{{ $message }}</small>
                                @enderror
                            </td>

                            <td>
                                <input type="text"
                                       wire:model.live="rows.{{ $index }}.name"
                                       placeholder="Plant Cane">
                                @error("rows.$index.name")
                                    <small class="error">{{ $message }}</small>
                                @enderror
                            </td>

                            <td>
                                <input type="text"
                                       class="wide-input"
                                       wire:model.live="rows.{{ $index }}.description"
                                       placeholder="Description">
                                @error("rows.$index.description")
                                    <small class="error">{{ $message }}</small>
                                @enderror
                            </td>

                            <td>
                                <select wire:model.live="rows.{{ $index }}.status">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                                @error("rows.$index.status")
                                    <small class="error">{{ $message }}</small>
                                @enderror
                            </td>

                            <td>
                                <div class="table-actions">
                                    <button type="button"
                                            wire:click="saveRow({{ $index }})"
                                            class="mini">
                                        Save
                                    </button>

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
                    <tr class="total-row">
                        <td>
                            @if(auth()->user()->hasPermission('planting_cycle_types.create'))
                                <button type="button"
                                        wire:click="addRow"
                                        class="plus-cell"
                                        title="Add row">
                                    +
                                </button>
                            @else
                                -
                            @endif
                        </td>

                        <td colspan="3" class="total-label">
                            Total Cycle Types
                        </td>

                        <td>{{ number_format((int) $this->totalCycleTypes) }}</td>

                        <td>-</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>