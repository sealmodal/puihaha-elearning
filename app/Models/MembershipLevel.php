<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MembershipLevel extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'fee', 'validity_days', 'benefits', 'is_active'];

    protected $casts = [
        'fee' => 'decimal:2',
        'validity_days' => 'integer',
        'is_active' => 'boolean',
    ];

    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(MembershipTransaction::class);
    }

    public function hasUnlimitedCourses(): bool
    {
        return is_null($this->course_limit) || strtolower((string) $this->name) === 'platinum';
    }
}