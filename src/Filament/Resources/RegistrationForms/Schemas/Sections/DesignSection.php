<?php

declare(strict_types=1);

namespace Madbox99\FilamentFormBuilder\Filament\Resources\RegistrationForms\Schemas\Sections;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Madbox99\FilamentFormBuilder\ValueObjects\DesignTokens;

final class DesignSection
{
    public static function make(): Section
    {
        return Section::make(__('filament-form-builder::form.sections.design'))
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
            ->columns(2);
    }
}
