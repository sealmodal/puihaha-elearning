<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'receipt_number',
        'membership_transaction_id',
        'amount_paid',
        'payment_method',
        'external_reference',
        'paid_at',
        'remarks',
    ];

    protected $casts = [
        'amount_paid' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Payment $payment) {
            if (empty($payment->receipt_number)) {
                do {
                    $receipt = 'RCP-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
                } while (static::where('receipt_number', $receipt)->exists());

                $payment->receipt_number = $receipt;
            }
        });
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(MembershipTransaction::class, 'membership_transaction_id');
    }
}