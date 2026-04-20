<?php

declare(strict_types=1);

namespace Madbox99\FilamentFormBuilder\Filament\Resources\RegistrationForms\Pages;

use Filament\Resources\Pages\CreateRecord;
use Madbox99\FilamentFormBuilder\Filament\Resources\RegistrationForms\RegistrationFormResource;

class CreateRegistrationForm extends CreateRecord
{
    protected static string $resource = RegistrationFormResource::class;
}
