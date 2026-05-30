<?php

use Livewire\Component;
use App\Models\TaskCategory;
use Illuminate\Support\Facades\Auth;

new class extends Component
{
    public $name;
    public $description;
    public $standard_fuel_per_hectare = 0;
    public $standard_hectare_per_hour = 0;
    public $status = 'active';

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:150',
            'description' => 'nullable|string',
            'standard_fuel_per_hectare' => 'nullable|numeric|min:0',
            'standard_hectare_per_hour' => 'nullable|numeric|min:0',
            'status' => 'required|in:active,inactive',
        ]);

        TaskCategory::create([
            'name' => $this->name,
            'description' => $this->description,
            'standard_fuel_per_hectare' => $this->standard_fuel_per_hectare ?: 0,
            'standard_hectare_per_hour' => $this->standard_hectare_per_hour ?: 0,
            'status' => $this->status,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        session()->flash('success', __('pages.task_category_created_success'));

        return redirect()->route('task-categories.index');
    }
};

?>

<div class="page">
    @include('components.shared-style')
    @include('components.toast-alert')

    <div class="page-header">
        <div>
            <h1 class="page-title">{{ __('pages.add_task_category') }}</h1>
            <p class="page-subtitle">{{ __('pages.add_task_category_subtitle') }}</p>
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

            <a href="{{ route('task-categories.index') }}" class="btn gray">
                {{ __('pages.back') }}
            </a>
        </div>
    </div>

    <div class="panel">
        <h2 class="panel-title">{{ __('pages.task_category_information') }}</h2>

        <div class="form-grid">
            <div>
                <label>{{ __('pages.name') }} *</label>
                <input type="text"
                       wire:model="name"
                       placeholder="{{ __('pages.task_category_placeholder') }}">
                @error('name') <small>{{ $message }}</small> @enderror
            </div>

            <div>
                <label>{{ __('pages.standard_fuel_per_hectare') }}</label>
                <input type="number"
                       step="0.01"
                       wire:model="standard_fuel_per_hectare">
                @error('standard_fuel_per_hectare') <small>{{ $message }}</small> @enderror
            </div>

            <div>
                <label>{{ __('pages.standard_hectare_per_hour') }}</label>
                <input type="number"
                       step="0.01"
                       wire:model="standard_hectare_per_hour">
                @error('standard_hectare_per_hour') <small>{{ $message }}</small> @enderror
            </div>

            <div>
                <label>{{ __('pages.status') }}</label>
                <select wire:model="status">
                    <option value="active">{{ __('pages.active') }}</option>
                    <option value="inactive">{{ __('pages.inactive') }}</option>
                </select>
                @error('status') <small>{{ $message }}</small> @enderror
            </div>

            <div style="grid-column: 1 / -1;">
                <label>{{ __('pages.description') }}</label>
                <textarea wire:model="description"
                          placeholder="{{ __('pages.description') }}"></textarea>
                @error('description') <small>{{ $message }}</small> @enderror
            </div>
        </div>

        <div class="btn-row">
            <button wire:click="save" class="btn">
                {{ __('pages.save_task_category') }}
            </button>

            <a href="{{ route('task-categories.index') }}" class="btn gray">
                {{ __('pages.cancel') }}
            </a>
        </div>
    </div>
</div>