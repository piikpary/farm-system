<?php

use Livewire\Component;
use App\Models\Zone;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

new class extends Component
{
    public $zone_code;
    public $name;
    public $total_area = 0;
    public $center_lat;
    public $center_lng;
    public $polygon_coordinates;
    public $location_note;
    public $status = 'active';

    public function save()
    {
        $this->storeZone();

        session()->flash('success', 'Zone created successfully.');

        return redirect()->route('zones.index');
    }

    public function saveAndNew()
    {
        $this->storeZone();

        session()->flash('success', 'Zone created successfully. You can add another zone.');

        return redirect()->route('zones.create');
    }

    private function storeZone()
    {
        $this->validate([
            'zone_code' => [
                'required',
                'string',
                'max:100',
                Rule::unique('zones', 'zone_code'),
            ],
            'name' => 'nullable|string|max:150',
            'total_area' => 'nullable|numeric|min:0',
            'center_lat' => 'nullable|numeric',
            'center_lng' => 'nullable|numeric',
            'polygon_coordinates' => 'nullable|string',
            'location_note' => 'nullable|string|max:1000',
            'status' => 'required|in:active,inactive',
        ]);

        Zone::create([
            'zone_code' => $this->zone_code,
            'name' => $this->name ?: null,
            'total_area' => $this->total_area ?: 0,
            'center_lat' => $this->center_lat ?: null,
            'center_lng' => $this->center_lng ?: null,
            'polygon_coordinates' => $this->polygon_coordinates ?: null,
            'location_note' => $this->location_note ?: null,
            'status' => $this->status,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);
    }
};

?>

<div class="page">
    @include('components.shared-style')
    @include('components.toast-alert')

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.css">

    <style>
        .excel-table input,
.excel-table select {
    min-width: 160px;
    height: 46px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    padding: 10px 12px;
    font-weight: 700;
    background: #ffffff;
}

.excel-table input[readonly] {
    background: #f8fafc;
    color: #475569;
}

        .excel-table th,
        .excel-table td {
            white-space: nowrap;
            vertical-align: top;
        }

        #zoneCreateMap {
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
            font-weight: 800;
        }

        .error {
            display: block;
            color: #dc2626;
            font-size: 12px;
            margin-top: 4px;
            font-weight: 700;
        }
    </style>

    <div class="page-header">
        <div>
            <h1 class="page-title">Add Zone</h1>
            <p class="page-subtitle">Create one zone and draw its GPS boundary.</p>
        </div>

        <div class="page-actions">
            <a href="{{ route('zones.index') }}" class="btn gray">
                {{ __('pages.back') }}
            </a>
        </div>
    </div>

    <div class="panel">
        <h2 class="panel-title">Zone Information</h2>

        <div class="table-wrap">
            <table class="excel-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Zone Code *</th>
                        <th>Name</th>
                        <th>Total Area (Ha)</th>
                        <th>Status</th>
                        <th>Center Lat</th>
                        <th>Center Lng</th>
                        <th>Location Note</th>
                    </tr>
                </thead>

                <tbody>
                    <tr>
                        <td>1</td>

                        <td>
                            <input type="text"
                                   wire:model="zone_code"
                                   placeholder="U-121">
                            @error('zone_code')
                                <small class="error">{{ $message }}</small>
                            @enderror
                        </td>

                        <td>
                            <input type="text"
                                   wire:model="name"
                                   placeholder="Zone name">
                            @error('name')
                                <small class="error">{{ $message }}</small>
                            @enderror
                        </td>

                        <td>
                            <input type="number"
                                   step="0.01"
                                   wire:model="total_area"
                                   placeholder="12.50">
                            @error('total_area')
                                <small class="error">{{ $message }}</small>
                            @enderror
                        </td>

                        <td>
                            <select wire:model="status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                            @error('status')
                                <small class="error">{{ $message }}</small>
                            @enderror
                        </td>

                        <td>
                            <input type="number"
                                   step="0.00000001"
                                   wire:model="center_lat"
                                   readonly>
                            @error('center_lat')
                                <small class="error">{{ $message }}</small>
                            @enderror
                        </td>

                        <td>
                            <input type="number"
                                   step="0.00000001"
                                   wire:model="center_lng"
                                   readonly>
                            @error('center_lng')
                                <small class="error">{{ $message }}</small>
                            @enderror
                        </td>

                        <td>
                            <input type="text"
                                wire:model="location_note"
                                placeholder="Location note">
                            @error('location_note')
                                <small class="error">{{ $message }}</small>
                            @enderror
                        </td>
                    </tr>
                </tbody>

                <tfoot>
                    <tr style="background:#f8fafc;font-weight:900;">
                        <td colspan="3" style="text-align:right;">Total Area</td>
                        <td>{{ number_format((float) $total_area, 2) }} ha</td>
                        <td colspan="4">-</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <input type="hidden" wire:model="polygon_coordinates">

        <div style="margin-top:18px;">
            <div class="map-help">
                Draw zone boundary on the map. Use polygon or rectangle. Center latitude and longitude will auto-fill.
            </div>

            <div wire:ignore>
                <div id="zoneCreateMap"></div>
            </div>
        </div>

        <div class="btn-row" style="margin-top:18px;">
            <button wire:click="save" class="btn">
                Save Zone
            </button>

            <button wire:click="saveAndNew" class="btn light">
                Save & Add Another
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
            const defaultLat = 11.5564;
            const defaultLng = 104.9282;

            const map = L.map('zoneCreateMap').setView([defaultLat, defaultLng], 15);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 20,
                attribution: '&copy; OpenStreetMap'
            }).addTo(map);

            const drawnItems = new L.FeatureGroup();
            map.addLayer(drawnItems);

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

            function saveShape(layer) {
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

                saveShape(layer);
            });

            map.on(L.Draw.Event.EDITED, function (event) {
                event.layers.eachLayer(function (layer) {
                    saveShape(layer);
                });
            });

            map.on(L.Draw.Event.DELETED, function () {
                @this.set('center_lat', null);
                @this.set('center_lng', null);
                @this.set('polygon_coordinates', null);
            });

            setTimeout(function () {
                map.invalidateSize();
            }, 300);
        });
    </script>
</div>