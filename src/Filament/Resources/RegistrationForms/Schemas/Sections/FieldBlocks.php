<?php

declare(strict_types=1);

namespace Madbox99\FilamentFormBuilder\Filament\Resources\RegistrationForms\Schemas\Sections;

use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Support\Icons\Heroicon;
use Madbox99\FilamentFormBuilder\Support\FormFieldBlueprint;

final class FieldBlocks
{
    /**
     * @return list<Block>
     */
    public static function all(): array
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

    private static function block(string $type, Heroicon $icon): Block
    {
        return Block::make($type)
            ->label(function (?array $state) use ($type): string {
                $fieldLabel = isset($state['label']) && is_string($state['label']) ? trim($state['label']) : '';

                return $fieldLabel !== ''
                    ? $fieldLabel
                    : __('filament-form-builder::form.field_types.'.$type);
            })
            ->icon($icon);
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
}
