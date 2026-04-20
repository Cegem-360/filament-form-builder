<?php

declare(strict_types=1);

namespace Madbox99\FilamentFormBuilder\Filament\Resources\FormSubmissions\Pages;

use Filament\Resources\Pages\ViewRecord;
use Madbox99\FilamentFormBuilder\Filament\Resources\FormSubmissions\FormSubmissionResource;
use Override;

class ViewFormSubmission extends ViewRecord
{
    protected static string $resource = FormSubmissionResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [];
    }
}
