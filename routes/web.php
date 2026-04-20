<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Madbox99\FilamentFormBuilder\Http\Controllers\EmbedScriptController;
use Madbox99\FilamentFormBuilder\Http\Controllers\PublicFormController;

$middleware = (array) config('filament-form-builder.routes.middleware', ['web']);
$throttle = (array) config('filament-form-builder.routes.throttle', []);
$showPath = (string) config('filament-form-builder.routes.show_path', 'forms/{slug}');
$embedPath = (string) config('filament-form-builder.routes.embed_path', 'embed/forms/{slug}');
$scriptPath = (string) config('filament-form-builder.routes.widget_script_path', 'forms/embed.js');

Route::middleware($middleware)->group(function () use ($showPath, $embedPath, $scriptPath, $throttle): void {
    // Literal routes must be registered before catch-all {slug} routes so
    // that e.g. /forms/embed.js is not captured by /forms/{slug}.
    Route::get($scriptPath, EmbedScriptController::class)
        ->middleware('throttle:' . ($throttle['script'] ?? '120,1'))
        ->name('form-builder.widget.script');

    Route::get($embedPath, [PublicFormController::class, 'embed'])
        ->middleware('throttle:' . ($throttle['embed'] ?? '60,1'))
        ->name('form-builder.form.embed');

    Route::get($showPath, [PublicFormController::class, 'show'])
        ->middleware('throttle:' . ($throttle['show'] ?? '60,1'))
        ->name('form-builder.form.show');
});
