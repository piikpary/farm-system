<?php

use Livewire\Component;
use App\Models\Zone;
use App\Models\ZoneBlock;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

new class extends Component
{
    public $blockId;
    public $zone_id;
    public $block_code;
    public $name;
    public $area;
    public $center_lat;
    public $center_lng;
    public $polygon_coordinates;
    public $location_note;
    public $status;

    public function mount($block)
    {
        $block = ZoneBlock::findOrFail($block);

        $this->blockId = $block->id;
        $this->zone_id = $block->zone_id;
        $this->block_code = $block->block_code;
        $this->name = $block->name;
        $this->area = $block->area;
        $this->center_lat = $block->center_lat;
        $this->center_lng = $block->center_lng;
        $this->polygon_coordinates = $block->polygon_coordinates;
        $this->location_note = $block->location_note;
        $this->status = $block->status;
    }

    public function update()
    {
        $this->validate([
            'zone_id' => 'required|exists:zones,id',
            'block_code' => [
                'required',
                'string',
                'max:100',
                Rule::unique('zone_blocks', 'block_code')->ignore($this->blockId),
            ],
            'name' => 'nullable|string|max:150',
            'area' => 'nullable|numeric|min:0',
            'center_lat' => 'nullable|numeric',
            'center_lng' => 'nullable|numeric',
            'polygon_coordinates' => 'nullable|string',
            'location_note' => 'nullable|string|max:1000',
            'status' => 'required|in:active,inactive',
        ]);

        ZoneBlock::findOrFail($this->blockId)->update([
            'zone_id' => $this->zone_id,
            'block_code' => $this->block_code,
            'name' => $this->name ?: null,
            'area' => $this->area ?: 0,
            'center_lat' => $this->center_lat ?: null,
            'center_lng' => $this->center_lng ?: null,
            'polygon_coordinates' => $this->polygon_coordinates ?: null,
            'location_note' => $this->location_note ?: null,
            'status' => $this->status,
            'updated_by' => Auth::id(),
        ]);

        session()->flash('success', 'Block updated successfully.');

        return redirect()->route('zone-blocks.index');
    }

    public function with()
    {
        return [
            'zones' => Zone::where('status', 'active')->orderBy('zone_code')->get(),
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
        #blockEditMap {
            height: 430px;
            width: 100%;
            border-radius: 16px;
            border: 1px solid #d1d5db;
            overflow: hidden;
        }

        .map-help {
            margin-bottom: 10px;
            padding: 12px 14px;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #166534;
            border-radius: 12px;
            font-weight: 700;
        }
    </style>

    <div class="page-header">
        <div>
            <h1 class="page-title">Edit Zone Block</h1>
            <p class="page-subtitle">Update sub zone / block and GPS boundary.</p>
        </div>

        <div class="page-actions">
            <a href="{{ route('zone-blocks.index') }}" class="btn gray">
                Back
            </a>
        </div>
    </div>

    <div class="panel">
        <h2 class="panel-title">Block Information</h2>

        <div class="form-grid">
            <div>
                <label>Zone *</label>
                <select wire:model="zone_id">
                    <option value="">Select Zone</option>
                    @foreach($zones as $zone)
                        <option value="{{ $zone->id }}">
                            {{ $zone->zone_code }} - {{ $zone->name }}
                        </option>
                    @endforeach
                </select>
                @error('zone_id') <small>{{ $message }}</small> @enderror
            </div>

            <div>
                <label>Block Code *</label>
                <input type="text" wire:model="block_code">
                @error('block_code') <small>{{ $message }}</small> @enderror
            </div>

            <div>
                <label>Name</label>
                <input type="text" wire:model="name">
            </div>

            <div>
                <label>Area (Ha)</label>
                <input type="number" step="0.01" wire:model="area">
            </div>

            <div>
                <label>Status</label>
                <select wire:model="status">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>

            <div>
                <label>Center Lat</label>
                <input type="number" step="0.00000001" wire:model="center_lat" readonly>
            </div>

            <div>
                <label>Center Lng</label>
                <input type="number" step="0.00000001" wire:model="center_lng" readonly>
            </div>

            <div style="grid-column:1/-1;">
                <label>Location Note</label>
                <textarea wire:model="location_note"></textarea>
            </div>
        </div>

        <input type="hidden" wire:model="polygon_coordinates">

        <div style="margin-top:18px;">
            <div class="map-help">
                Edit block boundary on the map. Draw new polygon to replace old boundary.
            </div>

            <div wire:ignore>
                <div id="blockEditMap"></div>
            </div>
        </div>

        <div class="btn-row" style="margin-top:18px;">
            <button wire:click="update" class="btn">
                Update Block
            </button>

            <a href="{{ route('zone-blocks.index') }}" class="btn gray">
                Cancel
            </a>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const initialLat = Number(@json($center_lat ?: 11.5564));
            const initialLng = Number(@json($center_lng ?: 104.9282));
            const polygonData = @json($polygon_coordinates ? json_decode($polygon_coordinates, true) : []);

            const map = L.map('blockEditMap').setView([initialLat, initialLng], 15);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 20,
                attribution: '&copy; OpenStreetMap'
            }).addTo(map);

            const drawnItems = new L.FeatureGroup();
            map.addLayer(drawnItems);

            if (polygonData && polygonData.length > 0) {
                const polygon = L.polygon(polygonData);
                drawnItems.addLayer(polygon);
                map.fitBounds(polygon.getBounds());
            } else if (initialLat && initialLng) {
                const marker = L.marker([initialLat, initialLng]);
                drawnItems.addLayer(marker);
            }

            const drawControl = new L.Control.Draw({
                edit: {
                    featureGroup: drawnItems
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

            map.addControl(drawControl);

            function savePolygon(layer) {
                if (layer instanceof L.Marker) {
                    const latlng = layer.getLatLng();

                    @this.set('center_lat', latlng.lat.toFixed(8));
                    @this.set('center_lng', latlng.lng.toFixed(8));
                    @this.set('polygon_coordinates', null);

                    return;
                }

                const latLngs = layer.getLatLngs()[0].map(function (point) {
                    return [point.lat, point.lng];
                });

                const center = layer.getBounds().getCenter();

                @this.set('center_lat', center.lat.toFixed(8));
                @this.set('center_lng', center.lng.toFixed(8));
                @this.set('polygon_coordinates', JSON.stringify(latLngs));
            }

            map.on(L.Draw.Event.CREATED, function (event) {
                drawnItems.clearLayers();

                const layer = event.layer;
                drawnItems.addLayer(layer);

                savePolygon(layer);
            });

            map.on(L.Draw.Event.EDITED, function (event) {
                event.layers.eachLayer(function (layer) {
                    savePolygon(layer);
                });
            });

            map.on(L.Draw.Event.DELETED, function () {
                @this.set('center_lat', null);
                @this.set('center_lng', null);
                @this.set('polygon_coordinates', null);
            });
        });
    </script>
</div>