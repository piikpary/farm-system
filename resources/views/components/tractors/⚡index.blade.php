<?php

use Livewire\Component;
use App\Models\Tractor;
use App\Models\TractorFieldSetting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

new class extends Component
{
    public $rows = [];
    public $editingId = null;

    public function mount()
    {
        $this->addRow();
    }

    public function optionalFieldVisible($key)
    {
        return TractorFieldSetting::where('field_key', $key)
            ->where('is_visible', true)
            ->exists();
    }

    public function addRow()
    {
        $this->rows[] = [
            'tractor_no' => '',
            'iot_device_id' => '',
            'name' => '',
            'model' => '',
            'plate_no' => '',
            'fuel_capacity' => 0,
            'current_meter' => 0,
            'plow_width' => 0,
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
                "rows.$index.tractor_no" => [
                    'required',
                    'string',
                    'max:100',
                    Rule::unique('tractors', 'tractor_no'),
                ],
                "rows.$index.iot_device_id" => [
                    'nullable',
                    'string',
                    'max:150',
                    Rule::unique('tractors', 'iot_device_id'),
                ],
                "rows.$index.name" => 'nullable|string|max:150',
                "rows.$index.model" => 'nullable|string|max:150',
                "rows.$index.plate_no" => 'nullable|string|max:100',
                "rows.$index.fuel_capacity" => 'nullable|numeric|min:0',
                "rows.$index.current_meter" => 'nullable|numeric|min:0',
                "rows.$index.plow_width" => 'nullable|numeric|min:0',
                "rows.$index.status" => 'required|in:active,inactive',
            ]);
        }

        foreach ($this->rows as $row) {
            Tractor::create([
                'tractor_no' => $row['tractor_no'],
                'iot_device_id' => $row['iot_device_id'] ?: null,
                'name' => $row['name'] ?: null,
                'model' => $row['model'] ?: null,
                'plate_no' => $row['plate_no'] ?: null,
                'fuel_capacity' => $row['fuel_capacity'] ?: 0,
                'current_meter' => $row['current_meter'] ?: 0,
                'plow_width' => $row['plow_width'] ?: 0,
                'status' => $row['status'],
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);
        }

        session()->flash('success', 'Tractors created successfully.');

        $this->rows = [];
        $this->addRow();
    }

    public function edit($id)
    {
        $tractor = Tractor::findOrFail($id);

        $this->editingId = $tractor->id;

        $this->rows = [[
            'tractor_no' => $tractor->tractor_no,
            'iot_device_id' => $tractor->iot_device_id,
            'name' => $tractor->name,
            'model' => $tractor->model,
            'plate_no' => $tractor->plate_no,
            'fuel_capacity' => $tractor->fuel_capacity,
            'current_meter' => $tractor->current_meter,
            'plow_width' => $tractor->plow_width,
            'status' => $tractor->status,
        ]];
    }

    public function updateTractor()
    {
        if (!$this->editingId) {
            return;
        }

        $this->validate([
            'rows.0.tractor_no' => [
                'required',
                'string',
                'max:100',
                Rule::unique('tractors', 'tractor_no')->ignore($this->editingId),
            ],
            'rows.0.iot_device_id' => [
                'nullable',
                'string',
                'max:150',
                Rule::unique('tractors', 'iot_device_id')->ignore($this->editingId),
            ],
            'rows.0.name' => 'nullable|string|max:150',
            'rows.0.model' => 'nullable|string|max:150',
            'rows.0.plate_no' => 'nullable|string|max:100',
            'rows.0.fuel_capacity' => 'nullable|numeric|min:0',
            'rows.0.current_meter' => 'nullable|numeric|min:0',
            'rows.0.plow_width' => 'nullable|numeric|min:0',
            'rows.0.status' => 'required|in:active,inactive',
        ]);

        $row = $this->rows[0];

        Tractor::findOrFail($this->editingId)->update([
            'tractor_no' => $row['tractor_no'],
            'iot_device_id' => $row['iot_device_id'] ?: null,
            'name' => $row['name'] ?: null,
            'model' => $row['model'] ?: null,
            'plate_no' => $row['plate_no'] ?: null,
            'fuel_capacity' => $row['fuel_capacity'] ?: 0,
            'current_meter' => $row['current_meter'] ?: 0,
            'plow_width' => $row['plow_width'] ?: 0,
            'status' => $row['status'],
            'updated_by' => Auth::id(),
        ]);

        session()->flash('success', 'Tractor updated successfully.');

        $this->cancelEdit();
    }

    public function cancelEdit()
    {
        $this->editingId = null;
        $this->rows = [];
        $this->addRow();
    }

    public function delete($id)
    {
        if (!auth()->user()->hasPermission('tractors.delete')) {
            abort(403, 'Permission denied.');
        }

        Tractor::findOrFail($id)->delete();

        session()->flash('success', 'Tractor deleted successfully.');
    }

    public function with()
    {
        $tractors = Tractor::latest()->get();

        return [
            'tractors' => $tractors,
            'totalFuelCapacity' => $tractors->sum('fuel_capacity'),
            'showIotDeviceId' => $this->optionalFieldVisible('iot_device_id'),
            'showCurrentMeter' => $this->optionalFieldVisible('current_meter'),
            'showPlowWidth' => $this->optionalFieldVisible('plow_width'),
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
            min-width: 150px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 10px 12px;
            font-weight: 700;
            background: #ffffff;
        }

        .excel-table th {
            white-space: nowrap;
        }

        .excel-table td {
            white-space: nowrap;
        }

        .excel-actions {
            display: flex;
            gap: 10px;
            margin-bottom: 14px;
            flex-wrap: wrap;
        }

        .total-row {
            background: #f8fafc;
            font-weight: 900;
            border-top: 2px solid #d1d5db;
        }

        .total-row td {
            color: #0f172a;
        }
    </style>

    <div class="page-header">
        <div>
            <h1 class="page-title">{{ __('pages.tractors') }}</h1>
            <p class="page-subtitle">{{ __('pages.tractors_subtitle') }}</p>
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

            <a href="{{ route('dashboard') }}" class="btn gray">
                {{ __('pages.dashboard_button') }}
            </a>
        </div>
    </div>

    <div class="panel">
        <h2 class="panel-title">
            {{ $editingId ? __('pages.edit_tractor') : __('pages.add_tractor') }}
        </h2>

        @if(!$editingId)
            <div class="excel-actions">
                <button type="button" wire:click="addRow" class="btn light">
                    + Add Row
                </button>
            </div>
        @endif

        <div class="table-wrap">
            <table class="excel-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('pages.tractor_no') }} *</th>

                        @if($showIotDeviceId)
                            <th>{{ __('pages.iot_device_id') }}</th>
                        @endif

                        <th>{{ __('pages.name') }}</th>
                        <th>{{ __('pages.model') }}</th>
                        <th>{{ __('pages.plate_no') }}</th>
                        <th>{{ __('pages.fuel_capacity') }}</th>

                        @if($showCurrentMeter)
                            <th>{{ __('pages.current_meter') }}</th>
                        @endif

                        @if($showPlowWidth)
                            <th>{{ __('pages.plow_width') }}</th>
                        @endif

                        <th>{{ __('pages.status') }}</th>

                        @if(!$editingId)
                            <th>{{ __('pages.action') }}</th>
                        @endif
                    </tr>
                </thead>

                <tbody>
                    @foreach($rows as $index => $row)
                        <tr>
                            <td>{{ $index + 1 }}</td>

                            <td>
                                <input type="text"
                                       wire:model="rows.{{ $index }}.tractor_no"
                                       placeholder="T-01">
                                @error("rows.$index.tractor_no") <small>{{ $message }}</small> @enderror
                            </td>

                            @if($showIotDeviceId)
                                <td>
                                    <input type="text"
                                           wire:model="rows.{{ $index }}.iot_device_id"
                                           placeholder="TRK-T01-DEVICE-001">
                                    @error("rows.$index.iot_device_id") <small>{{ $message }}</small> @enderror
                                </td>
                            @endif

                            <td>
                                <input type="text"
                                       wire:model="rows.{{ $index }}.name"
                                       placeholder="{{ __('pages.tractor_name') }}">
                                @error("rows.$index.name") <small>{{ $message }}</small> @enderror
                            </td>

                            <td>
                                <input type="text"
                                       wire:model="rows.{{ $index }}.model"
                                       placeholder="{{ __('pages.model') }}">
                                @error("rows.$index.model") <small>{{ $message }}</small> @enderror
                            </td>

                            <td>
                                <input type="text"
                                       wire:model="rows.{{ $index }}.plate_no"
                                       placeholder="{{ __('pages.plate_number') }}">
                                @error("rows.$index.plate_no") <small>{{ $message }}</small> @enderror
                            </td>

                            <td>
                                <input type="number"
                                       step="0.01"
                                       wire:model="rows.{{ $index }}.fuel_capacity">
                                @error("rows.$index.fuel_capacity") <small>{{ $message }}</small> @enderror
                            </td>

                            @if($showCurrentMeter)
                                <td>
                                    <input type="number"
                                           step="0.01"
                                           wire:model="rows.{{ $index }}.current_meter">
                                    @error("rows.$index.current_meter") <small>{{ $message }}</small> @enderror
                                </td>
                            @endif

                            @if($showPlowWidth)
                                <td>
                                    <input type="number"
                                           step="0.01"
                                           wire:model="rows.{{ $index }}.plow_width"
                                           placeholder="2.00">
                                    @error("rows.$index.plow_width") <small>{{ $message }}</small> @enderror
                                </td>
                            @endif

                            <td>
                                <select wire:model="rows.{{ $index }}.status">
                                    <option value="active">{{ __('pages.active') }}</option>
                                    <option value="inactive">{{ __('pages.inactive') }}</option>
                                </select>
                                @error("rows.$index.status") <small>{{ $message }}</small> @enderror
                            </td>

                            @if(!$editingId)
                                <td>
                                    <button type="button"
                                            wire:click="removeRow({{ $index }})"
                                            class="mini danger">
                                        Remove
                                    </button>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="actions" style="margin-top: 16px;">
            @if($editingId)
                <button wire:click="updateTractor" class="btn">
                    {{ __('pages.update') }}
                </button>

                <button wire:click="cancelEdit" class="btn gray">
                    {{ __('pages.cancel') }}
                </button>
            @else
                <button wire:click="saveAll" class="btn">
                    Save All Tractors
                </button>
            @endif
        </div>
    </div>

    <div class="panel">
        <h2 class="panel-title">{{ __('pages.tractor_list') }}</h2>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>{{ __('pages.no') }}</th>

                        @if($showIotDeviceId)
                            <th>{{ __('pages.iot_device') }}</th>
                        @endif

                        <th>{{ __('pages.name') }}</th>
                        <th>{{ __('pages.model') }}</th>
                        <th>{{ __('pages.plate') }}</th>
                        <th>{{ __('pages.fuel_capacity_short') }}</th>

                        @if($showCurrentMeter)
                            <th>{{ __('pages.current_meter') }}</th>
                        @endif

                        @if($showPlowWidth)
                            <th>{{ __('pages.plow_width') }}</th>
                        @endif

                        <th>{{ __('pages.status') }}</th>
                        <th width="160">{{ __('pages.action') }}</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($tractors as $tractor)
                        <tr>
                            <td>{{ $tractor->tractor_no }}</td>

                            @if($showIotDeviceId)
                                <td>{{ $tractor->iot_device_id ?? '-' }}</td>
                            @endif

                            <td>{{ $tractor->name ?? '-' }}</td>
                            <td>{{ $tractor->model ?? '-' }}</td>
                            <td>{{ $tractor->plate_no ?? '-' }}</td>
                            <td>{{ number_format($tractor->fuel_capacity ?? 0, 2) }}</td>

                            @if($showCurrentMeter)
                                <td>{{ number_format($tractor->current_meter ?? 0, 2) }}</td>
                            @endif

                            @if($showPlowWidth)
                                <td>{{ number_format($tractor->plow_width ?? 0, 2) }} m</td>
                            @endif

                            <td>
                                <span class="status {{ $tractor->status }}">
                                    {{ $tractor->status === 'active' ? __('pages.active') : __('pages.inactive') }}
                                </span>
                            </td>

                            <td>
                                <div class="table-actions">
                                    @if(auth()->user()->hasPermission('tractors.edit'))
                                        <button wire:click="edit({{ $tractor->id }})" class="mini">
                                            {{ __('pages.edit') }}
                                        </button>
                                    @endif

                                    @if(auth()->user()->hasPermission('tractors.delete'))
                                        <button wire:click="delete({{ $tractor->id }})" class="mini danger">
                                            {{ __('pages.delete') }}
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="empty">
                                {{ __('pages.no_tractor_found') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>

                @if($tractors->count() > 0)
                    <tfoot>
                        <tr class="total-row">
                            <td colspan="{{ $showIotDeviceId ? 4 : 3 }}" style="text-align:right;">
                                {{ __('pages.total') }}
                            </td>

                            <td>{{ number_format($totalFuelCapacity, 2) }}</td>

                            @if($showCurrentMeter)
                                <td>-</td>
                            @endif

                            @if($showPlowWidth)
                                <td>-</td>
                            @endif

                            <td>-</td>
                            <td>-</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>