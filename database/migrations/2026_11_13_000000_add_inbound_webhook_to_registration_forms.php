<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('registration_forms')) {
            return;
        }

        if (Schema::hasColumn('registration_forms', 'inbound_token')) {
            return;
        }

        Schema::table('registration_forms', function (Blueprint $table): void {
            $table->boolean('inbound_enabled')->default(false);
            $table->string('inbound_token', 64)->nullable()->unique();
            $table->json('inbound_field_map')->nullable();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('registration_forms')) {
            return;
        }

        if (! Schema::hasColumn('registration_forms', 'inbound_token')) {
            return;
        }

        Schema::table('registration_forms', function (Blueprint $table): void {
            $table->dropUnique(['inbound_token']);
            $table->dropColumn(['inbound_enabled', 'inbound_token', 'inbound_field_map']);
        });
    }
};
