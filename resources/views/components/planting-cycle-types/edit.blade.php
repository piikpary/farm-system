<?php

use Livewire\Component;
use App\Models\PlantingCycleType;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

new class extends Component
{
    public $cycleId;
    public $code;
    public $name;
    public $description;
    public $status;

    public function mount($cycle)
    {
        $cycle = PlantingCycleType::findOrFail($cycle);

        $this->cycleId = $cycle->id;
        $this->code = $cycle->code;
        $this->name = $cycle->name;
        $this->description = $cycle->description;
        $this->status = $cycle->status;
    }

    public function update()
    {
        $this->validate([
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('planting_cycle_types', 'code')->ignore($this->cycleId),
            ],
            'name' => 'required|string|max:150',
            'description' => 'nullable|string|max:1000',
            'status' => 'required|in:active,inactive',
        ]);

        PlantingCycleType::findOrFail($this->cycleId)->update([
            'code' => strtoupper($this->code),
            'name' => $this->name,
            'description' => $this->description ?: null,
            'status' => $this->status,
            'updated_by' => Auth::id(),
        ]);

        session()->flash('success', 'Cycle type updated successfully.');

        return redirect()->route('planting-cycle-types.index');
    }
};

?>

<div class="page">
    @include('components.shared-style')
    @include('components.toast-alert')

    <div class="page-header">
        <div>
            <h1 class="page-title">Edit Planting Cycle Type</h1>
            <p class="page-subtitle">Update crop cycle information.</p>
        </div>

        <div class="page-actions">
            <a href="{{ route('planting-cycle-types.index') }}" class="btn gray">Back</a>
        </div>
    </div>

    <div class="panel">
        <h2 class="panel-title">Cycle Type Information</h2>

        <div class="form-grid">
            <div>
                <label>Code *</label>
                <input type="text" wire:model="code" placeholder="PC">
                @error('code') <small>{{ $message }}</small> @enderror
            </div>

            <div>
                <label>Cycle Type *</label>
                <input type="text" wire:model="name" placeholder="Plant Cane">
                @error('name') <small>{{ $message }}</small> @enderror
            </div>

            <div>
                <label>Status</label>
                <select wire:model="status">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>

            <div style="grid-column:1/-1;">
                <label>Description</label>
                <textarea wire:model="description"></textarea>
            </div>
        </div>

        <div class="btn-row">
            <button wire:click="update" class="btn">Update Cycle Type</button>
            <a href="{{ route('planting-cycle-types.index') }}" class="btn gray">Cancel</a>
        </div>
    </div>
</div>