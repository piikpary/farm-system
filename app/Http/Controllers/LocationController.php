<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search');

        $locations = Location::query()
            ->when($search, function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%");
            })
            ->orderBy('id')
            ->paginate(10)
            ->withQueryString();

        return view('locations.index', compact('locations', 'search'));
    }

    public function create()
    {
        return redirect()->route('locations.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:Active,Inactive'],
        ]);

        Location::create([
            'name' => $request->name,
            'status' => $request->status,
        ]);

        return redirect()
            ->route('locations.index')
            ->with('success', 'Location saved successfully.');
    }

    public function show(Location $location)
    {
        abort(404);
    }

    public function edit(Location $location)
    {
        return redirect()->route('locations.index');
    }

    public function update(Request $request, Location $location)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:Active,Inactive'],
        ]);

        $location->update([
            'name' => $request->name,
            'status' => $request->status,
        ]);

        return redirect()
            ->route('locations.index')
            ->with('success', 'Location updated successfully.');
    }

    public function destroy(Location $location)
    {
        $location->delete();

        return redirect()
            ->route('locations.index')
            ->with('success', 'Location deleted successfully.');
    }
}