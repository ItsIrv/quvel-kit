<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Tenant\Models\Tenant;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(Tenant::class, 'parent_id')->nullable()->constrained()->cascadeOnDelete();
            $table->char('public_id', 26)->unique();
            $table->string('name')->unique();
            $table->string('domain')->unique();
            $table->json('config')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
