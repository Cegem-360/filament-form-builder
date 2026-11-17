<?php

declare(strict_types=1);

namespace Madbox99\FilamentFormBuilder\Filament\Resources\FormSubmissions\Schemas;

use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;
use Madbox99\FilamentFormBuilder\Models\FormSubmission;
use Madbox99\FilamentFormBuilder\Support\FormFieldBlueprint;
use Madbox99\FilamentFormBuilder\Support\SubmissionDataPresenter;
use Madbox99\FilamentFormBuilder\Support\SubmissionLinks;

/**
 * Submission page, most useful first: who sent it, what they answered, where
 * it came from; the technical details are collapsed at the bottom.
 */
final class FormSubmissionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        $t = static fn (string $key): string => (string) __('filament-form-builder::form.submission_view.'.$key);

        return $schema
            ->components([
                Section::make($t('submitter'))
                    ->icon(Heroicon::OutlinedUser)
                    ->headerActions([
                        Action::make('openLead')
                            ->label($t('open_lead'))
                            ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                            ->url(fn (FormSubmission $record): ?string => SubmissionLinks::leadUrl($record->lead_id))
                            ->visible(fn (FormSubmission $record): bool => SubmissionLinks::leadUrl($record->lead_id) !== null),
                    ])
                    ->schema([
                        TextEntry::make('submitter_name')
                            ->label($t('name'))
                            ->state(fn (FormSubmission $record): ?string => SubmissionDataPresenter::contact($record)['name'])
                            ->size(TextSize::Large)
                            ->weight(FontWeight::SemiBold)
                            ->placeholder('—')
                            ->columnSpanFull(),
                        TextEntry::make('submitter_email')
                            ->label($t('email'))
                            ->state(fn (FormSubmission $record): ?string => SubmissionDataPresenter::contact($record)['email'])
                            ->icon(Heroicon::OutlinedEnvelope)
                            ->url(fn (?string $state): ?string => $state !== null ? 'mailto:'.$state : null)
                            ->copyable()
                            ->placeholder('—'),
                        TextEntry::make('submitter_phone')
                            ->label($t('phone'))
                            ->state(fn (FormSubmission $record): ?string => SubmissionDataPresenter::contact($record)['phone'])
                            ->icon(Heroicon::OutlinedPhone)
                            ->url(fn (?string $state): ?string => $state !== null ? 'tel:'.preg_replace('/[^\d+]/', '', $state) : null)
                            ->copyable()
                            ->placeholder('—'),
                        TextEntry::make('registrationForm.name')
                            ->label(__('filament-form-builder::form.fields.form'))
                            ->badge(),
                        TextEntry::make('created_at')
                            ->label(__('filament-form-builder::form.fields.submitted_at'))
                            ->dateTime()
                            ->since()
                            ->dateTimeTooltip(),
                    ])
                    ->columns(2),

                Section::make(__('filament-form-builder::form.sections.submitted_data'))
                    ->icon(Heroicon::OutlinedChatBubbleBottomCenterText)
                    ->schema(fn (?FormSubmission $record): array => $record instanceof FormSubmission
                        ? self::answerEntries($record)
                        : [])
                    ->columns(2),

                Section::make($t('origin'))
                    ->icon(Heroicon::OutlinedGlobeAlt)
                    ->visible(fn (?FormSubmission $record): bool => $record instanceof FormSubmission && SubmissionDataPresenter::origin($record) !== null)
                    ->schema(fn (?FormSubmission $record): array => $record instanceof FormSubmission
                        ? self::originEntries($record)
                        : [])
                    ->columns(2),

                Section::make($t('technical'))
                    ->icon(Heroicon::OutlinedCog6Tooth)
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        TextEntry::make('id')->label('ID'),
                        TextEntry::make('ip_address')
                            ->label(__('filament-form-builder::form.fields.ip_address'))
                            ->placeholder('—'),
                        TextEntry::make('user_agent')
                            ->label(__('filament-form-builder::form.fields.user_agent'))
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    /**
     * One labelled entry per answered field, in the form's field order; the
     * submitter's contact details are shown above, not repeated here.
     *
     * @return list<TextEntry>
     */
    private static function answerEntries(FormSubmission $record): array
    {
        $entries = [];

        foreach (SubmissionDataPresenter::answerRows($record) as $index => $row) {
            $entry = TextEntry::make('submitted_data_'.$index)
                ->label($row['label'])
                ->state($row['value'])
                ->placeholder('—');

            if (is_array($row['value'])) {
                $entry->badge();
            }

            if ($row['type'] === FormFieldBlueprint::TYPE_TEXTAREA && is_string($row['value'])) {
                $entry->formatStateUsing(fn (string $state): HtmlString => new HtmlString(nl2br(e($state))))->html();
            }

            if ($row['width'] === FormFieldBlueprint::WIDTH_FULL) {
                $entry->columnSpanFull();
            }

            $entries[] = $entry;
        }

        if ($entries === []) {
            $entries[] = TextEntry::make('no_answers')
                ->hiddenLabel()
                ->state(__('filament-form-builder::form.submission_view.no_answers'))
                ->color('gray')
                ->columnSpanFull();
        }

        return $entries;
    }

    /**
     * @return list<TextEntry>
     */
    private static function originEntries(FormSubmission $record): array
    {
        $origin = SubmissionDataPresenter::origin($record);
        $t = static fn (string $key): string => (string) __('filament-form-builder::form.submission_view.'.$key);

        if ($origin === null) {
            return [];
        }

        $entries = [
            TextEntry::make('origin_form')->label($t('origin_form'))->state($origin['form'])->placeholder('—'),
            TextEntry::make('origin_page')
                ->label($t('origin_page'))
                ->state($origin['page_title'] ?? $origin['page_url'])
                ->url($origin['page_url'])
                ->openUrlInNewTab()
                ->icon($origin['page_url'] !== null ? Heroicon::OutlinedArrowTopRightOnSquare : null)
                ->iconPosition('after')
                ->placeholder('—'),
        ];

        foreach ($origin['extra'] as $key => $value) {
            $entries[] = TextEntry::make('origin_extra_'.md5($key))->label($key)->state($value)->placeholder('—');
        }

        return $entries;
    }
}
