<?php

namespace App\Http\Controllers;

use App\Models\MembershipTransaction;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(): View
    {
        $payments = Payment::with(['transaction.member', 'transaction.level'])
            ->latest('paid_at')
            ->paginate(15);

        $payableTransactions = MembershipTransaction::with(['member', 'level', 'payments'])
            ->whereIn('status', ['Pending', 'Paid'])
            ->latest()
            ->get();

        return view('payments.index', compact('payments', 'payableTransactions'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'membership_transaction_id' => ['required', 'exists:membership_transactions,id'],
            'amount_paid' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'string', 'max:60'],
            'external_reference' => ['nullable', 'string', 'max:100'],
            'paid_at' => ['nullable', 'date'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ]);

        $transaction = MembershipTransaction::findOrFail($validated['membership_transaction_id']);

        if (in_array($transaction->status, ['Cancelled', 'Expired'], true)) {
            return back()->with('error', "Cannot record payment for a {$transaction->status} transaction.");
        }

        $remainingBalance = max(0, (float) $transaction->amount_due - $transaction->totalPaid());

        if ((float) $validated['amount_paid'] > $remainingBalance + 0.009) {
            return back()
                ->withInput()
                ->with('error', 'Payment amount cannot exceed remaining balance of ' . number_format($remainingBalance, 2) . '.');
        }

        DB::transaction(function () use ($validated, $transaction) {
            Payment::create([
                'membership_transaction_id' => $transaction->id,
                'amount_paid' => $validated['amount_paid'],
                'payment_method' => $validated['payment_method'],
                'external_reference' => $validated['external_reference'] ?? null,
                'paid_at' => $validated['paid_at'] ?? now(),
                'remarks' => $validated['remarks'] ?? null,
            ]);

            if ($transaction->isFullyPaid() && $transaction->status === 'Pending') {
                $transaction->update(['status' => 'Paid']);
            }
        });

        return back()->with('success', 'Payment recorded successfully.');
    }
}