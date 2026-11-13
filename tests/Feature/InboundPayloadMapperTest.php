<?php

declare(strict_types=1);

use Madbox99\FilamentFormBuilder\Models\RegistrationForm;
use Madbox99\FilamentFormBuilder\Support\FormFieldBlueprint;
use Madbox99\FilamentFormBuilder\Support\InboundPayloadMapper;

/**
 * @param  list<array{external: string, field: string}>  $map
 */
function mapperForm(array $map = []): RegistrationForm
{
    return new RegistrationForm([
        'fields' => [
            ['type' => FormFieldBlueprint::TYPE_EMAIL, 'data' => ['label' => 'Email', 'name' => 'email']],
            ['type' => FormFieldBlueprint::TYPE_TEXT, 'data' => ['label' => 'Name', 'name' => 'name']],
            [
                'type' => FormFieldBlueprint::TYPE_CHECKBOX_LIST,
                'data' => ['label' => 'Interests', 'name' => 'interests', 'options' => [['label' => 'A', 'value' => 'a'], ['label' => 'B', 'value' => 'b']]],
            ],
        ],
        'inbound_field_map' => $map,
    ]);
}

it('maps explicitly mapped keys', function (): void {
    $result = InboundPayloadMapper::map(mapperForm([['external' => 'your-email', 'field' => 'email']]), ['your-email' => 'anna@example.com']);

    expect($result->mapped)->toBe(['email' => 'anna@example.com'])
        ->and($result->unmapped)->toBe([]);
});

it('auto maps keys equal to form field keys', function (): void {
    $result = InboundPayloadMapper::map(mapperForm(), ['email' => 'anna@example.com', 'name' => 'Anna']);

    expect($result->mapped)->toBe(['email' => 'anna@example.com', 'name' => 'Anna']);
});

it('flattens nested payloads', function (): void {
    $result = InboundPayloadMapper::map(
        mapperForm([['external' => 'fields.email', 'field' => 'email']]),
        ['fields' => ['email' => 'anna@example.com', 'phone' => '123']],
    );

    expect($result->mapped)->toBe(['email' => 'anna@example.com'])
        ->and($result->unmapped)->toBe(['fields.phone' => '123']);
});

it('keeps list values intact', function (): void {
    expect(InboundPayloadMapper::flatten(['interests' => ['a', 'b'], 'x' => ['y' => ['z' => 1]]]))
        ->toBe(['interests' => ['a', 'b'], 'x.y.z' => 1]);

    $result = InboundPayloadMapper::map(mapperForm(), ['interests' => ['a', 'b']]);

    expect($result->mapped)->toBe(['interests' => ['a', 'b']]);
});

it('puts unknown keys into unmapped', function (): void {
    $result = InboundPayloadMapper::map(mapperForm(), ['email' => 'anna@example.com', 'utm_source' => 'wp']);

    expect($result->unmapped)->toBe(['utm_source' => 'wp']);
});

it('ignores map entries pointing to unknown fields', function (): void {
    $result = InboundPayloadMapper::map(mapperForm([['external' => 'your-email', 'field' => 'nope']]), ['your-email' => 'anna@example.com']);

    expect($result->mapped)->toBe([])
        ->and($result->unmapped)->toBe(['your-email' => 'anna@example.com']);
});

it('keeps the first value when two keys target one field', function (): void {
    $result = InboundPayloadMapper::map(
        mapperForm([['external' => 'your-email', 'field' => 'email']]),
        ['your-email' => 'first@example.com', 'email' => 'second@example.com'],
    );

    expect($result->mapped)->toBe(['email' => 'first@example.com'])
        ->and($result->unmapped)->toBe(['email' => 'second@example.com']);
});
