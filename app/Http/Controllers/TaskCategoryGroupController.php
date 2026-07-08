<?php

namespace App\Http\Controllers;

use App\Models\TaskCategoryGroup;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
                'unique:task_category_groups,name',
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
        ]);

        $taskCategoryGroup->update($validated);

        return redirect()
            ->route('task-category-groups.index')
            ->with('success', 'Task category group updated successfully.');
    }

    public function destroy(TaskCategoryGroup $taskCategoryGroup)
    {
        if ($taskCategoryGroup->taskCategories()->exists()) {
            return back()->with(
                'error',
                'This group cannot be deleted because it contains task categories.'
            );
        }

        $taskCategoryGroup->delete();

        return back()->with(
            'success',
            'Task category group deleted successfully.'
        );
    }
}