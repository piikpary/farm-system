<?php

use Livewire\Component;
use App\Models\Tractor;
use Illuminate\Support\Facades\Auth;

new class extends Component
{
    public $tractor_no;
    public $name;
    public $model;
    public $plate_no;
    public $fuel_capacity;
    public $current_meter;
    public $status = 'active';
    public $editingId = null;

    public function save()
    {
        $this->validate([
            'tractor_no' => 'required|string|max:100|unique:tractors,tractor_no,' . $this->editingId,
            'name' => 'nullable|string|max:150',
            'model' => 'nullable|string|max:150',
            'plate_no' => 'nullable|string|max:100',
            'fuel_capacity' => 'nullable|numeric|min:0',
            'current_meter' => 'nullable|numeric|min:0',
            'status' => 'required|in:active,inactive',
        ]);

        Tractor::updateOrCreate(
            ['id' => $this->editingId],
            [
                'tractor_no' => $this->tractor_no,
                'name' => $this->name,
                'model' => $this->model,
                'plate_no' => $this->plate_no,
                'fuel_capacity' => $this->fuel_capacity ?: 0,
                'current_meter' => $this->current_meter ?: 0,
                'status' => $this->status,
                'created_by' => $this->editingId ? null : Auth::id(),
                'updated_by' => Auth::id(),
            ]
        );

        session()->flash(
            'success',
            $this->editingId
                ? __('pages.tractor_updated_success')
                : __('pages.tractor_created_success')
        );

        $this->resetForm();
    }

    public function edit($id)
    {
        $tractor = Tractor::findOrFail($id);

        $this->editingId = $tractor->id;
        $this->tractor_no = $tractor->tractor_no;
        $this->name = $tractor->name;
        $this->model = $tractor->model;
        $this->plate_no = $tractor->plate_no;
        $this->fuel_capacity = $tractor->fuel_capacity;
        $this->current_meter = $tractor->current_meter;
        $this->status = $tractor->status;
    }

    public function delete($id)
    {
        Tractor::findOrFail($id)->delete();

        session()->flash('success', __('pages.tractor_deleted_success'));
    }

    public function resetForm()
    {
        $this->reset([
            'tractor_no',
            'name',
            'model',
            'plate_no',
            'fuel_capacity',
            'current_meter',
            'editingId',
        ]);

        $this->status = 'active';
    }

    public function with()
    {
        return [
            'tractors' => Tractor::latest()->get(),
        ];
    }
};

?>

<div class="page">
    @include('components.shared-style')

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

    @if (session('success'))
        <div class="alert">{{ session('success') }}</div>
    @endif

    <div class="panel">
        <h2 class="panel-title">
            {{ $editingId ? __('pages.edit_tractor') : __('pages.add_tractor') }}
        </h2>

        <div class="form-grid">
            <div>
                <label>{{ __('pages.tractor_no') }} *</label>
                <input type="text"
                       wire:model="tractor_no"
                       placeholder="T-01">
                @error('tractor_no') <small>{{ $message }}</small> @enderror
            </div>

            <div>
                <label>{{ __('pages.name') }}</label>
                <input type="text"
                       wire:model="name"
                       placeholder="{{ __('pages.tractor_name') }}">
                @error('name') <small>{{ $message }}</small> @enderror
            </div>

            <div>
                <label>{{ __('pages.model') }}</label>
                <input type="text"
                       wire:model="model"
                       placeholder="{{ __('pages.model') }}">
                @error('model') <small>{{ $message }}</small> @enderror
            </div>

            <div>
                <label>{{ __('pages.plate_no') }}</label>
                <input type="text"
                       wire:model="plate_no"
                       placeholder="{{ __('pages.plate_number') }}">
                @error('plate_no') <small>{{ $message }}</small> @enderror
            </div>

            <div>
                <label>{{ __('pages.fuel_capacity') }}</label>
                <input type="number"
                       step="0.01"
                       wire:model="fuel_capacity">
                @error('fuel_capacity') <small>{{ $message }}</small> @enderror
            </div>

            <div>
                <label>{{ __('pages.current_meter') }}</label>
                <input type="number"
                       step="0.01"
                       wire:model="current_meter">
                @error('current_meter') <small>{{ $message }}</small> @enderror
            </div>

            <div>
                <label>{{ __('pages.status') }}</label>
                <select wire:model="status">
                    <option value="active">{{ __('pages.active') }}</option>
                    <option value="inactive">{{ __('pages.inactive') }}</option>
                </select>
                @error('status') <small>{{ $message }}</small> @enderror
            </div>
        </div>

        <div class="actions">
            <button wire:click="save" class="btn">
                {{ $editingId ? __('pages.update') : __('pages.save') }}
            </button>

            @if($editingId)
                <button wire:click="resetForm" class="btn gray">
                    {{ __('pages.cancel') }}
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
                        <th>{{ __('pages.name') }}</th>
                        <th>{{ __('pages.model') }}</th>
                        <th>{{ __('pages.plate') }}</th>
                        <th>{{ __('pages.fuel_capacity_short') }}</th>
                        <th>{{ __('pages.status') }}</th>
                        <th width="140">{{ __('pages.action') }}</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($tractors as $tractor)
                        <tr>
                            <td>{{ $tractor->tractor_no }}</td>
                            <td>{{ $tractor->name ?? '-' }}</td>
                            <td>{{ $tractor->model ?? '-' }}</td>
                            <td>{{ $tractor->plate_no ?? '-' }}</td>
                            <td>{{ number_format($tractor->fuel_capacity ?? 0, 2) }}</td>

                            <td>
                                <span class="status {{ $tractor->status }}">
                                    {{ $tractor->status === 'active' ? __('pages.active') : __('pages.inactive') }}
                                </span>
                            </td>

                            <td>
                                <div class="table-actions">
                                    <button wire:click="edit({{ $tractor->id }})" class="mini">
                                        {{ __('pages.edit') }}
                                    </button>

                                    <button wire:click="delete({{ $tractor->id }})" class="mini danger">
                                        {{ __('pages.delete') }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="empty">
                                {{ __('pages.no_tractor_found') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>