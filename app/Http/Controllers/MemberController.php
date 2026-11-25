<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\MembershipCategory;
use App\Models\MembershipLevel;
use App\Models\MembershipTransaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MemberController extends Controller
{
    public function index(Request $request): View
    {
        $query = Member::with(['category', 'level']);

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('membership_number', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('membership_category_id', $request->integer('category_id'));
        }

        if ($request->filled('level_id')) {
            $query->where('membership_level_id', $request->integer('level_id'));
        }

        $members = $query->latest()->paginate(12)->withQueryString();
        $categories = MembershipCategory::where('is_active', true)->orderBy('name')->get();
        $levels = MembershipLevel::where('is_active', true)->orderBy('fee')->get();

        return view('members.index', compact('members', 'categories', 'levels'));
    }

    public function create(): View
    {
        $categories = MembershipCategory::where('is_active', true)->orderBy('name')->get();
        $levels = MembershipLevel::where('is_active', true)->orderBy('fee')->get();
        $suggestedNumber = Member::generateMembershipNumber();

        return view('members.create', compact('categories', 'levels', 'suggestedNumber'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'membership_number' => ['nullable', 'string', 'max:50', 'unique:members,membership_number'],
            'full_name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', 'unique:members,email'],
            'phone' => ['nullable', 'string', 'max:40'],
            'organization' => ['nullable', 'string', 'max:150'],
            'membership_category_id' => ['required', 'exists:membership_categories,id'],
            'membership_level_id' => ['required', 'exists:membership_levels,id'],
            'create_initial_application' => ['nullable', 'boolean'],
        ]);

        $level = MembershipLevel::findOrFail($validated['membership_level_id']);

        $member = DB::transaction(function () use ($request, $validated, $level) {
            $member = Member::create([
                'membership_number' => $validated['membership_number'] ?: Member::generateMembershipNumber(),
                'full_name' => $validated['full_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'organization' => $validated['organization'] ?? null,
                'membership_category_id' => $validated['membership_category_id'],
                'membership_level_id' => $level->id,
                'status' => 'active',
                'membership_expires_at' => now()->addDays($level->validity_days),
            ]);

            if ($request->boolean('create_initial_application', true)) {
                MembershipTransaction::create([
                    'member_id' => $member->id,
                    'membership_level_id' => $level->id,
                    'transaction_type' => 'application',
                    'amount_due' => $level->fee,
                    'status' => 'Pending',
                    'notes' => 'Initial membership application created upon registration.',
                ]);
            }

            return $member;
        });

        return redirect()->route('members.show', $member)
            ->with('success', 'Member registered successfully.');
    }

    public function show(Member $member): View
    {
        $user = auth()->user();
        if ($user->hasRole('member') && $member->user_id !== $user->id) {
            abort(403, 'You can only view your own member profile.');
        }
        $member->load(['category', 'level', 'transactions.level', 'transactions.payments']);
        $levels = MembershipLevel::where('is_active', true)->orderBy('fee')->get();

        return view('members.show', compact('member', 'levels'));
    }

    public function edit(Member $member): View
    {
        $categories = MembershipCategory::orderBy('name')->get();
        $levels = MembershipLevel::orderBy('fee')->get();

        return view('members.edit', compact('member', 'categories', 'levels'));
    }

    public function update(Request $request, Member $member): RedirectResponse
    {
        $validated = $request->validate([
            'membership_number' => ['required', 'string', 'max:50', 'unique:members,membership_number,' . $member->id],
            'full_name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', 'unique:members,email,' . $member->id],
            'phone' => ['nullable', 'string', 'max:40'],
            'organization' => ['nullable', 'string', 'max:150'],
            'membership_category_id' => ['required', 'exists:membership_categories,id'],
            'membership_level_id' => ['required', 'exists:membership_levels,id'],
            'status' => ['required', 'in:active,inactive'],
            'membership_expires_at' => ['nullable', 'date'],
        ]);

        $member->update($validated);

        return redirect()->route('members.show', $member)
            ->with('success', 'Member details updated.');
    }

    public function deactivate(Member $member): RedirectResponse
    {
        $newStatus = $member->status === 'active' ? 'inactive' : 'active';
        $member->update(['status' => $newStatus]);

        return back()->with('success', "Member marked as {$newStatus}.");
    }

    public function destroy(Member $member): RedirectResponse
    {
        $member->delete();

        return redirect()->route('members.index')
            ->with('success', 'Member record removed.');
    }

    public function printRecord(Member $member): View
    {
        $member->load(['category', 'level', 'transactions.payments']);
        return view('members.print', compact('member'));
    }
}