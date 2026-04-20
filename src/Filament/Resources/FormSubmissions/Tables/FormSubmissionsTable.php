<?php

declare(strict_types=1);

namespace Madbox99\FilamentFormBuilder\Filament\Resources\FormSubmissions\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Madbox99\FilamentFormBuilder\Models\FormSubmission;

final class FormSubmissionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with([
                'registrationForm:id,name',
            ]))
            ->columns([
                TextColumn::make('registrationForm.name')
                    ->label(__('filament-form-builder::form.fields.form'))
                    ->badge()
                    ->sortable()
                    ->searchable(),
                TextColumn::make('data_preview')
                    ->label(__('filament-form-builder::form.fields.data'))
                    ->state(fn (FormSubmission $record): string => self::buildDataPreview($record))
                    ->tooltip(fn (FormSubmission $record): string => self::buildDataPreview($record, null)),
                TextColumn::make('lead_id')
                    ->label(__('filament-form-builder::form.fields.lead'))
                    ->placeholder('—'),
                TextColumn::make('ip_address')
                    ->label(__('filament-form-builder::form.fields.ip_address'))
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label(__('filament-form-builder::form.fields.submitted_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }

    private static function buildDataPreview(FormSubmission $record, ?int $limit = 80): string
    {
        $data = $record->data ?? [];

        $parts = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = implode(', ', $value);
            }

            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            }

            $parts[] = sprintf('%s=%s', $key, (string) $value);
        }

        $preview = implode(' | ', $parts);

        return $limit !== null ? Str::limit($preview, $limit) : $preview;
    }
}
