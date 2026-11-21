<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add KTP NIK, phone, role, and verification status columns to the users table
     * so both Admin and Penyewa accounts share unified authentication with role-based approval.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('nik', 16)->nullable()->unique()->after('id');
            $table->string('phone', 20)->nullable()->after('name');
            $table->enum('role', ['admin', 'penyewa'])->default('penyewa')->index()->after('password');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->index()->after('role');
        });
    }

    /**
     * Revert the rental-specific user columns back to the default Laravel users schema.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['role']);
            $table->dropIndex(['status']);
            $table->dropUnique(['nik']);
            $table->dropColumn(['nik', 'phone', 'role', 'status']);
        });
    }
};
