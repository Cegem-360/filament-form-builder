<?php

declare(strict_types=1);

namespace Madbox99\FilamentFormBuilder\Filament\Resources\FormSubmissions\Pages;

use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Madbox99\FilamentFormBuilder\Filament\Resources\FormSubmissions\FormSubmissionResource;
use Madbox99\FilamentFormBuilder\Models\FormSubmission;
use Madbox99\FilamentFormBuilder\Models\RegistrationForm;
use Madbox99\FilamentFormBuilder\Support\SubmissionCsvExport;
use Override;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListFormSubmissions extends ListRecords
{
    protected static string $resource = FormSubmissionResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportCsv')
                ->label(__('filament-form-builder::form.export.action'))
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->action(fn (): StreamedResponse => $this->exportCsv()),
        ];
    }

    /**
     * Exports what the table currently shows: the active filters and search
     * apply. One column per field only when the form filter picks a form.
     */
    public function exportCsv(): StreamedResponse
    {
        /** @var Builder<FormSubmission> $query */
        $query = $this->getFilteredTableQuery();

        $formId = $this->tableFilters['registration_form_id']['value'] ?? null;
        $form = filled($formId) ? RegistrationForm::query()->find($formId) : null;

        return SubmissionCsvExport::download($query, $form);
    }
}
