<?php

namespace App\Http\Controllers;

use App\Models\Machine;
use Illuminate\Http\Request;

class MachineController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search');

        $machines = Machine::query()
            ->when($search, function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('brand', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%");
            })
            ->orderBy('id')
            ->paginate(10)
            ->withQueryString();

        return view('machines.index', compact('machines', 'search'));
    }

    public function create()
    {
        return redirect()->route('machines.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:Active,Inactive'],
        ]);

        Machine::create([
            'name' => $request->name,
            'brand' => $request->brand,
            'status' => $request->status,
        ]);

        return redirect()
            ->route('machines.index')
            ->with('success', 'Machine saved successfully.');
    }

    public function show(Machine $machine)
    {
        abort(404);
    }

    public function edit(Machine $machine)
    {
        return redirect()->route('machines.index');
    }

    public function update(Request $request, Machine $machine)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:Active,Inactive'],
        ]);

        $machine->update([
            'name' => $request->name,
            'brand' => $request->brand,
            'status' => $request->status,
        ]);

        return redirect()
            ->route('machines.index')
            ->with('success', 'Machine updated successfully.');
    }

    public function destroy(Machine $machine)
    {
        $machine->delete();

        return redirect()
            ->route('machines.index')
            ->with('success', 'Machine deleted successfully.');
    }
}