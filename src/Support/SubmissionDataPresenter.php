<?php

declare(strict_types=1);

namespace Madbox99\FilamentFormBuilder\Support;

use Madbox99\FilamentFormBuilder\Models\FormSubmission;

/**
 * Turns a submission's raw `data` payload into labelled, human readable rows
 * using the field definitions of the form it was submitted to.
 *
 * @phpstan-type SubmissionRow array{key: string, label: string, type: string|null, value: string|list<string>|null, width: string}
 */
final class SubmissionDataPresenter
{
    /**
     * Rows follow the form's field order; payload keys that are not (or no
     * longer) defined on the form are appended with their raw key as label.
     *
     * @param  list<FormFieldBlueprint>|null  $blueprints  pass the form's blueprints when presenting many rows of one form
     * @return list<SubmissionRow>
     */
    public static function rows(FormSubmission $submission, ?array $blueprints = null): array
    {
        /** @var array<string, mixed> $data */
        $data = is_array($submission->data) ? $submission->data : [];

        $blueprints ??= $submission->registrationForm !== null
            ? FormFieldBlueprint::fromForm($submission->registrationForm)
            : [];

        $rows = [];
        $handledKeys = [];

        foreach ($blueprints as $blueprint) {
            $handledKeys[$blueprint->key] = true;

            $rows[] = [
                'key' => $blueprint->key,
                'label' => $blueprint->label,
                'type' => $blueprint->type,
                'value' => self::formatValue($blueprint, $data[$blueprint->key] ?? null),
                'width' => $blueprint->width,
            ];
        }

        foreach ($data as $key => $value) {
            $key = (string) $key;

            if (isset($handledKeys[$key])) {
                continue;
            }

            $rows[] = [
                'key' => $key,
                'label' => $key,
                'type' => null,
                'value' => self::formatRawValue($value),
                'width' => FormFieldBlueprint::WIDTH_FULL,
            ];
        }

        return $rows;
    }

    /**
     * @return string|list<string>|null
     */
    private static function formatValue(FormFieldBlueprint $blueprint, mixed $value): string|array|null
    {
        if ($blueprint->type === FormFieldBlueprint::TYPE_CHECKBOX) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN)
                ? __('filament-form-builder::form.values.yes')
                : __('filament-form-builder::form.values.no');
        }

        if ($blueprint->options === []) {
            return self::formatRawValue($value);
        }

        $optionLabels = [];
        foreach ($blueprint->options as $option) {
            $optionLabels[$option['value']] = $option['label'];
        }

        if (is_array($value)) {
            $labels = array_map(
                fn (mixed $item): string => $optionLabels[self::scalarString($item)] ?? self::scalarString($item),
                array_values($value),
            );

            return $labels === [] ? null : $labels;
        }

        $stringValue = self::scalarString($value);

        return $stringValue === '' ? null : ($optionLabels[$stringValue] ?? $stringValue);
    }

    /**
     * @return string|list<string>|null
     */
    private static function formatRawValue(mixed $value): string|array|null
    {
        if (is_bool($value)) {
            return $value
                ? __('filament-form-builder::form.values.yes')
                : __('filament-form-builder::form.values.no');
        }

        if (is_array($value)) {
            $items = array_values(array_filter(
                array_map(self::scalarString(...), $value),
                fn (string $item): bool => $item !== '',
            ));

            return $items === [] ? null : $items;
        }

        $stringValue = self::scalarString($value);

        return $stringValue === '' ? null : $stringValue;
    }

    private static function scalarString(mixed $value): string
    {
        if (is_scalar($value)) {
            return trim((string) $value);
        }

        if (is_array($value)) {
            return (string) json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        return '';
    }
}
