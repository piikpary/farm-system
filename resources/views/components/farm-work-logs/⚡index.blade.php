<?php

use Livewire\Component;
use App\Models\FarmWorkLog;
use App\Models\Tractor;
use App\Models\Driver;
use App\Models\Zone;
use App\Models\TaskCategory;

new class extends Component
{
    public $search = '';
    public $date_from;
    public $date_to;
    public $tractor_id = '';
    public $driver_id = '';
    public $zone_id = '';
    public $task_category_id = '';

    public function delete($id)
    {
        FarmWorkLog::findOrFail($id)->delete();
        session()->flash('success', __('pages.work_log_deleted_success'));
    }

    public function resetFilter()
    {
        $this->reset([
            'search',
            'date_from',
            'date_to',
            'tractor_id',
            'driver_id',
            'zone_id',
            'task_category_id',
        ]);
    }

    public function with()
    {
        $logs = FarmWorkLog::with(['tractor', 'driver', 'zone', 'taskCategory'])
            ->when($this->date_from, fn ($q) => $q->whereDate('work_date', '>=', $this->date_from))
            ->when($this->date_to, fn ($q) => $q->whereDate('work_date', '<=', $this->date_to))
            ->when($this->tractor_id, fn ($q) => $q->where('tractor_id', $this->tractor_id))
            ->when($this->driver_id, fn ($q) => $q->where('driver_id', $this->driver_id))
            ->when($this->zone_id, fn ($q) => $q->where('zone_id', $this->zone_id))
            ->when($this->task_category_id, fn ($q) => $q->where('task_category_id', $this->task_category_id))
            ->when($this->search, function ($q) {
                $q->whereHas('tractor', fn ($sub) => $sub->where('tractor_no', 'like', '%' . $this->search . '%'))
                    ->orWhereHas('driver', fn ($sub) => $sub->where('name', 'like', '%' . $this->search . '%'))
                    ->orWhereHas('zone', fn ($sub) => $sub->where('zone_code', 'like', '%' . $this->search . '%'))
                    ->orWhereHas('taskCategory', fn ($sub) => $sub->where('name', 'like', '%' . $this->search . '%'));
            })
            ->latest()
            ->get();

        return [
            'logs' => $logs,
            'tractors' => Tractor::where('status', 'active')->orderBy('tractor_no')->get(),
            'drivers' => Driver::where('status', 'active')->orderBy('name')->get(),
            'zones' => Zone::where('status', 'active')->orderBy('zone_code')->get(),
            'taskCategories' => TaskCategory::where('status', 'active')->orderBy('name')->get(),
            'totalArea' => $logs->sum('working_area'),
            'totalHour' => $logs->sum('working_duration'),
            'totalDiesel' => $logs->sum('diesel_consumed'),
        ];
    }
};

?>

