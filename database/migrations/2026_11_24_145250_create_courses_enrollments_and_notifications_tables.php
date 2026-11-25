<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('membership_levels', function (Blueprint $table) {
            if (!Schema::hasColumn('membership_levels', 'course_limit')) {
                // null = unlimited (e.g., Platinum); integer = max enrolled courses (Basic=1, Silver=3, Gold=5)
                $table->unsignedInteger('course_limit')->nullable()->after('validity_days');
            }
        });

        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('course_code')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('instructor')->nullable();
            $table->unsignedInteger('duration_hours')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('course_level', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignId('membership_level_id')->constrained('membership_levels')->cascadeOnDelete();
            $table->unique(['course_id', 'membership_level_id']);
        });

        Schema::create('course_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->enum('status', ['enrolled', 'cancelled', 'expired'])->default('enrolled');
            $table->timestamp('enrolled_at')->nullable();
            $table->timestamps();
            $table->unique(['member_id', 'course_id']);
        });

        Schema::create('user_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('message');
            $table->string('type')->default('general'); // transaction, course, membership
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_notifications');
        Schema::dropIfExists('course_enrollments');
        Schema::dropIfExists('course_level');
        Schema::dropIfExists('courses');

        Schema::table('membership_levels', function (Blueprint $table) {
            if (Schema::hasColumn('membership_levels', 'course_limit')) {
                $table->dropColumn('course_limit');
            }
        });
    }
};