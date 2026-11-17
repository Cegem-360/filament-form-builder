<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Madbox99\FilamentFormBuilder\Models\FormSubmission;
use Madbox99\FilamentFormBuilder\Models\RegistrationForm;
use Madbox99\FilamentFormBuilder\Support\FormFieldBlueprint;
use Madbox99\FilamentFormBuilder\Support\SubmissionDataPresenter;
use Madbox99\FilamentFormBuilder\Support\SubmissionLinks;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app()->setLocale('en');
    SubmissionLinks::leadUrlUsing(null);
});

function summaryForm(array $fields): RegistrationForm
{
    return RegistrationForm::factory()->create(['fields' => $fields]);
}

it('finds the submitter by field type, whatever the field keys are', function (): void {
    $form = summaryForm([
        ['type' => FormFieldBlueprint::TYPE_TEXT, 'data' => ['name' => '1', 'label' => 'Név']],
        ['type' => FormFieldBlueprint::TYPE_EMAIL, 'data' => ['name' => '2', 'label' => 'Email cím']],
        ['type' => FormFieldBlueprint::TYPE_PHONE, 'data' => ['name' => '3', 'label' => 'Telefon']],
        ['type' => FormFieldBlueprint::TYPE_TEXTAREA, 'data' => ['name' => '4', 'label' => 'Üzenet']],
    ]);
    $submission = FormSubmission::factory()->for($form)->create(['data' => ['1' => 'Kovács Anna', '2' => 'anna@example.com', '3' => '+36 30 123 4567', '4' => 'Hello']]);

    expect(SubmissionDataPresenter::contact($submission))->toBe([
        'name' => 'Kovács Anna',
        'email' => 'anna@example.com',
        'phone' => '+36 30 123 4567',
    ]);
});

it('builds a name from first and last name fields', function (): void {
    app()->setLocale('hu');

    $form = summaryForm([
        ['type' => FormFieldBlueprint::TYPE_TEXT, 'data' => ['name' => 'last_name', 'label' => 'Last name']],
        ['type' => FormFieldBlueprint::TYPE_TEXT, 'data' => ['name' => 'first_name', 'label' => 'First name']],
        ['type' => FormFieldBlueprint::TYPE_TEXT, 'data' => ['name' => 'company', 'label' => 'Company']],
    ]);
    $submission = FormSubmission::factory()->for($form)->create(['data' => ['first_name' => 'Anna', 'last_name' => 'Kovács', 'company' => 'ACME']]);

    expect(SubmissionDataPresenter::contact($submission)['name'])->toBe('Kovács Anna');
});

it('leaves the name empty when no field looks like a name', function (): void {
    $form = summaryForm([
        ['type' => FormFieldBlueprint::TYPE_EMAIL, 'data' => ['name' => 'email', 'label' => 'Email']],
        ['type' => FormFieldBlueprint::TYPE_TEXT, 'data' => ['name' => 'company', 'label' => 'Company']],
    ]);
    $submission = FormSubmission::factory()->for($form)->create(['data' => ['email' => 'a@b.hu', 'company' => 'ACME']]);

    expect(SubmissionDataPresenter::contact($submission))->toBe(['name' => null, 'email' => 'a@b.hu', 'phone' => null]);
});

it('lists the answers without the contact fields and without the unmapped block', function (): void {
    $form = summaryForm([
        ['type' => FormFieldBlueprint::TYPE_TEXT, 'data' => ['name' => 'first_name', 'label' => 'Name']],
        ['type' => FormFieldBlueprint::TYPE_EMAIL, 'data' => ['name' => 'email', 'label' => 'Email']],
        ['type' => FormFieldBlueprint::TYPE_TEXT, 'data' => ['name' => 'subject', 'label' => 'Subject']],
    ]);
    $submission = FormSubmission::factory()->for($form)->create(['data' => [
        'first_name' => 'Anna', 'email' => 'a@b.hu', 'subject' => 'Offer', '_unmapped' => ['wp_form' => 'CF7'],
    ]]);

    expect(array_column(SubmissionDataPresenter::answerRows($submission), 'label'))->toBe(['Subject'])
        ->and(SubmissionDataPresenter::answerSummary($submission))->toBe('Subject: Offer');
});

it('describes where an external submission came from', function (): void {
    $submission = FormSubmission::factory()->for(summaryForm([]))->create(['data' => ['_unmapped' => [
        'wp_form' => 'Contact Form 7: ContactForm_HU',
        'wp_page_url' => 'https://example.com/kapcsolat/',
        'wp_page_title' => 'Kapcsolat',
        'utm_source' => 'google',
    ]]]);

    expect(SubmissionDataPresenter::origin($submission))->toBe([
        'form' => 'Contact Form 7: ContactForm_HU',
        'page_url' => 'https://example.com/kapcsolat/',
        'page_title' => 'Kapcsolat',
        'extra' => ['utm_source' => 'google'],
    ]);
});

it('has no origin for a submission made on the built-in form', function (): void {
    $submission = FormSubmission::factory()->for(summaryForm([]))->create(['data' => []]);

    expect(SubmissionDataPresenter::origin($submission))->toBeNull();
});

it('links a lead only when the host app tells how', function (): void {
    expect(SubmissionLinks::leadUrl(5))->toBeNull();

    SubmissionLinks::leadUrlUsing(fn (int|string $leadId): string => "/leads/{$leadId}");

    expect(SubmissionLinks::leadUrl(5))->toBe('/leads/5')
        ->and(SubmissionLinks::leadUrl(null))->toBeNull();
});
