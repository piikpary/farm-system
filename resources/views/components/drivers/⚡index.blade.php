<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Driver;

new class extends Component
{
    use WithPagination;

    public function delete($id)
    {
        Driver::findOrFail($id)->delete();

        session()->flash('success', __('pages.driver_deleted_success'));

        $this->resetPage();
    }

    public function with()
    {
        return [
            'drivers' => Driver::latest()->paginate(10),
        ];
    }
};

?>

<div class="page">
    @include('components.shared-style')

    <div class="page-header">
        <div>
            <h1 class="page-title">{{ __('pages.drivers') }}</h1>
            <p class="page-subtitle">{{ __('pages.drivers_subtitle') }}</p>
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

            <a href="{{ route('drivers.create') }}" class="btn">
                {{ __('pages.add_driver') }}
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert">{{ session('success') }}</div>
    @endif

    <div class="panel">
        <h2 class="panel-title">{{ __('pages.driver_list') }}</h2>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>{{ __('pages.name') }}</th>
                        <th>{{ __('pages.phone') }}</th>
                        <th>{{ __('pages.id_card') }}</th>
                        <th>{{ __('pages.address') }}</th>
                        <th>{{ __('pages.status') }}</th>
                        <th width="140">{{ __('pages.action') }}</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($drivers as $driver)
                        <tr>
                            <td>{{ $driver->name }}</td>
                            <td>{{ $driver->phone ?? '-' }}</td>
                            <td>{{ $driver->id_card_no ?? '-' }}</td>
                            <td>{{ $driver->address ?? '-' }}</td>

                            <td>
                                <span class="status {{ $driver->status }}">
                                    {{ $driver->status === 'active' ? __('pages.active') : __('pages.inactive') }}
                                </span>
                            </td>

                            <td>
                                <div class="table-actions">
                                    <a href="{{ route('drivers.edit', $driver->id) }}" class="mini">
                                        {{ __('pages.edit') }}
                                    </a>

                                    <button wire:click="delete({{ $driver->id }})" class="mini danger">
                                        {{ __('pages.delete') }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty">
                                {{ __('pages.no_driver_found') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pagination-wrap">
            {{ $drivers->links() }}
        </div>
    </div>
</div>