<?php

declare(strict_types=1);

namespace Madbox99\FilamentFormBuilder\Support;

use Illuminate\Database\Eloquent\Builder;
use Madbox99\FilamentFormBuilder\Models\FormSubmission;
use Madbox99\FilamentFormBuilder\Models\RegistrationForm;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Synchronous CSV export of form submissions. Deliberately not a Filament
 * Exporter: that needs the exports/notifications tables and a queue worker in
 * every host app, which most apps using this package do not have.
 *
 * Excel-friendly: UTF-8 BOM, and the list separator Excel expects for the
 * app locale (";" for hu, "," for en). With a form chosen, every
 * field gets its own column; across forms the answers go into one column.
 */
final class SubmissionCsvExport
{
    /** Locales whose Excel uses a decimal comma and therefore ";" as list separator. */
    private const array SEMICOLON_LOCALES = ['hu', 'de', 'fr', 'it', 'es', 'pt', 'nl', 'pl', 'cs', 'sk', 'ro', 'hr', 'sl', 'ru', 'uk', 'tr', 'da', 'sv', 'nb', 'fi'];

    /**
     * @param  Builder<FormSubmission>  $submissions
     */
    public static function download(Builder $submissions, ?RegistrationForm $form): StreamedResponse
    {
        $filename = str($form?->name ?? 'submissions')->slug()->append('-'.now()->format('Y-m-d').'.csv')->toString();

        return response()->streamDownload(function () use ($submissions, $form): void {
            $handle = fopen('php://output', 'wb');
            self::write($handle, $submissions, $form);
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @param  Builder<FormSubmission>  $submissions
     */
    public static function contents(Builder $submissions, ?RegistrationForm $form): string
    {
        $handle = fopen('php://temp', 'w+b');
        self::write($handle, $submissions, $form);
        rewind($handle);
        $contents = (string) stream_get_contents($handle);
        fclose($handle);

        return $contents;
    }

    /**
     * @param  resource  $handle
     * @param  Builder<FormSubmission>  $submissions
     */
    private static function write($handle, Builder $submissions, ?RegistrationForm $form): void
    {
        fwrite($handle, "\xEF\xBB\xBF");

        $separator = self::separator();
        $blueprints = $form instanceof RegistrationForm ? FormFieldBlueprint::fromForm($form) : null;
        $columns = $form instanceof RegistrationForm ? [...SubmissionExportRow::columns($form), ...self::extraKeys($submissions, $form)] : [];

        self::putRow($handle, $separator, [
            __('filament-form-builder::form.fields.form'),
            __('filament-form-builder::form.fields.submitted_at'),
            __('filament-form-builder::form.fields.lead'),
            ...($form instanceof RegistrationForm ? array_values($columns) : [__('filament-form-builder::form.export.answers')]),
        ]);

        /** @var array<int|string, list<FormFieldBlueprint>> $blueprintsByForm */
        $blueprintsByForm = [];

        $submissions->clone()->reorder()->with('registrationForm')->chunkById(500, function ($chunk) use ($handle, $form, $columns, $blueprints, $separator, &$blueprintsByForm): void {

            foreach ($chunk as $submission) {
                $leading = [
                    (string) $submission->registrationForm?->name,
                    (string) $submission->created_at?->format('Y-m-d H:i'),
                    (string) $submission->lead_id,
                ];

                if ($form instanceof RegistrationForm) {
                    $values = SubmissionExportRow::values($submission, $blueprints);
                    $cells = array_map(static fn (string $key): string => $values[$key] ?? '', array_keys($columns));
                } else {
                    $formBlueprints = $submission->registrationForm !== null
                        ? ($blueprintsByForm[$submission->registrationForm->getKey()] ??= FormFieldBlueprint::fromForm($submission->registrationForm))
                        : [];
                    $cells = [SubmissionExportRow::summary($submission, $formBlueprints)];
                }

                self::putRow($handle, $separator, [...$leading, ...$cells]);
            }
        });
    }

    /**
     * @param  resource  $handle
     * @param  list<string>  $cells
     */
    private static function putRow($handle, string $separator, array $cells): void
    {
        fputcsv($handle, array_map(self::neutralise(...), $cells), $separator, escape: '');
    }

    private static function separator(): string
    {
        $configured = config('filament-form-builder.export.csv_separator');

        if (is_string($configured) && $configured !== '') {
            return $configured;
        }

        $language = strtolower(substr(app()->getLocale(), 0, 2));

        return in_array($language, self::SEMICOLON_LOCALES, true) ? ';' : ',';
    }

    /**
     * Answer keys stored on submissions but no longer defined on the form
     * (field removed or renamed), so the export does not silently drop them.
     *
     * @param  Builder<FormSubmission>  $submissions
     * @return array<string, string> key ⇒ key (used as its own column label)
     */
    private static function extraKeys(Builder $submissions, RegistrationForm $form): array
    {
        $known = SubmissionExportRow::columns($form);
        $extra = [];

        $submissions->clone()->reorder()->setEagerLoads([])->select(['id', 'data'])->chunkById(500, function ($chunk) use ($known, &$extra): void {
            foreach ($chunk as $submission) {
                foreach (array_keys(is_array($submission->data) ? $submission->data : []) as $key) {
                    $key = (string) $key;

                    if (! isset($known[$key]) && $key !== '_unmapped') {
                        $extra[$key] = $key;
                    }
                }
            }
        });

        return $extra;
    }

    /**
     * Spreadsheets run a cell starting with = + - @ (or a tab / CR) as a
     * formula: a submitted "=HYPERLINK(…)" becomes a live link and a phone
     * number "+36 …" gets mangled. A leading apostrophe keeps it plain text.
     */
    private static function neutralise(string $cell): string
    {
        return $cell !== '' && in_array($cell[0], ['=', '+', '-', '@', "\t", "\r"], true) ? "'".$cell : $cell;
    }
}
