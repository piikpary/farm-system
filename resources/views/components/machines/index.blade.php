@props(['machines', 'search'])

<div class="page">
    @include('components.shared-style')
    @include('components.toast-alert')

    <style>
        .master-toolbar { display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; margin-bottom:14px; }
        .filter-box { display:flex; align-items:center; gap:10px; flex:1; max-width:480px; }
        .filter-box input { width:100%; height:44px; border:1px solid #d1d5db; border-radius:12px; padding:10px 14px; font-weight:700; background:#fff; }

        .master-table-wrap { overflow-x:auto; border:1px solid #e5e7eb; border-radius:16px; }
        .master-table { width:100%; min-width:1180px; border-collapse:collapse; background:#fff; }
        .master-table th { background:#f8fafc; color:#0f172a; font-size:12px; font-weight:900; text-transform:uppercase; padding:12px 10px; border-bottom:1px solid #e5e7eb; white-space:nowrap; }
        .master-table td { padding:10px; border-bottom:1px solid #eef2f7; vertical-align:middle; white-space:nowrap; }

        .master-table input,
        .master-table select {
            width:100%;
            min-width:140px;
            height:44px;
            padding:9px 10px;
            border:1px solid #d1d5db;
            border-radius:10px;
            font-size:13px;
            background:#fff;
            font-weight:700;
        }

        .wide-input { min-width:240px !important; }
        .row-no { width:45px; min-width:45px; text-align:center; font-weight:900; color:#64748b; }
        .new-row { background:#f0fdf4; }
        .new-row td { border-bottom:1px solid #bbf7d0; }
        .table-actions { display:flex; gap:6px; align-items:center; flex-wrap:wrap; }

        .total-row { background:#f8fafc; font-weight:900; border-top:2px solid #d1d5db; }
        .total-row td { border-bottom:0; padding:14px 10px; color:#0f172a; }
        .total-label { text-align:right; font-weight:900; }

        .plus-cell {
            width:34px;
            height:34px;
            border:none;
            border-radius:10px;
            background:#16a34a;
            color:#fff;
            font-size:20px;
            font-weight:900;
            cursor:pointer;
            display:inline-flex;
            align-items:center;
            justify-content:center;
            text-decoration:none;
        }

        .plus-cell:hover { background:#15803d; }
        .danger-plus { background:#dc2626; }
        .danger-plus:hover { background:#b91c1c; }

        .error { display:block; color:#dc2626; font-size:12px; margin-top:4px; font-weight:700; }
    </style>

    <div class="page-header">
        <div>
            <h1 class="page-title">Machine</h1>
            <p class="page-subtitle">List of farm machines.</p>
        </div>

        <div class="page-actions">
            <a href="{{ route('dashboard') }}" class="btn gray">Dashboard</a>
        </div>
    </div>

    <div class="panel">
        <div class="master-toolbar">
            <form method="GET" action="{{ route('machines.index') }}" class="filter-box">
                <input
                    type="text"
                    name="search"
                    value="{{ $search }}"
                    placeholder="Filter machine, number, brand, status"
                >
            </form>
        </div>

        <div class="master-table-wrap">
            <table class="master-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Machine *</th>
                        <th>Machine No</th>
                        <th>Brand</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th width="190">Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($machines as $machine)
                        @if((int) request('edit') === $machine->id)
                            <tr class="new-row">
                                <td class="row-no">
                                    {{ $machines->firstItem() + $loop->index }}
                                </td>

                                <td>
                                    <input form="update-machine-{{ $machine->id }}" type="text" name="name" value="{{ old('name', $machine->name) }}">
                                </td>

                                <td>
                                    <input form="update-machine-{{ $machine->id }}" type="text" name="machine_no" value="{{ old('machine_no', $machine->machine_no) }}">
                                </td>

                                <td>
                                    <input form="update-machine-{{ $machine->id }}" type="text" name="brand" value="{{ old('brand', $machine->brand) }}">
                                </td>

                                <td>
                                    <input form="update-machine-{{ $machine->id }}" type="text" class="wide-input" name="description" value="{{ old('description', $machine->description) }}">
                                </td>

                                <td>
                                    <select form="update-machine-{{ $machine->id }}" name="status">
                                        <option value="Active" @selected(old('status', $machine->status) === 'Active')>Active</option>
                                        <option value="Inactive" @selected(old('status', $machine->status) === 'Inactive')>Inactive</option>
                                    </select>
                                </td>

                                <td>
                                    <div class="table-actions">
                                        <form id="update-machine-{{ $machine->id }}" method="POST" action="{{ route('machines.update', $machine->id) }}">
                                            @csrf
                                            @method('PUT')
                                        </form>

                                        <button form="update-machine-{{ $machine->id }}" type="submit" class="mini">Save</button>

                                        <a href="{{ route('machines.index', ['search' => $search]) }}" class="mini danger" style="text-decoration:none;">
                                            Cancel
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @else
                            <tr>
                                <td class="row-no">
                                    {{ $machines->firstItem() + $loop->index }}
                                </td>

                                <td>{{ $machine->name }}</td>
                                <td>{{ $machine->machine_no ?? '-' }}</td>
                                <td>{{ $machine->brand ?? '-' }}</td>
                                <td>{{ $machine->description ?? '-' }}</td>
                                <td>
                                    <span class="status {{ strtolower($machine->status) }}">
                                        {{ ucfirst(strtolower($machine->status)) }}
                                    </span>
                                </td>

                                <td>
                                    <div class="table-actions">
                                        <a href="{{ route('machines.index', ['search' => $search, 'edit' => $machine->id]) }}" class="mini" style="text-decoration:none;">
                                            Edit
                                        </a>

                                        <form method="POST" action="{{ route('machines.destroy', $machine->id) }}" onsubmit="return confirm('Delete this machine?')">
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit" class="mini danger">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        @if(!request('create'))
                            <tr>
                                <td colspan="7" class="empty">No machine found.</td>
                            </tr>
                        @endif
                    @endforelse

                    @if(request('create'))
                        <tr class="new-row">
                            <td class="row-no">
                                <a href="{{ route('machines.index', ['search' => $search]) }}" class="plus-cell danger-plus" title="Remove row">×</a>
                            </td>

                            <td>
                                <input form="create-machine-form" type="text" name="name" value="{{ old('name') }}" placeholder="Machine name">
                            </td>

                            <td>
                                <input form="create-machine-form" type="text" name="machine_no" value="{{ old('machine_no') }}" placeholder="Machine No">
                            </td>

                            <td>
                                <input form="create-machine-form" type="text" name="brand" value="{{ old('brand') }}" placeholder="Brand">
                            </td>

                            <td>
                                <input form="create-machine-form" type="text" class="wide-input" name="description" value="{{ old('description') }}" placeholder="Description">
                            </td>

                            <td>
                                <select form="create-machine-form" name="status">
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                </select>
                            </td>

                            <td>
                                <div class="table-actions">
                                    <form id="create-machine-form" method="POST" action="{{ route('machines.store') }}">
                                        @csrf
                                    </form>

                                    <button form="create-machine-form" type="submit" class="mini">Save</button>

                                    <a href="{{ route('machines.index', ['search' => $search]) }}" class="mini danger" style="text-decoration:none;">
                                        Remove
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endif
                </tbody>

                <tfoot>
                    <tr class="total-row">
                        <td>
                            <a href="{{ route('machines.index', ['search' => $search, 'create' => 1]) }}" class="plus-cell" title="Add row">+</a>
                        </td>

                        <td colspan="4" class="total-label">Total Machines</td>
                        <td>{{ number_format((int) $machines->total()) }}</td>
                        <td>-</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div style="margin-top:14px;">
            {{ $machines->links() }}
        </div>
    </div>
</div>