<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\TaskCategory;
use App\Models\TaskCategoryGroup;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

new class extends Component
{
    use WithPagination;

    public $search = '';
    public $perPage = 10;
    public $rows = [];

    public $editingId = null;

    public $editRow = [
        'group_type' => 'planning',
        'task_category_group_id' => '',
        'name' => '',
        'standard_fuel_per_hectare' => '',
        'standard_hectare_per_hour' => '',
        'description' => '',
        'status' => 'active',
    ];

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function addRow()
    {
        $this->rows[] = $this->emptyRow();
    }

    public function emptyRow()
    {
        return [
            'group_type' => 'planning',
            'task_category_group_id' => '',
            'name' => '',
            'standard_fuel_per_hectare' => '',
            'standard_hectare_per_hour' => '',
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

    public function updatedRows($value, $key)
    {
        $this->resetValidation('rows.' . $key);

        $parts = explode('.', $key);
        $rowIndex = isset($parts[0]) ? (int) $parts[0] : null;
        $field = $parts[1] ?? null;

        if (
            $field === 'group_type' &&
            $rowIndex !== null &&
            isset($this->rows[$rowIndex])
        ) {
            $this->rows[$rowIndex]['task_category_group_id'] = '';
        }
    }

    public function updatedEditRow($value, $key)
    {
        $this->resetValidation('editRow.' . $key);

        if ($key === 'group_type') {
            $this->editRow['task_category_group_id'] = '';
        }
    }

    public function saveRow($index)
    {
        if (!auth()->user()->hasPermission('task_categories.create')) {
            abort(403, 'Permission denied.');
        }

        if (!isset($this->rows[$index])) {
            return;
        }

        $this->validate([
            "rows.$index.group_type" => 'required|in:planning,harvesting',
            "rows.$index.task_category_group_id" => [
                'required',
                Rule::exists('task_category_groups', 'id')->where(
                    fn ($query) => $query->where(
                        'group_type',
                        $this->rows[$index]['group_type'] ?? 'planning'
                    )
                ),
            ],
            "rows.$index.name" => 'required|string|max:150',
            "rows.$index.standard_fuel_per_hectare" => 'nullable|numeric|min:0',
            "rows.$index.standard_hectare_per_hour" => 'nullable|numeric|min:0',
            "rows.$index.description" => 'nullable|string|max:1000',
            "rows.$index.status" => 'required|in:active,inactive',
        ]);

        $row = $this->rows[$index];

        TaskCategory::create([
            'task_category_group_id' => $row['task_category_group_id'],
            'name' => $row['name'],
            'standard_fuel_per_hectare' => $row['standard_fuel_per_hectare'] ?: 0,
            'standard_hectare_per_hour' => $row['standard_hectare_per_hour'] ?: 0,
            'description' => $row['description'] ?: null,
            'status' => $row['status'],
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        unset($this->rows[$index]);
        $this->rows = array_values($this->rows);

        session()->flash('success', 'Task category saved successfully.');
    }

    public function edit($id)
    {
        if (!auth()->user()->hasPermission('task_categories.edit')) {
            abort(403, 'Permission denied.');
        }

        $taskCategory = TaskCategory::with('group')->findOrFail($id);

        $this->editingId = $taskCategory->id;

        $this->editRow = [
            'group_type' => $taskCategory->group?->group_type ?? 'planning',
            'task_category_group_id' => $taskCategory->task_category_group_id,
            'name' => $taskCategory->name,
            'standard_fuel_per_hectare' => $taskCategory->standard_fuel_per_hectare,
            'standard_hectare_per_hour' => $taskCategory->standard_hectare_per_hour,
            'description' => $taskCategory->description,
            'status' => $taskCategory->status,
        ];
    }

    public function cancelEdit()
    {
        $this->editingId = null;

        $this->editRow = [
            'group_type' => 'planning',
            'task_category_group_id' => '',
            'name' => '',
            'standard_fuel_per_hectare' => '',
            'standard_hectare_per_hour' => '',
            'description' => '',
            'status' => 'active',
        ];
    }

    public function updateRow()
    {
        if (!auth()->user()->hasPermission('task_categories.edit')) {
            abort(403, 'Permission denied.');
        }

        $taskCategory = TaskCategory::findOrFail($this->editingId);

        $this->validate([
            'editRow.group_type' => 'required|in:planning,harvesting',
            'editRow.task_category_group_id' => [
                'required',
                Rule::exists('task_category_groups', 'id')->where(
                    fn ($query) => $query->where(
                        'group_type',
                        $this->editRow['group_type'] ?? 'planning'
                    )
                ),
            ],
            'editRow.name' => 'required|string|max:150',
            'editRow.standard_fuel_per_hectare' => 'nullable|numeric|min:0',
            'editRow.standard_hectare_per_hour' => 'nullable|numeric|min:0',
            'editRow.description' => 'nullable|string|max:1000',
            'editRow.status' => 'required|in:active,inactive',
        ]);

        $taskCategory->update([
            'task_category_group_id' => $this->editRow['task_category_group_id'],
            'name' => $this->editRow['name'],
            'standard_fuel_per_hectare' => $this->editRow['standard_fuel_per_hectare'] ?: 0,
            'standard_hectare_per_hour' => $this->editRow['standard_hectare_per_hour'] ?: 0,
            'description' => $this->editRow['description'] ?: null,
            'status' => $this->editRow['status'],
            'updated_by' => Auth::id(),
        ]);

        $this->cancelEdit();

        session()->flash('success', 'Task category updated successfully.');
    }

    public function delete($id)
    {
        if (!auth()->user()->hasPermission('task_categories.delete')) {
            abort(403, 'Permission denied.');
        }

        TaskCategory::findOrFail($id)->delete();

        session()->flash('success', 'Task category deleted successfully.');
    }

    public function getTaskCategoryGroupsProperty()
    {
        return TaskCategoryGroup::query()
            ->orderByRaw("FIELD(group_type, 'planning', 'harvesting')")
            ->orderBy('name')
            ->get();
    }

    public function groupsForType($type)
    {
        return $this->taskCategoryGroups
            ->where('group_type', $type)
            ->values();
    }

    public function typeLabel($type): string
    {
        return $type === 'harvesting' ? 'Harvesting' : 'Planting';
    }

    public function taskCategoriesQuery()
    {
        return TaskCategory::query()
            ->with('group')
            ->when($this->search, function ($q) {
                $q->where(function ($query) {
                    $query->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('description', 'like', '%' . $this->search . '%')
                        ->orWhere('status', 'like', '%' . $this->search . '%')
                        ->orWhereHas('group', function ($groupQuery) {
                            $groupQuery->where(
                                'name',
                                'like',
                                '%' . $this->search . '%'
                            )
                                ->orWhere(
                                    'group_type',
                                    'like',
                                    '%' . $this->search . '%'
                                );
                        });
                });
            });
    }

    public function getTaskCategoriesProperty()
    {
        return $this->taskCategoriesQuery()
            ->orderBy('name')
            ->paginate($this->perPage);
    }

    public function getTotalFuelPerHaProperty()
    {
        return $this->taskCategoriesQuery()
            ->sum('standard_fuel_per_hectare');
    }

    public function getTotalHaPerHrProperty()
    {
        return $this->taskCategoriesQuery()
            ->sum('standard_hectare_per_hour');
    }

    public function getTotalTaskCategoriesProperty()
    {
        return $this->taskCategoriesQuery()
            ->count();
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
            min-width: 1450px;
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

        .group-select {
            min-width: 190px !important;
        }

        .wide-input {
            min-width: 260px !important;
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
            <h1 class="page-title">{{ __('pages.task_categories') }}</h1>
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
                       placeholder="Filter group, name, description, status">
            </div>
        </div>

        <div class="master-table-wrap">
            <table class="master-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Type *</th>
                        <th>Group *</th>
                        <th>Name *</th>
                        <th>Fuel / Ha/T</th>
                        <th>Ha/T / Hr</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th width="190">Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($this->taskCategories as $taskCategory)
                        @if($editingId === $taskCategory->id)
                            <tr class="new-row">
                                <td class="row-no">{{ $this->taskCategories->firstItem() + $loop->index }}</td>

                                <td>
                                    <select wire:model.live="editRow.group_type">
                                        <option value="planning">Planting</option>
                                        <option value="harvesting">Harvesting</option>
                                    </select>

                                    @error('editRow.group_type')
                                        <small class="error">{{ $message }}</small>
                                    @enderror
                                </td>

                                <td>
                                    <select
                                        class="group-select"
                                        wire:model.live="editRow.task_category_group_id"
                                    >
                                        <option value="">Select Group</option>

                                        @foreach($this->groupsForType($editRow['group_type'] ?? 'planning') as $group)
                                            <option value="{{ $group->id }}">
                                                {{ $group->name }}
                                            </option>
                                        @endforeach
                                    </select>

                                    @error('editRow.task_category_group_id')
                                        <small class="error">{{ $message }}</small>
                                    @enderror
                                </td>

                                <td>
                                    <input type="text" wire:model.live="editRow.name">

                                    @error('editRow.name')
                                        <small class="error">{{ $message }}</small>
                                    @enderror
                                </td>

                                <td>
                                    <input type="number"
                                           step="0.01"
                                           wire:model.live="editRow.standard_fuel_per_hectare">

                                    @error('editRow.standard_fuel_per_hectare')
                                        <small class="error">{{ $message }}</small>
                                    @enderror
                                </td>

                                <td>
                                    <input type="number"
                                           step="0.01"
                                           wire:model.live="editRow.standard_hectare_per_hour">

                                    @error('editRow.standard_hectare_per_hour')
                                        <small class="error">{{ $message }}</small>
                                    @enderror
                                </td>

                                <td>
                                    <input type="text"
                                           class="wide-input"
                                           wire:model.live="editRow.description">

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
                                <td class="row-no">{{ $this->taskCategories->firstItem() + $loop->index }}</td>

                                <td>
                                    {{ $this->typeLabel($taskCategory->group?->group_type ?? 'planning') }}
                                </td>

                                <td>
                                    {{ $taskCategory->group?->name ?? '-' }}
                                </td>

                                <td>{{ $taskCategory->name }}</td>

                                <td>
                                    {{ number_format((float) $taskCategory->standard_fuel_per_hectare, 2) }}
                                </td>

                                <td>
                                    {{ number_format((float) $taskCategory->standard_hectare_per_hour, 2) }}
                                </td>

                                <td>{{ $taskCategory->description ?? '-' }}</td>

                                <td>
                                    <span class="status {{ $taskCategory->status }}">
                                        {{ ucfirst($taskCategory->status) }}
                                    </span>
                                </td>

                                <td>
                                    <div class="table-actions">
                                        @if(auth()->user()->hasPermission('task_categories.edit'))
                                            <button type="button"
                                                    wire:click="edit({{ $taskCategory->id }})"
                                                    class="mini">
                                                Edit
                                            </button>
                                        @endif

                                        @if(auth()->user()->hasPermission('task_categories.delete'))
                                            <button type="button"
                                                    wire:click="delete({{ $taskCategory->id }})"
                                                    class="mini danger"
                                                    onclick="return confirm('Delete this task category?')">
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
                                <td colspan="9" class="empty">
                                    No task category found.
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
                                <select wire:model.live="rows.{{ $index }}.group_type">
                                    <option value="planning">Planting</option>
                                    <option value="harvesting">Harvesting</option>
                                </select>

                                @error("rows.$index.group_type")
                                    <small class="error">{{ $message }}</small>
                                @enderror
                            </td>

                            <td>
                                <select
                                    class="group-select"
                                    wire:model.live="rows.{{ $index }}.task_category_group_id"
                                >
                                    <option value="">Select Group</option>

                                    @foreach($this->groupsForType($row['group_type'] ?? 'planning') as $group)
                                        <option value="{{ $group->id }}">
                                            {{ $group->name }}
                                        </option>
                                    @endforeach
                                </select>

                                @error("rows.$index.task_category_group_id")
                                    <small class="error">{{ $message }}</small>
                                @enderror
                            </td>

                            <td>
                                <input type="text"
                                       wire:model.live="rows.{{ $index }}.name"
                                       placeholder="Fertilizer">

                                @error("rows.$index.name")
                                    <small class="error">{{ $message }}</small>
                                @enderror
                            </td>

                            <td>
                                <input type="number"
                                       step="0.01"
                                       wire:model.live="rows.{{ $index }}.standard_fuel_per_hectare"
                                       placeholder="0">

                                @error("rows.$index.standard_fuel_per_hectare")
                                    <small class="error">{{ $message }}</small>
                                @enderror
                            </td>

                            <td>
                                <input type="number"
                                       step="0.01"
                                       wire:model.live="rows.{{ $index }}.standard_hectare_per_hour"
                                       placeholder="0">

                                @error("rows.$index.standard_hectare_per_hour")
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
                            @if(auth()->user()->hasPermission('task_categories.create'))
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

                        <td>-</td>

                        <td>-</td>

                        <td class="total-label">
                            Total: {{ number_format((int) $this->totalTaskCategories) }}
                        </td>

                        <td>
                            {{ number_format((float) $this->totalFuelPerHa, 2) }}
                        </td>

                        <td>
                            {{ number_format((float) $this->totalHaPerHr, 2) }}
                        </td>

                        <td>-</td>
                        <td>-</td>
                        <td>-</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div style="margin-top:14px;">
            {{ $this->taskCategories->links() }}
        </div>
    </div>
</div>