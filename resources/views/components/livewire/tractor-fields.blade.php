<?php

use Livewire\Component;
use App\Models\TractorFieldSetting;

new class extends Component
{
    public function toggle($id)
    {
        $field = TractorFieldSetting::findOrFail($id);

        $field->update([
            'is_visible' => !$field->is_visible,
        ]);

        session()->flash('success', 'Tractor field setting updated successfully.');
    }

    public function with()
    {
        return [
            'fields' => TractorFieldSetting::orderBy('sort_order')->get(),
        ];
    }
};

?>

<div class="page">
    @include('components.shared-style')
    @include('components.toast-alert')

    <div class="page-header">
        <div>
            <h1 class="page-title">Tractor Field Settings</h1>
            <p class="page-subtitle">Control optional tractor fields and table columns.</p>
        </div>

        <a href="{{ route('dashboard') }}" class="btn gray">
            {{ __('pages.dashboard_button') }}
        </a>
    </div>

    <div class="panel">
        <h2 class="panel-title">Optional Tractor Fields</h2>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Field</th>
                        <th>Status</th>
                        <th width="120">Action</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($fields as $field)
                        <tr>
                            <td>{{ $field->field_label }}</td>

                            <td>
                                @if($field->is_visible)
                                    <span class="status active">Visible</span>
                                @else
                                    <span class="status inactive">Hidden</span>
                                @endif
                            </td>

                            <td>
                                <button wire:click="toggle({{ $field->id }})"
                                        class="mini {{ $field->is_visible ? 'danger' : '' }}">
                                    {{ $field->is_visible ? 'Hide' : 'Show' }}
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>