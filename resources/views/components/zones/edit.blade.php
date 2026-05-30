<?php

use Livewire\Component;
use App\Models\TaskCategory;

new class extends Component
{
    public function delete($id)
    {
        TaskCategory::findOrFail($id)->delete();

        session()->flash('success', __('pages.task_category_deleted_success'));
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
            <p class="page-subtitle">{{ __('pages.task_categories_list_subtitle') }}</p>
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

            <a href="{{ route('task-categories.create') }}" class="btn">
                {{ __('pages.add_task_category') }}
            </a>
        </div>
    </div>

    

    <div class="panel">
        <h2 class="panel-title">{{ __('pages.task_category_list') }}</h2>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>{{ __('pages.name') }}</th>
                        <th>{{ __('pages.fuel_per_hectare') }}</th>
                        <th>{{ __('pages.hectare_per_hour') }}</th>
                        <th>{{ __('pages.description') }}</th>
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
                            <td>{{ $task->description ?? '-' }}</td>

                            <td>
                                <span class="status {{ $task->status }}">
                                    {{ $task->status === 'active' ? __('pages.active') : __('pages.inactive') }}
                                </span>
                            </td>

                            <td>
                                <div class="table-actions">
                                    <a href="{{ route('task-categories.edit', $task->id) }}" class="mini">
                                        {{ __('pages.edit') }}
                                    </a>

                                    <button wire:click="delete({{ $task->id }})" class="mini danger">
                                        {{ __('pages.delete') }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty">
                                {{ __('pages.no_task_category_found') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>