<?php

use Livewire\Component;
use App\Models\FarmWorkLog;

new class extends Component
{
    public function with()
    {
        $rows = FarmWorkLog::with('zone')
            ->get()
            ->groupBy('zone_id')
            ->map(function ($items) {
                $first = $items->first();

                $area = $items->sum('working_area');
                $hours = $items->sum('working_duration');
                $diesel = $items->sum('diesel_consumed');
                $zoneTotalArea = $first->zone->total_area ?? 0;

                return [
                    'zone' => $first->zone->zone_code ?? '-',
                    'total_area' => $zoneTotalArea,
                    'finished_area' => $area,
                    'remaining_area' => max($zoneTotalArea - $area, 0),
                    'hours' => $hours,
                    'diesel' => $diesel,
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

    <div class="page-header">
        <div>
            <h1 class="page-title">{{ __('pages.zone_report') }}</h1>
            <p class="page-subtitle">{{ __('pages.zone_report_subtitle') }}</p>
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
        <h2 class="panel-title">{{ __('pages.zone_progress') }}</h2>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>{{ __('pages.zone') }}</th>
                        <th>{{ __('pages.logs') }}</th>
                        <th>{{ __('pages.total_area') }}</th>
                        <th>{{ __('pages.finished_area') }}</th>
                        <th>{{ __('pages.remaining_area') }}</th>
                        <th>{{ __('pages.total_hours') }}</th>
                        <th>{{ __('pages.total_diesel') }}</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td>{{ $row['zone'] }}</td>
                            <td>{{ $row['logs'] }}</td>
                            <td>{{ number_format($row['total_area'], 2) }}</td>
                            <td>{{ number_format($row['finished_area'], 2) }}</td>
                            <td>{{ number_format($row['remaining_area'], 2) }}</td>
                            <td>{{ number_format($row['hours'], 2) }}</td>
                            <td>{{ number_format($row['diesel'], 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="empty">
                                {{ __('pages.no_zone_report_data_found') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>