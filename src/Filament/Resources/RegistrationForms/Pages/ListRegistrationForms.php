<?php

declare(strict_types=1);

namespace Madbox99\FilamentFormBuilder\Filament\Resources\RegistrationForms\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Madbox99\FilamentFormBuilder\Filament\Resources\RegistrationForms\RegistrationFormResource;
use Override;

class ListRegistrationForms extends ListRecords
{
    protected static string $resource = RegistrationFormResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
