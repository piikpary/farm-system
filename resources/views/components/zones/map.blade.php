<?php

use Livewire\Component;
use App\Models\Zone;
use Illuminate\Support\Facades\Auth;

new class extends Component
{
    public $zoneId;
    public $zone_code;
    public $name;
    public $total_area;
    public $center_lat;
    public $center_lng;
    public $polygon_coordinates = [];

    public function mount($zone)
    {
        $zone = Zone::findOrFail($zone);

        $this->zoneId = $zone->id;
        $this->zone_code = $zone->zone_code;
        $this->name = $zone->name;
        $this->total_area = $zone->total_area;
        $this->center_lat = $zone->center_lat ?: 11.5564;
        $this->center_lng = $zone->center_lng ?: 104.9282;
        $this->polygon_coordinates = $zone->polygon_coordinates ?: [];
    }

    public function saveMap()
    {
        $this->validate([
            'center_lat' => 'required|numeric',
            'center_lng' => 'required|numeric',
            'polygon_coordinates' => 'nullable|array',
        ]);

        Zone::findOrFail($this->zoneId)->update([
            'center_lat' => $this->center_lat,
            'center_lng' => $this->center_lng,
            'polygon_coordinates' => $this->polygon_coordinates,
            'updated_by' => Auth::id(),
        ]);

        session()->flash('success', __('pages.zone_map_saved_success'));
    }
};

?>

<div class="page">
    @include('components.shared-style')

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">

    <style>
        #zoneMap {
            height: 580px;
            width: 100%;
            border-radius: 16px;
            border: 1px solid #e5e7eb;
            overflow: hidden;
            background: #f8fafc;
        }

        .map-help {
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            padding: 12px 14px;
            border-radius: 12px;
            color: #334155;
            font-weight: 700;
            margin-bottom: 14px;
        }

        .map-point-count {
            background: #ecfdf5;
            color: #166534;
            border: 1px solid #bbf7d0;
            padding: 10px 12px;
            border-radius: 12px;
            font-weight: 900;
            margin-bottom: 14px;
        }
    </style>

    <div class="page-header">
        <div>
            <h1 class="page-title">{{ __('pages.zone_map') }}</h1>
            <p class="page-subtitle">
                {{ __('pages.draw_land_boundary_for') }}
                {{ $zone_code }} - {{ $name ?? __('pages.unnamed_zone') }}
            </p>
        </div>

        <div class="page-actions">
            <div class="language-switcher">
                <a href="{{ route('language.switch', 'en') }}"
                   class="lang-btn {{ app()->getLocale() === 'en' ? 'active' : '' }}">
                    EN
                </a>

                <a href="{{ route('language.switch', 'km') }}"
                   class="lang-btn {{ app()->getLocale() === 'km' ? 'active' : '' }}">
                    ខ្មែរ
                </a>
            </div>

            <a href="{{ route('zones.index') }}" class="btn gray">
                {{ __('pages.back') }}
            </a>

            <button wire:click="saveMap" class="btn">
                {{ __('pages.save_map') }}
            </button>
        </div>
    </div>

    @if (session('success'))
        <div class="alert">{{ session('success') }}</div>
    @endif

    <div class="summary-grid">
        <div class="summary-card">
            <div class="summary-label">{{ __('pages.zone_code') }}</div>
            <div class="summary-value">{{ $zone_code }}</div>
        </div>

        <div class="summary-card">
            <div class="summary-label">{{ __('pages.zone_name') }}</div>
            <div class="summary-value">{{ $name ?? '-' }}</div>
        </div>

        <div class="summary-card">
            <div class="summary-label">{{ __('pages.total_area') }}</div>
            <div class="summary-value">{{ number_format($total_area ?? 0, 2) }} ha</div>
        </div>

        <div class="summary-card">
            <div class="summary-label">{{ __('pages.polygon_points') }}</div>
            <div class="summary-value" id="pointCount">{{ count($polygon_coordinates ?? []) }}</div>
        </div>
    </div>

    <div class="panel">
        <div class="map-help">
            {{ __('pages.zone_map_help') }}
        </div>

        <div class="map-point-count">
            {{ __('pages.selected_points') }}:
            <span id="selectedPointCount">{{ count($polygon_coordinates ?? []) }}</span>
        </div>

        <div class="btn-row" style="margin-bottom: 14px;">
            <button type="button" class="btn light" onclick="clearPolygon()">
                {{ __('pages.clear_polygon') }}
            </button>

            <button type="button" class="btn light" onclick="saveCurrentCenter()">
                {{ __('pages.save_current_center') }}
            </button>

            <button type="button" class="btn light" onclick="fixMapSize()">
                {{ __('pages.fix_map_view') }}
            </button>
        </div>

        <div wire:ignore>
            <div id="zoneMap"></div>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
        let map = null;
        let polygon = null;
        let markers = [];

        const initialLat = Number(@json($center_lat));
        const initialLng = Number(@json($center_lng));
        let points = @json($polygon_coordinates ?? []);
        const pointText = @json(__('pages.point'));

        function initZoneMap() {
            if (map !== null) {
                return;
            }

            map = L.map('zoneMap', {
                center: [initialLat, initialLng],
                zoom: 15,
                zoomControl: true,
            });

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 22,
                attribution: '&copy; OpenStreetMap'
            }).addTo(map);

            redrawPolygon();

            if (points.length >= 3) {
                const bounds = points.map(p => [p.lat, p.lng]);
                map.fitBounds(bounds);
            }

            map.on('click', function (e) {
                const point = {
                    lat: Number(e.latlng.lat.toFixed(7)),
                    lng: Number(e.latlng.lng.toFixed(7)),
                };

                points.push(point);

                redrawPolygon();
                updatePointCount();
                updateLivewireOnly();
            });

            setTimeout(function () {
                map.invalidateSize();
            }, 500);
        }

        function redrawPolygon() {
            markers.forEach(marker => map.removeLayer(marker));
            markers = [];

            if (polygon) {
                map.removeLayer(polygon);
                polygon = null;
            }

            points.forEach((point, index) => {
                const marker = L.marker([point.lat, point.lng])
                    .addTo(map)
                    .bindPopup(pointText + ' ' + (index + 1));

                markers.push(marker);
            });

            if (points.length >= 3) {
                polygon = L.polygon(points.map(p => [p.lat, p.lng]), {
                    color: '#16a34a',
                    fillColor: '#22c55e',
                    fillOpacity: 0.25,
                    weight: 3,
                }).addTo(map);
            }
        }

        function clearPolygon() {
            points = [];
            redrawPolygon();
            updatePointCount();
            updateLivewireOnly();
        }

        function saveCurrentCenter() {
            const center = map.getCenter();

            @this.set('center_lat', Number(center.lat.toFixed(7)));
            @this.set('center_lng', Number(center.lng.toFixed(7)));
        }

        function updateLivewireOnly() {
            @this.set('polygon_coordinates', points, false);

            if (points.length > 0) {
                @this.set('center_lat', points[0].lat, false);
                @this.set('center_lng', points[0].lng, false);
            }
        }

        function updatePointCount() {
            document.getElementById('selectedPointCount').innerText = points.length;
            document.getElementById('pointCount').innerText = points.length;
        }

        function fixMapSize() {
            if (map) {
                map.invalidateSize();
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            initZoneMap();
        });

        document.addEventListener('livewire:navigated', function () {
            initZoneMap();
        });
    </script>
</div>