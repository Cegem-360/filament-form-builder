<?php

declare(strict_types=1);

namespace Madbox99\FilamentFormBuilder\Filament\Resources\FormSubmissions\Tables;

use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\Indicator;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Madbox99\FilamentFormBuilder\Models\FormSubmission;
use Madbox99\FilamentFormBuilder\Support\SubmissionDataPresenter;
use Madbox99\FilamentFormBuilder\Support\SubmissionLinks;

final class FormSubmissionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with([
                'registrationForm:id,name,fields',
            ]))
            ->columns([
                TextColumn::make('created_at')
                    ->label(__('filament-form-builder::form.fields.submitted_at'))
                    ->since()
                    ->dateTimeTooltip()
                    ->description(fn (FormSubmission $record): string => (string) $record->created_at?->translatedFormat('Y. M j. H:i'))
                    ->sortable(),
                TextColumn::make('submitter')
                    ->label(__('filament-form-builder::form.submission_view.submitter'))
                    ->state(fn (FormSubmission $record): string => self::contact($record)['name'] ?? self::contact($record)['email'] ?? '—')
                    ->weight(FontWeight::Medium)
                    ->description(fn (FormSubmission $record): ?string => self::contactLine($record))
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->where('data', 'like', '%'.str_replace(['%', '_'], ['\\%', '\\_'], $search).'%')),
                TextColumn::make('answers')
                    ->label(__('filament-form-builder::form.submission_view.answers'))
                    ->state(fn (FormSubmission $record): string => SubmissionDataPresenter::answerSummary($record))
                    ->limit(110)
                    ->tooltip(fn (FormSubmission $record): ?string => Str::length(SubmissionDataPresenter::answerSummary($record)) > 110 ? SubmissionDataPresenter::answerSummary($record) : null)
                    ->wrap()
                    ->placeholder('—'),
                TextColumn::make('registrationForm.name')
                    ->label(__('filament-form-builder::form.fields.form'))
                    ->badge()
                    ->description(fn (FormSubmission $record): ?string => SubmissionDataPresenter::origin($record)['form'] ?? null)
                    ->sortable()
                    ->searchable(),
                TextColumn::make('lead_id')
                    ->label(__('filament-form-builder::form.fields.lead'))
                    ->formatStateUsing(fn (int|string $state): string => __('filament-form-builder::form.submission_view.lead_number', ['id' => $state]))
                    ->url(fn (FormSubmission $record): ?string => SubmissionLinks::leadUrl($record->lead_id))
                    ->color(fn (FormSubmission $record): ?string => SubmissionLinks::leadUrl($record->lead_id) !== null ? 'primary' : null)
                    ->icon(fn (FormSubmission $record): ?Heroicon => SubmissionLinks::leadUrl($record->lead_id) !== null ? Heroicon::OutlinedArrowTopRightOnSquare : null)
                    ->iconPosition('after')
                    ->placeholder('—'),
                TextColumn::make('ip_address')
                    ->label(__('filament-form-builder::form.fields.ip_address'))
                    ->toggleable(isToggledHiddenByDefault: true),
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

    /**
     * @return array{name: string|null, email: string|null, phone: string|null}
     */
    private static function contact(FormSubmission $record): array
    {
        return SubmissionDataPresenter::contact($record);
    }

    private static function contactLine(FormSubmission $record): ?string
    {
        $contact = self::contact($record);
        $parts = array_filter([$contact['name'] !== null ? $contact['email'] : null, $contact['phone']]);

        return $parts === [] ? null : implode(' · ', $parts);
    }
}
