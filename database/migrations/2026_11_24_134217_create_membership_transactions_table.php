<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('membership_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number')->unique();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->foreignId('membership_level_id')->constrained('membership_levels')->restrictOnDelete();
            $table->enum('transaction_type', ['application', 'renewal']);
            $table->decimal('amount_due', 10, 2);
            $table->enum('status', ['Pending', 'Paid', 'Approved', 'Expired', 'Cancelled'])->default('Pending');
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membership_transactions');
    }
};