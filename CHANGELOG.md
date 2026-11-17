# Changelog

## [0.10.0] - 2026-11-17

### Changed
- Submission page: the title is the submitter (name or e-mail), the subheading the form, time and external form;
  "Reply by e-mail" and "Open lead" header actions; answers in a wide column (long text as a highlighted message,
  short answers as a label/value list), contact, origin and technical details in a side column.

## [0.9.2] - 2026-11-17

### Changed
- Results page: the submitter's name, e-mail and phone fields are no longer shown as result cards; text fields are
  listed in one compact "Text answers" section at the bottom.
- The "Average rating" card uses a non-NPS rating field (the NPS question has its own card).

### Added
- `SubmissionDataPresenter::contactKeys()`.

## [0.9.1] - 2026-11-17

### Fixed
- Results page: clicking the 7/30/90-day buttons threw a TypeError (numeric array keys became ints).

## [0.9.0] - 2026-11-17

### Changed
- Results page redesigned with Chart.js charts (Filament `ChartWidget`): quick ranges (7/30/90 days, all), KPI cards
  (submissions, NPS, average rating, completion), submissions-per-day line chart, doughnut for single-choice fields
  with up to 4 options, horizontal bars for longer lists and checkbox lists, rating histograms coloured by NPS group
  (0–10) or a red→green ramp, NPS doughnut with the score in the middle.

### Added
- `ResultCharts` (chart configurations), `SubmissionStatistics::timeline()`, `Filament\Widgets\ResultChart`.

## [0.8.0] - 2026-11-17

### Changed
- Submission list: submitted time (relative + date), **Submitter** (name, e-mail · phone, found by field type and
  name-like keys/labels, so numeric field keys work), **Answers** with field labels, form badge with the external
  form name, lead as a link. Searching looks inside the answers too. IP is hidden by default.
- Submission page: Submitter section (mailto/tel links, copyable, "Open lead" button), Answers (multi-line text
  keeps its line breaks), **Where it came from** (WordPress form, page link, extra values) instead of the raw
  `_unmapped` JSON, and collapsed Technical details.

### Added
- `SubmissionLinks::leadUrlUsing(fn (int|string $leadId): ?string => …)` — lets the host app turn the lead id into
  a link.
- `SubmissionDataPresenter::contact()`, `answerRows()`, `answerSummary()`, `origin()`.

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
