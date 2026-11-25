<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('membership_number')->unique();
            $table->string('full_name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('organization')->nullable();
            $table->foreignId('membership_category_id')->constrained('membership_categories')->restrictOnDelete();
            $table->foreignId('membership_level_id')->constrained('membership_levels')->restrictOnDelete();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->date('membership_expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};