<?php

namespace App\Http\Controllers;

use App\Models\TaskCategoryGroup;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\TaskCategory;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TaskCategoryGroupController extends Controller
{
    public function index(Request $request)
    {
        $query = TaskCategoryGroup::query()
            ->withCount('taskCategories');

        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%')
                    ->orWhere('group_type', 'like', '%' . $search . '%');
            });
        }

        $groups = $query
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('task-category-groups.index', compact('groups'));
    }

    public function create()
    {
        return view('task-category-groups.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'group_type' => [
                'required',
                'in:planning,harvesting,facility',
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('task_category_groups', 'name')
                    ->where(function ($query) use ($request) {
                        $query->where(
                            'group_type',
                            $request->group_type
                        );
                    }),
            ],
            'description' => [
                'nullable',
                'string',
            ],
            'status' => [
                'required',
                'boolean',
            ],
        ]);

        TaskCategoryGroup::create($validated);

        return redirect()
            ->route('task-category-groups.index')
            ->with('success', 'Task category group created successfully.');
    }

    public function edit(TaskCategoryGroup $taskCategoryGroup)
    {
        return view(
            'task-category-groups.edit',
            compact('taskCategoryGroup')
        );
    }

   public function update(
    Request $request,
    TaskCategoryGroup $taskCategoryGroup
) {
    $validator = Validator::make(
        $request->all(),
        [
            'group_type' => [
                'required',
                'in:planning,harvesting,facility',
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('task_category_groups', 'name')
                    ->where(function ($query) use ($request) {
                        $query->where(
                            'group_type',
                            $request->group_type
                        );
                    })
                    ->ignore($taskCategoryGroup->id),
            ],
            'description' => [
                'nullable',
                'string',
            ],
            'status' => [
                'required',
                'boolean',
            ],
        ]
    );

    if ($validator->fails()) {
        return redirect()
            ->route('task-category-groups.index')
            ->withErrors($validator)
            ->withInput();
    }

    try {
        $taskCategoryGroup->update(
            $validator->validated()
        );

        return redirect()
            ->route('task-category-groups.index')
            ->with(
                'success',
                'Task category group updated successfully.'
            );
    } catch (QueryException $e) {
        $mysqlErrorCode = (int) ($e->errorInfo[1] ?? 0);

        if ($mysqlErrorCode === 1062) {
            return redirect()
                ->route('task-category-groups.index')
                ->with(
                    'error',
                    'This task group name already exists for the selected type.'
                );
        }

        throw $e;
    }
}

    public function destroy(TaskCategoryGroup $taskCategoryGroup)
    {
        if (!auth()->user()->hasPermission('task_categories.delete')) {
            abort(403, 'Permission denied.');
        }

        try {
            DB::transaction(function () use ($taskCategoryGroup) {
                TaskCategory::where(
                    'task_category_group_id',
                    $taskCategoryGroup->id
                )->delete();

                $taskCategoryGroup->delete();
            });

            return redirect()
                ->route('task-category-groups.index')
                ->with(
                    'success',
                    'Task group deleted successfully.'
                );
        } catch (QueryException $e) {
            $mysqlErrorCode = (int) ($e->errorInfo[1] ?? 0);

            if ($mysqlErrorCode === 1451) {
                return redirect()
                    ->route('task-category-groups.index')
                    ->with(
                        'error',
                        'Cannot delete this task group because one or more tasks are already used in a Work Plan or Work Log.'
                    );
            }

            throw $e;
        }
    }
}