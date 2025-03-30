<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            // Drop the global unique constraint on email
            $table->dropUnique(['email']);

            // Add tenant_id column
            $table->foreignId('tenant_id')
                ->nullable()
                ->after('id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            // Ensure emails are unique per tenant
            $table->unique(['tenant_id', 'email'], 'users_tenant_email_unique');
            $table->index('tenant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique('users_tenant_email_unique');
            $table->dropConstrainedForeignId('tenant_id');

            // Restore the global unique constraint on email (if rolling back)
            $table->unique('email');
        });
    }
};
