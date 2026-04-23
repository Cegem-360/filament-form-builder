<?php

declare(strict_types=1);

namespace Madbox99\FilamentFormBuilder\Filament\Resources\RegistrationForms\Schemas;

use Filament\Schemas\Components\Group;
use Filament\Schemas\Schema;
use Madbox99\FilamentFormBuilder\Filament\Resources\RegistrationForms\Schemas\Sections\CustomCssSection;
use Madbox99\FilamentFormBuilder\Filament\Resources\RegistrationForms\Schemas\Sections\DesignSection;
use Madbox99\FilamentFormBuilder\Filament\Resources\RegistrationForms\Schemas\Sections\DetailsSection;
use Madbox99\FilamentFormBuilder\Filament\Resources\RegistrationForms\Schemas\Sections\FieldsSection;
use Madbox99\FilamentFormBuilder\Filament\Resources\RegistrationForms\Schemas\Sections\SubmissionActionsSection;
use Madbox99\FilamentFormBuilder\Filament\Resources\RegistrationForms\Schemas\Sections\SubmissionSettingsSection;

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
                        DetailsSection::make(),
                        DesignSection::make(),
                        CustomCssSection::make(),
                        SubmissionSettingsSection::make(),
                        SubmissionActionsSection::make(),
                    ]),
                FieldsSection::make(),
            ]);
    }
}
