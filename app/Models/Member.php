<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Member extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'membership_number',
        'full_name',
        'email',
        'phone',
        'address',
        'latitude',
        'longitude',
        'weather_summary',
        'weather_temp_c',
        'membership_category_id',
        'membership_level_id',
        'status',
        'membership_expires_at',
    ];

    protected $casts = [
        'membership_expires_at' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (Member $member) {
            if (empty($member->membership_number)) {
                $member->membership_number = static::generateMembershipNumber();
            }
        });
    }

    public static function generateMembershipNumber(): string
    {
        do {
            $number = 'MBR-' . now()->format('Y') . '-' . strtoupper(Str::random(6));
        } while (static::where('membership_number', $number)->exists());

        return $number;
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(MembershipCategory::class, 'membership_category_id');
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(MembershipLevel::class, 'membership_level_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(MembershipTransaction::class)->latest();
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function enrollments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CourseEnrollment::class);
    }

    public function activeEnrollments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CourseEnrollment::class)->where('status', 'enrolled');
    }

    public function hasApprovedMembership(): bool
    {
        $notExpired = is_null($this->membership_expires_at) || $this->membership_expires_at->startOfDay()->gte(now()->startOfDay());

        return $this->status === 'active'
            && $notExpired
            && $this->transactions()->where('status', 'Approved')->exists();
    }
}