<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\FuelTransaction;

new class extends Component
{
    use WithPagination;

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

        <div class="table-wrap">
            <table>
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

                            <td>{{ $transaction->fuelStock->name ?? '-' }}</td>

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
                                    <strong style="color:#166534;">
                                        +{{ number_format($transaction->quantity, 2) }} L
                                    </strong>
                                @else
                                    <strong style="color:#dc2626;">
                                        -{{ number_format($transaction->quantity, 2) }} L
                                    </strong>
                                @endif
                            </td>

                            <td>{{ number_format($transaction->balance_after, 2) }} L</td>

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
            </table>
        </div>

        <div class="pagination-wrap">
            {{ $transactions->links() }}
        </div>
    </div>
</div>