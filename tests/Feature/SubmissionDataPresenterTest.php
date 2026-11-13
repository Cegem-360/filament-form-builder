<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Madbox99\FilamentFormBuilder\Models\FormSubmission;
use Madbox99\FilamentFormBuilder\Models\RegistrationForm;
use Madbox99\FilamentFormBuilder\Support\FormFieldBlueprint;
use Madbox99\FilamentFormBuilder\Support\SubmissionDataPresenter;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app()->setLocale('en');
});

it('labels submitted values with the form field labels in field order', function (): void {
    $form = RegistrationForm::factory()->create([
        'fields' => [
            ['type' => FormFieldBlueprint::TYPE_TEXT, 'data' => ['name' => '1', 'label' => 'Név', 'width' => 'half']],
            ['type' => FormFieldBlueprint::TYPE_EMAIL, 'data' => ['name' => '2', 'label' => 'Email cím']],
            ['type' => FormFieldBlueprint::TYPE_TEXTAREA, 'data' => ['name' => '4', 'label' => 'Megjegyzés']],
        ],
    ]);

    $submission = FormSubmission::factory()->for($form)->create([
        'data' => ['2' => 'anna@example.com', '1' => 'Anna', '4' => ''],
    ]);

    expect(SubmissionDataPresenter::rows($submission))->toBe([
        ['key' => '1', 'label' => 'Név', 'type' => 'text_input', 'value' => 'Anna', 'width' => 'half'],
        ['key' => '2', 'label' => 'Email cím', 'type' => 'email', 'value' => 'anna@example.com', 'width' => 'full'],
        ['key' => '4', 'label' => 'Megjegyzés', 'type' => 'textarea', 'value' => null, 'width' => 'full'],
    ]);
});

it('shows option labels for choice fields and yes or no for checkboxes', function (): void {
    $options = [['label' => 'Irodatervezés', 'value' => 'iroda'], ['label' => 'Pályázat', 'value' => 'palyazat']];

    $form = RegistrationForm::factory()->create([
        'fields' => [
            ['type' => FormFieldBlueprint::TYPE_RADIO, 'data' => ['name' => 'service', 'label' => 'Szolgáltatás', 'options' => $options]],
            ['type' => FormFieldBlueprint::TYPE_CHECKBOX_LIST, 'data' => ['name' => 'services', 'label' => 'Szolgáltatások', 'options' => $options]],
            ['type' => FormFieldBlueprint::TYPE_CHECKBOX, 'data' => ['name' => 'consent', 'label' => 'Hozzájárulás']],
        ],
    ]);

    $submission = FormSubmission::factory()->for($form)->create([
        'data' => ['service' => 'palyazat', 'services' => ['iroda', 'unknown'], 'consent' => true],
    ]);

    expect(collect(SubmissionDataPresenter::rows($submission))->pluck('value', 'key')->all())->toBe([
        'service' => 'Pályázat',
        'services' => ['Irodatervezés', 'unknown'],
        'consent' => 'Yes',
    ]);
});

it('appends payload keys that are not defined on the form with their raw key', function (): void {
    $form = RegistrationForm::factory()->create([
        'fields' => [
            ['type' => FormFieldBlueprint::TYPE_EMAIL, 'data' => ['name' => 'email', 'label' => 'E-mail']],
        ],
    ]);

    $submission = FormSubmission::factory()->for($form)->create([
        'data' => ['utm_source' => 'google', 'email' => 'anna@example.com', 'tags' => ['a', 'b']],
    ]);

    expect(SubmissionDataPresenter::rows($submission))->toBe([
        ['key' => 'email', 'label' => 'E-mail', 'type' => 'email', 'value' => 'anna@example.com', 'width' => 'full'],
        ['key' => 'utm_source', 'label' => 'utm_source', 'type' => null, 'value' => 'google', 'width' => 'full'],
        ['key' => 'tags', 'label' => 'tags', 'type' => null, 'value' => ['a', 'b'], 'width' => 'full'],
    ]);
});
