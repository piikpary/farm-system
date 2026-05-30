<?php

use Livewire\Component;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

new class extends Component
{
    public $role_id;
    public $name;
    public $email;
    public $phone;
    public $password;
    public $status = 'active';
    public $editingId = null;

    public function save()
    {
        $rules = [
            'role_id' => 'nullable|exists:roles,id',
            'name' => 'required|string|max:150',
            'email' => 'nullable|email|max:150|unique:users,email,' . $this->editingId,
            'phone' => 'nullable|string|max:50|unique:users,phone,' . $this->editingId,
            'status' => 'required|in:active,inactive',
        ];

        if (!$this->editingId) {
            $rules['password'] = 'required|string|min:8';
        } else {
            $rules['password'] = 'nullable|string|min:8';
        }

        $this->validate($rules);

        $data = [
            'role_id' => $this->role_id ?: null,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'status' => $this->status,
        ];

        if ($this->password) {
            $data['password'] = Hash::make($this->password);
        }

        User::updateOrCreate(
            ['id' => $this->editingId],
            $data
        );

        session()->flash(
            'success',
            $this->editingId
                ? __('pages.user_updated_success')
                : __('pages.user_created_success')
        );

        $this->resetForm();
    }

    public function edit($id)
    {
        $user = User::findOrFail($id);

        $this->editingId = $user->id;
        $this->role_id = $user->role_id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = $user->phone;
        $this->status = $user->status;
        $this->password = null;
    }

    public function delete($id)
    {
        if (auth()->id() == $id) {
            session()->flash('success', __('pages.cannot_delete_own_account'));
            return;
        }

        User::findOrFail($id)->delete();

        session()->flash('success', __('pages.user_deleted_success'));
    }

    public function resetForm()
    {
        $this->reset([
            'role_id',
            'name',
            'email',
            'phone',
            'password',
            'editingId',
        ]);

        $this->status = 'active';
    }

    public function with()
    {
        return [
            'users' => User::with('role')->latest()->get(),
            'roles' => Role::where('status', 'active')->orderBy('name')->get(),
        ];
    }
};

?>

<div class="page">
    @include('components.shared-style')

    <div class="page-header">
        <div>
            <h1 class="page-title">{{ __('pages.users') }}</h1>
            <p class="page-subtitle">{{ __('pages.users_subtitle') }}</p>
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
                {{ __('pages.dashboard_button') }}
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert">{{ session('success') }}</div>
    @endif

    <div class="panel">
        <h2 class="panel-title">
            {{ $editingId ? __('pages.edit_user') : __('pages.add_user') }}
        </h2>

        <div class="form-grid">
            <div>
                <label>{{ __('pages.role') }}</label>
                <select wire:model="role_id">
                    <option value="">{{ __('pages.no_role') }}</option>

                    @foreach($roles as $role)
                        <option value="{{ $role->id }}">{{ $role->name }}</option>
                    @endforeach
                </select>
                @error('role_id') <small>{{ $message }}</small> @enderror
            </div>

            <div>
                <label>{{ __('pages.name') }} *</label>
                <input type="text"
                       wire:model="name"
                       placeholder="{{ __('pages.user_name') }}">
                @error('name') <small>{{ $message }}</small> @enderror
            </div>

            <div>
                <label>{{ __('pages.email') }}</label>
                <input type="email"
                       wire:model="email"
                       placeholder="user@example.com">
                @error('email') <small>{{ $message }}</small> @enderror
            </div>

            <div>
                <label>{{ __('pages.phone') }}</label>
                <input type="text"
                       wire:model="phone"
                       placeholder="{{ __('pages.phone') }}">
                @error('phone') <small>{{ $message }}</small> @enderror
            </div>

            <div>
                <label>
                    {{ __('pages.password') }}
                    {{ $editingId ? __('pages.leave_empty_keep_old') : '*' }}
                </label>

                <input type="password"
                       wire:model="password"
                       placeholder="{{ __('pages.minimum_8_characters') }}">
                @error('password') <small>{{ $message }}</small> @enderror
            </div>

            <div>
                <label>{{ __('pages.status') }}</label>
                <select wire:model="status">
                    <option value="active">{{ __('pages.active') }}</option>
                    <option value="inactive">{{ __('pages.inactive') }}</option>
                </select>
                @error('status') <small>{{ $message }}</small> @enderror
            </div>
        </div>

        <div class="actions">
            <button wire:click="save" class="btn">
                {{ $editingId ? __('pages.update') : __('pages.save') }}
            </button>

            @if($editingId)
                <button wire:click="resetForm" class="btn gray">
                    {{ __('pages.cancel') }}
                </button>
            @endif
        </div>
    </div>

    <div class="panel">
        <h2 class="panel-title">{{ __('pages.user_list') }}</h2>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>{{ __('pages.name') }}</th>
                        <th>{{ __('pages.role') }}</th>
                        <th>{{ __('pages.email') }}</th>
                        <th>{{ __('pages.phone') }}</th>
                        <th>{{ __('pages.status') }}</th>
                        <th width="140">{{ __('pages.action') }}</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->role->name ?? '-' }}</td>
                            <td>{{ $user->email ?? '-' }}</td>
                            <td>{{ $user->phone ?? '-' }}</td>

                            <td>
                                <span class="status {{ $user->status }}">
                                    {{ $user->status === 'active' ? __('pages.active') : __('pages.inactive') }}
                                </span>
                            </td>

                            <td>
                                <div class="table-actions">
                                    <button wire:click="edit({{ $user->id }})" class="mini">
                                        {{ __('pages.edit') }}
                                    </button>

                                    <button wire:click="delete({{ $user->id }})" class="mini danger">
                                        {{ __('pages.delete') }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty">
                                {{ __('pages.no_user_found') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>