<?php

declare(strict_types=1);

namespace Madbox99\FilamentFormBuilder\Filament\Resources\FormSubmissions\Pages;

use Filament\Resources\Pages\ListRecords;
use Madbox99\FilamentFormBuilder\Filament\Resources\FormSubmissions\FormSubmissionResource;
use Override;

class ListFormSubmissions extends ListRecords
{
    protected static string $resource = FormSubmissionResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [];
    }
}
