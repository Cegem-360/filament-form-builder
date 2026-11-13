<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Madbox99\FilamentFormBuilder\Http\Controllers\EmbedScriptController;
use Madbox99\FilamentFormBuilder\Http\Controllers\InboundSubmissionController;
use Madbox99\FilamentFormBuilder\Http\Controllers\PublicFormController;
use Madbox99\FilamentFormBuilder\Http\Controllers\WordPressPluginController;
use Madbox99\FilamentFormBuilder\Http\Middleware\ExpectsJson;

$middleware = (array) config('filament-form-builder.routes.middleware', ['web']);
$throttle = (array) config('filament-form-builder.routes.throttle', []);
$showPath = (string) config('filament-form-builder.routes.show_path', 'forms/{slug}');
$embedPath = (string) config('filament-form-builder.routes.embed_path', 'embed/forms/{slug}');
$scriptPath = (string) config('filament-form-builder.routes.widget_script_path', 'forms/embed.js');
$inboundPath = (string) config('filament-form-builder.routes.inbound_path', 'forms/{slug}/inbound/{token}');
$inboundMiddleware = (array) config('filament-form-builder.routes.inbound_middleware', ['api']);

// Inbound webhook: server-to-server POSTs from external forms — no session,
// no CSRF. Registered first so `/forms/{slug}` never shadows it.
// The installable WordPress plugin: a static download, no session needed.
Route::get('forms/wordpress-plugin.zip', WordPressPluginController::class)
    ->middleware('throttle:'.($throttle['script'] ?? '120,1'))
    ->name('form-builder.wordpress-plugin');

Route::middleware($inboundMiddleware)->group(function () use ($inboundPath): void {
    Route::post($inboundPath, InboundSubmissionController::class)
        ->middleware(ExpectsJson::class)
        ->name('form-builder.form.inbound');
});

Route::middleware($middleware)->group(function () use ($showPath, $embedPath, $scriptPath, $throttle): void {
    // Literal routes must be registered before catch-all {slug} routes so
    // that e.g. /forms/embed.js is not captured by /forms/{slug}.
    Route::get($scriptPath, EmbedScriptController::class)
        ->middleware('throttle:'.($throttle['script'] ?? '120,1'))
        ->name('form-builder.widget.script');

    Route::get($embedPath, [PublicFormController::class, 'embed'])
        ->middleware('throttle:'.($throttle['embed'] ?? '60,1'))
        ->name('form-builder.form.embed');

    Route::get($showPath, [PublicFormController::class, 'show'])
        ->middleware('throttle:'.($throttle['show'] ?? '60,1'))
        ->name('form-builder.form.show');
});
