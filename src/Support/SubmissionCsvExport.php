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
 * Excel-friendly: UTF-8 BOM and ";" as separator. With a form chosen, every
 * field gets its own column; across forms the answers go into one column.
 */
final class SubmissionCsvExport
{
    private const string SEPARATOR = ';';

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

        $columns = $form instanceof RegistrationForm ? SubmissionExportRow::columns($form) : [];

        self::putRow($handle, [
            __('filament-form-builder::form.fields.form'),
            __('filament-form-builder::form.fields.submitted_at'),
            __('filament-form-builder::form.fields.lead'),
            ...($form instanceof RegistrationForm ? array_values($columns) : [__('filament-form-builder::form.export.answers')]),
        ]);

        $submissions->clone()->reorder()->with('registrationForm')->chunkById(500, function ($chunk) use ($handle, $form, $columns): void {
            foreach ($chunk as $submission) {
                $leading = [
                    (string) $submission->registrationForm?->name,
                    (string) $submission->created_at?->format('Y-m-d H:i'),
                    (string) $submission->lead_id,
                ];

                if ($form instanceof RegistrationForm) {
                    $values = SubmissionExportRow::values($submission);
                    $cells = array_map(static fn (string $key): string => $values[$key] ?? '', array_keys($columns));
                } else {
                    $cells = [SubmissionExportRow::summary($submission)];
                }

                self::putRow($handle, [...$leading, ...$cells]);
            }
        });
    }

    /**
     * @param  resource  $handle
     * @param  list<string>  $cells
     */
    private static function putRow($handle, array $cells): void
    {
        fputcsv($handle, array_map(self::neutralise(...), $cells), self::SEPARATOR, escape: '');
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
