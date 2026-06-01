<?php

use Livewire\Component;
use App\Models\ZoneBlock;

new class extends Component
{
    public $search = '';

    public function delete($id)
    {
        if (!auth()->user()->hasPermission('zone_blocks.delete')) {
            abort(403);
        }

        ZoneBlock::findOrFail($id)->delete();

        session()->flash('success', 'Block deleted successfully.');
    }

    public function with()
    {
        $blocks = ZoneBlock::with('zone')
            ->when($this->search, function ($q) {
                $q->where('block_code', 'like', '%' . $this->search . '%')
                    ->orWhere('name', 'like', '%' . $this->search . '%')
                    ->orWhereHas('zone', fn ($z) => $z->where('zone_code', 'like', '%' . $this->search . '%'));
            })
            ->latest()
            ->get();

        return [
            'blocks' => $blocks,
            'totalArea' => $blocks->sum('area'),
        ];
    }
};

?>

<div class="page">
    @include('components.shared-style')
    @include('components.toast-alert')

    <div class="page-header">
        <div>
            <h1 class="page-title">Zone Blocks</h1>
            <p class="page-subtitle">Manage sub zones / farm blocks.</p>
        </div>

        <div class="page-actions">
            <a href="{{ route('dashboard') }}" class="btn gray">Dashboard</a>

            @if(auth()->user()->hasPermission('zone_blocks.create'))
                <a href="{{ route('zone-blocks.create') }}" class="btn">Add Block</a>
            @endif
        </div>
    </div>

    <div class="panel">
        <h2 class="panel-title">Filter</h2>

        <div class="form-grid">
            <div>
                <label>Search</label>
                <input type="text" wire:model.live="search" placeholder="Search block, zone, soil type">
            </div>
        </div>
    </div>

    <div class="panel">
        <h2 class="panel-title">Block List</h2>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Zone</th>
                        <th>Block</th>
                        <th>Name</th>
                        <th>Area (Ha)</th>
                        <th>GPS Location</th>
                        <th>Status</th>
                        <th width="160">Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($blocks as $block)
                        <tr>
                            <td>{{ $block->zone->zone_code ?? '-' }}</td>
                            <td>{{ $block->block_code }}</td>
                            <td>{{ $block->name ?? '-' }}</td>
                            <td>{{ number_format($block->area, 2) }}</td>
                            
                            <td>
                                @if($block->center_lat && $block->center_lng)
                                    {{ $block->center_lat }}, {{ $block->center_lng }}
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                <span class="status {{ $block->status }}">
                                    {{ ucfirst($block->status) }}
                                </span>
                            </td>
                            <td>
                                <div class="table-actions">
                                    @if(auth()->user()->hasPermission('zone_blocks.edit'))
                                        <a href="{{ route('zone-blocks.edit', $block->id) }}" class="mini">Edit</a>
                                    @endif

                                    @if(auth()->user()->hasPermission('zone_blocks.delete'))
                                        <button wire:click="delete({{ $block->id }})" class="mini danger">Delete</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="empty">No block found.</td>
                        </tr>
                    @endforelse
                </tbody>

                @if($blocks->count() > 0)
                    <tfoot>
                        <tr style="background:#f8fafc; font-weight:900;">
                            <td colspan="3" style="text-align:right;">Total</td>
                            <td>{{ number_format($totalArea, 2) }}</td>
                            <td>-</td>
                            <td>-</td>
                            <td>-</td>
                            
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>