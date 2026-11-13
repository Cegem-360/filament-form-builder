<?php

declare(strict_types=1);

namespace Madbox99\FilamentFormBuilder\Filament\Resources\FormSubmissions\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Madbox99\FilamentFormBuilder\Models\FormSubmission;
use Madbox99\FilamentFormBuilder\Support\FormFieldBlueprint;
use Madbox99\FilamentFormBuilder\Support\SubmissionDataPresenter;

final class FormSubmissionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('filament-form-builder::form.sections.submission_details'))
                    ->schema([
                        TextEntry::make('registrationForm.name')
                            ->label(__('filament-form-builder::form.fields.form')),
                        TextEntry::make('created_at')
                            ->label(__('filament-form-builder::form.fields.submitted_at'))
                            ->dateTime(),
                        TextEntry::make('lead_id')
                            ->label(__('filament-form-builder::form.fields.lead'))
                            ->placeholder('—'),
                        TextEntry::make('ip_address')
                            ->label(__('filament-form-builder::form.fields.ip_address'))
                            ->placeholder('—'),
                        TextEntry::make('user_agent')
                            ->label(__('filament-form-builder::form.fields.user_agent'))
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make(__('filament-form-builder::form.sections.submitted_data'))
                    ->schema(fn (?FormSubmission $record): array => $record instanceof FormSubmission
                        ? self::submittedDataEntries($record)
                        : [])
                    ->columns(2),
            ]);
    }

    /**
     * One labelled entry per form field, in the form's field order.
     *
     * @return list<TextEntry>
     */
    private static function submittedDataEntries(FormSubmission $record): array
    {
        $entries = [];

        foreach (SubmissionDataPresenter::rows($record) as $index => $row) {
            $entry = TextEntry::make('submitted_data_'.$index)
                ->label($row['label'])
                ->state($row['value'])
                ->placeholder('—');

            if (is_array($row['value'])) {
                $entry->badge();
            }

            if ($row['width'] === FormFieldBlueprint::WIDTH_FULL) {
                $entry->columnSpanFull();
            }

            $entries[] = $entry;
        }

        return $entries;
    }
}
