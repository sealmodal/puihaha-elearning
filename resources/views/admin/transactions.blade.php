<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900">Transactions Control</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Update transaction statuses and notify members
                automatically.</p>
        </div>
    </x-slot>

    <div class="py-6 px-5 sm:px-7 space-y-6 w-full">
        @if(session('success'))
            <div class="p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-medium">
                {{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm font-medium">
                {{ session('error') }}</div>
        @endif

        <form method="GET" action="{{ route('admin.transactions') }}"
            class="ph-card p-4 flex flex-col sm:flex-row gap-3">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search ref # or member..."
                class="flex-1 rounded-xl border-slate-200 text-sm px-3.5 py-2">
            <select name="status" class="rounded-xl border-slate-200 text-sm px-3.5 py-2 bg-white">
                <option value="">All Statuses</option>
                @foreach(['Pending', 'Paid', 'Approved', 'Expired', 'Cancelled'] as $st)
                    <option value="{{ $st }}" @selected(request('status') === $st)>{{ $st }}</option>
                @endforeach
            </select>
            <button type="submit"
                class="px-5 py-2 rounded-xl bg-[#0a192f] text-white text-xs sm:text-sm font-semibold hover:bg-[#162b4d] cursor-pointer">
                Filter
            </button>
        </form>

        <div class="ph-card ph-table-wrap">
            <table class="ph-table">
                <thead>
                    <tr>
                        <th style="width: 28%;">Ref # &amp; Member</th>
                        <th style="width: 24%;">Plan &amp; Billing</th>
                        <th style="width: 48%;">Update Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $transaction)
                        <tr>
                            <td>
                                <div class="font-mono font-bold text-blue-700">{{ $transaction->reference_number }}</div>
                                <div class="font-semibold text-slate-900 mt-0.5">
                                    {{ $transaction->member?->full_name ?? 'Member' }}</div>
                                <div class="text-xs text-slate-500">{{ $transaction->member?->email }}</div>
                            </td>
                            <td>
                                <div class="capitalize font-semibold text-slate-900">{{ $transaction->transaction_type }} ·
                                    {{ $transaction->level?->name ?? 'Plan' }}</div>
                                <div class="font-mono text-xs text-slate-600 mt-0.5">
                                    Due: {{ number_format((float) $transaction->amount_due, 2) }} | Paid:
                                    {{ number_format($transaction->totalPaid(), 2) }}
                                </div>
                                <div class="mt-1">
                                    <span
                                        class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 text-slate-800">{{ $transaction->status }}</span>
                                </div>
                            </td>
                            <td>
                                @if(in_array($transaction->status, ['Approved', 'Cancelled'], true))
                                    <span
                                        class="inline-block px-3 py-1.5 rounded-lg bg-slate-100 text-slate-600 text-xs font-semibold">
                                        Complete Transaction
                                        ({{ $transaction->status }}{{ $transaction->status === 'Cancelled' ? ' by Client' : '' }})
                                    </span>
                                @else
                                    <form method="POST" action="{{ route('admin.transactions.status', $transaction) }}"
                                        class="flex flex-wrap sm:flex-nowrap gap-2 items-center w-full">
                                        @csrf
                                        @method('PATCH')
                                        <select name="status"
                                            class="rounded-lg border-slate-200 text-xs py-2 px-2.5 bg-white shrink-0">
                                            @foreach(['Pending', 'Paid', 'Approved'] as $st)
                                                <option value="{{ $st }}" @selected($transaction->status === $st)>{{ $st }}</option>
                                            @endforeach
                                            @if($transaction->status === 'Expired' || ($transaction->member?->membership_expires_at && $transaction->member->membership_expires_at->endOfDay()->isPast()))
                                                <option value="Expired" @selected($transaction->status === 'Expired')>Expired</option>
                                            @endif
                                        </select>
                                        <input type="text" name="notes" value="{{ $transaction->notes }}"
                                            placeholder="Add note..."
                                            class="flex-1 min-w-0 rounded-lg border-slate-200 text-xs py-2 px-3 bg-white">
                                        <button type="submit"
                                            class="px-4 py-2 rounded-lg bg-blue-600 text-white text-xs font-semibold hover:bg-blue-700 shrink-0 cursor-pointer">
                                            Update
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="py-8 text-center text-sm text-slate-500">
                                No transaction
                                found{{ request('search') || request('status') ? ' matching your filter criteria' : '' }}.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>{{ $transactions->links() }}</div>
    </div>
</x-app-layout>