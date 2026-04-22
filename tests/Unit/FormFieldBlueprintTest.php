<?php

declare(strict_types=1);

use Madbox99\FilamentFormBuilder\Models\RegistrationForm;
use Madbox99\FilamentFormBuilder\Support\FormFieldBlueprint;

function makeForm(array $fields): RegistrationForm
{
    $form = new RegistrationForm;
    $form->setRawAttributes([
        'id' => 1,
        'slug' => 'x',
        'fields' => json_encode($fields),
    ]);

    return $form;
}

it('skips blocks with unknown type', function (): void {
    $form = makeForm([
        ['type' => 'unknown', 'data' => ['name' => 'x', 'label' => 'X']],
        ['type' => FormFieldBlueprint::TYPE_TEXT, 'data' => ['name' => 'y', 'label' => 'Y']],
    ]);

    $blueprints = FormFieldBlueprint::fromForm($form);

    expect($blueprints)->toHaveCount(1)
        ->and($blueprints[0]->key)->toBe('y');
});

it('deduplicates keys by dropping later duplicates', function (): void {
    $form = makeForm([
        ['type' => FormFieldBlueprint::TYPE_TEXT, 'data' => ['name' => 'same', 'label' => 'A']],
        ['type' => FormFieldBlueprint::TYPE_EMAIL, 'data' => ['name' => 'same', 'label' => 'B']],
    ]);

    $blueprints = FormFieldBlueprint::fromForm($form);

    expect($blueprints)->toHaveCount(1)
        ->and($blueprints[0]->type)->toBe(FormFieldBlueprint::TYPE_TEXT);
});

it('generates a fallback key when name is missing', function (): void {
    $form = makeForm([
        ['type' => FormFieldBlueprint::TYPE_TEXT, 'data' => ['label' => '']],
    ]);

    $blueprints = FormFieldBlueprint::fromForm($form);

    expect($blueprints)->toHaveCount(1)
        ->and($blueprints[0]->key)->toBe('field_1');
});

it('includes an `in` rule for select options', function (): void {
    $form = makeForm([
        [
            'type' => FormFieldBlueprint::TYPE_SELECT,
            'data' => [
                'name' => 'color',
                'label' => 'Color',
                'required' => true,
                'options' => [
                    ['label' => 'Red', 'value' => 'red'],
                    ['label' => 'Blue', 'value' => 'blue'],
                ],
            ],
        ],
    ]);

    $rules = $form ? FormFieldBlueprint::fromForm($form)[0]->validationRules() : [];

    expect($rules)->toContain('in:red,blue');
});

it('includes min and max for number fields', function (): void {
    $form = makeForm([
        [
            'type' => FormFieldBlueprint::TYPE_NUMBER,
            'data' => [
                'name' => 'age',
                'label' => 'Age',
                'min' => 18,
                'max' => 99,
            ],
        ],
    ]);

    $rules = FormFieldBlueprint::fromForm($form)[0]->validationRules();

    expect($rules)->toContain('min:18')
        ->and($rules)->toContain('max:99');
});

it('rejects empty phone characters', function (): void {
    $form = makeForm([
        [
            'type' => FormFieldBlueprint::TYPE_PHONE,
            'data' => ['name' => 'p', 'label' => 'P', 'required' => true],
        ],
    ]);

    $rules = FormFieldBlueprint::fromForm($form)[0]->validationRules();

    expect(implode('|', $rules))->toContain('regex:');
});

it('requires accepted for required checkbox', function (): void {
    $form = makeForm([
        [
            'type' => FormFieldBlueprint::TYPE_CHECKBOX,
            'data' => ['name' => 'c', 'label' => 'C', 'required' => true],
        ],
    ]);

    $rules = FormFieldBlueprint::fromForm($form)[0]->validationRules();

    expect($rules)->toContain('accepted');
});
