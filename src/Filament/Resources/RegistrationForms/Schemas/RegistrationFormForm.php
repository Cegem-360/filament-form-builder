<?php

declare(strict_types=1);

namespace Madbox99\FilamentFormBuilder\Filament\Resources\RegistrationForms\Schemas;

use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\CodeEditor;
use Filament\Forms\Components\CodeEditor\Enums\Language;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Madbox99\FilamentFormBuilder\Support\CssSanitizer;
use Madbox99\FilamentFormBuilder\Support\FormFieldBlueprint;
use Madbox99\FilamentFormBuilder\ValueObjects\DesignTokens;
use Madbox99\FilamentFormBuilder\ValueObjects\SubmissionActions;

final class RegistrationFormForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Group::make()
                    ->columnSpan(2)
                    ->schema([
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
                                    ->columns(2),

                                Section::make(__('filament-form-builder::form.sections.design'))
                                    ->description(__('filament-form-builder::form.descriptions.design'))
                                    ->schema([
                                        ColorPicker::make('design_tokens.primary_color')
                                            ->label(__('filament-form-builder::form.fields.design_primary_color'))
                                            ->helperText(__('filament-form-builder::form.helpers.design_primary_color'))
                                            ->default(DesignTokens::DEFAULT_PRIMARY_COLOR)
                                            ->required(),
                                        Select::make('design_tokens.radius')
                                            ->label(__('filament-form-builder::form.fields.design_radius'))
                                            ->helperText(__('filament-form-builder::form.helpers.design_radius'))
                                            ->options([
                                                DesignTokens::RADIUS_NONE => __('filament-form-builder::form.fields.radius_none'),
                                                DesignTokens::RADIUS_SM => __('filament-form-builder::form.fields.radius_sm'),
                                                DesignTokens::RADIUS_MD => __('filament-form-builder::form.fields.radius_md'),
                                                DesignTokens::RADIUS_LG => __('filament-form-builder::form.fields.radius_lg'),
                                            ])
                                            ->default(DesignTokens::RADIUS_MD)
                                            ->selectablePlaceholder(false)
                                            ->required(),
                                        Select::make('design_tokens.card_treatment')
                                            ->label(__('filament-form-builder::form.fields.design_card_treatment'))
                                            ->helperText(__('filament-form-builder::form.helpers.design_card_treatment'))
                                            ->options([
                                                DesignTokens::CARD_FLAT => __('filament-form-builder::form.fields.card_flat'),
                                                DesignTokens::CARD_BORDERED => __('filament-form-builder::form.fields.card_bordered'),
                                                DesignTokens::CARD_SHADOW => __('filament-form-builder::form.fields.card_shadow'),
                                            ])
                                            ->default(DesignTokens::CARD_FLAT)
                                            ->selectablePlaceholder(false)
                                            ->required(),
                                        Select::make('design_tokens.input_background')
                                            ->label(__('filament-form-builder::form.fields.design_input_background'))
                                            ->helperText(__('filament-form-builder::form.helpers.design_input_background'))
                                            ->options([
                                                DesignTokens::INPUT_BG_GRAY => __('filament-form-builder::form.fields.input_bg_gray'),
                                                DesignTokens::INPUT_BG_WHITE => __('filament-form-builder::form.fields.input_bg_white'),
                                            ])
                                            ->default(DesignTokens::INPUT_BG_GRAY)
                                            ->selectablePlaceholder(false)
                                            ->required(),
                                        Select::make('design_tokens.submit_alignment')
                                            ->label(__('filament-form-builder::form.fields.design_submit_alignment'))
                                            ->helperText(__('filament-form-builder::form.helpers.design_submit_alignment'))
                                            ->options([
                                                DesignTokens::SUBMIT_LEFT => __('filament-form-builder::form.fields.submit_left'),
                                                DesignTokens::SUBMIT_CENTER => __('filament-form-builder::form.fields.submit_center'),
                                                DesignTokens::SUBMIT_FULL => __('filament-form-builder::form.fields.submit_full'),
                                            ])
                                            ->default(DesignTokens::SUBMIT_LEFT)
                                            ->selectablePlaceholder(false)
                                            ->required(),
                                        Select::make('design_tokens.max_width')
                                            ->label(__('filament-form-builder::form.fields.design_max_width'))
                                            ->helperText(__('filament-form-builder::form.helpers.design_max_width'))
                                            ->options([
                                                DesignTokens::WIDTH_NARROW => __('filament-form-builder::form.fields.max_width_narrow'),
                                                DesignTokens::WIDTH_DEFAULT => __('filament-form-builder::form.fields.max_width_default'),
                                                DesignTokens::WIDTH_WIDE => __('filament-form-builder::form.fields.max_width_wide'),
                                            ])
                                            ->default(DesignTokens::WIDTH_DEFAULT)
                                            ->selectablePlaceholder(false)
                                            ->required(),
                                    ])
                                    ->columns(2),

                                Section::make(__('filament-form-builder::form.sections.custom_css'))
                                    ->description(__('filament-form-builder::form.descriptions.custom_css'))
                                    ->schema([
                                        CodeEditor::make('custom_css')
                                            ->label(__('filament-form-builder::form.fields.custom_css'))
                                            ->language(Language::Css)
                                            ->helperText(__('filament-form-builder::form.helpers.custom_css'))
                                            ->dehydrateStateUsing(fn (?string $state): ?string => self::sanitiseCss($state))
                                            ->columnSpanFull(),
                                    ])
                                    ->collapsed()
                                    ->visible((bool) config('filament-form-builder.custom_css.enabled', true)),

                                Section::make(__('filament-form-builder::form.sections.submission_settings'))
                                    ->schema([
                                        Textarea::make('thank_you_message')
                                            ->label(__('filament-form-builder::form.fields.thank_you_message'))
                                            ->maxLength(65535),
                                        TextInput::make('redirect_url')
                                            ->label(__('filament-form-builder::form.fields.redirect_url'))
                                            ->url()
                                            ->maxLength(2048)
                                            ->helperText(__('filament-form-builder::form.helpers.redirect_url'))
                                            ->rule('regex:#^https?://#i'),
                                    ])
                                    ->columns(2),

                                Section::make(__('filament-form-builder::form.sections.submission_actions'))
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
                                    ->columns(2),
                            ]),

                        Section::make(__('filament-form-builder::form.sections.form_fields'))
                            ->schema([
                                Builder::make('fields')
                                    ->label(__('filament-form-builder::form.sections.form_fields'))
                                    ->blockNumbers(false)
                                    ->blockIcons()
                                    ->blocks(self::fieldBlocks())
                                    ->columnSpanFull()
                                    ->collapsible()
                                    ->required(),
                            ]),
            ]);
    }

    /**
     * @return list<Block>
     */
    private static function fieldBlocks(): array
    {
        return [
            self::block(FormFieldBlueprint::TYPE_TEXT, Heroicon::Bars3BottomLeft)
                ->schema([
                    ...self::commonFields(),
                    self::widthField(),
                    TextInput::make('placeholder')->label(__('filament-form-builder::form.fields.placeholder'))->maxLength(255),
                    TextInput::make('max_length')->label(__('filament-form-builder::form.fields.max_length'))->numeric()->minValue(1)->maxValue(65535),
                ]),

            self::block(FormFieldBlueprint::TYPE_EMAIL, Heroicon::Envelope)
                ->schema([
                    ...self::commonFields(),
                    self::widthField(),
                    TextInput::make('placeholder')->label(__('filament-form-builder::form.fields.placeholder'))->maxLength(255),
                ]),

            self::block(FormFieldBlueprint::TYPE_PHONE, Heroicon::Phone)
                ->schema([
                    ...self::commonFields(),
                    self::widthField(),
                    TextInput::make('placeholder')->label(__('filament-form-builder::form.fields.placeholder'))->maxLength(255),
                ]),

            self::block(FormFieldBlueprint::TYPE_NUMBER, Heroicon::Hashtag)
                ->schema([
                    ...self::commonFields(),
                    self::widthField(),
                    TextInput::make('placeholder')->label(__('filament-form-builder::form.fields.placeholder'))->maxLength(255),
                    TextInput::make('min')->label(__('filament-form-builder::form.fields.min'))->numeric(),
                    TextInput::make('max')->label(__('filament-form-builder::form.fields.max'))->numeric(),
                ]),

            self::block(FormFieldBlueprint::TYPE_TEXTAREA, Heroicon::Bars3)
                ->schema([
                    ...self::commonFields(),
                    TextInput::make('placeholder')->label(__('filament-form-builder::form.fields.placeholder'))->maxLength(255),
                    TextInput::make('max_length')->label(__('filament-form-builder::form.fields.max_length'))->numeric()->minValue(1)->maxValue(65535)->default(5000),
                ]),

            self::block(FormFieldBlueprint::TYPE_SELECT, Heroicon::ListBullet)
                ->schema([
                    ...self::commonFields(),
                    self::widthField(),
                    Repeater::make('options')
                        ->label(__('filament-form-builder::form.fields.options'))
                        ->schema([
                            TextInput::make('label')->label(__('filament-form-builder::form.fields.option_label'))->required()->maxLength(255),
                            TextInput::make('value')->label(__('filament-form-builder::form.fields.option_value'))->required()->maxLength(255)
                                ->rule('regex:/^[A-Za-z0-9._\-]+$/'),
                        ])
                        ->defaultItems(2)
                        ->columns(2)
                        ->columnSpanFull(),
                ]),

            self::block(FormFieldBlueprint::TYPE_CHECKBOX, Heroicon::CheckCircle)
                ->schema(self::commonFields()),

            self::block(FormFieldBlueprint::TYPE_DATE, Heroicon::Calendar)
                ->schema([
                    ...self::commonFields(),
                    self::widthField(),
                ]),
        ];
    }

    private static function widthField(): Select
    {
        return Select::make('width')
            ->label(__('filament-form-builder::form.fields.width'))
            ->options([
                FormFieldBlueprint::WIDTH_FULL => __('filament-form-builder::form.fields.width_full'),
                FormFieldBlueprint::WIDTH_HALF => __('filament-form-builder::form.fields.width_half'),
            ])
            ->default(FormFieldBlueprint::WIDTH_FULL)
            ->selectablePlaceholder(false)
            ->required();
    }

    private static function block(string $type, Heroicon $icon): Block
    {
        return Block::make($type)
            ->label(__('filament-form-builder::form.field_types.'.$type))
            ->icon($icon);
    }

    /**
     * @return list<TextInput|Toggle>
     */
    private static function commonFields(): array
    {
        return [
            TextInput::make('label')
                ->label(__('filament-form-builder::form.fields.field_label'))
                ->required()
                ->maxLength(255),
            TextInput::make('name')
                ->label(__('filament-form-builder::form.fields.field_key'))
                ->helperText(__('filament-form-builder::form.helpers.field_key'))
                ->required()
                ->maxLength(64)
                ->rule('regex:/^[a-zA-Z0-9_]+$/'),
            Toggle::make('required')
                ->label(__('filament-form-builder::form.fields.required'))
                ->default(false)
                ->inline(false),
        ];
    }

    private static function sanitiseCss(?string $state): ?string
    {
        if ($state === null) {
            return null;
        }

        $max = (int) config('filament-form-builder.custom_css.max_length', CssSanitizer::DEFAULT_MAX_LENGTH);
        $sanitized = CssSanitizer::sanitize($state, $max);

        return $sanitized === '' ? null : $sanitized;
    }
}
