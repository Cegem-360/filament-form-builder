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
    public const int MAX_UNMAPPED_LENGTH = 1000;

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

        return new MappedInboundPayload($mapped, array_map(self::unmappedValue(...), $unmapped));
    }

    /**
     * Number of leaf values in a payload; every list item counts, so a huge
     * list cannot slip past the field limit as a single key.
     */
    public static function countValues(array $payload): int
    {
        $count = 0;

        foreach ($payload as $value) {
            $count += is_array($value) ? max(1, self::countValues($value)) : 1;
        }

        return $count;
    }

    /**
     * Unmapped values are stored as plain, bounded scalars: lists become
     * comma-separated text, long strings are truncated.
     */
    private static function unmappedValue(mixed $value): string|int|float|bool|null
    {
        if (is_array($value)) {
            $value = array_is_list($value) && array_filter($value, is_array(...)) === []
                ? implode(', ', array_map(strval(...), $value))
                : (string) json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        if (is_string($value)) {
            return mb_substr($value, 0, self::MAX_UNMAPPED_LENGTH);
        }

        return is_scalar($value) || $value === null ? $value : null;
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
