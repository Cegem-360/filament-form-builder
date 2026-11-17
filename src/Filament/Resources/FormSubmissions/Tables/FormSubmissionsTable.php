<?php

declare(strict_types=1);

namespace Madbox99\FilamentFormBuilder\Filament\Resources\FormSubmissions\Tables;

use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\Indicator;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
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
            ->filters([
                SelectFilter::make('registration_form_id')
                    ->label(__('filament-form-builder::form.fields.form'))
                    ->relationship('registrationForm', 'name')
                    ->searchable()
                    ->preload(),
                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('from')->label(__('filament-form-builder::form.filters.from')),
                        DatePicker::make('until')->label(__('filament-form-builder::form.filters.until')),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $q, string $date): Builder => $q->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $q, string $date): Builder => $q->whereDate('created_at', '<=', $date)))
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['from'] ?? null) {
                            $indicators[] = Indicator::make(__('filament-form-builder::form.filters.from').': '.$data['from'])->removeField('from');
                        }

                        if ($data['until'] ?? null) {
                            $indicators[] = Indicator::make(__('filament-form-builder::form.filters.until').': '.$data['until'])->removeField('until');
                        }

                        return $indicators;
                    }),
                TernaryFilter::make('has_lead')
                    ->label(__('filament-form-builder::form.filters.has_lead'))
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereNotNull('lead_id'),
                        false: fn (Builder $query): Builder => $query->whereNull('lead_id'),
                    ),
            ])
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
