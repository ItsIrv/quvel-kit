<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLES = [
        // table => after column
        'users' => 'id',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach (self::TABLES as $table => $after) {
            Schema::table($table, function (Blueprint $table) use ($after): void {
                $table->foreignId('tenant_id')
                    // TODO: To be removed. This will always be 1 when multi-tenancy is disabled.
                    // Allowing for in-development ease.
                    ->nullable()
                    ->after($after)
                    ->constrained('tenants')
                    ->cascadeOnDelete()
                    ->index();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $table): void {
                $table->dropConstrainedForeignId('tenant_id');
            });
        }
    }
};
