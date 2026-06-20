<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\BlockRegister;
use App\Models\ZoneBlock;
use App\Models\PlantingCycleType;
use Illuminate\Support\Facades\Auth;

new class extends Component
{
    use WithPagination;

    public $paginationTheme = 'tailwind';

    public $search = '';
    public $perPage = 15;

    public $editingId = null;

    public $editRow = [
        'planting_date' => '',
        'planting_cycle_type_id' => '',
        'expected_harvest_date' => '',
        'status' => 'active',
    ];

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    public function edit($zoneBlockId)
    {
        if (!auth()->user()->hasPermission('block_registers.edit')) {
            abort(403, 'Permission denied.');
        }

        $zoneBlock = ZoneBlock::with([
            'zone',
            'blockRegister.plantingCycleType',
        ])->findOrFail($zoneBlockId);

        $register = $zoneBlock->blockRegister;

        $this->editingId = $zoneBlock->id;

        $this->editRow = [
            'planting_date' => $register?->planting_date
                ? $register->planting_date->format('Y-m-d')
                : '',

            'planting_cycle_type_id' =>
                $register?->planting_cycle_type_id ?? '',

            'expected_harvest_date' =>
                $register?->expected_harvest_date
                    ? $register->expected_harvest_date->format('Y-m-d')
                    : '',

            'status' => $register?->status ?? 'active',
        ];
    }

    public function cancelEdit()
    {
        $this->editingId = null;

        $this->editRow = [
            'planting_date' => '',
            'planting_cycle_type_id' => '',
            'expected_harvest_date' => '',
            'status' => 'active',
        ];
    }

    public function updateRow()
    {
        if (!auth()->user()->hasPermission('block_registers.edit')) {
            abort(403, 'Permission denied.');
        }

        $zoneBlock = ZoneBlock::findOrFail($this->editingId);

        $this->validate([
            'editRow.planting_date' =>
                'nullable|date',

            'editRow.planting_cycle_type_id' =>
                'nullable|exists:planting_cycle_types,id',

            'editRow.expected_harvest_date' =>
                'nullable|date|after_or_equal:editRow.planting_date',

            'editRow.status' =>
                'required|in:active,inactive',
        ]);

        $register = BlockRegister::firstOrNew([
            'zone_block_id' => $zoneBlock->id,
        ]);

        if (!$register->exists) {
            $register->created_by = Auth::id();
        }

        $register->planting_date =
            filled($this->editRow['planting_date'])
                ? $this->editRow['planting_date']
                : null;

        $register->planting_cycle_type_id =
            filled($this->editRow['planting_cycle_type_id'])
                ? $this->editRow['planting_cycle_type_id']
                : null;

        $register->expected_harvest_date =
            filled($this->editRow['expected_harvest_date'])
                ? $this->editRow['expected_harvest_date']
                : null;

        $register->status = $this->editRow['status'];
        $register->updated_by = Auth::id();
        $register->save();

        $this->cancelEdit();

        session()->flash(
            'success',
            'Block register updated successfully.'
        );
    }

    public function resetFilter()
    {
        $this->search = '';
        $this->perPage = 15;

        $this->resetPage();
    }

    private function registersQuery()
    {
        return ZoneBlock::query()
            ->with([
                'zone',
                'blockRegister.plantingCycleType',
            ])
            ->when($this->search, function ($query) {
                $search = trim($this->search);

                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where(
                            'block_code',
                            'like',
                            '%' . $search . '%'
                        )
                        ->orWhere(
                            'name',
                            'like',
                            '%' . $search . '%'
                        )
                        ->orWhereHas(
                            'zone',
                            function ($zoneQuery) use ($search) {
                                $zoneQuery
                                    ->where(
                                        'zone_code',
                                        'like',
                                        '%' . $search . '%'
                                    )
                                    ->orWhere(
                                        'name',
                                        'like',
                                        '%' . $search . '%'
                                    );
                            }
                        )
                        ->orWhereHas(
                            'blockRegister',
                            function ($registerQuery) use ($search) {
                                $registerQuery
                                    ->where(
                                        'status',
                                        'like',
                                        '%' . $search . '%'
                                    )
                                    ->orWhereHas(
                                        'plantingCycleType',
                                        function ($cycleQuery) use ($search) {
                                            $cycleQuery
                                                ->where(
                                                    'code',
                                                    'like',
                                                    '%' . $search . '%'
                                                )
                                                ->orWhere(
                                                    'name',
                                                    'like',
                                                    '%' . $search . '%'
                                                );
                                        }
                                    );
                            }
                        );
                });
            });
    }

    public function getRegistersProperty()
    {
        return $this->registersQuery()
            ->orderBy('zone_id')
            ->orderBy('block_code')
            ->paginate((int) $this->perPage);
    }

    public function getTotalAreaProperty()
    {
        return (clone $this->registersQuery())
            ->sum('area');
    }

    public function getTotalRegistersProperty()
    {
        return (clone $this->registersQuery())
            ->count();
    }

    public function with()
    {
        return [
            'cycleTypes' => PlantingCycleType::query()
                ->where('status', 'active')
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
            max-width: 520px;
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

        .rows-control {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .rows-control label {
            font-size: 13px;
            font-weight: 900;
            color: #334155;
            margin: 0;
        }

        .rows-control select {
            width: 130px;
            height: 42px;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            padding: 8px 10px;
            font-weight: 800;
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

        .pagination-wrap {
            padding: 14px;
            border-top: 1px solid #e5e7eb;
            background: #ffffff;
        }

        .pagination-wrap nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .pagination-wrap a,
        .pagination-wrap span {
            font-weight: 800;
        }

        @media (max-width: 900px) {
            .master-toolbar {
                align-items: stretch;
            }

            .filter-box {
                max-width: none;
                width: 100%;
            }

            .rows-control {
                width: 100%;
                justify-content: space-between;
            }
        }
    </style>

    <div class="page-header">
        <div>
            <h1 class="page-title">{{ __('pages.block_register') }}</h1>
            <p class="page-subtitle">Track variety, planting date, cycle type, and harvest date by block.</p>
        </div>

        <div class="page-actions">
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

            <div class="rows-control">
                <label>Rows Per Page</label>
                <select wire:model.live="perPage">
                    <option value="10">10 rows</option>
                    <option value="15">15 rows</option>
                    <option value="25">25 rows</option>
                    <option value="50">50 rows</option>
                    <option value="100">100 rows</option>
                </select>

                <button type="button"
                        wire:click="resetFilter"
                        class="btn gray">
                    Reset
                </button>
            </div>
        </div>

        <div class="master-table-wrap">
            <table class="master-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Block</th>
                        <th>Zone</th>
                        <th>Area (Ha)</th>
                        <th>Planting Date</th>
                        <th>Cycle Type</th>
                        <th>Expected Harvest</th>
                        <th>Status</th>
                        <th width="190">Action</th>
                    </tr>
                </thead>

                    <tbody>
                        @forelse($this->registers as $zoneBlock)
                            @php
                                $register = $zoneBlock->blockRegister;
                            @endphp

                            @if($editingId === $zoneBlock->id)
                                <tr
                                    class="new-row"
                                    wire:key="edit-zone-block-{{ $zoneBlock->id }}"
                                >
                                    <td class="row-no">
                                        {{
                                            ($this->registers->firstItem() ?? 1)
                                            + $loop->index
                                        }}
                                    </td>

                                    <td>
                                        {{ $zoneBlock->block_code }}
                                    </td>

                                    <td>
                                        {{ $zoneBlock->zone?->zone_code ?? '-' }}
                                    </td>

                                    <td>
                                        {{
                                            number_format(
                                                (float) $zoneBlock->area,
                                                2
                                            )
                                        }}
                                    </td>

                                    <td>
                                        <input
                                            type="date"
                                            wire:model.live="editRow.planting_date"
                                        >

                                        @error('editRow.planting_date')
                                            <small class="error">
                                                {{ $message }}
                                            </small>
                                        @enderror
                                    </td>

                                    <td>
                                        <select
                                            wire:model.live="editRow.planting_cycle_type_id"
                                        >
                                            <option value="">
                                                Select Cycle
                                            </option>

                                            @foreach($cycleTypes as $cycleType)
                                                <option value="{{ $cycleType->id }}">
                                                    {{ $cycleType->code }}
                                                    -
                                                    {{ $cycleType->name }}
                                                </option>
                                            @endforeach
                                        </select>

                                        @error('editRow.planting_cycle_type_id')
                                            <small class="error">
                                                {{ $message }}
                                            </small>
                                        @enderror
                                    </td>

                                    <td>
                                        <input
                                            type="date"
                                            wire:model.live="editRow.expected_harvest_date"
                                        >

                                        @error('editRow.expected_harvest_date')
                                            <small class="error">
                                                {{ $message }}
                                            </small>
                                        @enderror
                                    </td>

                                    <td>
                                        <select wire:model.live="editRow.status">
                                            <option value="active">
                                                Active
                                            </option>

                                            <option value="inactive">
                                                Inactive
                                            </option>
                                        </select>

                                        @error('editRow.status')
                                            <small class="error">
                                                {{ $message }}
                                            </small>
                                        @enderror
                                    </td>

                                    <td>
                                        <div class="table-actions">
                                            <button
                                                type="button"
                                                wire:click="updateRow"
                                                class="mini"
                                            >
                                                Save
                                            </button>

                                            <button
                                                type="button"
                                                wire:click="cancelEdit"
                                                class="mini danger"
                                            >
                                                Cancel
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @else
                                <tr wire:key="zone-block-register-{{ $zoneBlock->id }}">
                                    <td class="row-no">
                                        {{
                                            ($this->registers->firstItem() ?? 1)
                                            + $loop->index
                                        }}
                                    </td>

                                    <td>
                                        {{ $zoneBlock->block_code }}
                                    </td>

                                    <td>
                                        {{ $zoneBlock->zone?->zone_code ?? '-' }}
                                    </td>

                                    <td>
                                        {{
                                            number_format(
                                                (float) $zoneBlock->area,
                                                2
                                            )
                                        }}
                                    </td>

                                    <td>
                                        {{
                                            $register?->planting_date
                                                ? $register->planting_date->format('d M Y')
                                                : '-'
                                        }}
                                    </td>

                                    <td>
                                        @if($register?->plantingCycleType)
                                            {{ $register->plantingCycleType->code }}
                                            -
                                            {{ $register->plantingCycleType->name }}
                                        @else
                                            -
                                        @endif
                                    </td>

                                    <td>
                                        {{
                                            $register?->expected_harvest_date
                                                ? $register->expected_harvest_date
                                                    ->format('d M Y')
                                                : '-'
                                        }}
                                    </td>

                                    <td>
                                        @if($register)
                                            <span
                                                class="status {{ $register->status }}"
                                            >
                                                {{ ucfirst($register->status) }}
                                            </span>
                                        @else
                                            <span class="status inactive">
                                                Not Set
                                            </span>
                                        @endif
                                    </td>

                                    <td>
                                        <div class="table-actions">
                                            @if(
                                                auth()->user()
                                                    ->hasPermission('block_registers.edit')
                                            )
                                                <button
                                                    type="button"
                                                    wire:click="edit({{ $zoneBlock->id }})"
                                                    class="mini"
                                                >
                                                    Edit
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="9" class="empty">
                                    No zone block found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                    <tfoot>
                        <tr class="total-row">
                            <td>-</td>

                            <td colspan="2" class="total-label">
                                Total Registers:
                                {{ number_format((int) $this->totalRegisters) }}
                            </td>

                            <td>
                                {{
                                    number_format(
                                        (float) $this->totalArea,
                                        2
                                    )
                                }}
                            </td>

                            <td>-</td>
                            <td>-</td>
                            <td>-</td>
                            <td>-</td>
                            <td>-</td>
                        </tr>
                    </tfoot>
                </table>

            <div class="pagination-wrap">
                {{ $this->registers->links() }}
            </div>
        </div>
    </div>
</div>