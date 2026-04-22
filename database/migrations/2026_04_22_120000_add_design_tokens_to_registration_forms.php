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

        if (Schema::hasColumn('registration_forms', 'design_tokens')) {
            return;
        }

        Schema::table('registration_forms', function (Blueprint $table): void {
            $table->json('design_tokens')->nullable()->after('custom_css');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('registration_forms')) {
            return;
        }

        if (! Schema::hasColumn('registration_forms', 'design_tokens')) {
            return;
        }

        Schema::table('registration_forms', function (Blueprint $table): void {
            $table->dropColumn('design_tokens');
        });
    }
};
