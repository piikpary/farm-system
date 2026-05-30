<?php

use Livewire\Component;
use App\Models\FarmWorkLog;
use App\Models\Tractor;
use App\Models\TaskCategory;

new class extends Component
{
    public $date_from;
    public $date_to;
    public $tractor_id = '';
    public $task_category_id = '';

    public function resetFilter()
    {
        $this->reset(['date_from', 'date_to', 'tractor_id', 'task_category_id']);
    }

    public function with()
    {
        $logs = FarmWorkLog::with(['tractor', 'driver', 'zone', 'taskCategory'])
            ->when($this->date_from, fn ($q) => $q->whereDate('work_date', '>=', $this->date_from))
            ->when($this->date_to, fn ($q) => $q->whereDate('work_date', '<=', $this->date_to))
            ->when($this->tractor_id, fn ($q) => $q->where('tractor_id', $this->tractor_id))
            ->when($this->task_category_id, fn ($q) => $q->where('task_category_id', $this->task_category_id))
            ->latest()
            ->get();

        return [
            'logs' => $logs,
            'tractors' => Tractor::orderBy('tractor_no')->get(),
            'taskCategories' => TaskCategory::orderBy('name')->get(),
            'totalRequestFuel' => $logs->sum('request_fuel'),
            'totalConsumedFuel' => $logs->sum('diesel_consumed'),
            'totalVarianceFuel' => $logs->sum('variance_fuel'),
            'totalArea' => $logs->sum('working_area'),
        ];
    }
};

?>

<div class="page">
    @include('components.shared-style')
    @include('components.toast-alert')

    <div class="page-header">
        <div>
            <h1 class="page-title">{{ __('pages.fuel_report') }}</h1>
            <p class="page-subtitle">{{ __('pages.fuel_report_subtitle') }}</p>
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

    <div class="summary-grid">
        <div class="summary-card">
            <div class="summary-label">{{ __('pages.total_area') }}</div>
            <div class="summary-value">{{ number_format($totalArea, 2) }} ha</div>
        </div>

        <div class="summary-card">
            <div class="summary-label">{{ __('pages.request_fuel') }}</div>
            <div class="summary-value">{{ number_format($totalRequestFuel, 2) }} L</div>
        </div>

        <div class="summary-card">
            <div class="summary-label">{{ __('pages.consumed_fuel') }}</div>
            <div class="summary-value">{{ number_format($totalConsumedFuel, 2) }} L</div>
        </div>

        <div class="summary-card">
            <div class="summary-label">{{ __('pages.variance_fuel') }}</div>
            <div class="summary-value" style="color: {{ $totalVarianceFuel < 0 ? '#dc2626' : '#166534' }}">
                {{ number_format($totalVarianceFuel, 2) }} L
            </div>
        </div>
    </div>

    <div class="panel">
        <h2 class="panel-title">{{ __('pages.filter') }}</h2>

        <div class="form-grid">
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
        <h2 class="panel-title">{{ __('pages.fuel_detail') }}</h2>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>{{ __('pages.date') }}</th>
                        <th>{{ __('pages.tractor') }}</th>
                        <th>{{ __('pages.task') }}</th>
                        <th>{{ __('pages.area') }}</th>
                        <th>{{ __('pages.request_lha') }}</th>
                        <th>{{ __('pages.request_fuel') }}</th>
                        <th>{{ __('pages.consumed') }}</th>
                        <th>{{ __('pages.consumed_lha') }}</th>
                        <th>{{ __('pages.variance') }}</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td>{{ optional($log->work_date)->format('d M Y') }}</td>
                            <td>{{ $log->tractor->tractor_no ?? '-' }}</td>
                            <td>{{ $log->taskCategory->name ?? '-' }}</td>
                            <td>{{ number_format($log->working_area, 2) }}</td>
                            <td>{{ number_format($log->request_fuel_per_hectare, 2) }}</td>
                            <td>{{ number_format($log->request_fuel, 2) }}</td>
                            <td>{{ number_format($log->diesel_consumed, 2) }}</td>
                            <td>{{ number_format($log->diesel_per_hectare, 2) }}</td>
                            <td>
                                <strong style="color: {{ $log->variance_fuel < 0 ? '#dc2626' : '#166534' }}">
                                    {{ number_format($log->variance_fuel, 2) }}
                                </strong>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="empty">
                                {{ __('pages.no_fuel_report_data_found') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>