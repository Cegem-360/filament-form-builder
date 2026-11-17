<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Madbox99\FilamentFormBuilder\Models\FormSubmission;
use Madbox99\FilamentFormBuilder\Models\RegistrationForm;
use Madbox99\FilamentFormBuilder\Support\FormFieldBlueprint;
use Madbox99\FilamentFormBuilder\Support\SubmissionCsvExport;
use Madbox99\FilamentFormBuilder\Support\SubmissionExportRow;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app()->setLocale('en');
});

function exportForm(): RegistrationForm
{
    return RegistrationForm::factory()->create(['name' => 'Contact', 'fields' => [
        ['type' => FormFieldBlueprint::TYPE_TEXT, 'data' => ['name' => 'name', 'label' => 'Name']],
        ['type' => FormFieldBlueprint::TYPE_CHECKBOX_LIST, 'data' => ['name' => 'topics', 'label' => 'Topics', 'options' => [
            ['label' => 'Office', 'value' => 'office'], ['label' => 'Grant', 'value' => 'grant'],
        ]]],
        ['type' => FormFieldBlueprint::TYPE_CHECKBOX, 'data' => ['name' => 'consent', 'label' => 'Consent']],
    ]]);
}

it('uses the form fields as columns in field order', function (): void {
    expect(SubmissionExportRow::columns(exportForm()))->toBe([
        'name' => 'Name',
        'topics' => 'Topics',
        'consent' => 'Consent',
    ]);
});

it('formats a submission as one cell per field', function (): void {
    $submission = FormSubmission::factory()->for(exportForm())->create([
        'data' => ['topics' => ['office', 'grant'], 'consent' => true, 'utm' => 'wp'],
    ]);

    expect(SubmissionExportRow::values($submission))->toBe([
        'name' => '',
        'topics' => 'Office, Grant',
        'consent' => 'Yes',
        'utm' => 'wp',
    ]);
});

it('streams a CSV with one column per field when a form is chosen', function (): void {
    $form = exportForm();
    FormSubmission::factory()->for($form)->create(['data' => ['name' => 'Anna', 'topics' => ['grant'], 'consent' => false]]);

    $csv = SubmissionCsvExport::contents($form->submissions()->getQuery(), $form);
    $lines = explode("\n", trim(substr($csv, 3)));

    expect(substr($csv, 0, 3))->toBe("\xEF\xBB\xBF")
        ->and(str_getcsv($lines[0], ';'))->toBe(['Form', 'Submitted At', 'Lead', 'Name', 'Topics', 'Consent'])
        ->and(array_slice(str_getcsv($lines[1], ';'), 3))->toBe(['Anna', 'Grant', 'No']);
});

it('streams the answers as one text column when no form is chosen', function (): void {
    $form = exportForm();
    FormSubmission::factory()->for($form)->create(['data' => ['name' => 'Anna']]);

    $lines = explode("\n", trim(substr(SubmissionCsvExport::contents(FormSubmission::query(), null), 3)));

    expect(str_getcsv($lines[0], ';'))->toBe(['Form', 'Submitted At', 'Lead', 'Answers'])
        ->and(str_getcsv($lines[1], ';')[3])->toBe('Name: Anna | Consent: No');
});
