<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900">Admin Dashboard</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Manage member accounts, contact/location data, and
                membership tiers.</p>
        </div>
    </x-slot>

    <div class="py-6 px-5 sm:px-7 space-y-6 w-full">
        @if(session('success'))
            <div class="p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-medium">
                {{ session('success') }}
            </div>
        @endif

        <div class="ph-stats-grid">
            <div class="ph-card p-5 border-l-4 border-l-blue-600">
                <div class="text-xs font-bold uppercase text-slate-400">Total Members</div>
                <div class="text-2xl font-extrabold text-slate-900 mt-1 font-mono">{{ $stats['total_members'] }}</div>
            </div>
            <div class="ph-card p-5 border-l-4 border-l-teal-500">
                <div class="text-xs font-bold uppercase text-slate-400">Active Members</div>
                <div class="text-2xl font-extrabold text-teal-600 mt-1 font-mono">{{ $stats['active_members'] }}</div>
            </div>
            <div class="ph-card p-5 border-l-4 border-l-amber-500">
                <div class="text-xs font-bold uppercase text-slate-400">Pending Tx</div>
                <div class="text-2xl font-extrabold text-amber-600 mt-1 font-mono">{{ $stats['pending_tx'] }}</div>
            </div>
            <div class="ph-card p-5 border-l-4 border-l-sky-500">
                <div class="text-xs font-bold uppercase text-slate-400">Courses</div>
                <div class="text-2xl font-extrabold text-sky-600 mt-1 font-mono">{{ $stats['total_courses'] }}</div>
            </div>
        </div>

        <form method="GET" action="{{ route('admin.dashboard') }}" class="ph-card p-4 flex flex-col sm:flex-row gap-3">
            <input type="text" name="search" value="{{ request('search') }}"
                placeholder="Search member name, email, #, or address..."
                class="flex-1 rounded-xl border-slate-200 text-sm px-3.5 py-2">
            <button type="submit"
                class="px-5 py-2 rounded-xl bg-[#0a192f] text-white text-xs sm:text-sm font-semibold hover:bg-[#162b4d] cursor-pointer">
                Search
            </button>
        </form>

        <div class="ph-card ph-table-wrap">
            <table class="ph-table">
                <thead>
                    <tr>
                        <th style="width: 20%;">Member</th>
                        <th style="width: 28%;">Contact &amp; Location</th>
                        <th style="width: 36%;">Update Membership</th>
                        <th style="width: 16%; text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($members as $member)
                        <tr>
                            <td>
                                <div class="font-bold text-slate-900 text-sm">{{ $member->full_name }}</div>
                                <div class="font-mono text-xs text-blue-700 mt-0.5">{{ $member->membership_number }}</div>
                                <div class="mt-2 flex flex-wrap items-center gap-2 text-xs">
                                    <span
                                        class="px-2 py-0.5 rounded-full font-bold {{ $member->status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                                        {{ $member->status }}
                                    </span>
                                    <span class="text-slate-500">Exp:
                                        {{ optional($member->membership_expires_at)->format('Y-m-d') ?? '-' }}</span>
                                </div>
                            </td>
                            <td>
                                <div class="space-y-1 text-slate-600">
                                    <div class="font-semibold text-slate-800">{{ $member->email }}</div>
                                    <div>Phone: <span class="text-slate-800">{{ $member->phone ?? '-' }}</span></div>
                                    <div>Address: <span class="text-slate-800">{{ $member->address ?? '-' }}</span></div>
                                    <div class="font-mono text-[11px] text-slate-400">Coords:
                                        {{ $member->latitude ?? '-' }}, {{ $member->longitude ?? '-' }}
                                    </div>
                                    <div>Weather: <span class="text-slate-800">{{ $member->weather_summary ?? '-' }}
                                            {{ $member->weather_temp_c ? $member->weather_temp_c . '°C' : '' }}</span></div>
                                </div>
                            </td>
                            <td>
                                <form method="POST" action="{{ route('admin.members.update', $member) }}" class="space-y-2">
                                    @csrf
                                    @method('PATCH')
                                    <div class="flex flex-wrap gap-2">
                                        <select name="membership_category_id"
                                            class="rounded-lg border-slate-200 text-xs py-1.5 px-2.5 bg-white">
                                            @foreach($categories as $cat)
                                                <option value="{{ $cat->id }}"
                                                    @selected($member->membership_category_id == $cat->id)>{{ $cat->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <select name="membership_level_id"
                                            class="rounded-lg border-slate-200 text-xs py-1.5 px-2.5 bg-white">
                                            @foreach($levels as $lvl)
                                                <option value="{{ $lvl->id }}"
                                                    @selected($member->membership_level_id == $lvl->id)>{{ $lvl->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="flex flex-wrap gap-2 items-center">
                                        <select name="status"
                                            class="rounded-lg border-slate-200 text-xs py-1.5 px-2.5 bg-white">
                                            <option value="active" @selected($member->status === 'active')>active</option>
                                            <option value="inactive" @selected($member->status === 'inactive')>inactive
                                            </option>
                                        </select>
                                        <input type="date" name="membership_expires_at"
                                            value="{{ optional($member->membership_expires_at)->format('Y-m-d') }}"
                                            class="rounded-lg border-slate-200 text-xs py-1.5 px-2.5 bg-white">
                                        <button type="submit"
                                            class="px-3 py-1.5 rounded-lg bg-blue-600 text-white text-xs font-semibold hover:bg-blue-700 cursor-pointer">
                                            Save
                                        </button>
                                    </div>
                                </form>
                            </td>
                            <td style="text-align: right;">
                                <div class="inline-flex flex-col items-end gap-2">
                                    <form method="POST" action="{{ route('admin.members.toggle', $member) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"
                                            class="px-3 py-1.5 rounded-lg border border-amber-300 bg-amber-50 text-amber-900 text-xs font-semibold hover:bg-amber-100 cursor-pointer">
                                            {{ $member->status === 'active' ? 'Deactivate' : 'Activate' }}
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.members.delete', $member) }}"
                                        onsubmit="return confirm('Delete this member account?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="px-3 py-1.5 rounded-lg border border-rose-200 bg-rose-50 text-rose-700 text-xs font-semibold hover:bg-rose-100 cursor-pointer">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-8 text-center text-sm text-slate-500">
                                No member found{{ request('search') ? ' matching "' . request('search') . '"' : '' }}.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>{{ $members->links() }}</div>
    </div>
</x-app-layout>