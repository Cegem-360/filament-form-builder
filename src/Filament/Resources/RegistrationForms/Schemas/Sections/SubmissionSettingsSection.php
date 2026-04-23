<?php

declare(strict_types=1);

namespace Madbox99\FilamentFormBuilder\Filament\Resources\RegistrationForms\Schemas\Sections;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;

final class SubmissionSettingsSection
{
    public static function make(): Section
    {
        return Section::make(__('filament-form-builder::form.sections.submission_settings'))
            ->schema([
                Textarea::make('thank_you_message')
                    ->label(__('filament-form-builder::form.fields.thank_you_message'))
                    ->maxLength(65535),
                TextInput::make('redirect_url')
                    ->label(__('filament-form-builder::form.fields.redirect_url'))
                    ->url()
                    ->maxLength(2048)
                    ->helperText(__('filament-form-builder::form.helpers.redirect_url'))
                    ->rule('regex:#^https?://#i'),
            ])
            ->columns(2);
    }
}
