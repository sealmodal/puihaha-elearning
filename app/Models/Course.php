<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_code',
        'title',
        'description',
        'instructor',
        'duration_hours',
        'is_active',
    ];

    protected $casts = [
        'duration_hours' => 'integer',
        'is_active' => 'boolean',
    ];

    public function allowedLevels(): BelongsToMany
    {
        return $this->belongsToMany(MembershipLevel::class, 'course_level');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(CourseEnrollment::class);
    }

    public function activeEnrollments(): HasMany
    {
        return $this->hasMany(CourseEnrollment::class)->where('status', 'enrolled');
    }

    public function isAvailableForLevel(MembershipLevel $level): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($level->hasUnlimitedCourses()) {
            return true;
        }

        // If no specific levels are attached, available to all approved members
        if ($this->allowedLevels->isEmpty()) {
            return true;
        }

        $tierRank = [
            'basic' => 1,
            'silver' => 2,
            'gold' => 3,
            'platinum' => 4,
        ];

        $memberLevelName = strtolower(trim((string) $level->name));
        $memberRank = $tierRank[$memberLevelName] ?? null;

        if (!is_null($memberRank)) {
            foreach ($this->allowedLevels as $allowedLevel) {
                $allowedRank = $tierRank[strtolower(trim((string) $allowedLevel->name))] ?? null;
                if (!is_null($allowedRank) && $allowedRank <= $memberRank) {
                    return true;
                }
            }
        }

        return $this->allowedLevels->contains('id', $level->id);
    }
}