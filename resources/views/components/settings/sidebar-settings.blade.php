<?php

use Livewire\Component;
use App\Models\SidebarMenuSetting;

new class extends Component
{
    public function toggle($id)
    {
        $menu = SidebarMenuSetting::findOrFail($id);

        $menu->update([
            'is_visible' => !$menu->is_visible,
        ]);

        session()->flash('success', __('pages.sidebar_setting_updated_success'));
    }

    public function menuLabel($key)
    {
        return __('sidebar_menu.' . $key);
    }

    public function groupLabel($group)
    {
        return __('sidebar_menu_group.' . $group);
    }

    public function with()
    {
        return [
            'menus' => SidebarMenuSetting::orderBy('menu_group')
                ->orderBy('sort_order')
                ->get(),
        ];
    }
};

?>

<div class="page">
    @include('components.shared-style')
    @include('components.toast-alert')

    <div class="page-header">
        <div>
            <h1 class="page-title">{{ __('pages.sidebar_settings') }}</h1>
            <p class="page-subtitle">{{ __('pages.sidebar_settings_subtitle') }}</p>
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
                {{ __('pages.back') }}
            </a>
        </div>
    </div>

 

    <div class="panel">
        <h2 class="panel-title">{{ __('pages.menu_visibility') }}</h2>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>{{ __('pages.menu') }}</th>
                        <th>{{ __('pages.group') }}</th>
                        <th>{{ __('pages.status') }}</th>
                        <th width="160">{{ __('pages.action') }}</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($menus as $menu)
                        <tr>
                            <td>
                                {{ __('sidebar_menu.' . $menu->menu_key) }}
                            </td>

                            <td>
                                {{ __('sidebar_menu_group.' . $menu->menu_group) }}
                            </td>

                            <td>
                                @if($menu->is_visible)
                                    <span class="status active">{{ __('pages.visible') }}</span>
                                @else
                                    <span class="status inactive">{{ __('pages.hidden') }}</span>
                                @endif
                            </td>

                            <td>
                                <div class="table-actions">
                                    <button wire:click="toggle({{ $menu->id }})"
                                            class="mini {{ $menu->is_visible ? 'danger' : '' }}">
                                        {{ $menu->is_visible ? __('pages.hide') : __('pages.show') }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="empty">
                                {{ __('pages.no_sidebar_setting_found') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>