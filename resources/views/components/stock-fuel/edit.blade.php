<?php

use Livewire\Component;
use App\Models\FuelStock;
use Illuminate\Support\Facades\Auth;

new class extends Component
{
    public $fuelStockId;
    public $name;
    public $minimum_stock_alert = 0;
    public $status = 'active';

    public $opening_stock = 0;
    public $current_stock = 0;

    public function mount($fuelStock)
    {
        $stock = FuelStock::findOrFail($fuelStock);

        $this->fuelStockId = $stock->id;
        $this->name = $stock->name;
        $this->minimum_stock_alert = $stock->minimum_stock_alert;
        $this->status = $stock->status;

        // Display only, not editable
        $this->opening_stock = $stock->opening_stock;
        $this->current_stock = $stock->current_stock;
    }

    public function update()
    {
        $this->validate([
            'name' => 'required|string|max:150',
            'minimum_stock_alert' => 'nullable|numeric|min:0',
            'status' => 'required|in:active,inactive',
        ]);

        FuelStock::findOrFail($this->fuelStockId)->update([
            'name' => $this->name,
            'minimum_stock_alert' => $this->minimum_stock_alert ?: 0,
            'status' => $this->status,
            'updated_by' => Auth::id(),
        ]);

        session()->flash('success', __('pages.fuel_stock_updated_success'));

        return redirect()->route('stock-fuel.index');
    }
};

?>

<div class="page">
    @include('components.shared-style')

    <div class="page-header">
        <div>
            <h1 class="page-title">{{ __('pages.edit_fuel_stock') }}</h1>
            <p class="page-subtitle">{{ __('pages.edit_fuel_stock_subtitle') }}</p>
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

            <a href="{{ route('stock-fuel.index') }}" class="btn gray">
                {{ __('pages.back') }}
            </a>
        </div>
    </div>

    <div class="summary-grid">
        <div class="summary-card">
            <div class="summary-label">{{ __('pages.opening_stock') }}</div>
            <div class="summary-value">{{ number_format($opening_stock, 2) }} L</div>
        </div>

        <div class="summary-card">
            <div class="summary-label">{{ __('pages.current_stock') }}</div>
            <div class="summary-value" style="color: {{ $current_stock <= $minimum_stock_alert ? '#dc2626' : '#166534' }}">
                {{ number_format($current_stock, 2) }} L
            </div>
        </div>

        <div class="summary-card">
            <div class="summary-label">{{ __('pages.minimum_alert') }}</div>
            <div class="summary-value">{{ number_format($minimum_stock_alert, 2) }} L</div>
        </div>

        <div class="summary-card">
            <div class="summary-label">{{ __('pages.stock_status') }}</div>
            <div class="summary-value">
                {{ $status === 'active' ? __('pages.active') : __('pages.inactive') }}
            </div>
        </div>
    </div>

    <div class="panel">
        <h2 class="panel-title">{{ __('pages.fuel_stock_information') }}</h2>

        <div class="form-grid">
            <div>
                <label>{{ __('pages.stock_name') }} *</label>
                <input type="text"
                       wire:model="name"
                       placeholder="{{ __('pages.stock_name') }}">
                @error('name') <small>{{ $message }}</small> @enderror
            </div>

            <div>
                <label>{{ __('pages.minimum_alert') }}</label>
                <input type="number"
                       step="0.01"
                       wire:model.live="minimum_stock_alert"
                       placeholder="100">
                @error('minimum_stock_alert') <small>{{ $message }}</small> @enderror
            </div>

            <div>
                <label>{{ __('pages.status') }}</label>
                <select wire:model.live="status">
                    <option value="active">{{ __('pages.active') }}</option>
                    <option value="inactive">{{ __('pages.inactive') }}</option>
                </select>
                @error('status') <small>{{ $message }}</small> @enderror
            </div>

            <div>
                <label>{{ __('pages.opening_stock') }}</label>
                <input type="text" value="{{ number_format($opening_stock, 2) }} L" disabled>
            </div>

            <div>
                <label>{{ __('pages.current_stock') }}</label>
                <input type="text" value="{{ number_format($current_stock, 2) }} L" disabled>
            </div>
        </div>

        <div style="margin-top: 16px; padding: 14px; border-radius: 12px; background: #fff7ed; color: #9a3412; font-weight: 800;">
            {{ __('pages.fuel_quantity_warning') }}
        </div>

        <div class="btn-row">
            <button wire:click="update" class="btn">
                {{ __('pages.update_fuel_stock') }}
            </button>

            <a href="{{ route('stock-fuel.create') }}" class="btn light">
                {{ __('pages.adjust_fuel') }}
            </a>

            <a href="{{ route('stock-fuel.index') }}" class="btn gray">
                {{ __('pages.cancel') }}
            </a>
        </div>
    </div>
</div>