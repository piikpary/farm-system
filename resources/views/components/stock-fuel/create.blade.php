<?php

use Livewire\Component;
use App\Models\FuelStock;
use App\Models\FuelTransaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

new class extends Component
{
    public $fuel_stock_id = '';
    public $name = 'Main Diesel Stock';

    public $transaction_type = 'stock_in';
    public $adjustment_mode = 'increase';

    public $quantity = 0;
    public $minimum_stock_alert = 0;
    public $note;

    public function save()
    {
        $this->validate([
            'fuel_stock_id' => 'nullable|exists:fuel_stocks,id',
            'name' => 'required_without:fuel_stock_id|nullable|string|max:150',
            'transaction_type' => 'required|in:stock_in,adjustment',
            'adjustment_mode' => 'required_if:transaction_type,adjustment|in:increase,decrease,set_balance',
            'quantity' => 'required|numeric|min:0.01',
            'minimum_stock_alert' => 'nullable|numeric|min:0',
            'note' => 'nullable|string',
        ]);

        DB::transaction(function () {
            if ($this->fuel_stock_id) {
                $stock = FuelStock::lockForUpdate()->findOrFail($this->fuel_stock_id);
            } else {
                $stock = FuelStock::create([
                    'name' => $this->name,
                    'opening_stock' => 0,
                    'current_stock' => 0,
                    'minimum_stock_alert' => $this->minimum_stock_alert ?: 0,
                    'status' => 'active',
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ]);
            }

            $oldBalance = (float) $stock->current_stock;
            $qty = (float) $this->quantity;

            if ($this->transaction_type === 'stock_in') {
                $newBalance = $oldBalance + $qty;
                $transactionQty = $qty;
                $type = 'stock_in';
                $reference = 'STOCK-IN-' . now()->format('YmdHis');
                $defaultNote = 'Fuel stock added';
            } else {
                $type = 'adjustment';
                $reference = 'ADJUST-' . now()->format('YmdHis');

                if ($this->adjustment_mode === 'increase') {
                    $newBalance = $oldBalance + $qty;
                    $transactionQty = $qty;
                    $defaultNote = 'Fuel stock adjustment increase';
                } elseif ($this->adjustment_mode === 'decrease') {
                    if ($oldBalance < $qty) {
                        throw ValidationException::withMessages([
                            'quantity' => 'Cannot decrease more than current stock. Current stock: ' . number_format($oldBalance, 2) . ' L',
                        ]);
                    }

                    $newBalance = $oldBalance - $qty;
                    $transactionQty = $qty;
                    $defaultNote = 'Fuel stock adjustment decrease';
                } else {
                    $newBalance = $qty;
                    $transactionQty = abs($newBalance - $oldBalance);
                    $defaultNote = 'Fuel stock balance corrected from ' . number_format($oldBalance, 2) . ' L to ' . number_format($newBalance, 2) . ' L';
                }
            }

            $stock->current_stock = $newBalance;

            if (!$this->fuel_stock_id) {
                $stock->opening_stock = $newBalance;
            }

            $stock->updated_by = Auth::id();
            $stock->save();

            FuelTransaction::create([
                'fuel_stock_id' => $stock->id,
                'transaction_date' => now()->toDateString(),
                'type' => $type,
                'quantity' => $transactionQty,
                'balance_after' => $stock->current_stock,
                'reference_no' => $reference,
                'note' => $this->note ?: $defaultNote,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);
        });

        session()->flash('success', 'Fuel stock saved successfully.');

        return redirect()->route('stock-fuel.index');
    }

    public function with()
    {
        return [
            'fuelStocks' => FuelStock::where('status', 'active')
                ->orderBy('name')
                ->get(),
        ];
    }
};

?>

<div class="page">
    @include('components.shared-style')

    <div class="page-header">
        <div>
            <h1 class="page-title">{{ __('pages.add_adjust_fuel_stock') }}</h1>
            <p class="page-subtitle">{{ __('pages.add_adjust_fuel_stock_subtitle') }}</p>
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

    <div class="panel">
        <h2 class="panel-title">{{ __('pages.fuel_stock_information') }}</h2>

        <div class="form-grid">
            <div>
                <label>{{ __('pages.select_stock') }}</label>
                <select wire:model.live="fuel_stock_id">
                    <option value="">{{ __('pages.create_new_stock') }}</option>

                    @foreach($fuelStocks as $stock)
                        <option value="{{ $stock->id }}">
                            {{ $stock->name }} - {{ number_format($stock->current_stock, 2) }} L
                        </option>
                    @endforeach
                </select>
                @error('fuel_stock_id') <small>{{ $message }}</small> @enderror
            </div>

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
                       wire:model="minimum_stock_alert"
                       placeholder="100">
                @error('minimum_stock_alert') <small>{{ $message }}</small> @enderror
            </div>

            <div>
                <label>{{ __('pages.transaction_type') }} *</label>
                <select wire:model.live="transaction_type">
                    <option value="stock_in">{{ __('pages.stock_in') }}</option>
                    <option value="adjustment">{{ __('pages.adjustment') }}</option>
                </select>
                @error('transaction_type') <small>{{ $message }}</small> @enderror
            </div>

            @if($transaction_type === 'adjustment')
                <div>
                    <label>{{ __('pages.adjustment_type') }} *</label>
                    <select wire:model.live="adjustment_mode">
                        <option value="increase">{{ __('pages.increase_stock') }}</option>
                        <option value="decrease">{{ __('pages.decrease_stock') }}</option>
                        <option value="set_balance">{{ __('pages.set_exact_balance') }}</option>
                    </select>
                    @error('adjustment_mode') <small>{{ $message }}</small> @enderror
                </div>
            @endif

            <div>
                <label>
                    @if($transaction_type === 'adjustment' && $adjustment_mode === 'set_balance')
                        {{ __('pages.new_balance') }} *
                    @else
                        {{ __('pages.fuel_quantity') }} *
                    @endif
                </label>

                <input type="number"
                       step="0.01"
                       wire:model="quantity"
                       placeholder="0">
                @error('quantity') <small>{{ $message }}</small> @enderror
            </div>

            <div style="grid-column: 1 / -1;">
                <label>{{ __('pages.note') }}</label>
                <textarea wire:model="note"
                          placeholder="{{ __('pages.reason_or_note') }}"></textarea>
                @error('note') <small>{{ $message }}</small> @enderror
            </div>
        </div>

        <div class="btn-row">
            <button wire:click="save" class="btn">
                {{ __('pages.save_fuel_stock') }}
            </button>

            <a href="{{ route('stock-fuel.index') }}" class="btn gray">
                {{ __('pages.cancel') }}
            </a>
        </div>
    </div>
</div>