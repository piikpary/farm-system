<?php

namespace App\Livewire\FarmWorkLogs;

use App\Models\Driver;
use App\Models\FarmWorkLog;
use App\Models\TaskCategory;
use App\Models\Tractor;
use App\Models\Zone;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Create extends Component
{
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

    public function mount()
    {
        $this->work_date = now()->toDateString();
    }

    public function updated($property)
    {
        if (in_array($property, [
            'working_duration',
            'working_area',
            'diesel_start',
            'diesel_refill',
            'diesel_end',
            'task_category_id',
        ])) {
            $this->calculate();
        }
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

    public function save()
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

        FarmWorkLog::create([
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
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        session()->flash('success', 'Farm work log created successfully.');

        return redirect()->route('farm-work-logs.index');
    }

    public function render()
    {
        return view('livewire.farm-work-logs.create', [
            'tractors' => Tractor::where('status', 'active')->orderBy('tractor_no')->get(),
            'drivers' => Driver::where('status', 'active')->orderBy('name')->get(),
            'zones' => Zone::where('status', 'active')->orderBy('zone_code')->get(),
            'taskCategories' => TaskCategory::where('status', 'active')->orderBy('name')->get(),
        ]);
    }
}