<?php

use Livewire\Component;
use App\Models\Driver;
use Illuminate\Support\Facades\Auth;

new class extends Component
{
    public $driverId;
    public $name;
    public $phone;
    public $address;
    public $id_card_no;
    public $status = 'active';

    public function mount($driver)
    {
        $driver = Driver::findOrFail($driver);

        $this->driverId = $driver->id;
        $this->name = $driver->name;
        $this->phone = $driver->phone;
        $this->address = $driver->address;
        $this->id_card_no = $driver->id_card_no;
        $this->status = $driver->status;
    }

    public function update()
    {
        $this->validate([
            'name' => 'required|string|max:150',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'id_card_no' => 'nullable|string|max:100',
            'status' => 'required|in:active,inactive',
        ]);

        Driver::findOrFail($this->driverId)->update([
            'name' => $this->name,
            'phone' => $this->phone,
            'address' => $this->address,
            'id_card_no' => $this->id_card_no,
            'status' => $this->status,
            'updated_by' => Auth::id(),
        ]);

        session()->flash('success', __('pages.driver_updated_success'));

        return redirect()->route('drivers.index');
    }
};

?>

<div class="page">
    @include('components.shared-style')

    <div class="page-header">
        <div>
            <h1 class="page-title">{{ __('pages.edit_driver') }}</h1>
            <p class="page-subtitle">{{ __('pages.edit_driver_subtitle') }}</p>
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
        <h2 class="panel-title">{{ __('pages.driver_information') }}</h2>

        <div class="form-grid">
            <div>
                <label>{{ __('pages.name') }} *</label>
                <input type="text"
                       wire:model="name"
                       placeholder="{{ __('pages.driver_name') }}">
                @error('name') <small>{{ $message }}</small> @enderror
            </div>

            <div>
                <label>{{ __('pages.phone') }}</label>
                <input type="text"
                       wire:model="phone"
                       placeholder="{{ __('pages.phone_number') }}">
                @error('phone') <small>{{ $message }}</small> @enderror
            </div>

            <div>
                <label>{{ __('pages.id_card_no') }}</label>
                <input type="text"
                       wire:model="id_card_no"
                       placeholder="{{ __('pages.id_card_number') }}">
                @error('id_card_no') <small>{{ $message }}</small> @enderror
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
                <label>{{ __('pages.address') }}</label>
                <textarea wire:model="address"
                          placeholder="{{ __('pages.address') }}"></textarea>
                @error('address') <small>{{ $message }}</small> @enderror
            </div>
        </div>

        <div class="btn-row">
            <button wire:click="update" class="btn">
                {{ __('pages.update_driver') }}
            </button>

            <a href="{{ route('drivers.index') }}" class="btn gray">
                {{ __('pages.cancel') }}
            </a>
        </div>
    </div>
</div>