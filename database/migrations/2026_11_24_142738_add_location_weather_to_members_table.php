<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            if (!Schema::hasColumn('members', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('members', 'address')) {
                $table->string('address')->nullable()->after('organization');
            }
            if (!Schema::hasColumn('members', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable()->after('address');
            }
            if (!Schema::hasColumn('members', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            }
            if (!Schema::hasColumn('members', 'weather_summary')) {
                $table->string('weather_summary')->nullable()->after('longitude');
            }
            if (!Schema::hasColumn('members', 'weather_temp_c')) {
                $table->decimal('weather_temp_c', 5, 2)->nullable()->after('weather_summary');
            }
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            if (Schema::hasColumn('members', 'user_id')) {
                $table->dropConstrainedForeignId('user_id');
            }
            $table->dropColumn(array_filter(
                ['address', 'latitude', 'longitude', 'weather_summary', 'weather_temp_c'],
                fn($col) => Schema::hasColumn('members', $col)
            ));
        });
    }
};