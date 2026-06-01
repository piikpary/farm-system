<?php

use Livewire\Component;
use App\Models\ZoneBlock;
use App\Models\PlantingCycleType;
use App\Models\BlockRegister;
use Illuminate\Support\Facades\Auth;

new class extends Component
{
    public $registerId;
    public $zone_block_id;
    public $variety;
    public $planting_date;
    public $planting_cycle_type_id;
    public $expected_harvest_date;
    public $status;
    public $note;

    public function mount($register)
    {
        $register = BlockRegister::findOrFail($register);

        $this->registerId = $register->id;
        $this->zone_block_id = $register->zone_block_id;
        $this->variety = $register->variety;
        $this->planting_date = optional($register->planting_date)->format('Y-m-d');
        $this->planting_cycle_type_id = $register->planting_cycle_type_id;
        $this->expected_harvest_date = optional($register->expected_harvest_date)->format('Y-m-d');
        $this->status = $register->status;
        $this->note = $register->note;
    }

    public function update()
    {
        $this->validate([
            'zone_block_id' => 'required|exists:zone_blocks,id',
            'variety' => 'nullable|string|max:150',
            'planting_date' => 'nullable|date',
            'planting_cycle_type_id' => 'nullable|exists:planting_cycle_types,id',
            'expected_harvest_date' => 'nullable|date',
            'status' => 'required|in:active,inactive',
            'note' => 'nullable|string|max:1000',
        ]);

        BlockRegister::findOrFail($this->registerId)->update([
            'zone_block_id' => $this->zone_block_id,
            'variety' => $this->variety ?: null,
            'planting_date' => $this->planting_date ?: null,
            'planting_cycle_type_id' => $this->planting_cycle_type_id ?: null,
            'expected_harvest_date' => $this->expected_harvest_date ?: null,
            'status' => $this->status,
            'note' => $this->note ?: null,
            'updated_by' => Auth::id(),
        ]);

        session()->flash('success', 'Block register updated successfully.');

        return redirect()->route('block-registers.index');
    }

    public function with()
    {
        return [
            'blocks' => ZoneBlock::with('zone')->where('status', 'active')->orderBy('block_code')->get(),
            'cycleTypes' => PlantingCycleType::where('status', 'active')->orderBy('code')->get(),
        ];
    }
};

?>

<div class="page">
    @include('components.shared-style')
    @include('components.toast-alert')

    <div class="page-header">
        <div>
            <h1 class="page-title">Edit Block Register</h1>
            <p class="page-subtitle">Update planting register information.</p>
        </div>

        <div class="page-actions">
            <a href="{{ route('block-registers.index') }}" class="btn gray">Back</a>
        </div>
    </div>

    <div class="panel">
        <h2 class="panel-title">Register Information</h2>

        <div class="form-grid">
            <div>
                <label>Block *</label>
                <select wire:model="zone_block_id">
                    <option value="">Select Block</option>
                    @foreach($blocks as $block)
                        <option value="{{ $block->id }}">
                            {{ $block->block_code }} - {{ $block->zone->zone_code ?? '-' }}
                        </option>
                    @endforeach
                </select>
                @error('zone_block_id') <small>{{ $message }}</small> @enderror
            </div>

            <div>
                <label>Variety</label>
                <input type="text" wire:model="variety">
            </div>

            <div>
                <label>Planting Date</label>
                <input type="date" wire:model="planting_date">
            </div>

            <div>
                <label>Cycle Type</label>
                <select wire:model="planting_cycle_type_id">
                    <option value="">Select Cycle</option>
                    @foreach($cycleTypes as $cycle)
                        <option value="{{ $cycle->id }}">{{ $cycle->code }} - {{ $cycle->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label>Expected Harvest</label>
                <input type="date" wire:model="expected_harvest_date">
            </div>

            <div>
                <label>Status</label>
                <select wire:model="status">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>

            <div style="grid-column:1/-1;">
                <label>Note</label>
                <textarea wire:model="note"></textarea>
            </div>
        </div>

        <div class="btn-row">
            <button wire:click="update" class="btn">Update Register</button>
            <a href="{{ route('block-registers.index') }}" class="btn gray">Cancel</a>
        </div>
    </div>
</div>