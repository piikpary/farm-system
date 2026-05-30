<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">

    <style>
        table {
            border-collapse: collapse;
            width: 100%;
        }

        th {
            background: #e5f3e8;
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
        }

        td {
            text-align: center;
            vertical-align: middle;
        }

        .title {
            font-size: 18px;
            font-weight: bold;
            background: #198754;
            color: #ffffff;
        }

        .date-row {
            font-weight: bold;
            background: #f3f4f6;
        }

        .text-left {
            text-align: left;
        }

        .danger {
            color: #dc2626;
            font-weight: bold;
        }

        .success {
            color: #15803d;
            font-weight: bold;
        }
    </style>
</head>

<body>
<table border="1">
    <thead>
        <tr>
            <th colspan="15" class="title">
                {{ __('pages.task_category_summary_report') }}
            </th>
        </tr>

        <tr>
            <th colspan="15" class="date-row">
                {{ __('pages.date') }}:
                {{ $from_date ?: __('pages.all') }}
                -
                {{ $to_date ?: __('pages.all') }}
            </th>
        </tr>

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
        @forelse ($rows as $index => $row)
            <tr>
                <td>{{ $index + 1 }}</td>

                <td class="text-left">
                    {{ $row['task_category'] ?? '-' }}
                </td>

                <td>{{ number_format($row['total_area'] ?? 0, 2) }}</td>
                <td>{{ number_format($row['finish_area'] ?? 0, 2) }}</td>
                <td>{{ number_format($row['remaining_area'] ?? 0, 2) }}</td>

                <td>{{ number_format($row['request_fuel_per_hectare'] ?? 0, 2) }}</td>
                <td>{{ number_format($row['request_fuel'] ?? 0, 2) }}</td>

                <td>{{ number_format($row['consumed_fuel'] ?? 0, 2) }}</td>
                <td>{{ number_format($row['consumed_fuel_per_hectare'] ?? 0, 2) }}</td>

                <td>{{ number_format($row['remaining_fuel'] ?? 0, 2) }}</td>
                <td>{{ number_format($row['remaining_fuel_per_hectare'] ?? 0, 2) }}</td>

                <td class="{{ ($row['variance_fuel'] ?? 0) > 0 ? 'danger' : 'success' }}">
                    {{ number_format($row['variance_fuel'] ?? 0, 2) }}
                </td>

                <td class="{{ ($row['variance_fuel_per_hectare'] ?? 0) > 0 ? 'danger' : 'success' }}">
                    {{ number_format($row['variance_fuel_per_hectare'] ?? 0, 2) }}
                </td>

                <td>{{ number_format($row['total_working_hour'] ?? 0, 2) }}</td>
                <td>{{ number_format($row['hectare_per_hour'] ?? 0, 2) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="15">
                    {{ __('pages.no_report_data_found') }}
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
</body>
</html>