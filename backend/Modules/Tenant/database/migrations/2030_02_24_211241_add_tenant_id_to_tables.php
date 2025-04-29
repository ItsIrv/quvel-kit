<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach (config('tenant.tables', []) as $tableName => $settings) {
            Schema::table($tableName, static function (Blueprint $table) use ($settings): void {

                // Add tenant_id column
                $tenantIdColumn = $table->foreignId('tenant_id')
                    ->after($settings['after'] ?? 'id')
                    ->constrained('tenants');

                if ($settings['cascadeDelete'] ?? false) {
                    $tenantIdColumn->cascadeOnDelete();
                }

                foreach ($settings['dropUnique'] ?? [] as $column) {
                    $table->dropUnique([$column]);
                }

                foreach ($settings['compoundUnique'] ?? [] as $column) {
                    $table->unique(['tenant_id', $column], $column . '_tenant_unique');
                }

                $table->index('tenant_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (config('tenant.tables', []) as $tableName => $settings) {
            Schema::table($tableName, static function (Blueprint $table) use ($settings): void {
                // Drop tenant_id column
                $table->dropConstrainedForeignId('tenant_id');

                // Drop added indexes
                foreach ($settings['compoundUnique'] ?? [] as $column) {
                    $table->dropIndex($column . '_tenant_unique');
                }

                // Restore dropped unique constraints
                foreach ($settings['dropUnique'] ?? [] as $column) {
                    $table->unique($column);
                }
            });
        }
    }
};
