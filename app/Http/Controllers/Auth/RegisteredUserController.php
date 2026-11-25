<?php

namespace App\Http\Controllers\Auth;
use App\Http\Controllers\MemberPortalController;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\MembershipCategory;
use App\Models\MembershipLevel;
use App\Models\MembershipTransaction;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        $categories = MembershipCategory::where('is_active', true)->orderBy('name')->get();
        $levels = MembershipLevel::where('is_active', true)->orderBy('fee')->get();

        return view('auth.register', compact('categories', 'levels'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email:rfc,dns',
                'regex:/^[^@\s]+@[^@\s]+\.[^@\s]{2,}$/i',
                'max:255',
                'unique:' . User::class,
            ],
            'phone' => ['nullable', 'string', 'max:40'],
            'membership_category_id' => ['required', 'exists:membership_categories,id'],
            'membership_level_id' => ['required', 'exists:membership_levels,id'],
            'address' => ['nullable', 'string', 'max:500'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'weather_summary' => ['nullable', 'string', 'max:255'],
            'weather_temp_c' => ['nullable', 'numeric'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => 'member',
            ]);

            $category = MembershipCategory::findOrFail($validated['membership_category_id']);
            $level = MembershipLevel::findOrFail($validated['membership_level_id']);

            $member = Member::create([
                'user_id' => $user->id,
                'membership_number' => Member::generateMembershipNumber(),
                'full_name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'address' => $validated['address'] ?? null,
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
                'weather_summary' => $validated['weather_summary'] ?? null,
                'weather_temp_c' => $validated['weather_temp_c'] ?? null,
                'membership_category_id' => $category->id,
                'membership_level_id' => $level->id,
                'status' => 'active',
            ]);

            $member->setRelation('category', $category);
            $pricing = MemberPortalController::discountedFeeFor($member, $level);

            $noteText = $pricing['discount_percent'] > 0
                ? "Initial membership application for {$level->name} ({$pricing['discount_percent']}% {$category->name} discount applied)."
                : "Initial membership application for {$level->name}.";

            $transaction = MembershipTransaction::create([
                'member_id' => $member->id,
                'membership_level_id' => $level->id,
                'transaction_type' => 'application',
                'amount_due' => $pricing['final_fee'],
                'status' => 'Pending',
                'notes' => $noteText,
            ]);

            NotificationService::notifyUser(
                $user,
                'Verify Your Email & Complete Payment',
                "Account created ({$member->membership_number}). Please verify your email first, then log in to complete payment for {$transaction->reference_number}.",
                'transaction',
                $member,
                $transaction,
                false
            );

            return $user;
        });

        event(new Registered($user));

        return redirect()->route('login')
            ->with('verification_sent', 'Registration successful! A verification email with your account details and verification link has been sent to ' . $user->email . '. Please verify your email address before logging in.');
    }
}