<div class="page">
    @include('components.shared-style')

    <div class="page-header">
        <div>
            <h1 class="page-title">{{ __('pages.farm_work_logs') }}</h1>
            <p class="page-subtitle">{{ __('pages.farm_work_logs_subtitle') }}</p>
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

            <a href="{{ route('farm-work-logs.export.csv', [
                'search' => $search,
                'date_from' => $date_from,
                'date_to' => $date_to,
                'tractor_id' => $tractor_id,
                'driver_id' => $driver_id,
                'zone_id' => $zone_id,
                'task_category_id' => $task_category_id,
            ]) }}" class="btn light">
                {{ __('pages.export_csv') }}
            </a>

            <a href="{{ route('farm-work-logs.export.excel', [
                'search' => $search,
                'date_from' => $date_from,
                'date_to' => $date_to,
                'tractor_id' => $tractor_id,
                'driver_id' => $driver_id,
                'zone_id' => $zone_id,
                'task_category_id' => $task_category_id,
            ]) }}" class="btn light">
                {{ __('pages.export_excel') }}
            </a>

            <a href="{{ route('dashboard') }}" class="btn gray">
                {{ __('pages.dashboard_button') }}
            </a>

            <a href="{{ route('farm-work-logs.create') }}" class="btn">
                {{ __('pages.add_work_log') }}
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert">{{ session('success') }}</div>
    @endif

    <div class="summary-grid">
        <div class="summary-card">
            <div class="summary-label">{{ __('pages.total_area') }}</div>
            <div class="summary-value">{{ number_format($totalArea, 2) }} ha</div>
        </div>

        <div class="summary-card">
            <div class="summary-label">{{ __('pages.total_hours') }}</div>
            <div class="summary-value">{{ number_format($totalHour, 2) }} hr</div>
        </div>

        <div class="summary-card">
            <div class="summary-label">{{ __('pages.total_diesel') }}</div>
            <div class="summary-value">{{ number_format($totalDiesel, 2) }} L</div>
        </div>

        <div class="summary-card">
            <div class="summary-label">{{ __('pages.records') }}</div>
            <div class="summary-value">{{ number_format($logs->count()) }}</div>
        </div>
    </div>

    <div class="panel">
        <h2 class="panel-title">{{ __('pages.filter') }}</h2>

        <div class="form-grid">
            <div>
                <label>{{ __('pages.search') }}</label>
                <input type="text"
                       wire:model.live="search"
                       placeholder="{{ __('pages.search_placeholder') }}">
            </div>

            <div>
                <label>{{ __('pages.date_from') }}</label>
                <input type="date" wire:model.live="date_from">
            </div>

            <div>
                <label>{{ __('pages.date_to') }}</label>
                <input type="date" wire:model.live="date_to">
            </div>

            <div>
                <label>{{ __('pages.tractor') }}</label>
                <select wire:model.live="tractor_id">
                    <option value="">{{ __('pages.all_tractors') }}</option>
                    @foreach($tractors as $tractor)
                        <option value="{{ $tractor->id }}">{{ $tractor->tractor_no }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label>{{ __('pages.driver') }}</label>
                <select wire:model.live="driver_id">
                    <option value="">{{ __('pages.all_drivers') }}</option>
                    @foreach($drivers as $driver)
                        <option value="{{ $driver->id }}">{{ $driver->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label>{{ __('pages.zone') }}</label>
                <select wire:model.live="zone_id">
                    <option value="">{{ __('pages.all_zones') }}</option>
                    @foreach($zones as $zone)
                        <option value="{{ $zone->id }}">{{ $zone->zone_code }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label>{{ __('pages.task_category') }}</label>
                <select wire:model.live="task_category_id">
                    <option value="">{{ __('pages.all_tasks') }}</option>
                    @foreach($taskCategories as $task)
                        <option value="{{ $task->id }}">{{ $task->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="actions">
            <button wire:click="resetFilter" class="btn light">
                {{ __('pages.reset_filter') }}
            </button>
        </div>
    </div>

    <div class="panel">
        <h2 class="panel-title">{{ __('pages.work_log_list') }}</h2>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>{{ __('pages.date') }}</th>
                        <th>{{ __('pages.tractor') }}</th>
                        <th>{{ __('pages.driver') }}</th>
                        <th>{{ __('pages.zone') }}</th>
                        <th>{{ __('pages.task') }}</th>
                        <th>{{ __('pages.hour') }}</th>
                        <th>{{ __('pages.area') }}</th>
                        <th>{{ __('pages.diesel_start') }}</th>
                        <th>{{ __('pages.diesel_refill') }}</th>
                        <th>{{ __('pages.diesel_end') }}</th>
                        <th>{{ __('pages.diesel_used') }}</th>
                        <th>{{ __('pages.lha') }}</th>
                        <th>{{ __('pages.hahr') }}</th>
                        <th>{{ __('pages.variance') }}</th>
                        <th width="140">{{ __('pages.action') }}</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td>{{ optional($log->work_date)->format('d M Y') }}</td>

                            <td>{{ $log->tractor->tractor_no ?? '-' }}</td>

                            <td>{{ $log->driver->name ?? '-' }}</td>

                            <td>{{ $log->zone->zone_code ?? '-' }}</td>

                            <td>{{ $log->taskCategory->name ?? '-' }}</td>

                            <td>{{ number_format($log->working_duration, 2) }}</td>

                            <td>{{ number_format($log->working_area, 2) }}</td>

                            <td>{{ number_format($log->diesel_start, 2) }}</td>

                            <td>{{ number_format($log->diesel_refill, 2) }}</td>

                            <td>{{ number_format($log->diesel_end, 2) }}</td>

                            <td>
                                <strong>{{ number_format($log->diesel_consumed, 2) }}</strong>
                            </td>

                            <td>{{ number_format($log->diesel_per_hectare, 2) }}</td>

                            <td>{{ number_format($log->hectare_per_hour, 2) }}</td>

                            <td>
                                <strong style="color: {{ $log->variance_fuel < 0 ? '#dc2626' : '#166534' }}">
                                    {{ number_format($log->variance_fuel, 2) }}
                                </strong>
                            </td>

                            <td>
                                <div class="table-actions">
                                    <a href="{{ route('farm-work-logs.map', $log->id) }}" class="mini">
                                        {{ __('pages.map') }}
                                    </a>

                                    <a href="{{ route('farm-work-logs.edit', $log->id) }}" class="mini">
                                        {{ __('pages.edit') }}
                                    </a>

                                    <button wire:click="delete({{ $log->id }})" class="mini danger">
                                        {{ __('pages.delete') }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="15" class="empty">
                                {{ __('pages.no_work_log_found') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>