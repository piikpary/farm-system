<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Zone;

new class extends Component
{
    use WithPagination;

    public function delete($id)
    {
        Zone::findOrFail($id)->delete();

        session()->flash('success', __('pages.zone_deleted_success'));

        $this->resetPage();
    }

    public function with()
    {
        return [
            'zones' => Zone::latest()->paginate(10),
        ];
    }
};

?>

<div class="page">
    @include('components.shared-style')

    <div class="page-header">
        <div>
            <h1 class="page-title">{{ __('pages.zones') }}</h1>
            <p class="page-subtitle">{{ __('pages.zones_subtitle') }}</p>
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

            <a href="{{ route('zones.create') }}" class="btn">
                {{ __('pages.add_zone') }}
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert">{{ session('success') }}</div>
    @endif

    <div class="panel">
        <h2 class="panel-title">{{ __('pages.zone_list') }}</h2>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>{{ __('pages.zone_code') }}</th>
                        <th>{{ __('pages.name') }}</th>
                        <th>{{ __('pages.total_area') }}</th>
                        <th>{{ __('pages.location_note') }}</th>
                        <th>{{ __('pages.status') }}</th>
                        <th width="140">{{ __('pages.action') }}</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($zones as $zone)
                        <tr>
                            <td>{{ $zone->zone_code }}</td>
                            <td>{{ $zone->name ?? '-' }}</td>
                            <td>{{ number_format($zone->total_area ?? 0, 2) }} ha</td>
                            <td>{{ $zone->location_note ?? '-' }}</td>

                            <td>
                                <span class="status {{ $zone->status }}">
                                    {{ $zone->status === 'active' ? __('pages.active') : __('pages.inactive') }}
                                </span>
                            </td>

                            <td>
                                <div class="table-actions">
                                    <a href="{{ route('zones.map', $zone->id) }}" class="mini">
                                        {{ __('pages.map') }}
                                    </a>

                                    <a href="{{ route('zones.edit', $zone->id) }}" class="mini">
                                        {{ __('pages.edit') }}
                                    </a>

                                    <button wire:click="delete({{ $zone->id }})" class="mini danger">
                                        {{ __('pages.delete') }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty">
                                {{ __('pages.no_zone_found') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pagination-wrap">
            {{ $zones->links() }}
        </div>
    </div>
</div>