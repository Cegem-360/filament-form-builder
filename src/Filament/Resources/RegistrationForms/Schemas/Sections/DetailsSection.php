<?php

declare(strict_types=1);

namespace Madbox99\FilamentFormBuilder\Filament\Resources\RegistrationForms\Schemas\Sections;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;

final class DetailsSection
{
    public static function make(): Section
    {
        return Section::make(__('filament-form-builder::form.sections.form_details'))
            ->schema([
                TextInput::make('name')
                    ->label(__('filament-form-builder::form.fields.name'))
                    ->required()
                    ->maxLength(255),
                TextInput::make('slug')
                    ->label(__('filament-form-builder::form.fields.slug'))
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->regex('/^[a-z0-9-]+$/')
                    ->maxLength(128),
                Textarea::make('description')
                    ->label(__('filament-form-builder::form.fields.description'))
                    ->maxLength(65535)
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->label(__('filament-form-builder::form.fields.active'))
                    ->default(true),
            ])
            ->columns(2);
    }
}
