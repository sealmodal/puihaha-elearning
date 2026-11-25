<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\MembershipCategory;
use App\Models\MembershipLevel;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@elearning.test'],
            [
                'name' => 'System Administrator',
                'password' => Hash::make('password123'),
                'role' => 'admin',
            ]
        );

        foreach (['Student', 'Regular', 'Senior Citizen', 'Corporate'] as $category) {
            MembershipCategory::firstOrCreate(['name' => $category], ['is_active' => true]);
        }

        $levelsData = [
            ['name' => 'Basic', 'fee' => 29.00, 'validity_days' => 365, 'course_limit' => 1, 'benefits' => 'Enroll in up to 1 course + core quizzes'],
            ['name' => 'Silver', 'fee' => 59.00, 'validity_days' => 365, 'course_limit' => 3, 'benefits' => 'Enroll in up to 3 courses + certificates'],
            ['name' => 'Gold', 'fee' => 99.00, 'validity_days' => 365, 'course_limit' => 5, 'benefits' => 'Enroll in up to 5 courses + workshops'],
            ['name' => 'Platinum', 'fee' => 199.00, 'validity_days' => 365, 'course_limit' => null, 'benefits' => 'Unlimited courses + full platform access'],
        ];

        $levels = [];
        foreach ($levelsData as $lvl) {
            $levels[$lvl['name']] = MembershipLevel::updateOrCreate(
                ['name' => $lvl['name']],
                $lvl + ['is_active' => true]
            );
        }

        $coursesData = [
            ['course_code' => 'CRS-101', 'title' => 'Introduction to Web Foundations', 'description' => 'HTML, CSS, and modern web structure.', 'instructor' => 'Prof. Clara Reyes', 'duration_hours' => 6, 'levels' => ['Basic', 'Silver', 'Gold', 'Platinum']],
            ['course_code' => 'CRS-201', 'title' => 'Laravel & Relational Databases', 'description' => 'MVC architecture, Eloquent ORM, and MySQL design.', 'instructor' => 'Eng. Marco Santos', 'duration_hours' => 12, 'levels' => ['Silver', 'Gold', 'Platinum']],
            ['course_code' => 'CRS-301', 'title' => 'API Design & System Integration', 'description' => 'REST endpoints, third-party APIs, and error handling.', 'instructor' => 'Dr. Elena Cruz', 'duration_hours' => 10, 'levels' => ['Gold', 'Platinum']],
            ['course_code' => 'CRS-401', 'title' => 'Enterprise Architecture & Cloud Ops', 'description' => 'Scalability, security, and production deployments.', 'instructor' => 'Architect Liam Tan', 'duration_hours' => 16, 'levels' => ['Platinum']],
        ];

        foreach ($coursesData as $item) {
            $course = Course::updateOrCreate(
                ['course_code' => $item['course_code']],
                [
                    'title' => $item['title'],
                    'description' => $item['description'],
                    'instructor' => $item['instructor'],
                    'duration_hours' => $item['duration_hours'],
                    'is_active' => true,
                ]
            );

            $levelIds = collect($item['levels'])
                ->map(fn($name) => $levels[$name]->id ?? null)
                ->filter()
                ->values()
                ->all();

            $course->allowedLevels()->sync($levelIds);
        }
    }
}