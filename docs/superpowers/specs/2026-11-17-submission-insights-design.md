# Submission insights: filters, export, rating field, results page

Date: 2026-11-17 · Package: madbox-99/filament-form-builder · Size: project, short design (agreed in conversation)

## Why

A client (Capstone, in marketinghub) wants to analyse what their forms collect: customer satisfaction, form
results as charts, filtered lists and exports. The package is used by 19 apps, so it belongs here, not in one app.

## Goals

1. **Submission filters** on the FormSubmissions list: form, submitted date range, linked lead yes/no.
2. **Submission export** (CSV/XLSX) via Filament's exporter: one column per form field, headed by the field label,
   values formatted like `SubmissionDataPresenter` (option labels, yes/no, lists joined). Exports respect the
   active table filters. Requires the form filter (one form = one column set); without it, a generic export with
   form name + flattened `label: value` text.
3. **Rating field type** (`rating`): integer scale, configurable `min` (0 or 1, default 1) and `max`
   (default 10, up to 10). Public form renders a row of numbered buttons; stored as an int in `data`; validated
   `integer|between:min,max`. Inbound webhook accepts numeric strings.
4. **Results page** per form (a page on the RegistrationForm resource, "Eredmények"): date-range filter, then
   per field:
   - select / radio / checkbox_list / checkbox: answer distribution as a bar chart (counts + %),
   - number / rating: count, average, min, max, and a distribution chart; for a 0–10 rating also NPS
     (promoters 9–10, passives 7–8, detractors 0–6, NPS = %promoters − %detractors),
   - text-like fields: response count only.

## Non-goals

- No new database tables or migrations (everything is computed from `form_submissions.data`).
- No Word/PDF report of results (later, in the app).
- No custom-dashboard data source (later, in marketinghub).
- No filtering on individual field values in the list (results page covers analysis).

## Constraints

- Filament v5, Pest tests in the package, translations hu + en like the existing `filament-form-builder::form.*`.
- Aggregation in PHP over the form's submissions (chunked), not JSON SQL — must work on MySQL and SQLite.
- Tenancy: reuse the package's existing tenant scoping of submissions/forms.

## Approaches considered

- **In the package (chosen):** every app gets it, one implementation.
- In marketinghub only: faster for one client, but the other 18 apps would re-implement it.
- Separate analytics package: overkill for computed-from-JSON stats.

## Left to the builder

Chart widget choice (Filament ChartWidget per field vs one Blade view with charts), exact labels/icons, chunk size.
Release as v0.7.0, then update the apps that use the package.
