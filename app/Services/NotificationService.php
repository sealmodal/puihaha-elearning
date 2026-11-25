<?php

namespace App\Services;

use App\Mail\MembershipNotificationMail;
use App\Models\Member;
use App\Models\MembershipTransaction;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    public static function notifyUser(?User $user, string $title, string $message, string $type = 'general', ?Member $member = null, ?MembershipTransaction $transaction = null, bool $sendEmail = true): void
    {
        if (!$user) {
            return;
        }

        UserNotification::create([
            'user_id' => $user->id,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'is_read' => false,
        ]);

        $recipientEmail = $member?->email ?? $user->email;

        if ($sendEmail && $recipientEmail) {
            try {
                Mail::to($recipientEmail)->send(new MembershipNotificationMail(
                    $title,
                    $member ?? $user->member,
                    $transaction,
                    $message
                ));
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }
}