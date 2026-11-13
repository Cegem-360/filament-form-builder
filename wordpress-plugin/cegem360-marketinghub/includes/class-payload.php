<?php

/**
 * Turns the submission data of the supported form plugins into the flat
 * key → value payload the MarketingHub inbound webhook expects.
 *
 * Pure PHP (no WordPress calls) so it can be unit tested.
 */
if (! defined('ABSPATH') && ! defined('CEGEM360_MH_TESTING')) {
    exit;
}

final class Cegem360_MarketingHub_Payload
{
    /**
     * Contact Form 7: WPCF7_Submission::get_posted_data(). Internal `_wpcf7*`
     * keys are dropped; one-item lists (select, radio) become plain values.
     *
     * @param  array<string, mixed>  $posted
     * @return array<string, mixed>
     */
    public static function from_cf7(array $posted): array
    {
        $payload = [];

        foreach ($posted as $key => $value) {
            $key = (string) $key;

            if ($key === '' || $key[0] === '_') {
                continue;
            }

            $payload[$key] = self::value($value);
        }

        return $payload;
    }

    /**
     * WPForms: the $fields argument of `wpforms_process_complete`. Keys are
     * the field labels as slugs (e.g. "E-mail cím" → "e_mail_cim").
     *
     * @param  array<int|string, array<string, mixed>>  $fields
     * @return array<string, mixed>
     */
    public static function from_wpforms(array $fields): array
    {
        $payload = [];

        foreach ($fields as $id => $field) {
            $field = (array) $field;
            $key = self::slug((string) ($field['name'] ?? ''));
            $key = $key !== '' ? $key : 'field_'.($field['id'] ?? $id);
            $value = $field['value'] ?? '';

            if (in_array($field['type'] ?? '', ['checkbox', 'select'], true) && is_string($value) && strpos($value, "\n") !== false) {
                $value = array_values(array_filter(array_map('trim', explode("\n", $value)), 'strlen'));
            }

            $payload[$key] = self::value($value);
        }

        return $payload;
    }

    /**
     * Elementor Pro: $record->get('fields'). Keys are the field IDs set in
     * the form widget (e.g. "name", "email").
     *
     * @param  array<string, array<string, mixed>>  $fields
     * @return array<string, mixed>
     */
    public static function from_elementor(array $fields): array
    {
        $payload = [];

        foreach ($fields as $id => $field) {
            $field = (array) $field;
            $key = (string) ($field['id'] ?? $id);

            if ($key === '') {
                continue;
            }

            $payload[$key] = self::value($field['raw_value'] ?? ($field['value'] ?? ''));
        }

        return $payload;
    }

    /**
     * Gravity Forms: the entry and form of `gform_after_submission`. Keys are
     * the field labels as slugs; multi-input fields (name, checkboxes) are
     * joined with a space or ", ".
     *
     * @param  array<string, mixed>  $entry
     * @param  array<string, mixed>  $form
     * @return array<string, mixed>
     */
    public static function from_gravity(array $entry, array $form): array
    {
        $payload = [];

        foreach ((array) ($form['fields'] ?? []) as $field) {
            $field = (array) $field;
            $id = (string) ($field['id'] ?? '');
            $key = self::slug((string) ($field['label'] ?? ''));
            $key = $key !== '' ? $key : 'field_'.$id;

            if (is_array($field['inputs'] ?? null) && $field['inputs'] !== []) {
                $parts = [];
                foreach ($field['inputs'] as $input) {
                    $input = (array) $input;
                    $part = trim((string) ($entry[(string) ($input['id'] ?? '')] ?? ''));
                    if ($part !== '') {
                        $parts[] = $part;
                    }
                }
                $value = implode(($field['type'] ?? '') === 'name' ? ' ' : ', ', $parts);
            } else {
                $value = $entry[$id] ?? '';
            }

            if ($value === '' || $value === null) {
                continue;
            }

            $payload[$key] = self::value($value);
        }

        return $payload;
    }

    /**
     * Adds where the form was sent from; never overwrites a form field.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public static function with_meta(array $payload, string $page_url, string $page_title, string $form_label): array
    {
        $meta = [
            'wp_form' => $form_label,
            'wp_page_url' => $page_url,
            'wp_page_title' => $page_title,
        ];

        foreach ($meta as $key => $value) {
            if ($value !== '' && ! array_key_exists($key, $payload)) {
                $payload[$key] = $value;
            }
        }

        return $payload;
    }

    public static function is_valid_webhook_url(string $url): bool
    {
        return preg_match('#^https://[^/\s]+/(?:[^\s]+/)?forms/[A-Za-z0-9_\-]+/inbound/[A-Za-z0-9]{32,128}$#', trim($url)) === 1;
    }

    /**
     * "E-mail cím" → "e_mail_cim".
     */
    public static function slug(string $label): string
    {
        $map = [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ö' => 'o', 'ő' => 'o', 'ú' => 'u', 'ü' => 'u', 'ű' => 'u',
            'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ö' => 'o', 'Ő' => 'o', 'Ú' => 'u', 'Ü' => 'u', 'Ű' => 'u',
        ];
        $label = strtr($label, $map);

        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $label);
            if ($converted !== false) {
                $label = $converted;
            }
        }

        $label = strtolower($label);
        $label = (string) preg_replace('/[^a-z0-9]+/', '_', $label);

        return trim($label, '_');
    }

    /**
     * @param  mixed  $value
     * @return mixed
     */
    private static function value($value)
    {
        if (is_array($value)) {
            $value = array_values(array_filter($value, static function ($item): bool {
                return $item !== '' && $item !== null;
            }));

            return count($value) === 1 ? (string) $value[0] : array_map('strval', $value);
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return is_scalar($value) || $value === null ? (string) $value : '';
    }
}
