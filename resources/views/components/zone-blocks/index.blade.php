<?php

use Livewire\Component;
use App\Models\Zone;
use App\Models\ZoneBlock;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

new class extends Component
{
    public $search = '';
    public $rows = [];

    public $editingId = null;

    public $editRow = [
        'zone_id' => '',
        'block_code' => '',
        'name' => '',
        'area' => '',
        'location_note' => '',
        'center_lat' => '',
        'center_lng' => '',
        'polygon_coordinates' => '',
        'status' => 'active',
    ];

    public $mapTarget = null;
    public $mapIndex = null;

    public function addRow()
    {
        $this->rows[] = $this->emptyRow();
    }

    public function emptyRow()
    {
        return [
            'zone_id' => '',
            'block_code' => '',
            'name' => '',
            'area' => '',
            'location_note' => '',
            'center_lat' => '',
            'center_lng' => '',
            'polygon_coordinates' => '',
            'status' => 'active',
        ];
    }

    public function removeRow($index)
    {
        if (!isset($this->rows[$index])) {
            return;
        }

        unset($this->rows[$index]);
        $this->rows = array_values($this->rows);
    }

    public function openMapForNew($index)
    {
        if (!isset($this->rows[$index])) {
            return;
        }

        $this->mapTarget = 'new';
        $this->mapIndex = $index;

        $this->dispatch(
            'open-block-map',
            target: 'new',
            index: $index,
            centerLat: $this->rows[$index]['center_lat'] ?: 11.5564,
            centerLng: $this->rows[$index]['center_lng'] ?: 104.9282,
            polygon: $this->rows[$index]['polygon_coordinates'] ?: ''
        );
    }

    public function openMapForEdit()
    {
        if (!$this->editingId) {
            return;
        }

        $this->mapTarget = 'edit';
        $this->mapIndex = null;

        $this->dispatch(
            'open-block-map',
            target: 'edit',
            index: null,
            centerLat: $this->editRow['center_lat'] ?: 11.5564,
            centerLng: $this->editRow['center_lng'] ?: 104.9282,
            polygon: $this->editRow['polygon_coordinates'] ?: ''
        );
    }

    public function setMapData($target, $index, $centerLat, $centerLng, $polygon)
    {
        if ($target === 'new' && isset($this->rows[$index])) {
            $this->rows[$index]['center_lat'] = $centerLat;
            $this->rows[$index]['center_lng'] = $centerLng;
            $this->rows[$index]['polygon_coordinates'] = $polygon;
        }

        if ($target === 'edit') {
            $this->editRow['center_lat'] = $centerLat;
            $this->editRow['center_lng'] = $centerLng;
            $this->editRow['polygon_coordinates'] = $polygon;
        }

        session()->flash('success', 'Map location selected.');
    }

    public function saveRow($index)
    {
        if (!auth()->user()->hasPermission('zone_blocks.create')) {
            abort(403, 'Permission denied.');
        }

        if (!isset($this->rows[$index])) {
            return;
        }

        $this->validate([
            "rows.$index.zone_id" => 'required|exists:zones,id',
            "rows.$index.block_code" => [
                'required',
                'string',
                'max:100',
                Rule::unique('zone_blocks', 'block_code'),
            ],
            "rows.$index.name" => 'nullable|string|max:150',
            "rows.$index.area" => 'nullable|numeric|min:0',
            "rows.$index.location_note" => 'nullable|string|max:1000',
            "rows.$index.center_lat" => 'nullable|numeric',
            "rows.$index.center_lng" => 'nullable|numeric',
            "rows.$index.polygon_coordinates" => 'nullable|string',
            "rows.$index.status" => 'required|in:active,inactive',
        ]);

        $row = $this->rows[$index];

        ZoneBlock::create([
            'zone_id' => $row['zone_id'],
            'block_code' => $row['block_code'],
            'name' => $row['name'] ?: null,
            'area' => $row['area'] ?: 0,
            'location_note' => $row['location_note'] ?: null,
            'center_lat' => $row['center_lat'] ?: null,
            'center_lng' => $row['center_lng'] ?: null,
            'polygon_coordinates' => $row['polygon_coordinates'] ?: null,
            'status' => $row['status'],
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        unset($this->rows[$index]);
        $this->rows = array_values($this->rows);

        session()->flash('success', 'Block saved successfully.');
    }

    public function edit($id)
    {
        if (!auth()->user()->hasPermission('zone_blocks.edit')) {
            abort(403, 'Permission denied.');
        }

        $block = ZoneBlock::findOrFail($id);

        $this->editingId = $block->id;

        $this->editRow = [
            'zone_id' => $block->zone_id,
            'block_code' => $block->block_code,
            'name' => $block->name,
            'area' => $block->area,
            'location_note' => $block->location_note,
            'center_lat' => $block->center_lat,
            'center_lng' => $block->center_lng,
            'polygon_coordinates' => $block->polygon_coordinates,
            'status' => $block->status,
        ];
    }

    public function cancelEdit()
    {
        $this->editingId = null;

        $this->editRow = [
            'zone_id' => '',
            'block_code' => '',
            'name' => '',
            'area' => '',
            'location_note' => '',
            'center_lat' => '',
            'center_lng' => '',
            'polygon_coordinates' => '',
            'status' => 'active',
        ];
    }

    public function updateRow()
    {
        if (!auth()->user()->hasPermission('zone_blocks.edit')) {
            abort(403, 'Permission denied.');
        }

        $block = ZoneBlock::findOrFail($this->editingId);

        $this->validate([
            'editRow.zone_id' => 'required|exists:zones,id',
            'editRow.block_code' => [
                'required',
                'string',
                'max:100',
                Rule::unique('zone_blocks', 'block_code')->ignore($block->id),
            ],
            'editRow.name' => 'nullable|string|max:150',
            'editRow.area' => 'nullable|numeric|min:0',
            'editRow.location_note' => 'nullable|string|max:1000',
            'editRow.center_lat' => 'nullable|numeric',
            'editRow.center_lng' => 'nullable|numeric',
            'editRow.polygon_coordinates' => 'nullable|string',
            'editRow.status' => 'required|in:active,inactive',
        ]);

        $block->update([
            'zone_id' => $this->editRow['zone_id'],
            'block_code' => $this->editRow['block_code'],
            'name' => $this->editRow['name'] ?: null,
            'area' => $this->editRow['area'] ?: 0,
            'location_note' => $this->editRow['location_note'] ?: null,
            'center_lat' => $this->editRow['center_lat'] ?: null,
            'center_lng' => $this->editRow['center_lng'] ?: null,
            'polygon_coordinates' => $this->editRow['polygon_coordinates'] ?: null,
            'status' => $this->editRow['status'],
            'updated_by' => Auth::id(),
        ]);

        $this->cancelEdit();

        session()->flash('success', 'Block updated successfully.');
    }

    public function delete($id)
    {
        if (!auth()->user()->hasPermission('zone_blocks.delete')) {
            abort(403, 'Permission denied.');
        }

        ZoneBlock::findOrFail($id)->delete();

        session()->flash('success', 'Block deleted successfully.');
    }

    public function getBlocksProperty()
    {
        return ZoneBlock::with('zone')
            ->when($this->search, function ($q) {
                $q->where(function ($query) {
                    $query->where('block_code', 'like', '%' . $this->search . '%')
                        ->orWhere('name', 'like', '%' . $this->search . '%')
                        ->orWhere('location_note', 'like', '%' . $this->search . '%')
                        ->orWhere('status', 'like', '%' . $this->search . '%')
                        ->orWhereHas('zone', function ($zoneQuery) {
                            $zoneQuery->where('zone_code', 'like', '%' . $this->search . '%')
                                ->orWhere('name', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->orderBy('block_code')
            ->get();
    }

    public function getTotalAreaProperty()
    {
        return $this->blocks->sum(function ($block) {
            return (float) ($block->area ?? 0);
        });
    }

    public function with()
    {
        return [
            'zones' => Zone::where('status', 'active')
                ->orderBy('zone_code')
                ->get(),
        ];
    }
};

?>

<div class="page">
    @include('components.shared-style')
    @include('components.toast-alert')

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.css">

    <style>
        .block-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 14px;
        }

        .filter-box {
            display: flex;
            align-items: center;
            gap: 10px;
            flex: 1;
            max-width: 480px;
        }

        .filter-box input {
            width: 100%;
            height: 44px;
            border: 1px solid #d1d5db;
            border-radius: 12px;
            padding: 10px 14px;
            font-weight: 700;
            background: #ffffff;
        }

        .block-table-wrap {
            overflow-x: auto;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
        }

        .block-table {
            width: 100%;
            min-width: 1320px;
            border-collapse: collapse;
            background: #ffffff;
        }

        .block-table th {
            background: #f8fafc;
            color: #0f172a;
            font-size: 12px;
            font-weight: 900;
            text-transform: uppercase;
            padding: 12px 10px;
            border-bottom: 1px solid #e5e7eb;
            white-space: nowrap;
        }

        .block-table td {
            padding: 10px;
            border-bottom: 1px solid #eef2f7;
            vertical-align: middle;
            white-space: nowrap;
        }

        .block-table input,
        .block-table select {
            width: 100%;
            min-width: 140px;
            height: 44px;
            padding: 9px 10px;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            font-size: 13px;
            background: #ffffff;
            font-weight: 700;
        }

        .location-input {
            min-width: 240px !important;
        }

        .row-no {
            width: 45px;
            min-width: 45px;
            text-align: center;
            font-weight: 900;
            color: #64748b;
        }

        .new-row {
            background: #f0fdf4;
        }

        .new-row td {
            border-bottom: 1px solid #bbf7d0;
        }

        .table-actions {
            display: flex;
            gap: 6px;
            align-items: center;
            flex-wrap: wrap;
        }

        .block-total-row {
            background: #f8fafc;
            font-weight: 900;
            border-top: 2px solid #d1d5db;
        }

        .block-total-row td {
            border-bottom: 0;
            padding: 14px 10px;
            color: #0f172a;
        }

        .total-label {
            text-align: right;
            font-weight: 900;
        }

        .plus-cell {
            width: 34px;
            height: 34px;
            border: none;
            border-radius: 10px;
            background: #16a34a;
            color: #ffffff;
            font-size: 20px;
            font-weight: 900;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .plus-cell:hover {
            background: #15803d;
        }

        .danger-plus {
            background: #dc2626;
        }

        .danger-plus:hover {
            background: #b91c1c;
        }

        .map-mini {
            background: #0f766e;
            color: #ffffff;
        }

        .map-mini:hover {
            background: #115e59;
        }

        .map-selected {
            color: #166534;
            font-size: 12px;
            font-weight: 900;
        }

        .error {
            display: block;
            color: #dc2626;
            font-size: 12px;
            margin-top: 4px;
            font-weight: 700;
        }

        .map-modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.58);
            z-index: 9998;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .map-modal-backdrop.active {
            display: flex;
        }

        .map-modal {
            width: min(1100px, 96vw);
            background: #ffffff;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.35);
        }

        .map-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 18px;
            border-bottom: 1px solid #e5e7eb;
        }

        .map-modal-title {
            font-size: 18px;
            font-weight: 900;
            color: #0f172a;
        }

        .map-modal-body {
            padding: 14px;
        }

        #blockPopupMap {
            width: 100%;
            height: 520px;
            border-radius: 14px;
            border: 1px solid #d1d5db;
            overflow: hidden;
        }

        .map-modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            padding: 14px 18px;
            border-top: 1px solid #e5e7eb;
        }
    </style>

    <div class="page-header">
        <div>
            <h1 class="page-title">{{ __('pages.zone_blocks') }}</h1>
            
        </div>

        <div class="page-actions">
            <a href="{{ route('dashboard') }}" class="btn gray">
                Dashboard
            </a>
        </div>
    </div>

    <div class="panel">
        <div class="block-toolbar">
            <div class="filter-box">
                <input type="text"
                       wire:model.live="search"
                       placeholder="Filter block, zone, name, location note, status">
            </div>
        </div>

        <div class="block-table-wrap">
            <table class="block-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Zone *</th>
                        <th>Block *</th>
                        <th>Name</th>
                        <th>Area (Ha)</th>
                        <th>Location Note</th>
                        <th>Map</th>
                        <th>Status</th>
                        <th width="230">Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($this->blocks as $block)
                        @if($editingId === $block->id)
                            <tr class="new-row">
                                <td class="row-no">{{ $loop->iteration }}</td>

                                <td>
                                    <select wire:model.live="editRow.zone_id">
                                        <option value="">Select Zone</option>
                                        @foreach($zones as $zone)
                                            <option value="{{ $zone->id }}">
                                                {{ $zone->zone_code }} {{ $zone->name ? '- ' . $zone->name : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('editRow.zone_id') <small class="error">{{ $message }}</small> @enderror
                                </td>

                                <td>
                                    <input type="text" wire:model.live="editRow.block_code">
                                    @error('editRow.block_code') <small class="error">{{ $message }}</small> @enderror
                                </td>

                                <td>
                                    <input type="text" wire:model.live="editRow.name">
                                    @error('editRow.name') <small class="error">{{ $message }}</small> @enderror
                                </td>

                                <td>
                                    <input type="number" step="0.01" wire:model.live="editRow.area">
                                    @error('editRow.area') <small class="error">{{ $message }}</small> @enderror
                                </td>

                                <td>
                                    <input type="text"
                                           class="location-input"
                                           wire:model.live="editRow.location_note">
                                    @error('editRow.location_note') <small class="error">{{ $message }}</small> @enderror
                                </td>

                                <td>
                                    <div class="table-actions">
                                        <button type="button"
                                                wire:click="openMapForEdit"
                                                class="mini map-mini">
                                            Map
                                        </button>

                                        @if(!empty($editRow['center_lat']) && !empty($editRow['center_lng']))
                                            <span class="map-selected">Selected</span>
                                        @else
                                            <span>-</span>
                                        @endif
                                    </div>
                                </td>

                                <td>
                                    <select wire:model.live="editRow.status">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                    @error('editRow.status') <small class="error">{{ $message }}</small> @enderror
                                </td>

                                <td>
                                    <div class="table-actions">
                                        <button type="button" wire:click="updateRow" class="mini">
                                            Save
                                        </button>

                                        <button type="button" wire:click="cancelEdit" class="mini danger">
                                            Cancel
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @else
                            <tr>
                                <td class="row-no">{{ $loop->iteration }}</td>

                                <td>
                                    {{ $block->zone->zone_code ?? '-' }}
                                </td>

                                <td>{{ $block->block_code }}</td>

                                <td>{{ $block->name ?? '-' }}</td>

                                <td>{{ number_format((float) $block->area, 2) }}</td>

                                <td>{{ $block->location_note ?? '-' }}</td>

                                <td>
                                    @if($block->center_lat && $block->center_lng)
                                        <span class="map-selected">Selected</span>
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
                                            <button type="button"
                                                    wire:click="edit({{ $block->id }})"
                                                    class="mini">
                                                Edit
                                            </button>
                                        @endif

                                        @if(auth()->user()->hasPermission('zone_blocks.delete'))
                                            <button type="button"
                                                    wire:click="delete({{ $block->id }})"
                                                    class="mini danger"
                                                    onclick="return confirm('Delete this block?')">
                                                Delete
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        @if(count($rows) === 0)
                            <tr>
                                <td colspan="9" class="empty">
                                    No block found.
                                </td>
                            </tr>
                        @endif
                    @endforelse

                    @foreach($rows as $index => $row)
                        <tr class="new-row">
                            <td class="row-no">
                                <button type="button"
                                        wire:click="removeRow({{ $index }})"
                                        class="plus-cell danger-plus"
                                        title="Remove row">
                                    ×
                                </button>
                            </td>

                            <td>
                                <select wire:model.live="rows.{{ $index }}.zone_id">
                                    <option value="">Select Zone</option>
                                    @foreach($zones as $zone)
                                        <option value="{{ $zone->id }}">
                                            {{ $zone->zone_code }} {{ $zone->name ? '- ' . $zone->name : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                @error("rows.$index.zone_id") <small class="error">{{ $message }}</small> @enderror
                            </td>

                            <td>
                                <input type="text"
                                       wire:model.live="rows.{{ $index }}.block_code"
                                       placeholder="Z1-B01">
                                @error("rows.$index.block_code") <small class="error">{{ $message }}</small> @enderror
                            </td>

                            <td>
                                <input type="text"
                                       wire:model.live="rows.{{ $index }}.name"
                                       placeholder="Block name">
                                @error("rows.$index.name") <small class="error">{{ $message }}</small> @enderror
                            </td>

                            <td>
                                <input type="number"
                                       step="0.01"
                                       wire:model.live="rows.{{ $index }}.area"
                                       placeholder="12.50">
                                @error("rows.$index.area") <small class="error">{{ $message }}</small> @enderror
                            </td>

                            <td>
                                <input type="text"
                                       class="location-input"
                                       wire:model.live="rows.{{ $index }}.location_note"
                                       placeholder="Location note">
                                @error("rows.$index.location_note") <small class="error">{{ $message }}</small> @enderror
                            </td>

                            <td>
                                <div class="table-actions">
                                    <button type="button"
                                            wire:click="openMapForNew({{ $index }})"
                                            class="mini map-mini">
                                        Map
                                    </button>

                                    @if(!empty($row['center_lat']) && !empty($row['center_lng']))
                                        <span class="map-selected">Selected</span>
                                    @else
                                        <span>-</span>
                                    @endif
                                </div>
                            </td>

                            <td>
                                <select wire:model.live="rows.{{ $index }}.status">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                                @error("rows.$index.status") <small class="error">{{ $message }}</small> @enderror
                            </td>

                            <td>
                                <div class="table-actions">
                                    <button type="button"
                                            wire:click="saveRow({{ $index }})"
                                            class="mini">
                                        Save
                                    </button>

                                    <button type="button"
                                            wire:click="removeRow({{ $index }})"
                                            class="mini danger">
                                        Remove
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>

                <tfoot>
                    <tr class="block-total-row">
                        <td>
                            @if(auth()->user()->hasPermission('zone_blocks.create'))
                                <button type="button"
                                        wire:click="addRow"
                                        class="plus-cell"
                                        title="Add row">
                                    +
                                </button>
                            @else
                                -
                            @endif
                        </td>

                        <td colspan="3" class="total-label">Total Area</td>

                        <td>{{ number_format((float) $this->totalArea, 2) }} ha</td>

                        <td>-</td>
                        <td>-</td>
                        <td>-</td>
                        <td>-</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div id="blockMapModal" class="map-modal-backdrop" wire:ignore>
        <div class="map-modal">
            <div class="map-modal-header">
                <div class="map-modal-title">Select Block Map Boundary</div>

                <button type="button" class="mini danger" onclick="closeBlockMapModal()">
                    Close
                </button>
            </div>

            <div class="map-modal-body">
                <div id="blockPopupMap"></div>
            </div>

            <div class="map-modal-footer">
                <button type="button" class="btn gray" onclick="clearBlockMapShape()">
                    Clear
                </button>

                <button type="button" class="btn" onclick="saveBlockMapShape()">
                    Save Map
                </button>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.js"></script>

    <script>
        let blockMap = null;
        let blockDrawnItems = null;
        let blockCurrentLayer = null;
        let blockCurrentTarget = null;
        let blockCurrentIndex = null;
        let blockCurrentCenterLat = null;
        let blockCurrentCenterLng = null;
        let blockCurrentPolygon = '';

        function initBlockMap() {
            if (blockMap) {
                return;
            }

            blockMap = L.map('blockPopupMap').setView([11.5564, 104.9282], 15);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 20,
                attribution: '&copy; OpenStreetMap'
            }).addTo(blockMap);

            blockDrawnItems = new L.FeatureGroup();
            blockMap.addLayer(blockDrawnItems);

            const drawControl = new L.Control.Draw({
                edit: {
                    featureGroup: blockDrawnItems
                },
                draw: {
                    polygon: true,
                    rectangle: true,
                    marker: true,
                    polyline: false,
                    circle: false,
                    circlemarker: false
                }
            });

            blockMap.addControl(drawControl);

            blockMap.on(L.Draw.Event.CREATED, function (event) {
                blockDrawnItems.clearLayers();

                blockCurrentLayer = event.layer;
                blockDrawnItems.addLayer(blockCurrentLayer);

                updateBlockMapDataFromLayer(blockCurrentLayer);
            });

            blockMap.on(L.Draw.Event.EDITED, function (event) {
                event.layers.eachLayer(function (layer) {
                    blockCurrentLayer = layer;
                    updateBlockMapDataFromLayer(layer);
                });
            });

            blockMap.on(L.Draw.Event.DELETED, function () {
                blockCurrentLayer = null;
                blockCurrentCenterLat = null;
                blockCurrentCenterLng = null;
                blockCurrentPolygon = '';
            });
        }

        function updateBlockMapDataFromLayer(layer) {
            if (layer instanceof L.Marker) {
                const latlng = layer.getLatLng();

                blockCurrentCenterLat = latlng.lat.toFixed(8);
                blockCurrentCenterLng = latlng.lng.toFixed(8);
                blockCurrentPolygon = '';

                return;
            }

            const latLngs = layer.getLatLngs()[0].map(function (point) {
                return [point.lat, point.lng];
            });

            const center = layer.getBounds().getCenter();

            blockCurrentCenterLat = center.lat.toFixed(8);
            blockCurrentCenterLng = center.lng.toFixed(8);
            blockCurrentPolygon = JSON.stringify(latLngs);
        }

        function drawExistingBlockShape(centerLat, centerLng, polygon) {
            blockDrawnItems.clearLayers();
            blockCurrentLayer = null;

            if (polygon) {
                try {
                    const points = JSON.parse(polygon);

                    if (Array.isArray(points) && points.length > 0) {
                        const polygonLayer = L.polygon(points);
                        blockCurrentLayer = polygonLayer;
                        blockDrawnItems.addLayer(polygonLayer);
                        blockMap.fitBounds(polygonLayer.getBounds());
                        updateBlockMapDataFromLayer(polygonLayer);

                        return;
                    }
                } catch (error) {}
            }

            if (centerLat && centerLng) {
                const marker = L.marker([centerLat, centerLng]);
                blockCurrentLayer = marker;
                blockDrawnItems.addLayer(marker);
                blockMap.setView([centerLat, centerLng], 16);
                updateBlockMapDataFromLayer(marker);

                return;
            }

            blockMap.setView([11.5564, 104.9282], 15);
        }

        function openBlockMapModal(data) {
            blockCurrentTarget = data.target;
            blockCurrentIndex = data.index;
            blockCurrentCenterLat = data.centerLat;
            blockCurrentCenterLng = data.centerLng;
            blockCurrentPolygon = data.polygon || '';

            document.getElementById('blockMapModal').classList.add('active');

            setTimeout(function () {
                initBlockMap();
                blockMap.invalidateSize();
                drawExistingBlockShape(blockCurrentCenterLat, blockCurrentCenterLng, blockCurrentPolygon);
            }, 250);
        }

        function closeBlockMapModal() {
            document.getElementById('blockMapModal').classList.remove('active');
        }

        function clearBlockMapShape() {
            if (!blockDrawnItems) {
                return;
            }

            blockDrawnItems.clearLayers();
            blockCurrentLayer = null;
            blockCurrentCenterLat = null;
            blockCurrentCenterLng = null;
            blockCurrentPolygon = '';
        }

        function saveBlockMapShape() {
            if (!blockCurrentCenterLat || !blockCurrentCenterLng) {
                alert('Please draw a polygon, rectangle, or marker first.');
                return;
            }

            @this.call(
                'setMapData',
                blockCurrentTarget,
                blockCurrentIndex,
                blockCurrentCenterLat,
                blockCurrentCenterLng,
                blockCurrentPolygon
            );

            closeBlockMapModal();
        }

        document.addEventListener('livewire:init', function () {
            Livewire.on('open-block-map', function (event) {
                openBlockMapModal(event);
            });
        });
    </script>
</div>