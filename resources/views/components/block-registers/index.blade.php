<?php

use Livewire\Component;
use App\Models\BlockRegister;

new class extends Component
{
    public $search = '';

    public function delete($id)
    {
        if (!auth()->user()->hasPermission('block_registers.delete')) {
            abort(403);
        }

        BlockRegister::findOrFail($id)->delete();

        session()->flash('success', 'Block register deleted successfully.');
    }

    public function with()
    {
        $registers = BlockRegister::with(['zoneBlock.zone', 'plantingCycleType'])
            ->when($this->search, function ($q) {
                $q->where('variety', 'like', '%' . $this->search . '%')
                    ->orWhereHas('zoneBlock', function ($b) {
                        $b->where('block_code', 'like', '%' . $this->search . '%');
                    })
                    ->orWhereHas('zoneBlock.zone', function ($z) {
                        $z->where('zone_code', 'like', '%' . $this->search . '%');
                    });
            })
            ->latest()
            ->get();

        return [
            'registers' => $registers,
            'totalArea' => $registers->sum(fn ($item) => $item->zoneBlock->area ?? 0),
        ];
    }
};

?>

<div class="page">
    @include('components.shared-style')
    @include('components.toast-alert')

    <div class="page-header">
        <div>
            <h1 class="page-title">Block Register</h1>
            <p class="page-subtitle">Track variety, planting date, cycle type, and harvest date by block.</p>
        </div>

        <div class="page-actions">
            <a href="{{ route('dashboard') }}" class="btn gray">Dashboard</a>

            @if(auth()->user()->hasPermission('block_registers.create'))
                <a href="{{ route('block-registers.create') }}" class="btn">Add Register</a>
            @endif
        </div>
    </div>

    <div class="panel">
        <h2 class="panel-title">Filter</h2>

        <div class="form-grid">
            <div>
                <label>Search</label>
                <input type="text" wire:model.live="search" placeholder="Search block, zone, variety">
            </div>
        </div>
    </div>

    <div class="panel">
        <h2 class="panel-title">Register List</h2>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Block</th>
                        <th>Zone</th>
                        <th>Area (Ha)</th>
                        <th>Variety</th>
                        <th>Planting Date</th>
                        <th>Cycle Type</th>
                        <th>Expected Harvest</th>
                        <th>Status</th>
                        <th width="160">Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($registers as $register)
                        <tr>
                            <td>{{ $register->zoneBlock->block_code ?? '-' }}</td>
                            <td>{{ $register->zoneBlock->zone->zone_code ?? '-' }}</td>
                            <td>{{ number_format($register->zoneBlock->area ?? 0, 2) }}</td>
                            <td>{{ $register->variety ?? '-' }}</td>
                            <td>{{ optional($register->planting_date)->format('d-M-Y') ?? '-' }}</td>
                            <td>{{ $register->plantingCycleType->code ?? '-' }}</td>
                            <td>{{ optional($register->expected_harvest_date)->format('M-Y') ?? '-' }}</td>
                            <td>
                                <span class="status {{ $register->status }}">
                                    {{ ucfirst($register->status) }}
                                </span>
                            </td>
                            <td>
                                <div class="table-actions">
                                    @if(auth()->user()->hasPermission('block_registers.edit'))
                                        <a href="{{ route('block-registers.edit', $register->id) }}" class="mini">Edit</a>
                                    @endif

                                    @if(auth()->user()->hasPermission('block_registers.delete'))
                                        <button wire:click="delete({{ $register->id }})" class="mini danger">Delete</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="empty">No block register found.</td>
                        </tr>
                    @endforelse
                </tbody>

                @if($registers->count() > 0)
                    <tfoot>
                        <tr style="background:#f8fafc;font-weight:900;">
                            <td colspan="2" style="text-align:right;">Total</td>
                            <td>{{ number_format($totalArea, 2) }}</td>
                            <td colspan="6">-</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>