<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\MembershipLevel;
use App\Models\MembershipTransaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function index(Request $request): View
    {
        $query = MembershipTransaction::with(['member', 'level', 'payments']);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('transaction_type')) {
            $query->where('transaction_type', $request->string('transaction_type'));
        }

        $transactions = $query->latest()->paginate(15)->withQueryString();
        $members = Member::orderBy('full_name')->get();
        $levels = MembershipLevel::where('is_active', true)->orderBy('fee')->get();

        return view('transactions.index', compact('transactions', 'members', 'levels'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'member_id' => ['required', 'exists:members,id'],
            'membership_level_id' => ['required', 'exists:membership_levels,id'],
            'transaction_type' => ['required', 'in:application,renewal'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $level = MembershipLevel::findOrFail($validated['membership_level_id']);

        $transaction = MembershipTransaction::create([
            'member_id' => $validated['member_id'],
            'membership_level_id' => $level->id,
            'transaction_type' => $validated['transaction_type'],
            'amount_due' => $level->fee,
            'status' => 'Pending',
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('transactions.show', $transaction)
            ->with('success', "Transaction {$transaction->reference_number} created.");
    }

    public function show(MembershipTransaction $transaction): View
    {
        $transaction->load(['member.category', 'member.level', 'level', 'payments']);
        return view('transactions.show', compact('transaction'));
    }

    public function approve(MembershipTransaction $transaction): RedirectResponse
    {
        // Business rule: Only paid transactions may be marked as approved
        if ($transaction->status !== 'Paid' && !$transaction->isFullyPaid()) {
            return back()->with('error', 'Only Paid transactions with full payment can be approved.');
        }

        DB::transaction(function () use ($transaction) {
            $level = $transaction->level;
            $member = $transaction->member;

            $baseDate = ($transaction->transaction_type === 'renewal' && $member->membership_expires_at && $member->membership_expires_at->isFuture())
                ? $member->membership_expires_at
                : now();

            $member->update([
                'membership_level_id' => $level->id,
                'status' => 'active',
                'membership_expires_at' => $baseDate->copy()->addDays($level->validity_days),
            ]);

            $transaction->update([
                'status' => 'Approved',
                'approved_at' => now(),
            ]);
        });

        return back()->with('success', 'Transaction approved and member validity updated.');
    }

    public function updateStatus(Request $request, MembershipTransaction $transaction): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:Pending,Paid,Approved,Expired,Cancelled'],
        ]);

        if ($validated['status'] === 'Approved') {
            return $this->approve($transaction);
        }

        $transaction->update(['status' => $validated['status']]);

        return back()->with('success', "Transaction status updated to {$validated['status']}.");
    }

    public function receipt(MembershipTransaction $transaction): View
    {
        // Inside TransactionController::receipt(MembershipTransaction $transaction)
        $user = auth()->user();
        if ($user->hasRole('member') && $transaction->member->user_id !== $user->id) {
            abort(403, 'You can only view receipts for your own transactions.');
        }
        $transaction->load(['member.category', 'member.level', 'level', 'payments']);
        return view('transactions.receipt', compact('transaction'));
    }
}