<?php

use Livewire\Component;
use App\Models\Zone;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

new class extends Component
{
    public $zoneId;
    public $zone_code;
    public $name;
    public $total_area;
    public $center_lat;
    public $center_lng;
    public $polygon_coordinates;
    public $location_note;
    public $status;

    public function mount($zone)
    {
        $zone = Zone::findOrFail($zone);

        $this->zoneId = $zone->id;
        $this->zone_code = $zone->zone_code;
        $this->name = $zone->name;
        $this->total_area = $zone->total_area;
        $this->center_lat = $zone->center_lat;
        $this->center_lng = $zone->center_lng;
        $this->polygon_coordinates = $zone->polygon_coordinates;
        $this->location_note = $zone->location_note;
        $this->status = $zone->status;
    }

    public function update()
    {
        $this->validate([
            'zone_code' => [
                'required',
                'string',
                'max:100',
                Rule::unique('zones', 'zone_code')->ignore($this->zoneId),
            ],
            'name' => 'nullable|string|max:150',
            'total_area' => 'nullable|numeric|min:0',
            'center_lat' => 'nullable|numeric',
            'center_lng' => 'nullable|numeric',
            'polygon_coordinates' => 'nullable|string',
            'location_note' => 'nullable|string|max:1000',
            'status' => 'required|in:active,inactive',
        ]);

        Zone::findOrFail($this->zoneId)->update([
            'zone_code' => $this->zone_code,
            'name' => $this->name ?: null,
            'total_area' => $this->total_area ?: 0,
            'center_lat' => $this->center_lat ?: null,
            'center_lng' => $this->center_lng ?: null,
            'polygon_coordinates' => $this->polygon_coordinates ?: null,
            'location_note' => $this->location_note ?: null,
            'status' => $this->status,
            'updated_by' => Auth::id(),
        ]);

        session()->flash('success', 'Zone updated successfully.');

        return redirect()->route('zones.index');
    }
};

?>

<div class="page">
    @include('components.shared-style')
    @include('components.toast-alert')

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.css">

    <style>
        #zoneEditMap {
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
            <h1 class="page-title">Edit Zone</h1>
            <p class="page-subtitle">Update zone information and GPS boundary.</p>
        </div>

        <div class="page-actions">
            <a href="{{ route('zones.index') }}" class="btn gray">
                {{ __('pages.back') }}
            </a>
        </div>
    </div>

    <div class="panel">
        <h2 class="panel-title">Zone Information</h2>

        <div class="form-grid">
            <div>
                <label>Zone Code *</label>
                <input type="text" wire:model="zone_code">
                @error('zone_code') <small>{{ $message }}</small> @enderror
            </div>

            <div>
                <label>Name</label>
                <input type="text" wire:model="name">
            </div>

            <div>
                <label>Total Area (Ha)</label>
                <input type="number" step="0.01" wire:model="total_area">
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
                Edit zone boundary on the map. Draw new polygon to replace old boundary.
            </div>

            <div wire:ignore>
                <div id="zoneEditMap"></div>
            </div>
        </div>

        <div class="btn-row" style="margin-top:18px;">
            <button wire:click="update" class="btn">
                Update Zone
            </button>

            <a href="{{ route('zones.index') }}" class="btn gray">
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

            const map = L.map('zoneEditMap').setView([initialLat, initialLng], 15);

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