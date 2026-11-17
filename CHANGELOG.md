# Changelog

## [0.7.2] - 2026-11-17

### Fixed
- Inbound submissions with extra (unmapped) values renumbered numeric field keys ("1", "2"… became "0", "1"…),
  so answers were stored under the wrong field. Field keys are now kept as they are.

## [0.7.1] - 2026-11-17

### Fixed
- Results: two options with the same label are no longer merged into one bar; a removed option whose value equals
  another option's label stays separate.
- Results: old rating answers outside the current scale are sorted into place.
- Results: number fields with more than 20 distinct values are grouped into 10 ranges.
- Results: the submission count no longer depends on the form having valid fields.
- Results: promoter/detractor colours in dark mode; the Results row action is only shown to users who can edit.
- Export: answers of fields removed from the form get their own columns; the separator follows the app locale
  (`;` for hu/de/fr…, `,` for en) and can be set with `filament-form-builder.export.csv_separator`; form fields
  are parsed once per export instead of once per row; works with eager-loaded, column-limited queries.
- An empty optional rating is stored as `null`.

## [0.7.0] - 2026-11-17

### Added
- `rating` field type (0–10 or 1–10 scale, stored as an integer; numbered buttons on the public form).
- Submission list filters: form, submitted date range, linked lead.
- CSV export of the (filtered) submission list; one column per field when a form is filtered. Cells that a
  spreadsheet would run as a formula are prefixed with `'`.
- Per-form **Results** page: answer distribution per field, average/min/max for numbers and ratings, NPS for
  0–10 ratings, date-range filter.

### Upgrade notes
- Run `php artisan filament:assets` (or `filament:upgrade`) so the results page styles are published.
- If you published the public form view (`resources/views/vendor/filament-form-builder`), re-publish it or add
  the `rating` case; otherwise rating fields render as plain text inputs.
- The CSV export is built in memory by Livewire: very large exports (tens of thousands of submissions) may hit
  the PHP memory limit; filter by form/date first.
