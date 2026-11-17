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
    public const string UNMAPPED_KEY = '_unmapped';

    /** Field keys that hold a whole name. */
    private const array NAME_KEYS = ['name', 'full_name', 'fullname', 'nev', 'teljes_nev', 'your_name'];

    /** Field labels (lower-case, accents removed) that ask for a whole name. */
    private const array NAME_LABELS = ['nev', 'name', 'teljes nev', 'full name', 'your name', 'neve', 'az on neve'];

    /**
     * Who sent the submission, found by field type (email, phone) and by
     * name-like keys or labels, so forms with numeric keys work too.
     *
     * @param  list<FormFieldBlueprint>|null  $blueprints
     * @return array{name: string|null, email: string|null, phone: string|null}
     */
    public static function contact(FormSubmission $submission, ?array $blueprints = null): array
    {
        $data = is_array($submission->data) ? $submission->data : [];
        $fields = self::contactFields($blueprints ?? self::blueprintsOf($submission));

        $value = static function (?string $key) use ($data): ?string {
            $string = $key !== null && is_scalar($data[$key] ?? null) ? trim((string) $data[$key]) : '';

            return $string === '' ? null : $string;
        };

        $name = $value($fields['name']);
        if ($name === null && ($fields['first_name'] !== null || $fields['last_name'] !== null)) {
            $parts = array_filter(app()->getLocale() === 'hu'
                ? [$value($fields['last_name']), $value($fields['first_name'])]
                : [$value($fields['first_name']), $value($fields['last_name'])]);
            $name = $parts === [] ? null : implode(' ', $parts);
        }

        return [
            'name' => $name,
            'email' => $value($fields['email']),
            'phone' => $value($fields['phone']),
        ];
    }

    /**
     * The answers apart from the submitter's contact details and the
     * external-form block, for showing next to the contact column/header.
     *
     * @param  list<FormFieldBlueprint>|null  $blueprints
     * @return list<SubmissionRow>
     */
    public static function answerRows(FormSubmission $submission, ?array $blueprints = null): array
    {
        $blueprints ??= self::blueprintsOf($submission);
        $skip = array_filter(self::contactFields($blueprints));
        $skip[] = self::UNMAPPED_KEY;

        return array_values(array_filter(
            self::rows($submission, $blueprints),
            static fn (array $row): bool => ! in_array($row['key'], $skip, true),
        ));
    }

    /**
     * The answered non-contact fields as one "Label: value · …" line.
     *
     * @param  list<FormFieldBlueprint>|null  $blueprints
     */
    public static function answerSummary(FormSubmission $submission, ?array $blueprints = null): string
    {
        $parts = [];

        foreach (self::answerRows($submission, $blueprints) as $row) {
            $value = is_array($row['value']) ? implode(', ', $row['value']) : (string) $row['value'];

            if ($value !== '') {
                $parts[] = $row['label'].': '.preg_replace('/\s+/u', ' ', $value);
            }
        }

        return implode(' · ', $parts);
    }

    /**
     * Where an external (webhook) submission came from: the WordPress plugin
     * sends wp_form / wp_page_url / wp_page_title; anything else is extra.
     *
     * @return array{form: string|null, page_url: string|null, page_title: string|null, extra: array<string, string>}|null
     */
    public static function origin(FormSubmission $submission): ?array
    {
        $data = is_array($submission->data) ? $submission->data : [];
        $unmapped = $data[self::UNMAPPED_KEY] ?? null;

        if (! is_array($unmapped) || $unmapped === []) {
            return null;
        }

        $string = static fn (mixed $value): ?string => is_scalar($value) && trim((string) $value) !== '' ? trim((string) $value) : null;
        $pageUrl = $string($unmapped['wp_page_url'] ?? null);

        $extra = [];
        foreach ($unmapped as $key => $value) {
            if (! in_array($key, ['wp_form', 'wp_page_url', 'wp_page_title'], true)) {
                $extra[(string) $key] = is_scalar($value) ? (string) $value : (string) json_encode($value, JSON_UNESCAPED_UNICODE);
            }
        }

        return [
            'form' => $string($unmapped['wp_form'] ?? null),
            'page_url' => $pageUrl !== null && preg_match('#^https?://#i', $pageUrl) === 1 ? $pageUrl : null,
            'page_title' => $string($unmapped['wp_page_title'] ?? null),
            'extra' => $extra,
        ];
    }

    /**
     * Keys of the fields that hold the submitter's name, e-mail and phone, in
     * form field order.
     *
     * @param  list<FormFieldBlueprint>  $blueprints
     * @return list<string>
     */
    public static function contactKeys(array $blueprints): array
    {
        $keys = array_filter(self::contactFields($blueprints));

        return array_values(array_filter(
            array_map(static fn (FormFieldBlueprint $blueprint): string => $blueprint->key, $blueprints),
            static fn (string $key): bool => in_array($key, $keys, true),
        ));
    }

    /**
     * @return list<FormFieldBlueprint>
     */
    private static function blueprintsOf(FormSubmission $submission): array
    {
        return $submission->registrationForm !== null ? FormFieldBlueprint::fromForm($submission->registrationForm) : [];
    }

    /**
     * @param  list<FormFieldBlueprint>  $blueprints
     * @return array{name: string|null, first_name: string|null, last_name: string|null, email: string|null, phone: string|null}
     */
    private static function contactFields(array $blueprints): array
    {
        $fields = ['name' => null, 'first_name' => null, 'last_name' => null, 'email' => null, 'phone' => null];

        foreach ($blueprints as $blueprint) {
            $key = strtolower($blueprint->key);
            $label = str($blueprint->label)->ascii()->lower()->trim()->rtrim(':*')->trim()->toString();

            if ($blueprint->type === FormFieldBlueprint::TYPE_EMAIL) {
                $fields['email'] ??= $blueprint->key;
            } elseif ($blueprint->type === FormFieldBlueprint::TYPE_PHONE) {
                $fields['phone'] ??= $blueprint->key;
            } elseif ($blueprint->type === FormFieldBlueprint::TYPE_TEXT) {
                if (in_array($key, ['first_name', 'firstname', 'keresztnev'], true)) {
                    $fields['first_name'] ??= $blueprint->key;
                } elseif (in_array($key, ['last_name', 'lastname', 'vezeteknev'], true)) {
                    $fields['last_name'] ??= $blueprint->key;
                } elseif (in_array($key, self::NAME_KEYS, true) || in_array($label, self::NAME_LABELS, true)) {
                    $fields['name'] ??= $blueprint->key;
                }
            }
        }

        return $fields;
    }

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
