<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Work</title>

    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f1f5f9;
            color: #0f172a;
        }

        .page {
            max-width: 520px;
            margin: 0 auto;
            min-height: 100vh;
            background: white;
            padding: 16px;
        }

        .header {
            background: #166534;
            color: white;
            padding: 18px;
            border-radius: 18px;
            margin-bottom: 16px;
        }

        .header h1 {
            margin: 0;
            font-size: 22px;
        }

        .card {
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 14px;
            margin-bottom: 14px;
            background: #ffffff;
        }

        .row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .row:last-child {
            border-bottom: none;
        }

        .label {
            color: #64748b;
            font-weight: bold;
        }

        .value {
            font-weight: bold;
            text-align: right;
        }

        .btn-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        button {
            border: none;
            border-radius: 12px;
            padding: 14px;
            font-weight: bold;
            cursor: pointer;
            color: white;
        }

        .start { background: #16a34a; }
        .pause { background: #f59e0b; }
        .resume { background: #22c55e; }
        .finish { background: #0f172a; grid-column: 1 / -1; }
    </style>
</head>

<body>
<div class="page">
    <div class="header">
        <h1>Driver Work</h1>
        <p>{{ optional($log->work_date)->format('d M Y') }}</p>
    </div>

    <div class="card">
        <div class="row">
            <span class="label">Status</span>
            <span class="value" id="workStatus">{{ ucfirst($log->work_status ?? 'pending') }}</span>
        </div>

        <div class="row">
            <span class="label">Driver</span>
            <span class="value">{{ $log->driver->name ?? '-' }}</span>
        </div>

        <div class="row">
            <span class="label">Tractor</span>
            <span class="value">{{ $log->tractor->tractor_no ?? '-' }}</span>
        </div>

        <div class="row">
            <span class="label">Zone</span>
            <span class="value">{{ $log->zone->zone_code ?? '-' }}</span>
        </div>

        <div class="row">
            <span class="label">Task</span>
            <span class="value">{{ $log->taskCategory->name ?? '-' }}</span>
        </div>
    </div>

    <div class="card">
        <div class="row">
            <span class="label">GPS Distance</span>
            <span class="value">{{ number_format($log->gps_distance_meters ?? 0, 2) }} m</span>
        </div>

        <div class="row">
            <span class="label">Estimated Area</span>
            <span class="value">{{ number_format($log->estimated_plowed_area ?? 0, 4) }} ha</span>
        </div>

        <div class="row">
            <span class="label">Progress</span>
            <span class="value">{{ number_format($log->gps_progress_percent ?? 0, 2) }}%</span>
        </div>
    </div>

    <div class="btn-grid">
        <button class="start" onclick="saveAction('start_work')">▶ Start</button>
        <button class="pause" onclick="saveAction('pause_work')">⏸ Pause</button>
        <button class="resume" onclick="saveAction('resume_work')">▶ Resume</button>
        <button class="finish" onclick="saveAction('finish_work')">✅ Finish Work</button>
    </div>
</div>

<script>
    const actionUrl = @json(route('driver.work.action', $token));
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    function saveAction(actionType) {
        let lat = null;
        let lng = null;

        function sendAction() {
            fetch(actionUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    action_type: actionType,
                    lat: lat,
                    lng: lng,
                    action_at: new Date().toISOString(),
                }),
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('workStatus').innerText = data.work_status;
                    alert(data.message);

                    if (actionType === 'finish_work') {
                        setTimeout(() => window.location.reload(), 800);
                    }
                } else {
                    alert(data.message || 'Action failed.');
                }
            })
            .catch(() => {
                alert('Failed to save action.');
            });
        }

        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                function (position) {
                    lat = Number(position.coords.latitude.toFixed(7));
                    lng = Number(position.coords.longitude.toFixed(7));
                    sendAction();
                },
                function () {
                    sendAction();
                }
            );
        } else {
            sendAction();
        }
    }
</script>
</body>
</html>