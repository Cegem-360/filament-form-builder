# Changelog

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
