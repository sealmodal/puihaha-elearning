<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('membership_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('membership_transactions', 'reference_number')) {
                $table->string('reference_number')->unique()->after('id');
            }
            if (!Schema::hasColumn('membership_transactions', 'member_id')) {
                $table->foreignId('member_id')->after('reference_number')->constrained('members')->cascadeOnDelete();
            }
            if (!Schema::hasColumn('membership_transactions', 'membership_level_id')) {
                $table->foreignId('membership_level_id')->after('member_id')->constrained('membership_levels')->restrictOnDelete();
            }
            if (!Schema::hasColumn('membership_transactions', 'transaction_type')) {
                $table->enum('transaction_type', ['application', 'renewal'])->default('application')->after('membership_level_id');
            }
            if (!Schema::hasColumn('membership_transactions', 'amount_due')) {
                $table->decimal('amount_due', 10, 2)->default(0.00)->after('transaction_type');
            }
            if (!Schema::hasColumn('membership_transactions', 'status')) {
                $table->enum('status', ['Pending', 'Paid', 'Approved', 'Expired', 'Cancelled'])->default('Pending')->after('amount_due');
            }
            if (!Schema::hasColumn('membership_transactions', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('status');
            }
            if (!Schema::hasColumn('membership_transactions', 'notes')) {
                $table->text('notes')->nullable()->after('approved_at');
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'receipt_number')) {
                $table->string('receipt_number')->unique()->after('id');
            }
            if (!Schema::hasColumn('payments', 'membership_transaction_id')) {
                $table->foreignId('membership_transaction_id')->after('receipt_number')->constrained('membership_transactions')->cascadeOnDelete();
            }
            if (!Schema::hasColumn('payments', 'amount_paid')) {
                $table->decimal('amount_paid', 10, 2)->default(0.00)->after('membership_transaction_id');
            }
            if (!Schema::hasColumn('payments', 'payment_method')) {
                $table->string('payment_method')->default('Cash')->after('amount_paid');
            }
            if (!Schema::hasColumn('payments', 'external_reference')) {
                $table->string('external_reference')->nullable()->after('payment_method');
            }
            if (!Schema::hasColumn('payments', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('external_reference');
            }
            if (!Schema::hasColumn('payments', 'remarks')) {
                $table->text('remarks')->nullable()->after('paid_at');
            }
        });
    }

    public function down(): void
    {
        //
    }
};