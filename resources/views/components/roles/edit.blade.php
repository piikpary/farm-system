<?php

use Livewire\Component;
use App\Models\Role;
use App\Models\Permission;

new class extends Component
{
    public $roleId;
    public $name;
    public $description;
    public $status = 'active';
    public $permission_ids = [];

    public function mount($role)
    {
        $role = Role::with('permissions')->findOrFail($role);

        $this->roleId = $role->id;
        $this->name = $role->name;
        $this->description = $role->description;
        $this->status = $role->status;

        $this->permission_ids = $role->permissions
            ->pluck('id')
            ->toArray();
    }

    public function update()
    {
        if (!auth()->user()->hasPermission('roles.edit')) {
            abort(403, 'Permission denied.');
        }

        $this->validate([
            'name' => 'required|string|max:150|unique:roles,name,' . $this->roleId,
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
            'permission_ids' => 'nullable|array',
            'permission_ids.*' => 'exists:permissions,id',
        ]);

        $role = Role::findOrFail($this->roleId);

        $role->update([
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
        ]);

        if (auth()->user()->hasPermission('roles.assign_permissions')) {
            $role->permissions()->sync($this->permission_ids);
        }

        session()->flash('success', __('pages.role_updated_success'));

        return redirect()->route('roles.index');
    }

    public function toggleGroup($group)
    {
        if (!auth()->user()->hasPermission('roles.assign_permissions')) {
            abort(403, 'Permission denied.');
        }

        $permissions = Permission::where('group_name', $group)
            ->where('status', 'active')
            ->pluck('id')
            ->toArray();

        $allSelected = collect($permissions)
            ->every(fn ($id) => in_array($id, $this->permission_ids));

        if ($allSelected) {
            $this->permission_ids = array_values(array_diff($this->permission_ids, $permissions));
        } else {
            $this->permission_ids = array_values(array_unique(array_merge($this->permission_ids, $permissions)));
        }
    }

    public function selectAll()
    {
        if (!auth()->user()->hasPermission('roles.assign_permissions')) {
            abort(403, 'Permission denied.');
        }

        $this->permission_ids = Permission::where('status', 'active')
            ->pluck('id')
            ->toArray();
    }

    public function clearAll()
    {
        if (!auth()->user()->hasPermission('roles.assign_permissions')) {
            abort(403, 'Permission denied.');
        }

        $this->permission_ids = [];
    }

    public function with()
    {
        return [
            'permissionGroups' => Permission::where('status', 'active')
                ->orderBy('group_name')
                ->orderBy('name')
                ->get()
                ->groupBy('group_name'),
        ];
    }
};

?>

<div class="page">
    @include('components.shared-style')
    @include('components.toast-alert')

    <div class="page-header">
        <div>
            <h1 class="page-title">{{ __('pages.edit_role') }}</h1>
            <p class="page-subtitle">{{ __('pages.edit_role_subtitle') }}</p>
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

            <a href="{{ route('roles.index') }}" class="btn gray">
                {{ __('pages.back') }}
            </a>
        </div>
    </div>

    <div class="panel">
        <h2 class="panel-title">{{ __('pages.role_information') }}</h2>

        <div class="form-grid">
            <div>
                <label>{{ __('pages.name') }} *</label>
                <input type="text" wire:model="name" placeholder="{{ __('pages.role_name') }}">
                @error('name') <small>{{ $message }}</small> @enderror
            </div>

            <div>
                <label>{{ __('pages.status') }}</label>
                <select wire:model="status">
                    <option value="active">{{ __('pages.active') }}</option>
                    <option value="inactive">{{ __('pages.inactive') }}</option>
                </select>
                @error('status') <small>{{ $message }}</small> @enderror
            </div>

            <div style="grid-column: 1 / -1;">
                <label>{{ __('pages.description') }}</label>
                <textarea wire:model="description" placeholder="{{ __('pages.description') }}"></textarea>
                @error('description') <small>{{ $message }}</small> @enderror
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="page-header" style="padding:0; margin-bottom:16px;">
            <div>
                <h2 class="panel-title">{{ __('pages.permissions') }}</h2>
                <p class="page-subtitle">{{ __('pages.permissions_subtitle') }}</p>
            </div>

            @if(auth()->user()->hasPermission('roles.assign_permissions'))
                <div class="page-actions">
                    <button type="button" wire:click="selectAll" class="btn light">
                        {{ __('pages.select_all') }}
                    </button>

                    <button type="button" wire:click="clearAll" class="btn gray">
                        {{ __('pages.clear_all') }}
                    </button>
                </div>
            @endif
        </div>

        @error('permission_ids') <small>{{ $message }}</small> @enderror

        <div class="permission-groups">
            @foreach($permissionGroups as $group => $permissions)
                <div class="permission-card">
                    <div class="permission-card-head">
                        <h3>{{ __('permission_groups.' . $group) }}</h3>

                        @if(auth()->user()->hasPermission('roles.assign_permissions'))
                            <button type="button"
                                    wire:click="toggleGroup('{{ $group }}')"
                                    class="mini">
                                {{ __('pages.toggle_group') }}
                            </button>
                        @endif
                    </div>

                    <div class="permission-list">
                        @foreach($permissions as $permission)
                            <label class="permission-item">
                                <input type="checkbox"
                                       wire:model="permission_ids"
                                       value="{{ $permission->id }}"
                                       @disabled(!auth()->user()->hasPermission('roles.assign_permissions'))>

                                <span>
                                    {{ __('permissions.' . $permission->permission_key) }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        <div class="btn-row">
            <button wire:click="update" class="btn">
                {{ __('pages.update_role') }}
            </button>

            <a href="{{ route('roles.index') }}" class="btn gray">
                {{ __('pages.cancel') }}
            </a>
        </div>
    </div>
</div>

<style>
    .permission-groups {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
    }

    .permission-card {
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        background: #fff;
        overflow: hidden;
    }

    .permission-card-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 12px 14px;
        background: #f8fafc;
        border-bottom: 1px solid #e5e7eb;
    }

    .permission-card-head h3 {
        margin: 0;
        font-size: 14px;
        font-weight: 900;
        color: #0f172a;
        text-transform: capitalize;
    }

    .permission-list {
        padding: 12px 14px;
        display: grid;
        gap: 10px;
    }

    .permission-item {
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 700;
        color: #334155;
        cursor: pointer;
    }

    .permission-item input {
        width: 16px;
        height: 16px;
    }

    @media (max-width: 900px) {
        .permission-groups {
            grid-template-columns: 1fr;
        }
    }
</style>