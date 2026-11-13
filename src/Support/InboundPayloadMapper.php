<?php

declare(strict_types=1);

namespace Madbox99\FilamentFormBuilder\Support;

use Madbox99\FilamentFormBuilder\Models\RegistrationForm;
use Madbox99\FilamentFormBuilder\ValueObjects\MappedInboundPayload;

/**
 * Turns an external form's payload into the form's own field keys.
 *
 * Nested keys are flattened to dot notation (`fields[email]` → `fields.email`).
 * An explicit `inbound_field_map` entry wins; otherwise a key equal to a form
 * field key maps to itself. Everything else is kept as unmapped.
 */
final class InboundPayloadMapper
{
    public static function map(RegistrationForm $form, array $payload): MappedInboundPayload
    {
        $fieldKeys = array_map(
            static fn (FormFieldBlueprint $blueprint): string => $blueprint->key,
            FormFieldBlueprint::fromForm($form),
        );

        $explicitMap = [];
        foreach ((array) ($form->inbound_field_map ?? []) as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $external = is_string($entry['external'] ?? null) ? trim($entry['external']) : '';
            $field = is_string($entry['field'] ?? null) ? $entry['field'] : '';

            if ($external !== '' && in_array($field, $fieldKeys, true)) {
                $explicitMap[$external] = $field;
            }
        }

        $mapped = [];
        $unmapped = [];

        foreach (self::flatten($payload) as $key => $value) {
            $field = $explicitMap[$key] ?? (in_array($key, $fieldKeys, true) ? $key : null);

            if ($field !== null && ! array_key_exists($field, $mapped)) {
                $mapped[$field] = $value;
            } else {
                $unmapped[$key] = $value;
            }
        }

        return new MappedInboundPayload($mapped, $unmapped);
    }

    /**
     * @return array<string, mixed>
     */
    public static function flatten(array $payload, string $prefix = ''): array
    {
        $flat = [];

        foreach ($payload as $key => $value) {
            $path = $prefix === '' ? (string) $key : $prefix.'.'.$key;

            if (is_array($value) && $value !== [] && ! array_is_list($value)) {
                $flat = [...$flat, ...self::flatten($value, $path)];
            } else {
                $flat[$path] = $value;
            }
        }

        return $flat;
    }
}
