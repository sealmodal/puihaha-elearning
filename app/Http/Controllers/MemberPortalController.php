<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Member;
use App\Models\MembershipCategory;
use App\Models\MembershipLevel;
use App\Models\MembershipTransaction;
use App\Models\Payment;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MemberPortalController extends Controller
{
    public static function discountedFeeFor(Member $member, MembershipLevel $level): array
    {
        $baseFee = (float) $level->fee;
        $categoryName = strtolower(trim((string) ($member->category?->name ?? 'Regular')));
        $levelName = strtolower(trim((string) $level->name));

        $discountPercent = 0;

        if (in_array($categoryName, ['student', 'senior citizen'], true)) {
            $discountPercent = 5;
        } elseif ($categoryName === 'corporate' && in_array($levelName, ['gold', 'platinum'], true)) {
            $discountPercent = 10;
        }

        $finalFee = round($baseFee * (1 - ($discountPercent / 100)), 2);

        return [
            'base_fee' => $baseFee,
            'discount_percent' => $discountPercent,
            'final_fee' => $finalFee,
        ];
    }

    private function currentMember(Request $request): Member
    {
        $user = $request->user();

        if ($user->hasRole('admin', 'staff')) {
            abort(403, 'Staff and admin accounts do not use the member portal.');
        }

        $member = $user->member;

        if (!$member) {
            $defaultCategory = MembershipCategory::firstOrCreate(['name' => 'Regular'], ['is_active' => true]);
            $defaultLevel = MembershipLevel::firstOrCreate(
                ['name' => 'Basic'],
                ['fee' => 29.00, 'validity_days' => 365, 'course_limit' => 1, 'benefits' => 'Enroll in up to 1 course', 'is_active' => true]
            );

            $member = Member::updateOrCreate(
                ['email' => $user->email],
                [
                    'user_id' => $user->id,
                    'membership_number' => Member::generateMembershipNumber(),
                    'full_name' => $user->name,
                    'membership_category_id' => $defaultCategory->id,
                    'membership_level_id' => $defaultLevel->id,
                    'status' => 'active',
                ]
            );
        } elseif (is_null($member->user_id)) {
            $member->update(['user_id' => $user->id]);
        }

        if ($member->membership_expires_at && $member->membership_expires_at->endOfDay()->isPast() && $member->status === 'active') {
            $member->update(['status' => 'inactive']);
            $member->transactions()->where('status', 'Approved')->update(['status' => 'Expired']);
            $member->enrollments()->where('status', 'enrolled')->update(['status' => 'expired']);

            NotificationService::notifyUser(
                $user,
                'Membership Expired',
                "Your membership ({$member->membership_number}) has expired. Please select a plan to unlock courses.",
                'membership',
                $member
            );
        }

        return $member->load(['category', 'level', 'enrollments.course', 'transactions.level', 'transactions.payments']);
    }

    public function home(Request $request): View
    {
        $member = $this->currentMember($request);
        $courses = Course::with('allowedLevels')->where('is_active', true)->orderBy('course_code')->get();
        $canAccessCourses = $member->hasApprovedMembership();
        $activeCount = $member->activeEnrollments()->count();
        $limit = $member->level?->hasUnlimitedCourses() ? null : ($member->level?->course_limit ?? 1);

        return view('portal.home', compact('member', 'courses', 'canAccessCourses', 'activeCount', 'limit'));
    }

    public function enroll(Request $request, Course $course): RedirectResponse
    {
        $member = $this->currentMember($request);

        if (!$member->hasApprovedMembership()) {
            return back()->with('error', 'Courses are locked until your membership payment is recorded and approved.');
        }

        if (!$course->isAvailableForLevel($member->level)) {
            return back()->with('error', 'This course is not included in your current membership level.');
        }

        $alreadyEnrolled = CourseEnrollment::where('member_id', $member->id)
            ->where('course_id', $course->id)
            ->where('status', 'enrolled')
            ->exists();

        if ($alreadyEnrolled) {
            return back()->with('error', 'You are already enrolled in this course.');
        }

        $activeCount = $member->activeEnrollments()->count();
        $limit = $member->level->hasUnlimitedCourses() ? null : ($member->level->course_limit ?? 1);

        if (!is_null($limit) && $activeCount >= $limit) {
            return back()->with('error', "Your {$member->level->name} membership allows up to {$limit} active course(s).");
        }

        CourseEnrollment::updateOrCreate(
            ['member_id' => $member->id, 'course_id' => $course->id],
            ['status' => 'enrolled', 'enrolled_at' => now()]
        );

        NotificationService::notifyUser(
            $request->user(),
            'Course Enrolled — ' . $course->course_code,
            "You enrolled in {$course->title} ({$course->course_code}).",
            'course',
            $member,
            null,
            false
        );

        return back()->with('success', "Enrolled in {$course->title}.");
    }

    public function cancelEnrollment(Request $request, CourseEnrollment $enrollment): RedirectResponse
    {
        $member = $this->currentMember($request);

        if ($enrollment->member_id !== $member->id) {
            abort(403);
        }

        $enrollment->update(['status' => 'cancelled']);

        NotificationService::notifyUser(
            $request->user(),
            'Course Enrollment Cancelled',
            "Enrollment for {$enrollment->course->title} was cancelled.",
            'course',
            $member,
            null,
            false
        );

        return back()->with('success', 'Course enrollment cancelled.');
    }

    public function memberships(Request $request): View
    {
        $member = $this->currentMember($request);
        $levels = MembershipLevel::where('is_active', true)->orderBy('fee')->get()->map(function ($level) use ($member) {
            $pricing = self::discountedFeeFor($member, $level);
            $level->computed_base_fee = $pricing['base_fee'];
            $level->computed_discount = $pricing['discount_percent'];
            $level->computed_final_fee = $pricing['final_fee'];
            return $level;
        });

        return view('portal.memberships', compact('member', 'levels'));
    }

    public function selectMembership(Request $request): View|RedirectResponse
    {
        $member = $this->currentMember($request);

        $validated = $request->validate([
            'membership_level_id' => ['required', 'exists:membership_levels,id'],
        ]);

        $level = MembershipLevel::findOrFail($validated['membership_level_id']);

        if ($member->membership_level_id === $level->id && $member->hasApprovedMembership()) {
            return redirect()->route('portal.memberships')
                ->with('error', 'You are already on your current membership plan.');
        }

        $pricing = self::discountedFeeFor($member, $level);

        // No transaction or notification created yet; user chooses Confirm (Pending) or Pay Now
        return view('portal.membership-confirm', compact('member', 'level', 'pricing'));
    }


    public function profile(Request $request): View
    {
        $member = $this->currentMember($request);
        return view('portal.profile', compact('member'));
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $member = $this->currentMember($request);
        $user = $request->user();

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', 'unique:members,email,' . $member->id, 'unique:users,email,' . $user->id],
            'phone' => ['nullable', 'string', 'max:40'],
            'address' => ['nullable', 'string', 'max:500'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'weather_summary' => ['nullable', 'string', 'max:255'],
            'weather_temp_c' => ['nullable', 'numeric'],
        ]);

        DB::transaction(function () use ($user, $member, $validated) {
            $user->update([
                'name' => $validated['full_name'],
                'email' => $validated['email'],
            ]);

            $member->update([
                'user_id' => $user->id,
                'full_name' => $validated['full_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'address' => $validated['address'] ?? null,
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
                'weather_summary' => $validated['weather_summary'] ?? null,
                'weather_temp_c' => $validated['weather_temp_c'] ?? null,
            ]);
        });

        return back()->with('success', 'Profile and location details saved.');
    }

    public function transactions(Request $request): View
    {
        $member = $this->currentMember($request);
        $transactions = $member->transactions->map(function ($tx) use ($member) {
            return $this->syncUnpaidTransactionDiscount($member, $tx);
        });

        return view('portal.transactions', compact('member', 'transactions'));
    }

    public function payForm(Request $request, MembershipTransaction $transaction): View|RedirectResponse
    {
        $member = $this->currentMember($request);

        if ($transaction->member_id !== $member->id) {
            abort(403);
        }

        if (in_array($transaction->status, ['Cancelled', 'Expired'], true)) {
            return redirect()->route('portal.transactions')
                ->with('error', "This transaction is {$transaction->status} and cannot be paid.");
        }

        $transaction = $this->syncUnpaidTransactionDiscount($member, $transaction->load(['level', 'payments']));
        $remainingBalance = max(0, round((float) $transaction->amount_due - $transaction->totalPaid(), 2));

        return view('portal.pay', compact('member', 'transaction', 'remainingBalance'));
    }

    public function processPay(Request $request, MembershipTransaction $transaction): RedirectResponse
    {
        $member = $this->currentMember($request);

        if ($transaction->member_id !== $member->id) {
            abort(403);
        }

        if (in_array($transaction->status, ['Cancelled', 'Expired'], true)) {
            return redirect()->route('portal.transactions')
                ->with('error', "Cannot pay a {$transaction->status} transaction.");
        }

        $validated = $request->validate([
            'payment_method' => ['required', 'string', 'max:60'],
            'external_reference' => ['nullable', 'string', 'max:100'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ]);

        $transaction = $this->syncUnpaidTransactionDiscount($member, $transaction->load(['level', 'payments']));
        $remainingBalance = max(0, round((float) $transaction->amount_due - $transaction->totalPaid(), 2));

        if ($remainingBalance <= 0) {
            return redirect()->route('portal.transactions.receipt', $transaction)
                ->with('success', 'This transaction is already paid.');
        }

        DB::transaction(function () use ($request, $validated, $transaction, $member, $remainingBalance) {
            Payment::create([
                'membership_transaction_id' => $transaction->id,
                'amount_paid' => $remainingBalance,
                'payment_method' => $validated['payment_method'],
                'external_reference' => $validated['external_reference'] ?? null,
                'paid_at' => now(),
                'remarks' => $validated['remarks'] ?? null,
            ]);

            if ($transaction->status === 'Pending') {
                $transaction->update(['status' => 'Paid']);
            }

            NotificationService::notifyUser(
                $request->user(),
                'Payment Recorded — ' . $transaction->reference_number,
                'Paid ' . number_format($remainingBalance, 2) . " via {$validated['payment_method']}. Status: {$transaction->status}.",
                'transaction',
                $member,
                $transaction->fresh()
            );
        });

        return redirect()->route('portal.transactions.receipt', $transaction)
            ->with('success', 'Payment recorded! Reference #' . $transaction->reference_number);
    }

    public function receipt(Request $request, MembershipTransaction $transaction): View
    {
        $member = $this->currentMember($request);

        if ($transaction->member_id !== $member->id && !$request->user()->hasRole('admin', 'staff')) {
            abort(403);
        }

        $transaction->load(['member.category', 'member.level', 'level', 'payments']);

        return view('transactions.receipt', compact('transaction'));
    }

    public function notifications(Request $request): View
    {
        $notifications = $request->user()->notifications()->paginate(20);
        return view('portal.notifications', compact('notifications'));
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->notifications()->where('is_read', false)->update(['is_read' => true]);
        return back()->with('success', 'All notifications marked as read.');
    }

    public function paySelectedPlan(Request $request): RedirectResponse
    {
        $member = $this->currentMember($request);

        $validated = $request->validate([
            'membership_level_id' => ['required', 'exists:membership_levels,id'],
            'payment_method' => ['required', 'string', 'max:60'],
            'external_reference' => ['nullable', 'string', 'max:100'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ]);

        $level = MembershipLevel::findOrFail($validated['membership_level_id']);
        $pricing = self::discountedFeeFor($member, $level);

        $transaction = DB::transaction(function () use ($request, $validated, $member, $level, $pricing) {
            $tx = MembershipTransaction::create([
                'member_id' => $member->id,
                'membership_level_id' => $level->id,
                'transaction_type' => 'application',
                'amount_due' => $pricing['final_fee'],
                'status' => 'Paid',
                'notes' => $pricing['discount_percent'] > 0
                    ? "Paid {$level->name} plan ({$pricing['discount_percent']}% {$member->category->name} discount)."
                    : "Paid {$level->name} plan.",
            ]);

            Payment::create([
                'membership_transaction_id' => $tx->id,
                'amount_paid' => $pricing['final_fee'],
                'payment_method' => $validated['payment_method'],
                'external_reference' => $validated['external_reference'] ?? null,
                'paid_at' => now(),
                'remarks' => $validated['remarks'] ?? null,
            ]);

            NotificationService::notifyUser(
                $request->user(),
                'Payment Recorded — ' . $tx->reference_number,
                'Paid ' . number_format($pricing['final_fee'], 2) . " via {$validated['payment_method']} for {$level->name}.",
                'transaction',
                $member,
                $tx
            );

            return $tx;
        });

        return redirect()->route('portal.transactions.receipt', $transaction)
            ->with('success', 'Payment recorded! Reference #' . $transaction->reference_number);
    }


    public function confirmPendingPlan(Request $request): RedirectResponse
    {
        $member = $this->currentMember($request);

        $validated = $request->validate([
            'membership_level_id' => ['required', 'exists:membership_levels,id'],
        ]);

        $level = MembershipLevel::findOrFail($validated['membership_level_id']);
        $pricing = self::discountedFeeFor($member, $level);

        $noteText = $pricing['discount_percent'] > 0
            ? "Confirmed {$level->name} plan ({$pricing['discount_percent']}% {$member->category->name} discount applied)."
            : "Confirmed {$level->name} plan.";

        $transaction = MembershipTransaction::create([
            'member_id' => $member->id,
            'membership_level_id' => $level->id,
            'transaction_type' => 'application',
            'amount_due' => $pricing['final_fee'],
            'status' => 'Pending',
            'notes' => $noteText,
        ]);

        NotificationService::notifyUser(
            $request->user(),
            'Pending Membership Transaction — ' . $transaction->reference_number,
            "Transaction {$transaction->reference_number} for {$level->name} is now Pending (Amount Due: " . number_format($pricing['final_fee'], 2) . ").",
            'transaction',
            $member,
            $transaction
        );

        return redirect()->route('portal.transactions')
            ->with('success', "Transaction {$transaction->reference_number} created as Pending.");
    }

    private function syncUnpaidTransactionDiscount(Member $member, MembershipTransaction $transaction): MembershipTransaction
    {
        if (
            in_array($transaction->status, ['Pending'], true)
            && $transaction->totalPaid() <= 0
            && $transaction->level
        ) {
            $pricing = self::discountedFeeFor($member, $transaction->level);

            if ((float) $transaction->amount_due !== (float) $pricing['final_fee']) {
                $discountNote = $pricing['discount_percent'] > 0
                    ? " ({$pricing['discount_percent']}% {$member->category->name} discount applied)"
                    : '';

                $transaction->update([
                    'amount_due' => $pricing['final_fee'],
                    'notes' => trim(($transaction->notes ?? "Selected {$transaction->level->name} plan.") . $discountNote),
                ]);
            }
        }

        return $transaction->fresh(['level', 'payments']);
    }
}