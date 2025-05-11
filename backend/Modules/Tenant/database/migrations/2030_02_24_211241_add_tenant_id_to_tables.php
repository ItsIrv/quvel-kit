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
                $tenantIdColumn = $table->foreignId('tenant_id')
                    ->after($settings['after'] ?? 'id')
                    ->constrained('tenants');

                if ($settings['cascade_delete'] ?? false) {
                    $tenantIdColumn->cascadeOnDelete();
                }

                foreach ($settings['drop_uniques'] ?? [] as $columns) {
                    $table->dropUnique($columns);
                }

                foreach ($settings['tenant_unique_constraints'] ?? [] as $columns) {
                    $name = implode('_', $columns) . '_tenant_unique';
                    $table->unique(array_merge(['tenant_id'], $columns), $name);
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
                $table->dropConstrainedForeignId('tenant_id');

                foreach ($settings['tenant_unique_constraints'] ?? [] as $columns) {
                    $name = implode('_', $columns) . '_tenant_unique';
                    $table->dropUnique($name);
                }

                foreach ($settings['drop_uniques'] ?? [] as $columns) {
                    $table->unique($columns);
                }
            });
        }
    }
};
