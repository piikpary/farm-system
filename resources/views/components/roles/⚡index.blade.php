<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Role;

new class extends Component
{
    use WithPagination;

    public function delete($id)
    {
        Role::findOrFail($id)->delete();

        session()->flash('success', __('pages.role_deleted_success'));

        $this->resetPage();
    }

    public function with()
    {
        return [
            'roles' => Role::latest()->paginate(10),
        ];
    }
};

?>

<div class="page">
    @include('components.shared-style')
    @include('components.toast-alert')

    <div class="page-header">
        <div>
            <h1 class="page-title">{{ __('pages.roles') }}</h1>
            <p class="page-subtitle">{{ __('pages.roles_subtitle') }}</p>
        </div>

        <div class="page-actions">
            {{-- <div class="language-switcher">
                <a href="{{ route('language.switch', 'en') }}"
                   class="lang-btn {{ app()->getLocale() === 'en' ? 'active' : '' }}">
                    EN
                </a>

                <a href="{{ route('language.switch', 'km') }}"
                   class="lang-btn {{ app()->getLocale() === 'km' ? 'active' : '' }}">
                    ខ្មែរ
                </a>
            </div> --}}

            <a href="{{ route('roles.create') }}" class="btn">
                {{ __('pages.add_role') }}
            </a>
        </div>
    </div>


    <div class="panel">
        <h2 class="panel-title">{{ __('pages.role_list') }}</h2>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>{{ __('pages.name') }}</th>
                        <th>{{ __('pages.description') }}</th>
                        <th>{{ __('pages.status') }}</th>
                        <th width="140">{{ __('pages.action') }}</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($roles as $role)
                        <tr>
                            <td>{{ $role->name }}</td>
                            <td>{{ $role->description ?? '-' }}</td>

                            <td>
                                <span class="status {{ $role->status }}">
                                    {{ $role->status === 'active' ? __('pages.active') : __('pages.inactive') }}
                                </span>
                            </td>

                            <td>
                                <div class="table-actions">
                                    <a href="{{ route('roles.edit', $role->id) }}" class="mini">
                                        {{ __('pages.edit') }}
                                    </a>

                                    <button wire:click="delete({{ $role->id }})" class="mini danger">
                                        {{ __('pages.delete') }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="empty">
                                {{ __('pages.no_role_found') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pagination-wrap">
            {{ $roles->links() }}
        </div>
    </div>
</div>