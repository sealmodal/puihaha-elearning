<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class MembershipTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference_number',
        'member_id',
        'membership_level_id',
        'transaction_type',
        'amount_due',
        'status',
        'approved_at',
        'notes',
    ];

    protected $casts = [
        'amount_due' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (MembershipTransaction $transaction) {
            if (empty($transaction->reference_number)) {
                $prefix = $transaction->transaction_type === 'renewal' ? 'RNW' : 'APP';
                do {
                    $ref = "TXN-{$prefix}-" . now()->format('Ymd') . '-' . strtoupper(Str::random(5));
                } while (static::where('reference_number', $ref)->exists());

                $transaction->reference_number = $ref;
            }
        });
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(MembershipLevel::class, 'membership_level_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest();
    }

    public function totalPaid(): float
    {
        return (float) $this->payments()->sum('amount_paid');
    }

    public function isFullyPaid(): bool
    {
        return $this->totalPaid() >= (float) $this->amount_due;
    }
}