<?php

declare(strict_types=1);

namespace Madbox99\FilamentFormBuilder\Filament\Resources\RegistrationForms\Schemas\Sections;

use Filament\Forms\Components\CodeEditor;
use Filament\Forms\Components\CodeEditor\Enums\Language;
use Filament\Schemas\Components\Section;
use Madbox99\FilamentFormBuilder\Support\CssSanitizer;

final class CustomCssSection
{
    public static function make(): Section
    {
        return Section::make(__('filament-form-builder::form.sections.custom_css'))
            ->description(__('filament-form-builder::form.descriptions.custom_css'))
            ->schema([
                CodeEditor::make('custom_css')
                    ->label(__('filament-form-builder::form.fields.custom_css'))
                    ->language(Language::Css)
                    ->helperText(__('filament-form-builder::form.helpers.custom_css'))
                    ->dehydrateStateUsing(fn (?string $state): ?string => self::sanitiseCss($state))
                    ->columnSpanFull(),
            ])
            ->collapsed()
            ->visible((bool) config('filament-form-builder.custom_css.enabled', true));
    }

    private static function sanitiseCss(?string $state): ?string
    {
        if ($state === null) {
            return null;
        }

        $max = (int) config('filament-form-builder.custom_css.max_length', CssSanitizer::DEFAULT_MAX_LENGTH);
        $sanitized = CssSanitizer::sanitize($state, $max);

        return $sanitized === '' ? null : $sanitized;
    }
}
