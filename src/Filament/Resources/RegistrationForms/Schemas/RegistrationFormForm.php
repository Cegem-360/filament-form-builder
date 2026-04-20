<?php

declare(strict_types=1);

namespace Madbox99\FilamentFormBuilder\Filament\Resources\RegistrationForms\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Madbox99\FilamentFormBuilder\ValueObjects\SubmissionActions;

final class RegistrationFormForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('filament-form-builder::form.sections.form_details'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('filament-form-builder::form.fields.name'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('slug')
                            ->label(__('filament-form-builder::form.fields.slug'))
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Textarea::make('description')
                            ->label(__('filament-form-builder::form.fields.description'))
                            ->maxLength(65535)
                            ->columnSpanFull(),
                        Toggle::make('is_active')
                            ->label(__('filament-form-builder::form.fields.active'))
                            ->default(true),
                    ])
                    ->columns(2),

                Section::make(__('filament-form-builder::form.sections.form_fields'))
                    ->schema([
                        Repeater::make('fields')
                            ->label(__('filament-form-builder::form.sections.form_fields'))
                            ->schema([
                                TextInput::make('name')
                                    ->label(__('filament-form-builder::form.fields.field_name'))
                                    ->required(),
                                Select::make('type')
                                    ->label(__('filament-form-builder::form.fields.field_type'))
                                    ->options([
                                        'text' => 'Text',
                                        'email' => 'Email',
                                        'phone' => 'Phone',
                                        'number' => 'Number',
                                        'textarea' => 'Textarea',
                                        'select' => 'Select',
                                        'checkbox' => 'Checkbox',
                                        'date' => 'Date',
                                    ])
                                    ->required(),
                                Toggle::make('required')
                                    ->label(__('filament-form-builder::form.fields.required'))
                                    ->default(false),
                                TextInput::make('placeholder')
                                    ->label(__('filament-form-builder::form.fields.placeholder'))
                                    ->maxLength(255),
                            ])
                            ->columns(2)
                            ->columnSpanFull()
                            ->defaultItems(1),
                    ]),

                Section::make(__('filament-form-builder::form.sections.submission_settings'))
                    ->schema([
                        Textarea::make('thank_you_message')
                            ->label(__('filament-form-builder::form.fields.thank_you_message'))
                            ->maxLength(65535),
                        TextInput::make('redirect_url')
                            ->label(__('filament-form-builder::form.fields.redirect_url'))
                            ->url()
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Section::make(__('filament-form-builder::form.sections.submission_actions'))
                    ->schema([
                        Toggle::make('submission_actions.' . SubmissionActions::KEY_CREATE_SUBMISSION)
                            ->label(__('filament-form-builder::form.fields.save_submissions'))
                            ->default(true),
                        Toggle::make('submission_actions.' . SubmissionActions::KEY_CREATE_LEAD_IF_HAS_EMAIL)
                            ->label(__('filament-form-builder::form.fields.create_lead_if_email'))
                            ->default(true),
                        TagsInput::make('submission_actions.' . SubmissionActions::KEY_NOTIFY_EMAILS)
                            ->label(__('filament-form-builder::form.fields.notify_emails'))
                            ->placeholder(__('filament-form-builder::form.fields.add_email'))
                            ->nestedRecursiveRules(['email'])
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
