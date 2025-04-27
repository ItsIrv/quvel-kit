<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', static function (Blueprint $table): void {
            $table->string('password')->nullable()->change();
            $table->string('provider_id')->nullable()->after('email');
            $table->string('avatar')->nullable()->after('provider_id');

            $table->unique('provider_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', static function (Blueprint $table): void {
            $table->string('password')->nullable(false)->change();
            $table->dropColumn(['provider_id', 'avatar']);
        });
    }
};
