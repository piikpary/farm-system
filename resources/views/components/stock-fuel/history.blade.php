<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\FuelTransaction;


new class extends Component
{
    use WithPagination;

    public function getTotalStockInProperty()
    {
        return FuelTransaction::where('type', 'stock_in')->sum('quantity');
    }

    public function getTotalStockOutProperty()
    {
        return FuelTransaction::whereIn('type', [
            'stock_out',
            'refill_to_tractor',
        ])->sum('quantity');
    }

    public function getNetFuelProperty()
    {
        return $this->totalStockIn - $this->totalStockOut;
    }

    public function getLatestBalanceProperty()
    {
        return FuelTransaction::latest()->value('balance_after') ?? 0;
    }

    public function with()
    {
        return [
            'transactions' => FuelTransaction::with([
                    'fuelStock',
                    'tractor',
                    'farmWorkLog',
                    'creator',
                ])
                ->latest()
                ->paginate(15),
        ];
    }
};

?>

<div class="page">
    @include('components.shared-style')
    @include('components.toast-alert')

    <style>
        .excel-table-wrap {
            overflow-x: auto;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
        }

        .excel-table {
            min-width: 1450px;
            width: 100%;
            border-collapse: collapse;
            background: #ffffff;
        }

        .excel-table th {
            background: #f8fafc;
            color: #0f172a;
            font-size: 12px;
            font-weight: 900;
            text-transform: uppercase;
            padding: 12px 10px;
            border-bottom: 1px solid #e5e7eb;
            white-space: nowrap;
        }

        .excel-table td {
            padding: 14px 10px;
            border-bottom: 1px solid #eef2f7;
            vertical-align: middle;
            white-space: nowrap;
        }

        .fuel-total-row {
            background: #f8fafc;
            font-weight: 900;
            border-top: 2px solid #d1d5db;
        }

        .fuel-total-row td {
            border-bottom: 0;
            color: #0f172a;
        }

        .total-label {
            text-align: right;
            font-weight: 900;
        }

        .stock-in {
            color: #15803d;
            font-weight: 900;
        }

        .stock-out {
            color: #dc2626;
            font-weight: 900;
        }

        .net-fuel {
            color: #0f172a;
            font-weight: 900;
        }

        .balance {
            color: #166534;
            font-weight: 900;
        }
    </style>

    <div class="page-header">
        <div>
            <h1 class="page-title">{{ __('pages.fuel_history_title') }}</h1>
            <p class="page-subtitle">{{ __('pages.fuel_history_subtitle') }}</p>
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

            <a href="{{ route('stock-fuel.index') }}" class="btn gray">
                {{ __('pages.back_to_stock') }}
            </a>

            <a href="{{ route('stock-fuel.create') }}" class="btn">
                {{ __('pages.add_fuel_stock') }}
            </a>
        </div>
    </div>

    <div class="panel">
        <h2 class="panel-title">{{ __('pages.fuel_transaction_list') }}</h2>

        <div class="excel-table-wrap">
            <table class="excel-table">
                <thead>
                    <tr>
                        <th>{{ __('pages.date') }}</th>
                        <th>{{ __('pages.stock') }}</th>
                        <th>{{ __('pages.type') }}</th>
                        <th>{{ __('pages.tractor') }}</th>
                        <th>{{ __('pages.work_log') }}</th>
                        <th>{{ __('pages.quantity') }}</th>
                        <th>{{ __('pages.balance_after') }}</th>
                        <th>{{ __('pages.reference') }}</th>
                        <th>{{ __('pages.created_by') }}</th>
                        <th>{{ __('pages.note') }}</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($transactions as $transaction)
                        <tr>
                            <td>{{ optional($transaction->transaction_date)->format('d M Y') }}</td>

                            <td>{{ $transaction->fuelStock->stock_name ?? '-' }}</td>

                            <td>
                                @if($transaction->type === 'stock_in')
                                    <span class="status active">
                                        {{ __('pages.stock_in') }}
                                    </span>
                                @elseif($transaction->type === 'refill_to_tractor')
                                    <span class="status inactive">
                                        {{ __('pages.used_by_work_log') }}
                                    </span>
                                @elseif($transaction->type === 'stock_out')
                                    <span class="status inactive">
                                        {{ __('pages.stock_out') }}
                                    </span>
                                @else
                                    <span class="status pending">
                                        {{ __('pages.adjustment') }}
                                    </span>
                                @endif
                            </td>

                            <td>{{ $transaction->tractor->tractor_no ?? '-' }}</td>

                            <td>
                                @if($transaction->farm_work_log_id)
                                    #{{ $transaction->farm_work_log_id }}
                                @else
                                    -
                                @endif
                            </td>

                            <td>
                                @if($transaction->type === 'stock_in')
                                    <strong class="stock-in">
                                        +{{ number_format($transaction->quantity, 2) }} L
                                    </strong>
                                @else
                                    <strong class="stock-out">
                                        -{{ number_format($transaction->quantity, 2) }} L
                                    </strong>
                                @endif
                            </td>

                            <td class="balance">
                                {{ number_format($transaction->balance_after, 2) }} L
                            </td>

                            <td>{{ $transaction->reference_no ?? '-' }}</td>

                            <td>{{ $transaction->creator->name ?? '-' }}</td>

                            <td>{{ $transaction->note ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="empty">
                                {{ __('pages.no_fuel_transaction_found') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>

                <tfoot>
                    <tr class="fuel-total-row">
                        <td colspan="5" class="total-label">
                            Total
                        </td>

                        <td>
                            In:
                            <span class="stock-in">
                                {{ number_format((float) $this->totalStockIn, 2) }} L
                            </span>
                            /
                            Out:
                            <span class="stock-out">
                                {{ number_format((float) $this->totalStockOut, 2) }} L
                            </span>
                        </td>

                        <td class="balance">
                            {{ number_format((float) $this->latestBalance, 2) }} L
                        </td>

                        <td>
                            Net:
                            <span class="net-fuel">
                                {{ number_format((float) $this->netFuel, 2) }} L
                            </span>
                        </td>

                        <td>-</td>
                        <td>-</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="pagination-wrap">
            {{ $transactions->links() }}
        </div>
    </div>
</div>