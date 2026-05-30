<?php

use Livewire\Component;
use App\Models\FarmWorkLog;
use App\Models\Tractor;
use App\Models\Driver;
use App\Models\Zone;
use App\Models\TaskCategory;
use App\Models\FuelStock;
use App\Models\FuelTransaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

new class extends Component
{
    public $farmWorkLogId;

    public $work_date;
    public $tractor_id;
    public $driver_id;
    public $zone_id;
    public $task_category_id;

    public $working_duration = 0;
    public $working_area = 0;

    public $diesel_start = 0;
    public $diesel_refill = 0;
    public $diesel_end = 0;

    public $diesel_consumed = 0;
    public $diesel_per_hectare = 0;
    public $hectare_per_hour = 0;

    public $request_fuel_per_hectare = 0;
    public $request_fuel = 0;
    public $variance_fuel = 0;

    public $note;

    public function mount($farmWorkLog)
    {
        $log = FarmWorkLog::findOrFail($farmWorkLog);

        $this->farmWorkLogId = $log->id;
        $this->work_date = optional($log->work_date)->format('Y-m-d');
        $this->tractor_id = $log->tractor_id;
        $this->driver_id = $log->driver_id;
        $this->zone_id = $log->zone_id;
        $this->task_category_id = $log->task_category_id;
        $this->working_duration = $log->working_duration;
        $this->working_area = $log->working_area;
        $this->diesel_start = $log->diesel_start;
        $this->diesel_refill = $log->diesel_refill;
        $this->diesel_end = $log->diesel_end;
        $this->note = $log->note;

        $this->calculate();
    }

    public function updated()
    {
        $this->calculate();
    }

    public function calculate()
    {
        $dieselStart = (float) $this->diesel_start;
        $dieselRefill = (float) $this->diesel_refill;
        $dieselEnd = (float) $this->diesel_end;
        $workingArea = (float) $this->working_area;
        $workingDuration = (float) $this->working_duration;

        $this->diesel_consumed = max($dieselStart + $dieselRefill - $dieselEnd, 0);

        $this->diesel_per_hectare = $workingArea > 0
            ? round($this->diesel_consumed / $workingArea, 2)
            : 0;

        $this->hectare_per_hour = $workingDuration > 0
            ? round($workingArea / $workingDuration, 2)
            : 0;

        $taskCategory = TaskCategory::find($this->task_category_id);

        $this->request_fuel_per_hectare = $taskCategory
            ? (float) $taskCategory->standard_fuel_per_hectare
            : 0;

        $this->request_fuel = round($workingArea * $this->request_fuel_per_hectare, 2);
        $this->variance_fuel = round($this->request_fuel - $this->diesel_consumed, 2);
    }

    public function update()
    {
        $this->calculate();

        $this->validate([
            'work_date' => 'required|date',
            'tractor_id' => 'required|exists:tractors,id',
            'driver_id' => 'required|exists:drivers,id',
            'zone_id' => 'required|exists:zones,id',
            'task_category_id' => 'required|exists:task_categories,id',
            'working_duration' => 'required|numeric|min:0.01',
            'working_area' => 'required|numeric|min:0.01',
            'diesel_start' => 'required|numeric|min:0',
            'diesel_refill' => 'required|numeric|min:0',
            'diesel_end' => 'required|numeric|min:0',
            'note' => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () {
            $log = FarmWorkLog::lockForUpdate()->findOrFail($this->farmWorkLogId);

            $oldDieselConsumed = (float) $log->diesel_consumed;
            $newDieselConsumed = (float) $this->diesel_consumed;
            $difference = round($newDieselConsumed - $oldDieselConsumed, 2);

            $fuelStock = FuelStock::where('status', 'active')
                ->lockForUpdate()
                ->first();

            if (!$fuelStock) {
                throw ValidationException::withMessages([
                    'diesel_consumed' => __('pages.no_active_stock_fuel'),
                ]);
            }

            if ($difference > 0 && (float) $fuelStock->current_stock < $difference) {
                throw ValidationException::withMessages([
                    'diesel_consumed' => __('pages.not_enough_stock_fuel_for_update') . ' '
                        . number_format($difference, 2) . ' L. '
                        . __('pages.current_stock') . ': '
                        . number_format($fuelStock->current_stock, 2) . ' L',
                ]);
            }

            $log->update([
                'work_date' => $this->work_date,
                'tractor_id' => $this->tractor_id,
                'driver_id' => $this->driver_id,
                'zone_id' => $this->zone_id,
                'task_category_id' => $this->task_category_id,
                'working_duration' => $this->working_duration,
                'working_area' => $this->working_area,
                'diesel_start' => $this->diesel_start,
                'diesel_refill' => $this->diesel_refill,
                'diesel_end' => $this->diesel_end,
                'diesel_consumed' => $this->diesel_consumed,
                'diesel_per_hectare' => $this->diesel_per_hectare,
                'hectare_per_hour' => $this->hectare_per_hour,
                'request_fuel_per_hectare' => $this->request_fuel_per_hectare,
                'request_fuel' => $this->request_fuel,
                'variance_fuel' => $this->variance_fuel,
                'note' => $this->note,
                'updated_by' => Auth::id(),
            ]);

            if ($difference != 0) {
                if ($difference > 0) {
                    $fuelStock->current_stock = (float) $fuelStock->current_stock - $difference;
                    $transactionNote = __('pages.fuel_deducted_after_editing_work_log') . ' #' . $log->id;
                    $transactionQuantity = $difference;
                } else {
                    $returnQuantity = abs($difference);
                    $fuelStock->current_stock = (float) $fuelStock->current_stock + $returnQuantity;
                    $transactionNote = __('pages.fuel_returned_after_editing_work_log') . ' #' . $log->id;
                    $transactionQuantity = $returnQuantity;
                }

                $fuelStock->updated_by = Auth::id();
                $fuelStock->save();

                FuelTransaction::create([
                    'fuel_stock_id' => $fuelStock->id,
                    'transaction_date' => $this->work_date,
                    'type' => 'adjustment',
                    'tractor_id' => $this->tractor_id,
                    'farm_work_log_id' => $log->id,
                    'quantity' => $transactionQuantity,
                    'balance_after' => $fuelStock->current_stock,
                    'reference_no' => 'EDIT-WORKLOG-' . $log->id . '-' . now()->format('YmdHis'),
                    'note' => $transactionNote,
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ]);
            }
        });

        session()->flash('success', __('pages.work_log_updated_success'));

        return redirect()->route('farm-work-logs.index');
    }

    public function with()
    {
        return [
            'tractors' => Tractor::where('status', 'active')->orderBy('tractor_no')->get(),
            'drivers' => Driver::where('status', 'active')->orderBy('name')->get(),
            'zones' => Zone::where('status', 'active')->orderBy('zone_code')->get(),
            'taskCategories' => TaskCategory::where('status', 'active')->orderBy('name')->get(),
        ];
    }
};

?>

<div class="page">
    @include('components.shared-style')

    <div class="page-header">
        <div>
            <h1 class="page-title">{{ __('pages.edit_farm_work_log') }}</h1>
            <p class="page-subtitle">{{ __('pages.edit_farm_work_log_subtitle') }}</p>
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

            <a href="{{ route('farm-work-logs.index') }}" class="btn gray">
                {{ __('pages.back') }}
            </a>
        </div>
    </div>

    <div class="panel">
        <h2 class="panel-title">{{ __('pages.work_information') }}</h2>

        <div class="form-grid">
            <div>
                <label>{{ __('pages.work_date') }} *</label>
                <input type="date" wire:model.live="work_date">
                @error('work_date') <small>{{ $message }}</small> @enderror
            </div>

            <div>
                <label>{{ __('pages.tractor') }} *</label>
                <select wire:model.live="tractor_id">
                    <option value="">{{ __('pages.select_tractor') }}</option>
                    @foreach($tractors as $tractor)
                        <option value="{{ $tractor->id }}">{{ $tractor->tractor_no }}</option>
                    @endforeach
                </select>
                @error('tractor_id') <small>{{ $message }}</small> @enderror
            </div>

            <div>
                <label>{{ __('pages.driver') }} *</label>
                <select wire:model.live="driver_id">
                    <option value="">{{ __('pages.select_driver') }}</option>
                    @foreach($drivers as $driver)
                        <option value="{{ $driver->id }}">{{ $driver->name }}</option>
                    @endforeach
                </select>
                @error('driver_id') <small>{{ $message }}</small> @enderror
            </div>

            <div>
                <label>{{ __('pages.zone') }} *</label>
                <select wire:model.live="zone_id">
                    <option value="">{{ __('pages.select_zone') }}</option>
                    @foreach($zones as $zone)
                        <option value="{{ $zone->id }}">{{ $zone->zone_code }}</option>
                    @endforeach
                </select>
                @error('zone_id') <small>{{ $message }}</small> @enderror
            </div>

            <div>
                <label>{{ __('pages.task_category') }} *</label>
                <select wire:model.live="task_category_id">
                    <option value="">{{ __('pages.select_task') }}</option>
                    @foreach($taskCategories as $task)
                        <option value="{{ $task->id }}">{{ $task->name }}</option>
                    @endforeach
                </select>
                @error('task_category_id') <small>{{ $message }}</small> @enderror
            </div>

            <div>
                <label>{{ __('pages.working_duration') }} *</label>
                <input type="number" step="0.01" wire:model.live="working_duration">
                @error('working_duration') <small>{{ $message }}</small> @enderror
            </div>

            <div>
                <label>{{ __('pages.working_area') }} *</label>
                <input type="number" step="0.01" wire:model.live="working_area">
                @error('working_area') <small>{{ $message }}</small> @enderror
            </div>
        </div>
    </div>

    <div class="panel">
        <h2 class="panel-title">{{ __('pages.diesel_information') }}</h2>

        <div class="form-grid">
            <div>
                <label>{{ __('pages.diesel_start') }} *</label>
                <input type="number" step="0.01" wire:model.live="diesel_start">
                @error('diesel_start') <small>{{ $message }}</small> @enderror
            </div>

            <div>
                <label>{{ __('pages.diesel_refill') }} *</label>
                <input type="number" step="0.01" wire:model.live="diesel_refill">
                @error('diesel_refill') <small>{{ $message }}</small> @enderror
            </div>

            <div>
                <label>{{ __('pages.diesel_end') }} *</label>
                <input type="number" step="0.01" wire:model.live="diesel_end">
                @error('diesel_end') <small>{{ $message }}</small> @enderror
            </div>
        </div>
    </div>

    <div class="summary-grid">
        <div class="summary-card">
            <div class="summary-label">{{ __('pages.diesel_consumed') }}</div>
            <div class="summary-value">{{ number_format($diesel_consumed, 2) }} L</div>
        </div>

        <div class="summary-card">
            <div class="summary-label">{{ __('pages.diesel_per_hectare') }}</div>
            <div class="summary-value">{{ number_format($diesel_per_hectare, 2) }}</div>
        </div>

        <div class="summary-card">
            <div class="summary-label">{{ __('pages.hectare_per_hour') }}</div>
            <div class="summary-value">{{ number_format($hectare_per_hour, 2) }}</div>
        </div>

        <div class="summary-card">
            <div class="summary-label">{{ __('pages.fuel_variance') }}</div>
            <div class="summary-value" style="color: {{ $variance_fuel < 0 ? '#dc2626' : '#166534' }}">
                {{ number_format($variance_fuel, 2) }}
            </div>
        </div>
    </div>

    <div class="panel">
        <label>{{ __('pages.note') }}</label>
        <textarea wire:model="note" placeholder="{{ __('pages.optional_note') }}"></textarea>

        @error('diesel_consumed') <small>{{ $message }}</small> @enderror

        <div class="btn-row">
            <button wire:click="update" class="btn">
                {{ __('pages.update_work_log') }}
            </button>

            <a href="{{ route('farm-work-logs.index') }}" class="btn gray">
                {{ __('pages.cancel') }}
            </a>
        </div>
    </div>
</div>