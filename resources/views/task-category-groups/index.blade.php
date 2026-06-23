@extends('layouts.app')

@section('content')
    <div class="page">
        @include('components.shared-style')
        @include('components.toast-alert')

        <style>
            .master-toolbar {
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

            .master-table-wrap {
                overflow-x: auto;
                border: 1px solid #e5e7eb;
                border-radius: 16px;
            }

            .master-table {
                width: 100%;
                min-width: 1050px;
                border-collapse: collapse;
                background: #ffffff;
            }

            .master-table th {
                background: #f8fafc;
                color: #0f172a;
                font-size: 12px;
                font-weight: 900;
                text-transform: uppercase;
                padding: 12px 10px;
                border-bottom: 1px solid #e5e7eb;
                white-space: nowrap;
                text-align: left;
            }

            .master-table td {
                padding: 10px;
                border-bottom: 1px solid #eef2f7;
                vertical-align: middle;
                white-space: nowrap;
            }

            .master-table input,
            .master-table select {
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

            .wide-input {
                min-width: 280px !important;
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
                background: #f0fdf4;
                border-bottom: 1px solid #bbf7d0;
            }

            .table-actions {
                display: flex;
                gap: 6px;
                align-items: center;
                justify-content: flex-end;
                flex-wrap: wrap;
            }

            .total-row {
                background: #f8fafc;
                font-weight: 900;
                border-top: 2px solid #d1d5db;
            }

            .total-row td {
                border-bottom: 0;
                padding: 14px 10px;
                color: #0f172a;
                background: #f8fafc;
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

            .error {
                display: block;
                color: #dc2626;
                font-size: 12px;
                margin-top: 4px;
                font-weight: 700;
                white-space: normal;
            }

            .hidden-row {
                display: none;
            }

            .group-name {
                font-weight: 700;
                color: #0f172a;
            }

            .group-count {
                font-weight: 800;
                color: #0f172a;
            }

            .pagination-box {
                margin-top: 16px;
            }

            @media (max-width: 768px) {
                .filter-box {
                    max-width: 100%;
                }
            }
        </style>

        <div class="page-header">
            <div>
                <h1 class="page-title">Task Group</h1>
            </div>

            <div class="page-actions">
                <a href="{{ route('dashboard') }}" class="btn gray">
                    Dashboard
                </a>
            </div>
        </div>

        <div class="panel">
            @if(session('success'))
                <div class="alert success">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="alert danger">
                    {{ session('error') }}
                </div>
            @endif

            @if($errors->any())
                <div class="alert danger">
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <div class="master-toolbar">
                <form
                    action="{{ route('task-category-groups.index') }}"
                    method="GET"
                    class="filter-box"
                    id="groupSearchForm"
                >
                    <input
                        type="text"
                        name="search"
                        id="groupSearchInput"
                        value="{{ request('search') }}"
                        placeholder="Filter name, description, type, status"
                        autocomplete="off"
                    >
                </form>
            </div>

            <form
                id="createGroupForm"
                action="{{ route('task-category-groups.store') }}"
                method="POST"
            >
                @csrf
            </form>

            @foreach($groups as $group)
                <form
                    id="updateGroupForm{{ $group->id }}"
                    action="{{ route('task-category-groups.update', $group) }}"
                    method="POST"
                >
                    @csrf
                    @method('PUT')
                </form>

                <form
                    id="deleteGroupForm{{ $group->id }}"
                    action="{{ route('task-category-groups.destroy', $group) }}"
                    method="POST"
                    onsubmit="return confirm('Delete this task category group?');"
                >
                    @csrf
                    @method('DELETE')
                </form>
            @endforeach

            <div class="master-table-wrap">
                <table class="master-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Type *</th>
                            <th>Name *</th>
                            <th>Description</th>
                            <th>Task</th>
                            <th>Status</th>
                            <th width="190">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($groups as $group)
                            <tr id="groupRow{{ $group->id }}">
                                <td class="row-no">
                                    {{ $groups->firstItem() + $loop->index }}
                                </td>

                                <td>
                                    <span class="group-display-{{ $group->id }}">
                                        {{ ucfirst($group->group_type ?? 'planning') }}
                                    </span>

                                    <select
                                        name="group_type"
                                        form="updateGroupForm{{ $group->id }}"
                                        class="group-edit-{{ $group->id }} hidden-row"
                                        required
                                    >
                                        <option
                                            value="planning"
                                            @selected(($group->group_type ?? 'planning') === 'planning')
                                        >
                                            Planning
                                        </option>

                                        <option
                                            value="harvesting"
                                            @selected(($group->group_type ?? 'planning') === 'harvesting')
                                        >
                                            Harvesting
                                        </option>
                                    </select>
                                </td>

                                <td>
                                    <span class="group-display-{{ $group->id }} group-name">
                                        {{ $group->name }}
                                    </span>

                                    <input
                                        type="text"
                                        name="name"
                                        value="{{ $group->name }}"
                                        form="updateGroupForm{{ $group->id }}"
                                        class="group-edit-{{ $group->id }} hidden-row"
                                        required
                                    >
                                </td>

                                <td>
                                    <span class="group-display-{{ $group->id }}">
                                        {{ $group->description ?: '-' }}
                                    </span>

                                    <input
                                        type="text"
                                        name="description"
                                        value="{{ $group->description }}"
                                        form="updateGroupForm{{ $group->id }}"
                                        class="wide-input group-edit-{{ $group->id }} hidden-row"
                                        placeholder="Description"
                                    >
                                </td>

                                <td>
                                    <span class="group-count">
                                        {{ number_format((int) ($group->task_categories_count ?? 0)) }}
                                    </span>
                                </td>

                                <td>
                                    <span class="group-display-{{ $group->id }}">
                                        <span class="status {{ $group->status ? 'active' : 'inactive' }}">
                                            {{ $group->status ? 'Active' : 'Inactive' }}
                                        </span>
                                    </span>

                                    <select
                                        name="status"
                                        form="updateGroupForm{{ $group->id }}"
                                        class="group-edit-{{ $group->id }} hidden-row"
                                        required
                                    >
                                        <option
                                            value="1"
                                            @selected((bool) $group->status)
                                        >
                                            Active
                                        </option>

                                        <option
                                            value="0"
                                            @selected(!(bool) $group->status)
                                        >
                                            Inactive
                                        </option>
                                    </select>
                                </td>

                                <td>
                                    <div class="table-actions">
                                        <div class="group-display-{{ $group->id }}">
                                            @if(auth()->user()->hasPermission('task_categories.edit'))
                                                <button
                                                    type="button"
                                                    class="mini"
                                                    onclick="enableGroupEdit({{ $group->id }})"
                                                >
                                                    Edit
                                                </button>
                                            @endif

                                            @if(auth()->user()->hasPermission('task_categories.delete'))
                                                <button
                                                    type="submit"
                                                    class="mini danger"
                                                    form="deleteGroupForm{{ $group->id }}"
                                                >
                                                    Delete
                                                </button>
                                            @endif
                                        </div>

                                        <div class="group-edit-{{ $group->id }} hidden-row">
                                            <button
                                                type="submit"
                                                class="mini"
                                                form="updateGroupForm{{ $group->id }}"
                                            >
                                                Save
                                            </button>

                                            <button
                                                type="button"
                                                class="mini danger"
                                                onclick="cancelGroupEdit({{ $group->id }})"
                                            >
                                                Cancel
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            @if(!$errors->any())
                                <tr id="emptyGroupRow">
                                    <td colspan="7" class="empty">
                                        No task category groups found.
                                    </td>
                                </tr>
                            @endif
                        @endforelse

                        <tr
                            id="createGroupRow"
                            class="new-row {{ $errors->any() ? '' : 'hidden-row' }}"
                        >
                            <td class="row-no">
                                <button
                                    type="button"
                                    class="plus-cell danger-plus"
                                    onclick="hideCreateGroupRow()"
                                    title="Remove row"
                                >
                                    ×
                                </button>
                            </td>

                            <td>
                                <select
                                    name="group_type"
                                    form="createGroupForm"
                                    required
                                >
                                    <option
                                        value="planning"
                                        @selected(old('group_type', 'planning') === 'planning')
                                    >
                                        Planning
                                    </option>

                                    <option
                                        value="harvesting"
                                        @selected(old('group_type') === 'harvesting')
                                    >
                                        Harvesting
                                    </option>
                                </select>

                                @error('group_type')
                                    <small class="error">{{ $message }}</small>
                                @enderror
                            </td>

                            <td>
                                <input
                                    type="text"
                                    name="name"
                                    value="{{ old('name') }}"
                                    form="createGroupForm"
                                    placeholder="Group name"
                                    required
                                >

                                @error('name')
                                    <small class="error">{{ $message }}</small>
                                @enderror
                            </td>

                            <td>
                                <input
                                    type="text"
                                    name="description"
                                    value="{{ old('description') }}"
                                    form="createGroupForm"
                                    class="wide-input"
                                    placeholder="Description"
                                >

                                @error('description')
                                    <small class="error">{{ $message }}</small>
                                @enderror
                            </td>

                            <td>
                                -
                            </td>

                            <td>
                                <select
                                    name="status"
                                    form="createGroupForm"
                                    required
                                >
                                    <option
                                        value="1"
                                        @selected(old('status', '1') === '1')
                                    >
                                        Active
                                    </option>

                                    <option
                                        value="0"
                                        @selected(old('status') === '0')
                                    >
                                        Inactive
                                    </option>
                                </select>

                                @error('status')
                                    <small class="error">{{ $message }}</small>
                                @enderror
                            </td>

                            <td>
                                <div class="table-actions">
                                    <button
                                        type="submit"
                                        class="mini"
                                        form="createGroupForm"
                                    >
                                        Save
                                    </button>

                                    <button
                                        type="button"
                                        class="mini danger"
                                        onclick="hideCreateGroupRow()"
                                    >
                                        Remove
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>

                    <tfoot>
                        <tr class="total-row">
                            <td>
                                @if(auth()->user()->hasPermission('task_categories.create'))
                                    <button
                                        type="button"
                                        class="plus-cell"
                                        onclick="showCreateGroupRow()"
                                        title="Add row"
                                    >
                                        +
                                    </button>
                                @else
                                    -
                                @endif
                            </td>

                            <td>-</td>

                            <td class="total-label">
                                Total: {{ number_format((int) $groups->total()) }}
                            </td>

                            <td>-</td>

                            <td>
                                {{ number_format((int) $groups->sum('task_categories_count')) }}
                            </td>

                            <td>-</td>
                            <td>-</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            @if($groups->hasPages())
                <div class="pagination-box">
                    {{ $groups->links() }}
                </div>
            @endif
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const searchInput = document.getElementById('groupSearchInput');
            const searchForm = document.getElementById('groupSearchForm');

            let searchTimer;

            if (searchInput && searchForm) {
                searchInput.addEventListener('input', function () {
                    clearTimeout(searchTimer);

                    searchTimer = setTimeout(function () {
                        searchForm.submit();
                    }, 500);
                });
            }
        });

        function showCreateGroupRow() {
            const createRow = document.getElementById('createGroupRow');
            const emptyRow = document.getElementById('emptyGroupRow');

            if (createRow) {
                createRow.classList.remove('hidden-row');

                const nameInput = createRow.querySelector('input[name="name"]');

                if (nameInput) {
                    setTimeout(function () {
                        nameInput.focus();
                    }, 100);
                }
            }

            if (emptyRow) {
                emptyRow.classList.add('hidden-row');
            }
        }

        function hideCreateGroupRow() {
            const createRow = document.getElementById('createGroupRow');
            const createForm = document.getElementById('createGroupForm');
            const emptyRow = document.getElementById('emptyGroupRow');

            if (createForm) {
                createForm.reset();
            }

            if (createRow) {
                createRow.classList.add('hidden-row');
            }

            if (emptyRow) {
                emptyRow.classList.remove('hidden-row');
            }
        }

        function enableGroupEdit(groupId) {
            document
                .querySelectorAll('.group-display-' + groupId)
                .forEach(function (element) {
                    element.classList.add('hidden-row');
                });

            document
                .querySelectorAll('.group-edit-' + groupId)
                .forEach(function (element) {
                    element.classList.remove('hidden-row');
                });

            const row = document.getElementById('groupRow' + groupId);

            if (row) {
                const nameInput = row.querySelector('input[name="name"]');

                if (nameInput) {
                    nameInput.focus();
                    nameInput.select();
                }
            }
        }

        function cancelGroupEdit(groupId) {
            const updateForm = document.getElementById(
                'updateGroupForm' + groupId
            );

            if (updateForm) {
                updateForm.reset();
            }

            document
                .querySelectorAll('.group-display-' + groupId)
                .forEach(function (element) {
                    element.classList.remove('hidden-row');
                });

            document
                .querySelectorAll('.group-edit-' + groupId)
                .forEach(function (element) {
                    element.classList.add('hidden-row');
                });
        }
    </script>
@endsection