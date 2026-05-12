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

it('renders radio options with an in rule like select', function (): void {
    $form = makeForm([
        [
            'type' => FormFieldBlueprint::TYPE_RADIO,
            'data' => [
                'name' => 'plan',
                'label' => 'Plan',
                'required' => true,
                'options' => [
                    ['label' => 'Free', 'value' => 'free'],
                    ['label' => 'Pro', 'value' => 'pro'],
                ],
            ],
        ],
    ]);

    $blueprint = FormFieldBlueprint::fromForm($form)[0];

    expect($blueprint->validationRules())->toContain('in:free,pro')
        ->and($blueprint->defaultValue())->toBe('');
});

it('treats checkbox_list as an array with min:1 when required', function (): void {
    $form = makeForm([
        [
            'type' => FormFieldBlueprint::TYPE_CHECKBOX_LIST,
            'data' => [
                'name' => 'topics',
                'label' => 'Topics',
                'required' => true,
                'options' => [
                    ['label' => 'A', 'value' => 'a'],
                    ['label' => 'B', 'value' => 'b'],
                ],
            ],
        ],
    ]);

    $blueprint = FormFieldBlueprint::fromForm($form)[0];

    expect($blueprint->validationRules())->toContain('array')
        ->and($blueprint->validationRules())->toContain('min:1')
        ->and($blueprint->arrayItemValidationRules())->toContain('in:a,b')
        ->and($blueprint->defaultValue())->toBe([]);
});

it('does not add min:1 for optional checkbox_list', function (): void {
    $form = makeForm([
        [
            'type' => FormFieldBlueprint::TYPE_CHECKBOX_LIST,
            'data' => [
                'name' => 'topics',
                'label' => 'Topics',
                'options' => [
                    ['label' => 'A', 'value' => 'a'],
                ],
            ],
        ],
    ]);

    $rules = FormFieldBlueprint::fromForm($form)[0]->validationRules();

    expect($rules)->toContain('nullable')
        ->and($rules)->not->toContain('min:1');
});

it('allows half width on radio but not on checkbox_list', function (): void {
    expect(FormFieldBlueprint::supportsWidthChoice(FormFieldBlueprint::TYPE_RADIO))->toBeTrue()
        ->and(FormFieldBlueprint::supportsWidthChoice(FormFieldBlueprint::TYPE_CHECKBOX_LIST))->toBeFalse();
});
