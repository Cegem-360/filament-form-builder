<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('registration_forms')) {
            return;
        }

        $tenantKey = (string) config('filament-form-builder.tenant_foreign_key', 'team_id');

        Schema::create('registration_forms', function (Blueprint $table) use ($tenantKey): void {
            $table->id();
            $table->unsignedBigInteger($tenantKey)->nullable();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            // Filament Builder JSON: [{"type":"text_input","data":{...}}, ...]
            $table->json('fields');
            $table->json('submission_actions')->nullable();
            $table->text('thank_you_message')->nullable();
            $table->string('redirect_url', 2048)->nullable();
            $table->longText('custom_css')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('submissions_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index($tenantKey);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registration_forms');
    }
};
