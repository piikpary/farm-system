<?php

use Livewire\Component;
use App\Models\PlantingCycleType;

new class extends Component
{
    public $search = '';

    public function delete($id)
    {
        if (!auth()->user()->hasPermission('planting_cycle_types.delete')) {
            abort(403);
        }

        PlantingCycleType::findOrFail($id)->delete();

        session()->flash('success', 'Cycle type deleted successfully.');
    }

    public function with()
    {
        return [
            'cycles' => PlantingCycleType::when($this->search, function ($q) {
                    $q->where('code', 'like', '%' . $this->search . '%')
                        ->orWhere('name', 'like', '%' . $this->search . '%');
                })
                ->orderBy('code')
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
            <h1 class="page-title">Planting Cycle Types</h1>
            <p class="page-subtitle">Manage crop planting cycle master data.</p>
        </div>

        <div class="page-actions">
            <a href="{{ route('dashboard') }}" class="btn gray">Dashboard</a>

            @if(auth()->user()->hasPermission('planting_cycle_types.create'))
                <a href="{{ route('planting-cycle-types.create') }}" class="btn">Add Cycle Type</a>
            @endif
        </div>
    </div>

    <div class="panel">
        <h2 class="panel-title">Filter</h2>

        <div class="form-grid">
            <div>
                <label>Search</label>
                <input type="text" wire:model.live="search" placeholder="Search code or name">
            </div>
        </div>
    </div>

    <div class="panel">
        <h2 class="panel-title">Cycle Type List</h2>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Cycle Type</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th width="150">Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($cycles as $cycle)
                        <tr>
                            <td>{{ $cycle->code }}</td>
                            <td>{{ $cycle->name }}</td>
                            <td>{{ $cycle->description ?? '-' }}</td>
                            <td>
                                <span class="status {{ $cycle->status }}">{{ ucfirst($cycle->status) }}</span>
                            </td>
                            <td>
                                <div class="table-actions">
                                    @if(auth()->user()->hasPermission('planting_cycle_types.edit'))
                                        <a href="{{ route('planting-cycle-types.edit', $cycle->id) }}" class="mini">Edit</a>
                                    @endif

                                    @if(auth()->user()->hasPermission('planting_cycle_types.delete'))
                                        <button wire:click="delete({{ $cycle->id }})" class="mini danger">Delete</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="empty">No cycle type found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>