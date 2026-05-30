<?php

use Livewire\Component;
use App\Models\FarmWorkLog;
use Illuminate\Support\Facades\DB;

new class extends Component
{
    public $from_date;
    public $to_date;

    public function resetFilter()
    {
        $this->reset(['from_date', 'to_date']);
    }

    public function with()
    {
        $query = FarmWorkLog::with('taskCategory')
            ->when($this->from_date, fn ($q) => $q->whereDate('work_date', '>=', $this->from_date))
            ->when($this->to_date, fn ($q) => $q->whereDate('work_date', '<=', $this->to_date))
            ->get();

        $rows = $query
            ->groupBy('task_category_id')
            ->map(function ($items) {
                $first = $items->first();

                $totalArea = $items->sum('working_area');
                $finishArea = $items->sum('working_area');
                $remainingArea = max($totalArea - $finishArea, 0);

                $requestFuel = $items->sum('request_fuel');
                $consumedFuel = $items->sum('diesel_consumed');
                $remainingFuel = $requestFuel - $consumedFuel;
                $varianceFuel = $consumedFuel - $requestFuel;

                $totalWorkingHour = $items->sum('working_duration');

                return [
                    'task_category' => $first->taskCategory->name ?? '-',
                    'total_area' => $totalArea,
                    'finish_area' => $finishArea,
                    'remaining_area' => $remainingArea,

                    'request_fuel_per_hectare' => $totalArea > 0 ? $requestFuel / $totalArea : 0,
                    'request_fuel' => $requestFuel,

                    'consumed_fuel' => $consumedFuel,
                    'consumed_fuel_per_hectare' => $totalArea > 0 ? $consumedFuel / $totalArea : 0,

                    'remaining_fuel' => $remainingFuel,
                    'remaining_fuel_per_hectare' => $totalArea > 0 ? $remainingFuel / $totalArea : 0,

                    'variance_fuel' => $varianceFuel,
                    'variance_fuel_per_hectare' => $totalArea > 0 ? $varianceFuel / $totalArea : 0,

                    'total_working_hour' => $totalWorkingHour,
                    'hectare_per_hour' => $totalWorkingHour > 0 ? $totalArea / $totalWorkingHour : 0,
                ];
            })
            ->values();

        return [
            'rows' => $rows,
        ];
    }
};

?>

<div class="page">
    @include('components.shared-style')

    <div class="page-header">
        <div>
            <h1 class="page-title">{{ __('pages.task_category_summary_report') }}</h1>
            <p class="page-subtitle">{{ __('pages.task_category_summary_report_subtitle') }}</p>
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

    <div class="panel">
        <div class="form-grid" style="align-items: end;">
            <div>
                <label>{{ __('pages.from_date') }}</label>
                <input type="date" wire:model.live="from_date">
            </div>

            <div>
                <label>{{ __('pages.to_date') }}</label>
                <input type="date" wire:model.live="to_date">
            </div>

            <div>
                <button wire:click="$refresh" class="btn">
                    {{ __('pages.filter') }}
                </button>
            </div>

            <div>
                <button wire:click="resetFilter" class="btn light">
                    {{ __('pages.reset') }}
                </button>
            </div>

            <div>
                <a href="{{ route('reports.task-category-summary.export.excel', [
                    'from_date' => $from_date,
                    'to_date' => $to_date,
                ]) }}" class="btn">
                    {{ __('pages.export_excel') }}
                </a>
            </div>

            <div>
                <a href="{{ route('reports.task-category-summary.export.csv', [
                    'from_date' => $from_date,
                    'to_date' => $to_date,
                ]) }}" class="btn gray">
                    {{ __('pages.export_csv') }}
                </a>
            </div>
        </div>

        <div class="table-wrap" style="margin-top: 18px;">
            <table>
                <thead>
                    <tr>
                        <th>{{ __('pages.no') }}</th>
                        <th>{{ __('pages.task_category') }}</th>
                        <th>{{ __('pages.total_area_hec') }}</th>
                        <th>{{ __('pages.finish_area_hec') }}</th>
                        <th>{{ __('pages.remaining_area_hec') }}</th>
                        <th>{{ __('pages.request_fuel_l_hec') }}</th>
                        <th>{{ __('pages.request_fuel_l') }}</th>
                        <th>{{ __('pages.consumed_fuel_l') }}</th>
                        <th>{{ __('pages.consumed_fuel_l_hec') }}</th>
                        <th>{{ __('pages.remaining_fuel_l') }}</th>
                        <th>{{ __('pages.remaining_fuel_l_hec') }}</th>
                        <th>{{ __('pages.variance_fuel_l') }}</th>
                        <th>{{ __('pages.variance_fuel_l_hec') }}</th>
                        <th>{{ __('pages.total_working_hour_hr') }}</th>
                        <th>{{ __('pages.total_working_hour_hec_hr') }}</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($rows as $index => $row)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $row['task_category'] }}</td>

                            <td>{{ number_format($row['total_area'], 2) }}</td>
                            <td>{{ number_format($row['finish_area'], 2) }}</td>
                            <td>{{ number_format($row['remaining_area'], 2) }}</td>

                            <td>{{ number_format($row['request_fuel_per_hectare'], 2) }}</td>
                            <td>{{ number_format($row['request_fuel'], 2) }}</td>

                            <td>{{ number_format($row['consumed_fuel'], 2) }}</td>
                            <td>{{ number_format($row['consumed_fuel_per_hectare'], 2) }}</td>

                            <td>{{ number_format($row['remaining_fuel'], 2) }}</td>
                            <td>{{ number_format($row['remaining_fuel_per_hectare'], 2) }}</td>

                            <td>
                                <strong style="color: {{ $row['variance_fuel'] > 0 ? '#dc2626' : '#166534' }}">
                                    {{ number_format($row['variance_fuel'], 2) }}
                                </strong>
                            </td>

                            <td>
                                <strong style="color: {{ $row['variance_fuel_per_hectare'] > 0 ? '#dc2626' : '#166534' }}">
                                    {{ number_format($row['variance_fuel_per_hectare'], 2) }}
                                </strong>
                            </td>

                            <td>{{ number_format($row['total_working_hour'], 2) }}</td>
                            <td>{{ number_format($row['hectare_per_hour'], 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="15" class="empty">
                                {{ __('pages.no_report_data_found') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>