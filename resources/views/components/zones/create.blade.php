<?php

use Livewire\Component;
use App\Models\Zone;
use Illuminate\Support\Facades\Auth;

new class extends Component
{
    public $zone_code;
    public $name;
    public $total_area = 0;
    public $location_note;
    public $status = 'active';

    public function save()
    {
        $this->validate([
            'zone_code' => 'required|string|max:100|unique:zones,zone_code',
            'name' => 'nullable|string|max:150',
            'total_area' => 'nullable|numeric|min:0',
            'location_note' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        Zone::create([
            'zone_code' => $this->zone_code,
            'name' => $this->name,
            'total_area' => $this->total_area ?: 0,
            'location_note' => $this->location_note,
            'status' => $this->status,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        session()->flash('success', __('pages.zone_created_success'));

        return redirect()->route('zones.index');
    }
};

?>

<div class="page">
    @include('components.shared-style')

    <div class="page-header">
        <div>
            <h1 class="page-title">{{ __('pages.add_zone') }}</h1>
            <p class="page-subtitle">{{ __('pages.add_zone_subtitle') }}</p>
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

            <a href="{{ route('zones.index') }}" class="btn gray">
                {{ __('pages.back') }}
            </a>
        </div>
    </div>

    <div class="panel">
        <h2 class="panel-title">{{ __('pages.zone_information') }}</h2>

        <div class="form-grid">
            <div>
                <label>{{ __('pages.zone_code') }} *</label>
                <input type="text"
                       wire:model="zone_code"
                       placeholder="U1.I2">
                @error('zone_code') <small>{{ $message }}</small> @enderror
            </div>

            <div>
                <label>{{ __('pages.name') }}</label>
                <input type="text"
                       wire:model="name"
                       placeholder="{{ __('pages.zone_name') }}">
                @error('name') <small>{{ $message }}</small> @enderror
            </div>

            <div>
                <label>{{ __('pages.total_area') }}</label>
                <input type="number"
                       step="0.01"
                       wire:model="total_area">
                @error('total_area') <small>{{ $message }}</small> @enderror
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
                <label>{{ __('pages.location_note') }}</label>
                <textarea wire:model="location_note"
                          placeholder="{{ __('pages.location_note') }}"></textarea>
                @error('location_note') <small>{{ $message }}</small> @enderror
            </div>
        </div>

        <div class="btn-row">
            <button wire:click="save" class="btn">
                {{ __('pages.save_zone') }}
            </button>

            <a href="{{ route('zones.index') }}" class="btn gray">
                {{ __('pages.cancel') }}
            </a>
        </div>
    </div>
</div>