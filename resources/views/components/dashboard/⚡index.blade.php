<?php

use Livewire\Component;
use App\Models\FarmWorkLog;
use App\Models\Tractor;
use App\Models\Driver;
use App\Models\Zone;
use App\Models\TaskCategory;

new class extends Component
{
    public function with()
    {
        $totalArea = FarmWorkLog::sum('working_area');
        $totalHours = FarmWorkLog::sum('working_duration');
        $totalDiesel = FarmWorkLog::sum('diesel_consumed');

        $dailyLogs = FarmWorkLog::selectRaw('DATE(work_date) as date')
            ->selectRaw('SUM(working_area) as total_area')
            ->selectRaw('SUM(working_duration) as total_hours')
            ->selectRaw('SUM(diesel_consumed) as total_diesel')
            ->whereDate('work_date', '>=', now()->subDays(14))
            ->groupByRaw('DATE(work_date)')
            ->orderByRaw('DATE(work_date)')
            ->get();

        return [
            'totalArea' => $totalArea,
            'totalHours' => $totalHours,
            'totalDiesel' => $totalDiesel,
            'avgDieselPerHectare' => $totalArea > 0 ? $totalDiesel / $totalArea : 0,
            'avgHectarePerHour' => $totalHours > 0 ? $totalArea / $totalHours : 0,
            'todayLogs' => FarmWorkLog::whereDate('work_date', now())->count(),
            'totalTractors' => Tractor::count(),
            'totalDrivers' => Driver::count(),
            'totalZones' => Zone::count(),
            'totalTasks' => TaskCategory::count(),

            'chartLabels' => $dailyLogs->map(fn ($row) => \Carbon\Carbon::parse($row->date)->format('d M'))->values(),
            'chartArea' => $dailyLogs->map(fn ($row) => round((float) $row->total_area, 2))->values(),
            'chartDiesel' => $dailyLogs->map(fn ($row) => round((float) $row->total_diesel, 2))->values(),
            'chartHours' => $dailyLogs->map(fn ($row) => round((float) $row->total_hours, 2))->values(),
        ];
    }
};

?>

<div class="page">
    @include('components.shared-style')

    <div class="page-header">
        <div>
            <h1 class="page-title">{{ __('pages.dashboard') }}</h1>
            <p class="page-subtitle">{{ __('pages.dashboard_subtitle') }}</p>
        </div>

        <a href="{{ route('farm-work-logs.create') }}" class="btn">
            {{ __('pages.add_work_log') }}
        </a>
    </div>

    <div class="summary-grid">
        <div class="summary-card">
            <div class="summary-label">{{ __('pages.total_working_area') }}</div>
            <div class="summary-value">{{ number_format($totalArea, 2) }} ha</div>
        </div>

        <div class="summary-card">
            <div class="summary-label">{{ __('pages.total_working_hours') }}</div>
            <div class="summary-value">{{ number_format($totalHours, 2) }} hr</div>
        </div>

        <div class="summary-card">
            <div class="summary-label">{{ __('pages.diesel_consumed') }}</div>
            <div class="summary-value">{{ number_format($totalDiesel, 2) }} L</div>
        </div>

        <div class="summary-card">
            <div class="summary-label">{{ __('pages.today_work_logs') }}</div>
            <div class="summary-value">{{ number_format($todayLogs) }}</div>
        </div>

        <div class="summary-card">
            <div class="summary-label">{{ __('pages.average_diesel_ha') }}</div>
            <div class="summary-value">{{ number_format($avgDieselPerHectare, 2) }}</div>
        </div>

        <div class="summary-card">
            <div class="summary-label">{{ __('pages.average_ha_hr') }}</div>
            <div class="summary-value">{{ number_format($avgHectarePerHour, 2) }}</div>
        </div>

        <div class="summary-card">
            <div class="summary-label">{{ __('pages.tractors_drivers') }}</div>
            <div class="summary-value">{{ $totalTractors }} / {{ $totalDrivers }}</div>
        </div>

        <div class="summary-card">
            <div class="summary-label">{{ __('pages.zones_tasks') }}</div>
            <div class="summary-value">{{ $totalZones }} / {{ $totalTasks }}</div>
        </div>
    </div>

    <div class="panel">
        <div class="chart-head">
            <div>
                <h2 class="panel-title">{{ __('pages.work_performance_chart') }}</h2>
                <p class="chart-subtitle">{{ __('pages.work_performance_chart_subtitle') }}</p>
            </div>
        </div>

        <div class="chart-box">
            <canvas id="workPerformanceChart"></canvas>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const chartElement = document.getElementById('workPerformanceChart');

            if (!chartElement) {
                return;
            }

            const labels = @json($chartLabels);
            const areaData = @json($chartArea);
            const dieselData = @json($chartDiesel);
            const hourData = @json($chartHours);

            new Chart(chartElement, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: '{{ __("pages.area") }}',
                            data: areaData,
                            borderColor: '#16a34a',
                            backgroundColor: 'rgba(22, 163, 74, 0.12)',
                            tension: 0.35,
                            fill: true,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                        },
                        {
                            label: '{{ __("pages.diesel") }}',
                            data: dieselData,
                            borderColor: '#2563eb',
                            backgroundColor: 'rgba(37, 99, 235, 0.10)',
                            tension: 0.35,
                            fill: false,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                        },
                        {
                            label: '{{ __("pages.hour") }}',
                            data: hourData,
                            borderColor: '#f59e0b',
                            backgroundColor: 'rgba(245, 158, 11, 0.10)',
                            tension: 0.35,
                            fill: false,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                usePointStyle: true,
                                boxWidth: 8,
                                font: {
                                    weight: '700',
                                }
                            }
                        },
                        tooltip: {
                            backgroundColor: '#0f172a',
                            padding: 12,
                            cornerRadius: 10,
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false,
                            },
                            ticks: {
                                font: {
                                    weight: '700',
                                }
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: '#e5e7eb',
                            },
                            ticks: {
                                font: {
                                    weight: '700',
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>
</div>