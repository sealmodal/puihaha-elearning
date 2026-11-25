<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Member;
use App\Models\MembershipCategory;
use App\Models\MembershipLevel;
use App\Models\MembershipTransaction;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function dashboard(Request $request): View
    {
        $query = Member::with(['user', 'category', 'level', 'transactions']);

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('membership_number', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%");
            });
        }

        $members = $query->latest()->paginate(15)->withQueryString();
        $categories = MembershipCategory::orderBy('name')->get();
        $levels = MembershipLevel::orderBy('fee')->get();

        $stats = [
            'total_members' => Member::count(),
            'active_members' => Member::where('status', 'active')->count(),
            'pending_tx' => MembershipTransaction::where('status', 'Pending')->count(),
            'total_courses' => Course::count(),
        ];

        return view('admin.dashboard', compact('members', 'categories', 'levels', 'stats'));
    }

    public function updateMember(Request $request, Member $member): RedirectResponse
    {
        $validated = $request->validate([
            'membership_category_id' => ['required', 'exists:membership_categories,id'],
            'membership_level_id' => ['required', 'exists:membership_levels,id'],
            'status' => ['required', 'in:active,inactive'],
            'membership_expires_at' => ['nullable', 'date'],
        ]);

        $member->update($validated);
        $member->load('level');

        NotificationService::notifyUser(
            $member->user,
            'Membership Updated',
            "Your membership status is now {$member->status} under level {$member->level->name}.",
            'membership',
            $member
        );

        return back()->with('success', "Updated membership for {$member->full_name}.");
    }

    public function toggleMemberStatus(Member $member): RedirectResponse
    {
        $nextStatus = $member->status === 'active' ? 'inactive' : 'active';
        $member->update(['status' => $nextStatus]);

        NotificationService::notifyUser(
            $member->user,
            'Account Status: ' . ucfirst($nextStatus),
            "Your member account has been set to {$nextStatus}.",
            'membership',
            $member
        );

        return back()->with('success', "Member marked as {$nextStatus}.");
    }

    public function deleteMember(Member $member): RedirectResponse
    {
        DB::transaction(function () use ($member) {
            $linkedUser = $member->user;
            $member->delete();

            if ($linkedUser && $linkedUser->role === 'member') {
                $linkedUser->delete();
            }
        });

        return back()->with('success', 'Member account deleted.');
    }

    public function transactions(Request $request): View
    {
        $query = MembershipTransaction::with(['member.category', 'member.level', 'level', 'payments']);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('reference_number', 'like', "%{$search}%")
                    ->orWhereHas('member', fn($mq) => $mq->where('full_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"));
            });
        }

        $transactions = $query->latest()->paginate(20)->withQueryString();
        
        $transactions->getCollection()->transform(function ($tx) {
            if ($tx->status === 'Pending' && $tx->totalPaid() <= 0 && $tx->member && $tx->level) {
                $pricing = \App\Http\Controllers\MemberPortalController::discountedFeeFor($tx->member, $tx->level);
                if ((float) $tx->amount_due !== (float) $pricing['final_fee']) {
                    $tx->update(['amount_due' => $pricing['final_fee']]);
                }
            }
            return $tx;
        });
        return view('admin.transactions', compact('transactions'));
    }

    public function updateTransactionStatus(Request $request, MembershipTransaction $transaction): RedirectResponse
    {
        if (in_array($transaction->status, ['Approved', 'Cancelled'], true)) {
            return back()->with('error', "Transaction {$transaction->reference_number} is {$transaction->status} (finished) and cannot be edited.");
        }

        $validated = $request->validate([
            'status' => ['required', 'in:Pending,Paid,Approved,Expired'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        if ($validated['status'] === 'Expired') {
            $expiresAt = $transaction->member?->membership_expires_at;
            if (!$expiresAt || !$expiresAt->endOfDay()->isPast()) {
                return back()->with('error', 'Expired status only applies once the membership period has expired.');
            }
        }

        $newStatus = $validated['status'];

        DB::transaction(function () use ($transaction, $newStatus, $validated) {
            $member = $transaction->member;
            $level = $transaction->level;

            $updates = [
                'status' => $newStatus,
                'notes' => $validated['notes'] ?? $transaction->notes,
            ];

            if ($newStatus === 'Approved') {
                $updates['approved_at'] = now();

                $baseDate = ($transaction->transaction_type === 'renewal' && $member->membership_expires_at && $member->membership_expires_at->isFuture())
                    ? $member->membership_expires_at
                    : now();

                $member->update([
                    'membership_level_id' => $level->id,
                    'status' => 'active',
                    'membership_expires_at' => $baseDate->copy()->addDays($level->validity_days),
                ]);

                $member->enrollments()->where('status', 'expired')->update(['status' => 'enrolled']);
            } elseif (in_array($newStatus, ['Expired', 'Cancelled'], true)) {
                $member->enrollments()->where('status', 'enrolled')->update(['status' => 'expired']);
                if ($newStatus === 'Expired') {
                    $member->update(['status' => 'inactive']);
                }
            }

            $transaction->update($updates);
        });

        $transaction->load(['member.user', 'level']);

        NotificationService::notifyUser(
            $transaction->member?->user,
            "Transaction {$newStatus} — {$transaction->reference_number}",
            "Your {$transaction->transaction_type} transaction ({$transaction->reference_number}) for {$transaction->level->name} is now {$newStatus}.",
            'transaction',
            $transaction->member,
            $transaction
        );

        return back()->with('success', "Transaction {$transaction->reference_number} updated to {$newStatus}.");
    }

    public function courses(): View
    {
        $courses = Course::with(['allowedLevels', 'enrollments.member'])->orderBy('course_code')->get();
        $levels = MembershipLevel::orderBy('fee')->get();

        return view('admin.courses', compact('courses', 'levels'));
    }

    public function storeCourse(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'course_code' => ['required', 'string', 'max:40', 'unique:courses,course_code'],
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:1000'],
            'instructor' => ['nullable', 'string', 'max:120'],
            'duration_hours' => ['required', 'integer', 'min:1', 'max:1000'],
            'level_ids' => ['nullable', 'array'],
            'level_ids.*' => ['exists:membership_levels,id'],
        ]);

        $course = DB::transaction(function () use ($request, $validated) {
            $course = Course::create([
                'course_code' => strtoupper($validated['course_code']),
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'instructor' => $validated['instructor'] ?? null,
                'duration_hours' => $validated['duration_hours'],
                'is_active' => true,
            ]);

            $course->allowedLevels()->sync($request->input('level_ids', []));

            return $course;
        });

        // Notify active members
        foreach (User::where('role', 'member')->with('member')->get() as $memberUser) {
            NotificationService::notifyUser(
                $memberUser,
                'New Course Available — ' . $course->title,
                "Course {$course->course_code}: {$course->title} has been added to the platform.",
                'course',
                $memberUser->member
            );
        }

        return back()->with('success', "Course {$course->title} added.");
    }

    public function updateCourse(Request $request, Course $course): RedirectResponse
    {
        $validated = $request->validate([
            'course_code' => ['required', 'string', 'max:40', 'unique:courses,course_code,' . $course->id],
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:1000'],
            'instructor' => ['nullable', 'string', 'max:120'],
            'duration_hours' => ['required', 'integer', 'min:1', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
            'level_ids' => ['nullable', 'array'],
            'level_ids.*' => ['exists:membership_levels,id'],
        ]);

        DB::transaction(function () use ($request, $course, $validated) {
            $course->update([
                'course_code' => strtoupper($validated['course_code']),
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'instructor' => $validated['instructor'] ?? null,
                'duration_hours' => $validated['duration_hours'],
                'is_active' => $request->boolean('is_active', false),
            ]);

            $course->allowedLevels()->sync($request->input('level_ids', []));
        });

        return back()->with('success', "Course {$course->title} updated.");
    }

    public function destroyCourse(Course $course): RedirectResponse
    {
        $course->delete();
        return back()->with('success', 'Course removed.');
    }
}