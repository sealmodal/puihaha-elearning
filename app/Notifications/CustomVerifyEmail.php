<?php

namespace App\Notifications;

use App\Models\Member;
use App\Models\MembershipTransaction;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class CustomVerifyEmail extends VerifyEmail
{
    public function __construct(
        protected ?Member $member = null,
        protected ?MembershipTransaction $transaction = null,
    ) {
    }

    public function toMail($notifiable): MailMessage
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        $mail = (new MailMessage)
            ->subject('Verify Your Email & Account Details — Puihaha Learning')
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Please verify your email address before logging in to Puihaha Learning.')
            ->action('Verify Email Address', $verificationUrl)
            ->line('If the button does not work, copy and paste this verification URL into your browser:')
            ->line($verificationUrl);

        if ($this->member) {
            $categoryName = $this->member->category?->name ?? 'Regular';
            $levelName = $this->member->level?->name ?? 'Basic';

            $mail->line("Membership Number: {$this->member->membership_number}")
                ->line("Category: {$categoryName}")
                ->line("Level: {$levelName}")
                ->line('Status: ' . ucfirst((string) $this->member->status));
        }

        if ($this->transaction) {
            $mail->line("Transaction Ref #: {$this->transaction->reference_number}")
                ->line('Amount Due: ' . number_format((float) $this->transaction->amount_due, 2))
                ->line("Transaction Status: {$this->transaction->status}");
        }

        return $mail
            ->line('Already verified? Log in here: ' . route('login'))
            ->line('If you did not create this account, no action is required.');
    }
}