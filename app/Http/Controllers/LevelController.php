<?php

namespace App\Http\Controllers;

use App\Models\MembershipLevel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LevelController extends Controller
{
    public function index(): View
    {
        $levels = MembershipLevel::withCount('members')->orderBy('fee')->get();
        return view('levels.index', compact('levels'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:membership_levels,name'],
            'fee' => ['required', 'numeric', 'min:0'],
            'validity_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'benefits' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        MembershipLevel::create([
            'name' => $validated['name'],
            'fee' => $validated['fee'],
            'validity_days' => $validated['validity_days'],
            'benefits' => $validated['benefits'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Membership level created.');
    }

    public function update(Request $request, MembershipLevel $level): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:membership_levels,name,' . $level->id],
            'fee' => ['required', 'numeric', 'min:0'],
            'validity_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'benefits' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $level->update([
            'name' => $validated['name'],
            'fee' => $validated['fee'],
            'validity_days' => $validated['validity_days'],
            'benefits' => $validated['benefits'] ?? null,
            'is_active' => $request->boolean('is_active', false),
        ]);

        return back()->with('success', 'Membership level updated.');
    }

    public function destroy(MembershipLevel $level): RedirectResponse
    {
        if ($level->members()->exists() || $level->transactions()->exists()) {
            return back()->with('error', 'Cannot delete a level linked to members or transactions.');
        }

        $level->delete();
        return back()->with('success', 'Membership level deleted.');
    }
}