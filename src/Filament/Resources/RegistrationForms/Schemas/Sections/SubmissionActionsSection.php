<?php

declare(strict_types=1);

namespace Madbox99\FilamentFormBuilder\Filament\Resources\RegistrationForms\Schemas\Sections;

use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Madbox99\FilamentFormBuilder\ValueObjects\SubmissionActions;

final class SubmissionActionsSection
{
    public static function make(): Section
    {
        return Section::make(__('filament-form-builder::form.sections.submission_actions'))
            ->schema([
                Toggle::make('submission_actions.'.SubmissionActions::KEY_CREATE_SUBMISSION)
                    ->label(__('filament-form-builder::form.fields.save_submissions'))
                    ->default(true),
                Toggle::make('submission_actions.'.SubmissionActions::KEY_CREATE_LEAD_IF_HAS_EMAIL)
                    ->label(__('filament-form-builder::form.fields.create_lead_if_email'))
                    ->default(true),
                TagsInput::make('submission_actions.'.SubmissionActions::KEY_NOTIFY_EMAILS)
                    ->label(__('filament-form-builder::form.fields.notify_emails'))
                    ->placeholder(__('filament-form-builder::form.fields.add_email'))
                    ->nestedRecursiveRules(['email'])
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }
}
