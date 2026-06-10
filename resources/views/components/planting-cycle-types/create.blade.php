<?php

use Livewire\Component;
use App\Models\PlantingCycleType;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

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
            'code' => '',
            'name' => '',
            'description' => '',
            'status' => 'active',
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
                "rows.$index.code" => [
                    'required',
                    'string',
                    'max:20',
                    Rule::unique('planting_cycle_types', 'code'),
                ],
                "rows.$index.name" => 'required|string|max:150',
                "rows.$index.description" => 'nullable|string|max:1000',
                "rows.$index.status" => 'required|in:active,inactive',
            ]);
        }

        foreach ($this->rows as $row) {
            PlantingCycleType::create([
                'code' => strtoupper($row['code']),
                'name' => $row['name'],
                'description' => $row['description'] ?: null,
                'status' => $row['status'],
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);
        }

        session()->flash('success', 'Cycle types created successfully.');

        return redirect()->route('planting-cycle-types.index');
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
            <h1 class="page-title">{{ __('pages.add_planting_cycle_type') }}</h1>
            <p class="page-subtitle">Create cycle types such as PC, R1, R2, R3, RP.</p>
        </div>

        <div class="page-actions">
            <a href="{{ route('planting-cycle-types.index') }}" class="btn gray">Back</a>
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
                        <th>Code *</th>
                        <th>Cycle Type *</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($rows as $index => $row)
                        <tr>
                            <td>{{ $index + 1 }}</td>

                            <td>
                                <input type="text" wire:model="rows.{{ $index }}.code" placeholder="PC">
                                @error("rows.$index.code") <small>{{ $message }}</small> @enderror
                            </td>

                            <td>
                                <input type="text" wire:model="rows.{{ $index }}.name" placeholder="Plant Cane">
                                @error("rows.$index.name") <small>{{ $message }}</small> @enderror
                            </td>

                            <td>
                                <input type="text" wire:model="rows.{{ $index }}.description" placeholder="Description">
                            </td>

                            <td>
                                <select wire:model="rows.{{ $index }}.status">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
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
                        <td colspan="3">-</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="actions" style="margin-top:16px;">
            <button wire:click="saveAll" class="btn">Save All Cycle Types</button>
            <a href="{{ route('planting-cycle-types.index') }}" class="btn gray">Cancel</a>
        </div>
    </div>
</div>