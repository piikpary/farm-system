<?php

use Livewire\Component;
use App\Models\FarmWorkLog;

new class extends Component
{
    public $farmWorkLogId;
    public $log;
    public $zonePolygon = [];
    public $trackPoints = [];
    public $actionPoints = [];
    public $centerLat = 11.5564;
    public $centerLng = 104.9282;

    public function mount($farmWorkLog)
    {
        $this->log = FarmWorkLog::with([
            'driver',
            'tractor',
            'zone',
            'taskCategory',
        ])->findOrFail($farmWorkLog);

        $this->farmWorkLogId = $this->log->id;

        $this->zonePolygon = $this->log->zone?->polygon_coordinates ?? [];

        if ($this->log->zone && $this->log->zone->center_lat && $this->log->zone->center_lng) {
            $this->centerLat = $this->log->zone->center_lat;
            $this->centerLng = $this->log->zone->center_lng;
        }

        $this->trackPoints = $this->log->gpsTracks()
            ->orderBy('tracked_at')
            ->get()
            ->map(fn ($track) => [
                'lat' => (float) $track->lat,
                'lng' => (float) $track->lng,
                'tracked_at' => optional($track->tracked_at)->format('Y-m-d H:i:s'),
            ])
            ->toArray();

        $this->actionPoints = $this->log->workActions()
            ->orderBy('action_at')
            ->get()
            ->map(fn ($action) => [
                'id' => $action->id,
                'action_type' => $action->action_type,
                'lat' => $action->lat ? (float) $action->lat : null,
                'lng' => $action->lng ? (float) $action->lng : null,
                'note' => $action->note,
                'action_at' => optional($action->action_at)->format('Y-m-d H:i:s'),
            ])
            ->toArray();
    }
};

?>

