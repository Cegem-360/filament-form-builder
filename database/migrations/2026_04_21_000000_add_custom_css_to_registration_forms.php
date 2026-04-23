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

        if (Schema::hasColumn('registration_forms', 'custom_css')) {
            return;
        }

        Schema::table('registration_forms', function (Blueprint $table): void {
            $table->longText('custom_css')->nullable();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('registration_forms')) {
            return;
        }

        if (! Schema::hasColumn('registration_forms', 'custom_css')) {
            return;
        }

        Schema::table('registration_forms', function (Blueprint $table): void {
            $table->dropColumn('custom_css');
        });
    }
};
