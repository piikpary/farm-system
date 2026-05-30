<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\FuelStock;
use App\Models\FuelTransaction;

new class extends Component
{
    use WithPagination;

    public function with()
    {
        $totalCurrentStock = FuelStock::where('status', 'active')->sum('current_stock');

        $totalStockIn = FuelTransaction::where('type', 'stock_in')->sum('quantity');

        $totalStockOut = FuelTransaction::whereIn('type', [
            'stock_out',
            'refill_to_tractor',
        ])->sum('quantity');

        $lowStockCount = FuelStock::where('status', 'active')
            ->whereColumn('current_stock', '<=', 'minimum_stock_alert')
            ->count();

        return [
            'fuelStocks' => FuelStock::latest()->paginate(10),
            'totalCurrentStock' => $totalCurrentStock,
            'totalStockIn' => $totalStockIn,
            'totalStockOut' => $totalStockOut,
            'lowStockCount' => $lowStockCount,
        ];
    }
};

?>

<div class="page">
    @include('components.shared-style')

    <div class="page-header">
        <div>
            <h1 class="page-title">{{ __('pages.stock_fuel') }}</h1>
            <p class="page-subtitle">{{ __('pages.stock_fuel_subtitle') }}</p>
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

            <a href="{{ route('stock-fuel.create') }}" class="btn">
                {{ __('pages.add_adjust_fuel') }}
            </a>

            <a href="{{ route('stock-fuel.history') }}" class="btn gray">
                {{ __('pages.fuel_history') }}
            </a>
        </div>
    </div>

    <div class="summary-grid">
        <div class="summary-card">
            <div class="summary-label">{{ __('pages.current_fuel_stock') }}</div>
            <div class="summary-value">{{ number_format($totalCurrentStock, 2) }} L</div>
        </div>

        <div class="summary-card">
            <div class="summary-label">{{ __('pages.total_stock_in') }}</div>
            <div class="summary-value">{{ number_format($totalStockIn, 2) }} L</div>
        </div>

        <div class="summary-card">
            <div class="summary-label">{{ __('pages.total_stock_out') }}</div>
            <div class="summary-value">{{ number_format($totalStockOut, 2) }} L</div>
        </div>

        <div class="summary-card">
            <div class="summary-label">{{ __('pages.low_stock_alert') }}</div>
            <div class="summary-value" style="color: {{ $lowStockCount > 0 ? '#dc2626' : '#166534' }}">
                {{ number_format($lowStockCount) }}
            </div>
        </div>
    </div>

    <div class="panel">
        <h2 class="panel-title">{{ __('pages.fuel_stock_list') }}</h2>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>{{ __('pages.stock_name') }}</th>
                        <th>{{ __('pages.opening_stock') }}</th>
                        <th>{{ __('pages.current_stock') }}</th>
                        <th>{{ __('pages.minimum_alert') }}</th>
                        <th>{{ __('pages.status') }}</th>
                        <th width="110">{{ __('pages.action') }}</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($fuelStocks as $stock)
                        <tr>
                            <td>{{ $stock->name }}</td>

                            <td>{{ number_format($stock->opening_stock, 2) }} L</td>

                            <td>
                                <strong style="color: {{ $stock->current_stock <= $stock->minimum_stock_alert ? '#dc2626' : '#166534' }}">
                                    {{ number_format($stock->current_stock, 2) }} L
                                </strong>

                                @if($stock->current_stock <= $stock->minimum_stock_alert)
                                    <span class="status inactive" style="margin-left: 6px;">
                                        {{ __('pages.low') }}
                                    </span>
                                @endif
                            </td>

                            <td>{{ number_format($stock->minimum_stock_alert, 2) }} L</td>

                            <td>
                                <span class="status {{ $stock->status }}">
                                    {{ $stock->status === 'active' ? __('pages.active') : __('pages.inactive') }}
                                </span>
                            </td>

                            <td>
                                <div class="table-actions">
                                    <a href="{{ route('stock-fuel.edit', $stock->id) }}" class="mini">
                                        {{ __('pages.edit') }}
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty">
                                {{ __('pages.no_fuel_stock_found') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pagination-wrap">
            {{ $fuelStocks->links() }}
        </div>
    </div>
</div>