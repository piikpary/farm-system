<?php

use Livewire\Component;
use App\Models\BlockRegister;
use App\Models\ZoneBlock;
use App\Models\PlantingCycleType;
use Illuminate\Support\Facades\Auth;

new class extends Component
{
    public $search = '';
    public $rows = [];

    public $editingId = null;

    public $editRow = [
        'zone_block_id' => '',
        'variety' => '',
        'planting_date' => '',
        'planting_cycle_type_id' => '',
        'expected_harvest' => '',
        'status' => 'active',
    ];

    public function addRow()
    {
        $this->rows[] = $this->emptyRow();
    }

    public function emptyRow()
    {
        return [
            'zone_block_id' => '',
            'variety' => '',
            'planting_date' => '',
            'planting_cycle_type_id' => '',
            'expected_harvest' => '',
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
        if (!auth()->user()->hasPermission('block_registers.create')) {
            abort(403, 'Permission denied.');
        }

        if (!isset($this->rows[$index])) {
            return;
        }

        $this->validate([
            "rows.$index.zone_block_id" => 'required|exists:zone_blocks,id',
            "rows.$index.variety" => 'nullable|string|max:150',
            "rows.$index.planting_date" => 'nullable|date',
            "rows.$index.planting_cycle_type_id" => 'nullable|exists:planting_cycle_types,id',
            "rows.$index.expected_harvest" => 'nullable|date',
            "rows.$index.status" => 'required|in:active,inactive',
        ]);

        $row = $this->rows[$index];

        BlockRegister::create([
            'zone_block_id' => $row['zone_block_id'],
            'variety' => $row['variety'] ?: null,
            'planting_date' => $row['planting_date'] ?: null,
            'planting_cycle_type_id' => $row['planting_cycle_type_id'] ?: null,
            'expected_harvest' => $row['expected_harvest'] ?: null,
            'status' => $row['status'],
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        unset($this->rows[$index]);
        $this->rows = array_values($this->rows);

        session()->flash('success', 'Block register saved successfully.');
    }

    public function edit($id)
    {
        if (!auth()->user()->hasPermission('block_registers.edit')) {
            abort(403, 'Permission denied.');
        }

        $register = BlockRegister::findOrFail($id);

        $this->editingId = $register->id;

        $this->editRow = [
            'zone_block_id' => $register->zone_block_id,
            'variety' => $register->variety,
            'planting_date' => optional($register->planting_date)->format('Y-m-d') ?: $register->planting_date,
            'planting_cycle_type_id' => $register->planting_cycle_type_id,
            'expected_harvest' => optional($register->expected_harvest)->format('Y-m-d') ?: $register->expected_harvest,
            'status' => $register->status,
        ];
    }

    public function cancelEdit()
    {
        $this->editingId = null;

        $this->editRow = [
            'zone_block_id' => '',
            'variety' => '',
            'planting_date' => '',
            'planting_cycle_type_id' => '',
            'expected_harvest' => '',
            'status' => 'active',
        ];
    }

    public function updateRow()
    {
        if (!auth()->user()->hasPermission('block_registers.edit')) {
            abort(403, 'Permission denied.');
        }

        $register = BlockRegister::findOrFail($this->editingId);

        $this->validate([
            'editRow.zone_block_id' => 'required|exists:zone_blocks,id',
            'editRow.variety' => 'nullable|string|max:150',
            'editRow.planting_date' => 'nullable|date',
            'editRow.planting_cycle_type_id' => 'nullable|exists:planting_cycle_types,id',
            'editRow.expected_harvest' => 'nullable|date',
            'editRow.status' => 'required|in:active,inactive',
        ]);

        $register->update([
            'zone_block_id' => $this->editRow['zone_block_id'],
            'variety' => $this->editRow['variety'] ?: null,
            'planting_date' => $this->editRow['planting_date'] ?: null,
            'planting_cycle_type_id' => $this->editRow['planting_cycle_type_id'] ?: null,
            'expected_harvest' => $this->editRow['expected_harvest'] ?: null,
            'status' => $this->editRow['status'],
            'updated_by' => Auth::id(),
        ]);

        $this->cancelEdit();

        session()->flash('success', 'Block register updated successfully.');
    }

    public function delete($id)
    {
        if (!auth()->user()->hasPermission('block_registers.delete')) {
            abort(403, 'Permission denied.');
        }

        BlockRegister::findOrFail($id)->delete();

        session()->flash('success', 'Block register deleted successfully.');
    }

    public function getRegistersProperty()
    {
        return BlockRegister::with(['zoneBlock.zone', 'plantingCycleType'])
            ->when($this->search, function ($q) {
                $q->where(function ($query) {
                    $query->where('variety', 'like', '%' . $this->search . '%')
                        ->orWhere('status', 'like', '%' . $this->search . '%')
                        ->orWhereHas('zoneBlock', function ($blockQuery) {
                            $blockQuery->where('block_code', 'like', '%' . $this->search . '%')
                                ->orWhere('name', 'like', '%' . $this->search . '%')
                                ->orWhereHas('zone', function ($zoneQuery) {
                                    $zoneQuery->where('zone_code', 'like', '%' . $this->search . '%')
                                        ->orWhere('name', 'like', '%' . $this->search . '%');
                                });
                        })
                        ->orWhereHas('plantingCycleType', function ($cycleQuery) {
                            $cycleQuery->where('code', 'like', '%' . $this->search . '%')
                                ->orWhere('name', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->latest('planting_date')
            ->latest('id')
            ->get();
    }

    public function getTotalAreaProperty()
    {
        return $this->registers->sum(function ($register) {
            return (float) optional($register->zoneBlock)->area;
        });
    }

    public function getTotalRegistersProperty()
    {
        return $this->registers->count();
    }

    public function with()
    {
        return [
            'zoneBlocks' => ZoneBlock::with('zone')
                ->where('status', 'active')
                ->orderBy('block_code')
                ->get(),

            'cycleTypes' => PlantingCycleType::where('status', 'active')
                ->orderBy('code')
                ->get(),
        ];
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
            min-width: 1280px;
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

        .block-select {
            min-width: 230px !important;
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
            <h1 class="page-title">Block Register</h1>
            <p class="page-subtitle">Track variety, planting date, cycle type, and harvest date by block.</p>
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
                       placeholder="Filter block, zone, variety, cycle type, status">
            </div>
        </div>

        <div class="master-table-wrap">
            <table class="master-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Block *</th>
                        <th>Zone</th>
                        <th>Area (Ha)</th>
                        <th>Variety</th>
                        <th>Planting Date</th>
                        <th>Cycle Type</th>
                        <th>Expected Harvest</th>
                        <th>Status</th>
                        <th width="190">Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($this->registers as $register)
                        @if($editingId === $register->id)
                            <tr class="new-row">
                                <td class="row-no">{{ $loop->iteration }}</td>

                                <td>
                                    <select class="block-select"
                                            wire:model.live="editRow.zone_block_id">
                                        <option value="">Select Block</option>
                                        @foreach($zoneBlocks as $block)
                                            <option value="{{ $block->id }}">
                                                {{ $block->block_code }}
                                                {{ $block->name ? '- ' . $block->name : '' }}
                                                {{ $block->zone ? '(' . $block->zone->zone_code . ')' : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('editRow.zone_block_id')
                                        <small class="error">{{ $message }}</small>
                                    @enderror
                                </td>

                                <td>
                                    @php
                                        $selectedEditBlock = $zoneBlocks->firstWhere('id', (int) $editRow['zone_block_id']);
                                    @endphp

                                    {{ optional(optional($selectedEditBlock)->zone)->zone_code ?? '-' }}
                                </td>

                                <td>
                                    {{ number_format((float) optional($selectedEditBlock)->area, 2) }}
                                </td>

                                <td>
                                    <input type="text"
                                           wire:model.live="editRow.variety"
                                           placeholder="KK3">
                                    @error('editRow.variety')
                                        <small class="error">{{ $message }}</small>
                                    @enderror
                                </td>

                                <td>
                                    <input type="date"
                                           wire:model.live="editRow.planting_date">
                                    @error('editRow.planting_date')
                                        <small class="error">{{ $message }}</small>
                                    @enderror
                                </td>

                                <td>
                                    <select wire:model.live="editRow.planting_cycle_type_id">
                                        <option value="">Select Cycle</option>
                                        @foreach($cycleTypes as $cycleType)
                                            <option value="{{ $cycleType->id }}">
                                                {{ $cycleType->code }} - {{ $cycleType->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('editRow.planting_cycle_type_id')
                                        <small class="error">{{ $message }}</small>
                                    @enderror
                                </td>

                                <td>
                                    <input type="date"
                                           wire:model.live="editRow.expected_harvest">
                                    @error('editRow.expected_harvest')
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

                                <td>{{ $register->zoneBlock->block_code ?? '-' }}</td>

                                <td>{{ $register->zoneBlock->zone->zone_code ?? '-' }}</td>

                                <td>{{ number_format((float) optional($register->zoneBlock)->area, 2) }}</td>

                                <td>{{ $register->variety ?? '-' }}</td>

                                <td>
                                    {{ optional($register->planting_date)->format('d M Y') ?: ($register->planting_date ?? '-') }}
                                </td>

                                <td>
                                    @if($register->plantingCycleType)
                                        {{ $register->plantingCycleType->code }} - {{ $register->plantingCycleType->name }}
                                    @else
                                        -
                                    @endif
                                </td>

                                <td>
                                    {{ optional($register->expected_harvest)->format('d M Y') ?: ($register->expected_harvest ?? '-') }}
                                </td>

                                <td>
                                    <span class="status {{ $register->status }}">
                                        {{ ucfirst($register->status) }}
                                    </span>
                                </td>

                                <td>
                                    <div class="table-actions">
                                        @if(auth()->user()->hasPermission('block_registers.edit'))
                                            <button type="button"
                                                    wire:click="edit({{ $register->id }})"
                                                    class="mini">
                                                Edit
                                            </button>
                                        @endif

                                        @if(auth()->user()->hasPermission('block_registers.delete'))
                                            <button type="button"
                                                    wire:click="delete({{ $register->id }})"
                                                    class="mini danger"
                                                    onclick="return confirm('Delete this block register?')">
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
                                <td colspan="10" class="empty">
                                    No block register found.
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
                                <select class="block-select"
                                        wire:model.live="rows.{{ $index }}.zone_block_id">
                                    <option value="">Select Block</option>
                                    @foreach($zoneBlocks as $block)
                                        <option value="{{ $block->id }}">
                                            {{ $block->block_code }}
                                            {{ $block->name ? '- ' . $block->name : '' }}
                                            {{ $block->zone ? '(' . $block->zone->zone_code . ')' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                @error("rows.$index.zone_block_id")
                                    <small class="error">{{ $message }}</small>
                                @enderror
                            </td>

                            <td>
                                @php
                                    $selectedBlock = $zoneBlocks->firstWhere('id', (int) $row['zone_block_id']);
                                @endphp

                                {{ optional(optional($selectedBlock)->zone)->zone_code ?? '-' }}
                            </td>

                            <td>
                                {{ number_format((float) optional($selectedBlock)->area, 2) }}
                            </td>

                            <td>
                                <input type="text"
                                       wire:model.live="rows.{{ $index }}.variety"
                                       placeholder="KK3">
                                @error("rows.$index.variety")
                                    <small class="error">{{ $message }}</small>
                                @enderror
                            </td>

                            <td>
                                <input type="date"
                                       wire:model.live="rows.{{ $index }}.planting_date">
                                @error("rows.$index.planting_date")
                                    <small class="error">{{ $message }}</small>
                                @enderror
                            </td>

                            <td>
                                <select wire:model.live="rows.{{ $index }}.planting_cycle_type_id">
                                    <option value="">Select Cycle</option>
                                    @foreach($cycleTypes as $cycleType)
                                        <option value="{{ $cycleType->id }}">
                                            {{ $cycleType->code }} - {{ $cycleType->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error("rows.$index.planting_cycle_type_id")
                                    <small class="error">{{ $message }}</small>
                                @enderror
                            </td>

                            <td>
                                <input type="date"
                                       wire:model.live="rows.{{ $index }}.expected_harvest">
                                @error("rows.$index.expected_harvest")
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
                            @if(auth()->user()->hasPermission('block_registers.create'))
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

                        <td colspan="2" class="total-label">
                            Total Registers: {{ number_format((int) $this->totalRegisters) }}
                        </td>

                        <td>{{ number_format((float) $this->totalArea, 2) }}</td>

                        <td>-</td>
                        <td>-</td>
                        <td>-</td>
                        <td>-</td>
                        <td>-</td>
                        <td>-</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>