<div class="page">
    @include('components.shared-style')
    @include('components.toast-alert')

    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css">

    <style>
        #trackingMap {
            height: 620px;
            width: 100%;
            border-radius: 16px;
            border: 1px solid #e5e7eb;
            overflow: hidden;
        }

        .tracking-status {
            padding: 12px 14px;
            border-radius: 12px;
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            font-weight: 800;
            color: #334155;
            margin-bottom: 14px;
        }

        .tracking-status.active {
            background: #dcfce7;
            color: #166534;
            border-color: #bbf7d0;
        }

        .action-panel {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 14px;
        }

        .action-btn {
            border: none;
            padding: 10px 12px;
            border-radius: 10px;
            font-weight: 900;
            cursor: pointer;
            background: #e5e7eb;
            color: #111827;
        }

        .action-btn.start {
            background: #16a34a;
            color: white;
        }

        .action-btn.pause {
            background: #f59e0b;
            color: white;
        }

        .action-btn.problem {
            background: #dc2626;
            color: white;
        }

        .action-btn.finish {
            background: #0f172a;
            color: white;
        }

        .legend {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 12px;
            font-size: 13px;
            color: #334155;
            font-weight: 800;
        }

        .legend span {
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            padding: 6px 10px;
            border-radius: 999px;
        }
    </style>

    <div class="page-header">
        <div>
            <h1 class="page-title">{{ __('pages.driver_action_map') }}</h1>
            <p class="page-subtitle">
                {{ __('pages.driver_action_map_subtitle') }}
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

            <a href="{{ route('farm-work-logs.index') }}" class="btn gray">
                {{ __('pages.back') }}
            </a>
        </div>
    </div>

    <div class="summary-grid">
        <div class="summary-card">
            <div class="summary-label">{{ __('pages.driver') }}</div>
            <div class="summary-value">{{ $log->driver->name ?? '-' }}</div>
        </div>

        <div class="summary-card">
            <div class="summary-label">{{ __('pages.tractor') }}</div>
            <div class="summary-value">{{ $log->tractor->tractor_no ?? '-' }}</div>
        </div>

        <div class="summary-card">
            <div class="summary-label">{{ __('pages.zone') }}</div>
            <div class="summary-value">{{ $log->zone->zone_code ?? '-' }}</div>
        </div>

        <div class="summary-card">
            <div class="summary-label">{{ __('pages.actions_gps') }}</div>
            <div class="summary-value">
                <span id="actionCount">{{ count($actionPoints) }}</span> /
                <span id="gpsCount">{{ count($trackPoints) }}</span>
            </div>
        </div>
    </div>

    <div class="panel">
        <div id="trackingStatus" class="tracking-status">
            {{ __('pages.gps_stopped') }}
        </div>

        <div class="legend">
            <span>🟩 {{ __('pages.zone_boundary') }}</span>
            <span>🔵 {{ __('pages.gps_route') }}</span>
            <span>▶ {{ __('pages.start_work') }}</span>
            <span>⏸ {{ __('pages.pause') }}</span>
            <span>▶ {{ __('pages.resume') }}</span>
            <span>⛽ {{ __('pages.refill_diesel') }}</span>
            <span>⚠ {{ __('pages.problem') }}</span>
            <span>✅ {{ __('pages.finish_work') }}</span>
        </div>

        <div class="action-panel">
            <button type="button" class="action-btn start" onclick="saveAction('start_work')">
                ▶ {{ __('pages.start_work') }}
            </button>

            <button type="button" class="action-btn pause" onclick="saveAction('pause_work')">
                ⏸ {{ __('pages.pause') }}
            </button>

            <button type="button" class="action-btn start" onclick="saveAction('resume_work')">
                ▶ {{ __('pages.resume') }}
            </button>

            <button type="button" class="action-btn" onclick="saveAction('refill_diesel')">
                ⛽ {{ __('pages.refill_diesel') }}
            </button>

            <button type="button" class="action-btn problem" onclick="saveAction('problem')">
                ⚠ {{ __('pages.problem') }}
            </button>

            <button type="button" class="action-btn finish" onclick="saveAction('finish_work')">
                ✅ {{ __('pages.finish_work') }}
            </button>
        </div>

        <div class="btn-row" style="margin-bottom: 14px;">
            <button type="button" class="btn" onclick="startTracking()">
                {{ __('pages.start_gps') }}
            </button>

            <button type="button" class="btn red" onclick="stopTracking()">
                {{ __('pages.stop_gps') }}
            </button>

            <button type="button" class="btn light" onclick="window.location.reload()">
                {{ __('pages.reload_map') }}
            </button>
        </div>

        <div id="trackingMap"></div>
    </div>

    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

    <script>
        let map;
        let zonePolygon = @json($zonePolygon);
        let trackPoints = @json($trackPoints);
        let actionPoints = @json($actionPoints);
        let trackingLine;
        let liveMarker;
        let watchId = null;
        let lastLat = null;
        let lastLng = null;

        const farmWorkLogId = @json($farmWorkLogId);
        const centerLat = Number(@json($centerLat));
        const centerLng = Number(@json($centerLng));

        const text = {
            latestGpsPoint: @json(__('pages.latest_gps_point')),
            time: @json(__('pages.time')),
            note: @json(__('pages.note')),
            gpsNotSupported: @json(__('pages.gps_not_supported')),
            gpsRunning: @json(__('pages.gps_running')),
            gpsStopped: @json(__('pages.gps_stopped_short')),
            gpsError: @json(__('pages.gps_error')),
            problemNotePrompt: @json(__('pages.problem_note_prompt')),
            actionSaved: @json(__('pages.action_saved')),
            failedToSaveAction: @json(__('pages.failed_to_save_action')),
        };

        const actionLabels = {
            start_work: '▶ ' + @json(__('pages.start_work')),
            pause_work: '⏸ ' + @json(__('pages.pause')),
            resume_work: '▶ ' + @json(__('pages.resume')),
            refill_diesel: '⛽ ' + @json(__('pages.refill_diesel')),
            problem: '⚠ ' + @json(__('pages.problem')),
            finish_work: '✅ ' + @json(__('pages.finish_work')),
        };

        const actionColors = {
            start_work: '#16a34a',
            pause_work: '#f59e0b',
            resume_work: '#22c55e',
            refill_diesel: '#2563eb',
            problem: '#dc2626',
            finish_work: '#0f172a',
        };

        function initTrackingMap() {
            if (map) {
                return;
            }

            map = L.map('trackingMap').setView([centerLat, centerLng], 15);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 22,
                attribution: '&copy; OpenStreetMap'
            }).addTo(map);

            drawZonePolygon();
            drawTrackingLine();
            drawActionMarkers();

            setTimeout(() => map.invalidateSize(), 300);
        }

        function drawZonePolygon() {
            if (!zonePolygon || zonePolygon.length < 3) {
                return;
            }

            const polygon = L.polygon(zonePolygon.map(p => [p.lat, p.lng]), {
                color: '#16a34a',
                fillColor: '#22c55e',
                fillOpacity: 0.18,
                weight: 3,
            }).addTo(map);

            map.fitBounds(polygon.getBounds());
        }

        function drawTrackingLine() {
            if (trackingLine) {
                map.removeLayer(trackingLine);
            }

            if (!trackPoints || trackPoints.length === 0) {
                return;
            }

            const latlngs = trackPoints.map(p => [p.lat, p.lng]);

            trackingLine = L.polyline(latlngs, {
                color: '#2563eb',
                weight: 4,
            }).addTo(map);

            const lastPoint = trackPoints[trackPoints.length - 1];

            lastLat = lastPoint.lat;
            lastLng = lastPoint.lng;

            if (liveMarker) {
                map.removeLayer(liveMarker);
            }

            liveMarker = L.marker([lastPoint.lat, lastPoint.lng])
                .addTo(map)
                .bindPopup(text.latestGpsPoint);

            document.getElementById('gpsCount').innerText = trackPoints.length;
        }

        function drawActionMarkers() {
            if (!actionPoints || actionPoints.length === 0) {
                return;
            }

            actionPoints.forEach(action => {
                if (!action.lat || !action.lng) {
                    return;
                }

                addActionMarker(action);
            });

            document.getElementById('actionCount').innerText = actionPoints.length;
        }

        function addActionMarker(action) {
            const color = actionColors[action.action_type] ?? '#0f172a';
            const label = actionLabels[action.action_type] ?? action.action_type;

            const markerHtml = `
                <div style="
                    background:${color};
                    color:white;
                    width:32px;
                    height:32px;
                    border-radius:50%;
                    display:flex;
                    align-items:center;
                    justify-content:center;
                    font-size:14px;
                    font-weight:900;
                    border:2px solid white;
                    box-shadow:0 4px 12px rgba(0,0,0,.25);
                ">
                    ${label.substring(0, 1)}
                </div>
            `;

            const icon = L.divIcon({
                html: markerHtml,
                className: '',
                iconSize: [32, 32],
                iconAnchor: [16, 16],
            });

            L.marker([action.lat, action.lng], { icon })
                .addTo(map)
                .bindPopup(`
                    <strong>${label}</strong><br>
                    ${text.time}: ${action.action_at ?? '-'}<br>
                    ${text.note}: ${action.note ?? '-'}
                `);
        }

        function startTracking() {
            if (!navigator.geolocation) {
                alert(text.gpsNotSupported);
                return;
            }

            if (watchId !== null) {
                return;
            }

            const status = document.getElementById('trackingStatus');
            status.classList.add('active');
            status.innerText = text.gpsRunning;

            watchId = navigator.geolocation.watchPosition(
                function (position) {
                    const lat = Number(position.coords.latitude.toFixed(7));
                    const lng = Number(position.coords.longitude.toFixed(7));
                    const speed = position.coords.speed ? Number(position.coords.speed.toFixed(2)) : null;
                    const accuracy = position.coords.accuracy ? Number(position.coords.accuracy.toFixed(2)) : null;

                    lastLat = lat;
                    lastLng = lng;

                    const point = {
                        lat: lat,
                        lng: lng,
                        tracked_at: new Date().toISOString(),
                    };

                    trackPoints.push(point);
                    drawTrackingLine();

                    fetch(@json(route('driver-gps-tracks.store')), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': @json(csrf_token()),
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            farm_work_log_id: farmWorkLogId,
                            lat: lat,
                            lng: lng,
                            speed: speed,
                            accuracy: accuracy,
                            tracked_at: point.tracked_at,
                        }),
                    });
                },
                function (error) {
                    alert(text.gpsError + ': ' + error.message);
                    stopTracking();
                },
                {
                    enableHighAccuracy: true,
                    maximumAge: 0,
                    timeout: 10000,
                }
            );
        }

        function stopTracking() {
            if (watchId !== null) {
                navigator.geolocation.clearWatch(watchId);
                watchId = null;
            }

            const status = document.getElementById('trackingStatus');
            status.classList.remove('active');
            status.innerText = text.gpsStopped;
        }

        function saveAction(actionType) {
            if (!navigator.geolocation) {
                saveActionWithPosition(actionType, lastLat, lastLng);
                return;
            }

            navigator.geolocation.getCurrentPosition(
                function (position) {
                    const lat = Number(position.coords.latitude.toFixed(7));
                    const lng = Number(position.coords.longitude.toFixed(7));

                    lastLat = lat;
                    lastLng = lng;

                    saveActionWithPosition(actionType, lat, lng);
                },
                function () {
                    saveActionWithPosition(actionType, lastLat, lastLng);
                },
                {
                    enableHighAccuracy: true,
                    maximumAge: 0,
                    timeout: 8000,
                }
            );
        }

        function saveActionWithPosition(actionType, lat, lng) {
            let note = '';

            if (actionType === 'problem') {
                note = prompt(text.problemNotePrompt) || '';
            }

            fetch(@json(route('driver-work-actions.store')), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': @json(csrf_token()),
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    farm_work_log_id: farmWorkLogId,
                    action_type: actionType,
                    lat: lat,
                    lng: lng,
                    note: note,
                    action_at: new Date().toISOString(),
                }),
            })
            .then(response => response.json())
            .then(data => {
                const action = {
                    id: data.action_id,
                    action_type: actionType,
                    lat: lat,
                    lng: lng,
                    note: note,
                    action_at: new Date().toLocaleString(),
                };

                actionPoints.push(action);
                addActionMarker(action);

                document.getElementById('actionCount').innerText = actionPoints.length;

                alert(text.actionSaved + ': ' + (actionLabels[actionType] ?? actionType));
            })
            .catch(error => {
                console.error(error);
                alert(text.failedToSaveAction);
            });
        }

        document.addEventListener('DOMContentLoaded', initTrackingMap);
    </script>
</div>