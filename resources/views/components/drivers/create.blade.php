<?php

use Livewire\Component;
use App\Models\Driver;
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
            'name' => '',
            'phone' => '',
            'id_card_no' => '',
            'status' => 'active',
            'address' => '',
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

    public function copyRow($index)
    {
        if (!isset($this->rows[$index])) {
            return;
        }

        $this->rows[] = [
            'name' => $this->rows[$index]['name'],
            'phone' => '',
            'id_card_no' => '',
            'status' => $this->rows[$index]['status'],
            'address' => $this->rows[$index]['address'],
        ];
    }

    public function saveAll()
    {
        foreach ($this->rows as $index => $row) {
            $this->validate([
                "rows.$index.name" => 'required|string|max:150',

                "rows.$index.phone" => [
                    'nullable',
                    'string',
                    'max:50',
                    Rule::unique('drivers', 'phone'),
                ],

                "rows.$index.id_card_no" => [
                    'nullable',
                    'string',
                    'max:100',
                    Rule::unique('drivers', 'id_card_no'),
                ],

                "rows.$index.status" => 'required|in:active,inactive',
                "rows.$index.address" => 'nullable|string|max:1000',
            ]);
        }

        foreach ($this->rows as $row) {
            Driver::create([
                'name' => $row['name'],
                'phone' => $row['phone'] ?: null,
                'id_card_no' => $row['id_card_no'] ?: null,
                'status' => $row['status'],
                'address' => $row['address'] ?: null,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);
        }

        session()->flash('success', __('pages.driver_created_success'));

        return redirect()->route('drivers.index');
    }

    public function getTotalRowsProperty()
    {
        return count($this->rows);
    }
};

?>

<div class="page">
    @include('components.shared-style')
    @include('components.toast-alert')

    <style>
        .excel-table input,
        .excel-table select,
        .excel-table textarea {
            min-width: 160px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 10px 12px;
            font-weight: 700;
            background: #ffffff;
        }

        .excel-table textarea {
            min-width: 260px;
            height: 44px;
            resize: vertical;
        }

        .excel-table th,
        .excel-table td {
            white-space: nowrap;
            vertical-align: top;
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
            <h1 class="page-title">{{ __('pages.add_driver') }}</h1>
            <p class="page-subtitle">{{ __('pages.add_driver_subtitle') }}</p>
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

            <a href="{{ route('drivers.index') }}" class="btn gray">
                {{ __('pages.back') }}
            </a>
        </div>
    </div>

    <div class="panel">
        <div class="excel-actions">
            <button type="button" wire:click="addRow" class="btn light">
                + {{ __('pages.add_row') }}
            </button>
        </div>

        <div class="table-wrap">
            <table class="excel-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('pages.name') }} *</th>
                        <th>{{ __('pages.phone') }}</th>
                        <th>{{ __('pages.id_card_no') }}</th>
                        <th>{{ __('pages.status') }}</th>
                        <th>{{ __('pages.address') }}</th>
                        <th>{{ __('pages.action') }}</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($rows as $index => $row)
                        <tr>
                            <td>{{ $index + 1 }}</td>

                            <td>
                                <input type="text"
                                       wire:model="rows.{{ $index }}.name"
                                       placeholder="{{ __('pages.driver_name') }}">
                                @error("rows.$index.name")
                                    <small class="error">{{ $message }}</small>
                                @enderror
                            </td>

                            <td>
                                <input type="text"
                                       wire:model="rows.{{ $index }}.phone"
                                       placeholder="{{ __('pages.phone_number') }}">
                                @error("rows.$index.phone")
                                    <small class="error">{{ $message }}</small>
                                @enderror
                            </td>

                            <td>
                                <input type="text"
                                       wire:model="rows.{{ $index }}.id_card_no"
                                       placeholder="{{ __('pages.id_card_number') }}">
                                @error("rows.$index.id_card_no")
                                    <small class="error">{{ $message }}</small>
                                @enderror
                            </td>

                            <td>
                                <select wire:model="rows.{{ $index }}.status">
                                    <option value="active">{{ __('pages.active') }}</option>
                                    <option value="inactive">{{ __('pages.inactive') }}</option>
                                </select>

                                @error("rows.$index.status")
                                    <small class="error">{{ $message }}</small>
                                @enderror
                            </td>

                            <td>
                                <textarea wire:model="rows.{{ $index }}.address"
                                          placeholder="{{ __('pages.address') }}"></textarea>

                                @error("rows.$index.address")
                                    <small class="error">{{ $message }}</small>
                                @enderror
                            </td>

                            <td>
                                <div class="table-actions">
                                    <button type="button"
                                            wire:click="copyRow({{ $index }})"
                                            class="mini">
                                        {{ __('pages.copy') }}
                                    </button>

                                    <button type="button"
                                            wire:click="removeRow({{ $index }})"
                                            class="mini danger">
                                        {{ __('pages.remove') }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>

                <tfoot>
                    <tr class="total-row">
                        <td colspan="6" style="text-align:right;">
                            {{ __('pages.total_rows') }}
                        </td>
                        <td>{{ $this->totalRows }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="actions" style="margin-top: 16px;">
            <button wire:click="saveAll" class="btn">
                {{ __('pages.save_all_drivers') }}
            </button>

            <a href="{{ route('drivers.index') }}" class="btn gray">
                {{ __('pages.cancel') }}
            </a>
        </div>
    </div>
</div>