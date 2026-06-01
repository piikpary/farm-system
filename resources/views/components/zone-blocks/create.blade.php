<?php

use Livewire\Component;
use App\Models\Zone;
use App\Models\ZoneBlock;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

new class extends Component
{
    public $zone_id;
    public $block_code;
    public $name;
    public $area = 0;
    public $center_lat;
    public $center_lng;
    public $polygon_coordinates;
    public $location_note;
    public $status = 'active';

    public function save()
    {
        $this->storeBlock();

        session()->flash('success', 'Block created successfully.');

        return redirect()->route('zone-blocks.index');
    }

    public function saveAndNew()
    {
        $this->storeBlock();

        session()->flash('success', 'Block created successfully. You can add another block.');

        return redirect()->route('zone-blocks.create');
    }

    private function storeBlock()
    {
        $this->validate([
            'zone_id' => 'required|exists:zones,id',
            'block_code' => [
                'required',
                'string',
                'max:100',
                Rule::unique('zone_blocks', 'block_code'),
            ],
            'name' => 'nullable|string|max:150',
            'area' => 'nullable|numeric|min:0',
            'center_lat' => 'nullable|numeric',
            'center_lng' => 'nullable|numeric',
            'polygon_coordinates' => 'nullable|string',
            'location_note' => 'nullable|string|max:1000',
            'status' => 'required|in:active,inactive',
        ]);

        ZoneBlock::create([
            'zone_id' => $this->zone_id,
            'block_code' => $this->block_code,
            'name' => $this->name ?: null,
            'area' => $this->area ?: 0,
            'center_lat' => $this->center_lat ?: null,
            'center_lng' => $this->center_lng ?: null,
            'polygon_coordinates' => $this->polygon_coordinates ?: null,
            'location_note' => $this->location_note ?: null,
            'status' => $this->status,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);
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

        #blockCreateMap {
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
            <h1 class="page-title">Add Zone Block</h1>
            <p class="page-subtitle">Create one sub zone / block and draw GPS boundary.</p>
        </div>

        <div class="page-actions">
            <a href="{{ route('zone-blocks.index') }}" class="btn gray">
                Back
            </a>
        </div>
    </div>

    <div class="panel">
        <h2 class="panel-title">Block Information</h2>

        <div class="table-wrap">
            <table class="excel-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Zone *</th>
                        <th>Block Code *</th>
                        <th>Name</th>
                        <th>Area (Ha)</th>
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
                            <select wire:model="zone_id">
                                <option value="">Select Zone</option>
                                @foreach($zones as $zone)
                                    <option value="{{ $zone->id }}">
                                        {{ $zone->zone_code }} - {{ $zone->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('zone_id')
                                <small class="error">{{ $message }}</small>
                            @enderror
                        </td>

                        <td>
                            <input type="text"
                                   wire:model="block_code"
                                   placeholder="Z1-B01">
                            @error('block_code')
                                <small class="error">{{ $message }}</small>
                            @enderror
                        </td>

                        <td>
                            <input type="text"
                                   wire:model="name"
                                   placeholder="Block name">
                            @error('name')
                                <small class="error">{{ $message }}</small>
                            @enderror
                        </td>

                        <td>
                            <input type="number"
                                   step="0.01"
                                   wire:model="area"
                                   placeholder="12.50">
                            @error('area')
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
                        <td colspan="4" style="text-align:right;">Total Area</td>
                        <td>{{ number_format((float) $area, 2) }} ha</td>
                        <td colspan="4">-</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <input type="hidden" wire:model="polygon_coordinates">

        <div style="margin-top:18px;">
            <div class="map-help">
                Draw block boundary on the map. This block should be inside the selected zone.
            </div>

            <div wire:ignore>
                <div id="blockCreateMap"></div>
            </div>
        </div>

        <div class="btn-row" style="margin-top:18px;">
            <button wire:click="save" class="btn">
                Save Block
            </button>

            <button wire:click="saveAndNew" class="btn light">
                Save & Add Another
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
            const defaultLat = 11.5564;
            const defaultLng = 104.9282;

            const map = L.map('blockCreateMap').setView([defaultLat, defaultLng], 15);

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