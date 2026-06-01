<?php

use Livewire\Component;
use App\Models\ZoneBlock;
use App\Models\PlantingCycleType;
use App\Models\BlockRegister;
use Illuminate\Support\Facades\Auth;

new class extends Component
{
    public $rows = [];

    public function mount()
    {
        $this->addRow();
    }

    public function addRow()
    {
        $this->rows[] = [
            'zone_block_id' => '',
            'variety' => '',
            'planting_date' => '',
            'planting_cycle_type_id' => '',
            'expected_harvest_date' => '',
            'status' => 'active',
            'note' => '',
        ];
    }

    public function removeRow($index)
    {
        unset($this->rows[$index]);
        $this->rows = array_values($this->rows);

        if (count($this->rows) === 0) {
            $this->addRow();
        }
    }

    public function saveAll()
    {
        foreach ($this->rows as $index => $row) {
            $this->validate([
                "rows.$index.zone_block_id" => 'required|exists:zone_blocks,id',
                "rows.$index.variety" => 'nullable|string|max:150',
                "rows.$index.planting_date" => 'nullable|date',
                "rows.$index.planting_cycle_type_id" => 'nullable|exists:planting_cycle_types,id',
                "rows.$index.expected_harvest_date" => 'nullable|date',
                "rows.$index.status" => 'required|in:active,inactive',
                "rows.$index.note" => 'nullable|string|max:1000',
            ]);
        }

        foreach ($this->rows as $row) {
            BlockRegister::create([
                'zone_block_id' => $row['zone_block_id'],
                'variety' => $row['variety'] ?: null,
                'planting_date' => $row['planting_date'] ?: null,
                'planting_cycle_type_id' => $row['planting_cycle_type_id'] ?: null,
                'expected_harvest_date' => $row['expected_harvest_date'] ?: null,
                'status' => $row['status'],
                'note' => $row['note'] ?: null,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);
        }

        session()->flash('success', 'Block registers created successfully.');

        return redirect()->route('block-registers.index');
    }

    public function with()
    {
        return [
            'blocks' => ZoneBlock::with('zone')->where('status', 'active')->orderBy('block_code')->get(),
            'cycleTypes' => PlantingCycleType::where('status', 'active')->orderBy('code')->get(),
        ];
    }
};

?>

<div class="page">
    @include('components.shared-style')
    @include('components.toast-alert')

    <style>
        .excel-table input,
        .excel-table select {
            min-width: 160px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 10px 12px;
            font-weight: 700;
            background: #ffffff;
        }

        .excel-table th,
        .excel-table td {
            white-space: nowrap;
            vertical-align: top;
        }
    </style>

    <div class="page-header">
        <div>
            <h1 class="page-title">Add Block Register</h1>
            <p class="page-subtitle">Excel-style block planting register.</p>
        </div>

        <div class="page-actions">
            <a href="{{ route('block-registers.index') }}" class="btn gray">Back</a>
        </div>
    </div>

    <div class="panel">
        <div class="actions" style="margin-bottom:14px;">
            <button type="button" wire:click="addRow" class="btn light">+ Add Row</button>
        </div>

        <div class="table-wrap">
            <table class="excel-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Block *</th>
                        <th>Variety</th>
                        <th>Planting Date</th>
                        <th>Cycle Type</th>
                        <th>Expected Harvest</th>
                        <th>Status</th>
                        <th>Note</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($rows as $index => $row)
                        <tr>
                            <td>{{ $index + 1 }}</td>

                            <td>
                                <select wire:model="rows.{{ $index }}.zone_block_id">
                                    <option value="">Select Block</option>
                                    @foreach($blocks as $block)
                                        <option value="{{ $block->id }}">
                                            {{ $block->block_code }} - {{ $block->zone->zone_code ?? '-' }}
                                        </option>
                                    @endforeach
                                </select>
                                @error("rows.$index.zone_block_id") <small>{{ $message }}</small> @enderror
                            </td>

                            <td>
                                <input type="text" wire:model="rows.{{ $index }}.variety" placeholder="KK3">
                            </td>

                            <td>
                                <input type="date" wire:model="rows.{{ $index }}.planting_date">
                            </td>

                            <td>
                                <select wire:model="rows.{{ $index }}.planting_cycle_type_id">
                                    <option value="">Select Cycle</option>
                                    @foreach($cycleTypes as $cycle)
                                        <option value="{{ $cycle->id }}">{{ $cycle->code }} - {{ $cycle->name }}</option>
                                    @endforeach
                                </select>
                            </td>

                            <td>
                                <input type="date" wire:model="rows.{{ $index }}.expected_harvest_date">
                            </td>

                            <td>
                                <select wire:model="rows.{{ $index }}.status">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </td>

                            <td>
                                <input type="text" wire:model="rows.{{ $index }}.note" placeholder="Note">
                            </td>

                            <td>
                                <button type="button" wire:click="removeRow({{ $index }})" class="mini danger">
                                    Remove
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>

                <tfoot>
                    <tr style="background:#f8fafc;font-weight:900;">
                        <td colspan="2" style="text-align:right;">Total Rows</td>
                        <td>{{ count($rows) }}</td>
                        <td colspan="6">-</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="actions" style="margin-top:16px;">
            <button wire:click="saveAll" class="btn">Save All Registers</button>
            <a href="{{ route('block-registers.index') }}" class="btn gray">Cancel</a>
        </div>
    </div>
</div>