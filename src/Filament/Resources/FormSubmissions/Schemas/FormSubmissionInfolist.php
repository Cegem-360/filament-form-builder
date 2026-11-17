<?php

declare(strict_types=1);

namespace Madbox99\FilamentFormBuilder\Filament\Resources\FormSubmissions\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
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
 * Submission page: the answers take the wide column (long messages read like
 * a message, short answers as a tidy label/value list); contact details,
 * origin and the technical details sit in a narrow side column.
 */
final class FormSubmissionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        $t = static fn (string $key): string => (string) __('filament-form-builder::form.submission_view.'.$key);

        return $schema
            ->components([
                Grid::make(['default' => 1, 'lg' => 3])
                    ->columnSpanFull()
                    ->schema([
                        Group::make([
                            Section::make(__('filament-form-builder::form.sections.submitted_data'))
                                ->icon(Heroicon::OutlinedChatBubbleBottomCenterText)
                                ->schema(fn (?FormSubmission $record): array => $record instanceof FormSubmission
                                    ? self::answerEntries($record)
                                    : []),
                        ])->columnSpan(['lg' => 2]),

                        Group::make([
                            Section::make($t('contact'))
                                ->icon(Heroicon::OutlinedUser)
                                ->schema([
                                    TextEntry::make('submitter_name')
                                        ->hiddenLabel()
                                        ->state(fn (FormSubmission $record): ?string => SubmissionDataPresenter::contact($record)['name'])
                                        ->size(TextSize::Large)
                                        ->weight(FontWeight::SemiBold)
                                        ->visible(fn (FormSubmission $record): bool => SubmissionDataPresenter::contact($record)['name'] !== null),
                                    TextEntry::make('submitter_email')
                                        ->hiddenLabel()
                                        ->state(fn (FormSubmission $record): ?string => SubmissionDataPresenter::contact($record)['email'])
                                        ->icon(Heroicon::OutlinedEnvelope)
                                        ->url(fn (?string $state): ?string => $state !== null ? 'mailto:'.$state : null)
                                        ->color('primary')
                                        ->copyable()
                                        ->placeholder($t('no_email')),
                                    TextEntry::make('submitter_phone')
                                        ->hiddenLabel()
                                        ->state(fn (FormSubmission $record): ?string => SubmissionDataPresenter::contact($record)['phone'])
                                        ->icon(Heroicon::OutlinedPhone)
                                        ->url(fn (?string $state): ?string => $state !== null ? 'tel:'.preg_replace('/[^\d+]/', '', $state) : null)
                                        ->copyable()
                                        ->placeholder($t('no_phone')),
                                    TextEntry::make('lead_id')
                                        ->label(__('filament-form-builder::form.fields.lead'))
                                        ->inlineLabel()
                                        ->formatStateUsing(fn (int|string $state): string => $t('lead_number_short').$state)
                                        ->url(fn (FormSubmission $record): ?string => SubmissionLinks::leadUrl($record->lead_id))
                                        ->color(fn (FormSubmission $record): ?string => SubmissionLinks::leadUrl($record->lead_id) !== null ? 'primary' : null)
                                        ->placeholder('—'),
                                ]),

                            Section::make($t('origin'))
                                ->icon(Heroicon::OutlinedGlobeAlt)
                                ->visible(fn (?FormSubmission $record): bool => $record instanceof FormSubmission && SubmissionDataPresenter::origin($record) !== null)
                                ->schema(fn (?FormSubmission $record): array => $record instanceof FormSubmission
                                    ? self::originEntries($record)
                                    : []),

                            Section::make($t('technical'))
                                ->icon(Heroicon::OutlinedCog6Tooth)
                                ->collapsible()
                                ->collapsed()
                                ->schema([
                                    TextEntry::make('id')->label('ID')->inlineLabel(),
                                    TextEntry::make('created_at')
                                        ->label(__('filament-form-builder::form.fields.submitted_at'))
                                        ->inlineLabel()
                                        ->dateTime(),
                                    TextEntry::make('ip_address')
                                        ->label(__('filament-form-builder::form.fields.ip_address'))
                                        ->inlineLabel()
                                        ->placeholder('—'),
                                    TextEntry::make('user_agent')
                                        ->label(__('filament-form-builder::form.fields.user_agent'))
                                        ->placeholder('—')
                                        ->size(TextSize::ExtraSmall)
                                        ->color('gray'),
                                ]),
                        ])->columnSpan(['lg' => 1]),
                    ]),
            ]);
    }

    /**
     * Long answers (textareas) read like a message, full width and with their
     * line breaks; the rest is a label/value list.
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
                $entry->badge()->inlineLabel();
            } elseif ($row['type'] === FormFieldBlueprint::TYPE_TEXTAREA && is_string($row['value'])) {
                $entry->formatStateUsing(fn (string $state): HtmlString => new HtmlString(nl2br(e($state))))
                    ->html()
                    ->size(TextSize::Medium)
                    ->extraAttributes(['class' => 'ffb-submission-message']);
            } else {
                $entry->inlineLabel()->weight(FontWeight::Medium);
            }

            $entries[] = $entry;
        }

        if ($entries === []) {
            $entries[] = TextEntry::make('no_answers')
                ->hiddenLabel()
                ->state(__('filament-form-builder::form.submission_view.no_answers'))
                ->color('gray');
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
                ->color($origin['page_url'] !== null ? 'primary' : null)
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
