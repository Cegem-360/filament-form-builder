<?php

declare(strict_types=1);

namespace Madbox99\FilamentFormBuilder\Filament\Resources\RegistrationForms\Schemas\Sections;

use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;
use Madbox99\FilamentFormBuilder\Filament\Pages\InboundWebhookHelp;
use Madbox99\FilamentFormBuilder\Models\RegistrationForm;
use Madbox99\FilamentFormBuilder\Support\FormFieldBlueprint;

/**
 * Lets an admin connect an external form (WordPress, Zapier…) to this form
 * through the secret inbound webhook URL, with a field mapping.
 */
final class InboundWebhookSection
{
    public static function make(): Section
    {
        return Section::make(__('filament-form-builder::form.sections.inbound'))
            ->description(__('filament-form-builder::form.descriptions.inbound'))
            ->afterHeader([
                Action::make('inboundHelp')
                    ->label(__('filament-form-builder::form.inbound_help.link'))
                    ->icon(Heroicon::OutlinedQuestionMarkCircle)
                    ->link()
                    ->url(fn (): string => InboundWebhookHelp::getUrl())
                    ->openUrlInNewTab()
                    ->visible(fn (): bool => (bool) config('filament-form-builder.filament.register_help_page', true)),
            ])
            ->collapsible()
            ->schema([
                Toggle::make('inbound_enabled')
                    ->label(__('filament-form-builder::form.fields.inbound_enabled'))
                    ->live(),
                Text::make(__('filament-form-builder::form.helpers.inbound_save_first'))
                    ->visible(fn (Get $get, ?RegistrationForm $record): bool => (bool) $get('inbound_enabled') && blank($record?->inbound_token)),
                TextInput::make('inbound_url')
                    ->label(__('filament-form-builder::form.fields.inbound_url'))
                    ->helperText(__('filament-form-builder::form.helpers.inbound_url'))
                    ->formatStateUsing(fn (?RegistrationForm $record): ?string => $record?->getInboundUrl())
                    ->dehydrated(false)
                    ->readOnly()
                    ->copyable(copyMessage: __('filament-form-builder::form.notifications.inbound_url_copied'))
                    ->extraInputAttributes(['class' => 'font-mono text-sm'])
                    ->suffixAction(
                        Action::make('regenerateInboundToken')
                            ->label(__('filament-form-builder::form.actions.regenerate_inbound_token'))
                            ->icon(Heroicon::OutlinedArrowPath)
                            ->requiresConfirmation()
                            ->modalDescription(__('filament-form-builder::form.helpers.inbound_regenerate'))
                            ->action(function (RegistrationForm $record, Set $set): void {
                                $record->regenerateInboundToken();
                                $set('inbound_url', $record->getInboundUrl());

                                Notification::make()
                                    ->title(__('filament-form-builder::form.notifications.inbound_token_regenerated'))
                                    ->success()
                                    ->send();
                            }),
                    )
                    ->visible(fn (Get $get, ?RegistrationForm $record): bool => (bool) $get('inbound_enabled') && filled($record?->inbound_token))
                    ->columnSpanFull(),
                Repeater::make('inbound_field_map')
                    ->label(__('filament-form-builder::form.fields.inbound_field_map'))
                    ->helperText(__('filament-form-builder::form.helpers.inbound_field_map'))
                    ->table([
                        TableColumn::make(__('filament-form-builder::form.fields.inbound_external'))->markAsRequired(),
                        TableColumn::make(__('filament-form-builder::form.fields.inbound_field'))->markAsRequired(),
                    ])
                    ->schema([
                        TextInput::make('external')
                            ->label(__('filament-form-builder::form.fields.inbound_external'))
                            ->placeholder('your-email')
                            ->required()
                            ->maxLength(255),
                        Select::make('field')
                            ->label(__('filament-form-builder::form.fields.inbound_field'))
                            ->options(fn (Get $get): array => self::fieldOptions($get('../../fields')))
                            ->required(),
                    ])
                    ->reorderable(false)
                    ->defaultItems(0)
                    ->addActionLabel(__('filament-form-builder::form.actions.add_inbound_mapping'))
                    ->visible(fn (Get $get): bool => (bool) $get('inbound_enabled'))
                    ->columnSpanFull(),
            ]);
    }

    /**
     * Field key → label of the form's current (possibly unsaved) fields.
     *
     * @return array<string, string>
     */
    private static function fieldOptions(mixed $fields): array
    {
        $form = new RegistrationForm(['fields' => is_array($fields) ? array_values($fields) : []]);

        $options = [];
        foreach (FormFieldBlueprint::fromForm($form) as $blueprint) {
            $options[$blueprint->key] = "{$blueprint->label} ({$blueprint->key})";
        }

        return $options;
    }
}
