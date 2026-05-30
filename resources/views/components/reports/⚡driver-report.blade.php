<?php

use Livewire\Component;
use App\Models\FarmWorkLog;

new class extends Component
{
    public function with()
    {
        $rows = FarmWorkLog::with('driver')
            ->get()
            ->groupBy('driver_id')
            ->map(function ($items) {
                $first = $items->first();

                $area = $items->sum('working_area');
                $hours = $items->sum('working_duration');
                $diesel = $items->sum('diesel_consumed');

                return [
                    'driver' => $first->driver->name ?? '-',
                    'area' => $area,
                    'hours' => $hours,
                    'diesel' => $diesel,
                    'diesel_per_ha' => $area > 0 ? $diesel / $area : 0,
                    'ha_per_hr' => $hours > 0 ? $area / $hours : 0,
                    'logs' => $items->count(),
                ];
            })
            ->values();

        return ['rows' => $rows];
    }
};

?>

<div class="page">
    @include('components.shared-style')
    @include('components.toast-alert')

    <div class="page-header">
        <div>
            <h1 class="page-title">{{ __('pages.driver_report') }}</h1>
            <p class="page-subtitle">{{ __('pages.driver_report_subtitle') }}</p>
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
        <h2 class="panel-title">{{ __('pages.driver_performance') }}</h2>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>{{ __('pages.driver') }}</th>
                        <th>{{ __('pages.logs') }}</th>
                        <th>{{ __('pages.total_area') }}</th>
                        <th>{{ __('pages.total_hours') }}</th>
                        <th>{{ __('pages.total_diesel') }}</th>
                        <th>{{ __('pages.diesel_per_hectare') }}</th>
                        <th>{{ __('pages.hectare_per_hour') }}</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td>{{ $row['driver'] }}</td>
                            <td>{{ $row['logs'] }}</td>
                            <td>{{ number_format($row['area'], 2) }}</td>
                            <td>{{ number_format($row['hours'], 2) }}</td>
                            <td>{{ number_format($row['diesel'], 2) }}</td>
                            <td>{{ number_format($row['diesel_per_ha'], 2) }}</td>
                            <td>{{ number_format($row['ha_per_hr'], 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="empty">
                                {{ __('pages.no_driver_report_data_found') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>