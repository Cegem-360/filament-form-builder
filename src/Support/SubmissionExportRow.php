<?php

declare(strict_types=1);

namespace Madbox99\FilamentFormBuilder\Support;

use Madbox99\FilamentFormBuilder\Models\FormSubmission;
use Madbox99\FilamentFormBuilder\Models\RegistrationForm;

/**
 * A submission flattened to one text cell per field, for spreadsheet export.
 * Built on SubmissionDataPresenter so the cells read like the submission page.
 */
final class SubmissionExportRow
{
    /**
     * @return array<string, string> field key ⇒ field label, in form field order
     */
    public static function columns(RegistrationForm $form): array
    {
        $columns = [];

        foreach (FormFieldBlueprint::fromForm($form) as $blueprint) {
            $columns[$blueprint->key] = $blueprint->label;
        }

        return $columns;
    }

    /**
     * @return array<string, string> field key ⇒ cell; payload keys not on the form keep their raw key
     */
    public static function values(FormSubmission $submission): array
    {
        $values = [];

        foreach (SubmissionDataPresenter::rows($submission) as $row) {
            $values[$row['key']] = self::cell($row['value']);
        }

        return $values;
    }

    /**
     * All answers as one "Label: value | …" string, for exports that mix forms.
     */
    public static function summary(FormSubmission $submission): string
    {
        $parts = [];

        foreach (SubmissionDataPresenter::rows($submission) as $row) {
            $cell = self::cell($row['value']);

            if ($cell !== '') {
                $parts[] = $row['label'].': '.$cell;
            }
        }

        return implode(' | ', $parts);
    }

    /**
     * @param  string|list<string>|null  $value
     */
    private static function cell(string|array|null $value): string
    {
        return is_array($value) ? implode(', ', $value) : (string) $value;
    }
}
