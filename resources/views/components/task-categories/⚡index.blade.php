<?php

use Livewire\Component;
use App\Models\TaskCategory;
use Illuminate\Support\Facades\Auth;

new class extends Component
{
    public $name;
    public $description;
    public $standard_fuel_per_hectare = 0;
    public $standard_hectare_per_hour = 0;
    public $status = 'active';
    public $editingId = null;

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:150',
            'description' => 'nullable|string',
            'standard_fuel_per_hectare' => 'nullable|numeric|min:0',
            'standard_hectare_per_hour' => 'nullable|numeric|min:0',
            'status' => 'required|in:active,inactive',
        ]);

        TaskCategory::updateOrCreate(
            ['id' => $this->editingId],
            [
                'name' => $this->name,
                'description' => $this->description,
                'standard_fuel_per_hectare' => $this->standard_fuel_per_hectare ?: 0,
                'standard_hectare_per_hour' => $this->standard_hectare_per_hour ?: 0,
                'status' => $this->status,
                'created_by' => $this->editingId ? null : Auth::id(),
                'updated_by' => Auth::id(),
            ]
        );

        session()->flash(
            'success',
            $this->editingId
                ? __('pages.task_category_updated_success')
                : __('pages.task_category_created_success')
        );

        $this->resetForm();
    }

    public function edit($id)
    {
        $task = TaskCategory::findOrFail($id);

        $this->editingId = $task->id;
        $this->name = $task->name;
        $this->description = $task->description;
        $this->standard_fuel_per_hectare = $task->standard_fuel_per_hectare;
        $this->standard_hectare_per_hour = $task->standard_hectare_per_hour;
        $this->status = $task->status;
    }

    public function delete($id)
    {
        TaskCategory::findOrFail($id)->delete();

        session()->flash('success', __('pages.task_category_deleted_success'));
    }

    public function resetForm()
    {
        $this->reset([
            'name',
            'description',
            'standard_fuel_per_hectare',
            'standard_hectare_per_hour',
            'editingId',
        ]);

        $this->status = 'active';
    }

    public function with()
    {
        return [
            'taskCategories' => TaskCategory::latest()->get(),
        ];
    }
};

?>

<div class="page">
    @include('components.shared-style')
    @include('components.toast-alert')

    <div class="page-header">
        <div>
            <h1 class="page-title">{{ __('pages.task_categories') }}</h1>
            <p class="page-subtitle">{{ __('pages.task_categories_subtitle') }}</p>
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

    

    <div class="panel">
        <h2 class="panel-title">
            {{ $editingId ? __('pages.edit_task_category') : __('pages.add_task_category') }}
        </h2>

        <div class="form-grid">
            <div>
                <label>{{ __('pages.name') }} *</label>
                <input type="text"
                       wire:model="name"
                       placeholder="{{ __('pages.task_category_placeholder') }}">
                @error('name') <small>{{ $message }}</small> @enderror
            </div>

            <div>
                <label>{{ __('pages.standard_fuel_per_hectare') }}</label>
                <input type="number"
                       step="0.01"
                       wire:model="standard_fuel_per_hectare">
                @error('standard_fuel_per_hectare') <small>{{ $message }}</small> @enderror
            </div>

            <div>
                <label>{{ __('pages.standard_hectare_per_hour') }}</label>
                <input type="number"
                       step="0.01"
                       wire:model="standard_hectare_per_hour">
                @error('standard_hectare_per_hour') <small>{{ $message }}</small> @enderror
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
                <textarea wire:model="description"
                          placeholder="{{ __('pages.description') }}"></textarea>
                @error('description') <small>{{ $message }}</small> @enderror
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
        <h2 class="panel-title">{{ __('pages.task_category_list') }}</h2>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>{{ __('pages.name') }}</th>
                        <th>{{ __('pages.fuel_per_ha') }}</th>
                        <th>{{ __('pages.ha_per_hr') }}</th>
                        <th>{{ __('pages.status') }}</th>
                        <th width="140">{{ __('pages.action') }}</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($taskCategories as $task)
                        <tr>
                            <td>{{ $task->name }}</td>
                            <td>{{ number_format($task->standard_fuel_per_hectare ?? 0, 2) }}</td>
                            <td>{{ number_format($task->standard_hectare_per_hour ?? 0, 2) }}</td>

                            <td>
                                <span class="status {{ $task->status }}">
                                    {{ $task->status === 'active' ? __('pages.active') : __('pages.inactive') }}
                                </span>
                            </td>

                            <td>
                                <div class="table-actions">
                                    <button wire:click="edit({{ $task->id }})" class="mini">
                                        {{ __('pages.edit') }}
                                    </button>

                                    <button wire:click="delete({{ $task->id }})" class="mini danger">
                                        {{ __('pages.delete') }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="empty">
                                {{ __('pages.no_task_category_found') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>