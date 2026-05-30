<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
</head>

<body>
<table border="1">
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
            <th>{{ __('pages.note') }}</th>
        </tr>
    </thead>

    <tbody>
        @forelse($logs as $log)
            <tr>
                <td>{{ optional($log->work_date)->format('d M Y') }}</td>
                <td>{{ $log->tractor->tractor_no ?? '' }}</td>
                <td>{{ $log->driver->name ?? '' }}</td>
                <td>{{ $log->zone->zone_code ?? '' }}</td>
                <td>{{ $log->taskCategory->name ?? '' }}</td>
                <td>{{ number_format($log->working_duration, 2) }}</td>
                <td>{{ number_format($log->working_area, 2) }}</td>
                <td>{{ number_format($log->diesel_start, 2) }}</td>
                <td>{{ number_format($log->diesel_refill, 2) }}</td>
                <td>{{ number_format($log->diesel_end, 2) }}</td>
                <td>{{ number_format($log->diesel_consumed, 2) }}</td>
                <td>{{ number_format($log->diesel_per_hectare, 2) }}</td>
                <td>{{ number_format($log->hectare_per_hour, 2) }}</td>
                <td>{{ number_format($log->variance_fuel, 2) }}</td>
                <td>{{ $log->note ?? '' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="15">
                    {{ __('pages.no_work_log_found') }}
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
</body>
</html>