<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('form_submissions')) {
            return;
        }

        $tenantKey = (string) config('filament-form-builder.tenant_foreign_key', 'team_id');

        Schema::create('form_submissions', function (Blueprint $table) use ($tenantKey): void {
            $table->id();
            $table->foreignId('registration_form_id')
                ->constrained('registration_forms')
                ->cascadeOnDelete();
            $table->unsignedBigInteger($tenantKey)->nullable();
            $table->unsignedBigInteger('lead_id')->nullable();
            $table->json('data');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index([$tenantKey, 'created_at']);
            $table->index('registration_form_id');
            $table->index('lead_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_submissions');
    }
};
