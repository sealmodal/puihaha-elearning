<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number')->unique();
            $table->foreignId('membership_transaction_id')->constrained('membership_transactions')->cascadeOnDelete();
            $table->decimal('amount_paid', 10, 2);
            $table->string('payment_method'); // Cash, Card, Bank Transfer, E-Wallet
            $table->string('external_reference')->nullable();
            $table->timestamp('paid_at');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};