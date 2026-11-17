# Submission Insights Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add submission filters, a per-form export, a `rating` field type and a per-form results page to madbox-99/filament-form-builder.

**Architecture:** All analysis is computed in PHP from `form_submissions.data` through two pure support classes (`SubmissionStatistics`, `SubmissionExportRow`) that are unit-tested in the package (Testbench, no panel). Filament wiring (table filters, export action, results page) is thin and is exercised by Livewire tests in the marketinghub app, which has a panel.

**Tech Stack:** PHP 8.4, Laravel 13, Filament v5 (ChartWidget, Exporter), Pest 4, Testbench.

**Spec:** `docs/superpowers/specs/2026-11-17-submission-insights-design.md`

## Global Constraints

- No migrations, no new tables; everything derives from `form_submissions.data` and `registration_forms.fields`.
- Translations in both `resources/lang/hu/form.php` and `resources/lang/en/form.php` under `filament-form-builder::form.*`.
- Aggregation in PHP, chunked (`chunkById(500)`), never JSON SQL (must run on MySQL and SQLite).
- Rating: type key `rating`, `min` ∈ {0,1} default 1, `max` 2..10 default 10, stored as int.
- NPS only when the rating scale is exactly 0–10: promoters 9–10, passives 7–8, detractors 0–6, NPS = round(%promoters − %detractors).
- Tenancy: rely on the resources' existing tenant scoping; never query submissions without going through the scoped query passed in.
- Every PHP file `declare(strict_types=1);`, Pint clean.

## Review Focus

1. Submissions whose `data` lacks a field (form edited later) — stats count them as "no answer", not as 0.
2. Option values in `data` that no longer exist on the form — shown under their raw value, not dropped.
3. Rating sent as numeric string via inbound webhook (`"8"`) — accepted and stored as int 8.
4. A form with zero submissions — results page shows an empty state, no division by zero.
5. Export of a checkbox_list answer — one cell, option labels joined with ", ".

---

### Task 1: `rating` field type (blueprint, builder block, public form, inbound)

**Files:**
- Modify: `src/Support/FormFieldBlueprint.php` (const `TYPE_RATING = 'rating'`, add to `TYPES` and `WIDTH_ELIGIBLE_TYPES`; rules; `normalise()` for inbound)
- Modify: `src/Filament/Resources/RegistrationForms/Schemas/Sections/FieldBlocks.php` (new block, icon `Heroicon::Star`; `min` Select [0,1] default 1, `max` numeric 2..10 default 10)
- Modify: `src/Support/FormBlueprintSchema.php` (JSON schema entry for `rating`)
- Modify: `resources/views/livewire/public-registration-form.blade.php` (`@case(TYPE_RATING)`: radio group rendered as a horizontal row of numbered buttons min..max, `.ffb-rating` styles in `partials/base-styles.blade.php`)
- Modify: `src/Http/Controllers/InboundSubmissionController.php` (cast numeric-string rating to int before storing)
- Modify: both `resources/lang/*/form.php` (`field_types.rating`)
- Test: `tests/Unit/FormFieldBlueprintTest.php`, `tests/Feature/InboundSubmissionTest.php`

**Interfaces:**
- Produces: `FormFieldBlueprint::TYPE_RATING`; for rating blueprints `$min` (int, default 1) and `$max` (int, default 10) are always non-null; `FormFieldBlueprint::isNpsScale(): bool` (true iff type rating, min 0, max 10).

- [ ] **Step 1: Failing tests**
  - `rating defaults to 1..10`: block `['type'=>'rating','data'=>['name'=>'r','label'=>'R']]` → `min===1`, `max===10`, `validationRules()` contains `'integer'`, `'between:1,10'`.
  - `rating 0..10 is an NPS scale`: min 0 → `isNpsScale()` true; min 1 → false.
  - `max is clamped to 10`: data max 15 → `max===10`.
  - Inbound: form with rating field `r`, POST `{"r":"8", ...required}` → 201 and stored `data['r'] === 8`; `{"r":"11"}` → 422.
- [ ] **Step 2:** `vendor/bin/pest tests/Unit/FormFieldBlueprintTest.php tests/Feature/InboundSubmissionTest.php` → FAIL
- [ ] **Step 3:** Implement (rules: `[required|nullable, 'integer', "between:{$min},{$max}"]`).
- [ ] **Step 4:** Same command → PASS; full `vendor/bin/pest` green.
- [ ] **Step 5:** Commit `feat(fields): értékelő (rating) mezőtípus, 0–10 / 1–10 skála`

### Task 2: `SubmissionStatistics` (per-field aggregation)

**Files:**
- Create: `src/Support/SubmissionStatistics.php`
- Create: `src/ValueObjects/FieldStatistics.php`
- Test: `tests/Feature/SubmissionStatisticsTest.php`

**Interfaces:**
- Consumes: `FormFieldBlueprint::fromForm()`, `isNpsScale()` (Task 1).
- Produces:
  - `SubmissionStatistics::forForm(RegistrationForm $form, Builder $submissions): list<FieldStatistics>` — `$submissions` is an already-scoped/filtered `FormSubmission` query.
  - `FieldStatistics` (readonly): `string $key, string $label, string $type, int $answered, int $total, array<string,int> $distribution` (label ⇒ count, in option order then unknown raw values; for number/rating keyed by the numeric value ascending), `?float $average, int|float|null $min, int|float|null $max, ?array $nps` (`['promoters'=>int,'passives'=>int,'detractors'=>int,'score'=>int]` or null), `bool $isChartable` (true for select/radio/checkbox/checkbox_list/rating/number).

- [ ] **Step 1: Failing tests** (factories/plain inserts like existing feature tests):
  - radio with options a/b, submissions a,a,b,(missing) → distribution `['A'=>2,'B'=>1]`, answered 3, total 4.
  - checkbox_list answers `[a,b]`,`[a]` → `['A'=>2,'B'=>1]`, answered 2.
  - checkbox true,false,true → `['Igen'=>2,'Nem'=>1]` (translated yes/no).
  - unknown option value `z` → appears as `'z'=>1`.
  - rating 0–10 answers 10,9,8,3 → average 7.5, min 3, max 10, nps `promoters 2, passives 1, detractors 1, score 25`.
  - rating 1–10 → `nps === null`.
  - text field → `isChartable false`, answered counts non-empty strings.
  - zero submissions → every field answered 0, average null, no exception.
- [ ] **Step 2:** `vendor/bin/pest tests/Feature/SubmissionStatisticsTest.php` → FAIL
- [ ] **Step 3:** Implement with `chunkById(500)` over `$submissions->clone()`; reuse `SubmissionDataPresenter`'s option-label logic by extracting a public static `optionLabel(FormFieldBlueprint, string $value): string` there (no behaviour change; its tests stay green).
- [ ] **Step 4:** PASS + full suite green.
- [ ] **Step 5:** Commit `feat(stats): mezőnkénti beküldés-statisztika (eloszlás, átlag, NPS)`

### Task 3: Export rows + Filament exporter

**Files:**
- Create: `src/Support/SubmissionExportRow.php`
- Create: `src/Filament/Exports/FormSubmissionExporter.php`
- Modify: `src/Filament/Resources/FormSubmissions/Pages/ListFormSubmissions.php` (header `ExportAction::make()->exporter(FormSubmissionExporter::class)`)
- Modify: lang files (`export.*` labels, completed-notification body)
- Test: `tests/Feature/SubmissionExportRowTest.php`

**Interfaces:**
- Produces: `SubmissionExportRow::columns(RegistrationForm $form): array<string,string>` (key ⇒ label, form field order) and `SubmissionExportRow::values(FormSubmission $s): array<string,string>` (key ⇒ cell string; lists joined ", ", yes/no translated, missing ⇒ ""). Exporter: fixed columns `registrationForm.name`, `created_at`, `lead_id`, plus `ExportColumn::make('data.{key}')->label($label)->state(...)` for every field of the form chosen in the table's `registration_form_id` filter; when no form filter is set, a single `answers` column with `label: value | …`.

- [ ] **Step 1: Failing tests:** columns follow field order with labels; checkbox_list → `"A, B"`; checkbox → `"Igen"`; missing key → `""`; payload key not on form → included under raw key.
- [ ] **Step 2:** `vendor/bin/pest tests/Feature/SubmissionExportRowTest.php` → FAIL
- [ ] **Step 3:** Implement; exporter reads the active filter via `$this->options['registration_form_id'] ?? null` passed from the action (`->options(fn ($livewire) => ['registration_form_id' => $livewire->getTableFilterState('registration_form_id')['value'] ?? null])`).
- [ ] **Step 4:** PASS + full suite.
- [ ] **Step 5:** Commit `feat(export): beküldések exportja mezőnkénti oszlopokkal`

### Task 4: Table filters

**Files:**
- Modify: `src/Filament/Resources/FormSubmissions/Tables/FormSubmissionsTable.php` — `->filters([...])`:
  - `SelectFilter::make('registration_form_id')->relationship('registrationForm','name')->searchable()->preload()`
  - `Filter::make('created_at')` with `DatePicker from/until`, `whereDate` both bounds, indicators
  - `TernaryFilter::make('has_lead')` → `whereNotNull/whereNull('lead_id')`
- Modify: lang files (`filters.*`)
- Test: in marketinghub (Task 6).

- [ ] **Step 1:** Implement; **Step 2:** `vendor/bin/pest` green, Pint; **Step 3:** Commit `feat(submissions): szűrők (űrlap, dátum, lead)`

### Task 5: Results page + charts

**Files:**
- Create: `src/Filament/Resources/RegistrationForms/Pages/FormResults.php` (extends `Filament\Resources\Pages\Page`, `InteractsWithRecord`; route `/{record}/results`; date-range `from`/`until` public properties, `#[Url]`)
- Create: `resources/views/pages/form-results.blade.php` (per field a `x-filament::section`: answered/total, average/min/max, NPS badge; chart via `@livewire(FieldDistributionChart::class, [...], key(...))` for chartable fields; empty state when total 0)
- Create: `src/Filament/Widgets/FieldDistributionChart.php` (`ChartWidget`, type `bar`, receives `array $distribution` and `string $label`)
- Modify: `RegistrationFormResource::getPages()` add `'results' => FormResults::route('/{record}/results')`
- Modify: `RegistrationFormsTable` record action `Action::make('results')->url(...)->icon(Heroicon::ChartBar)`; `EditRegistrationForm` header action to results
- Modify: lang files (`results.*`)
- Test: in marketinghub (Task 6).

**Interfaces:** Consumes `SubmissionStatistics::forForm()` (Task 2) with `$record->submissions()->when(from)->when(until)`.

- [ ] **Step 1:** Implement; **Step 2:** `vendor/bin/pest` green, Pint; **Step 3:** Commit `feat(results): űrlap eredmények oldal mezőnkénti diagramokkal`

### Task 6: Release + marketinghub integration tests

**Files:**
- Modify: `CHANGELOG.md` (`## [0.7.0] - 2026-11-17`), tag `v0.7.0`, push main + tag (merge `feature/submission-insights` with `--no-ff`).
- marketinghub: `composer update madbox-99/filament-form-builder`; Test: `tests/Feature/Filament/FormSubmissionInsightsTest.php`:
  - list page filters by form (`filterTable('registration_form_id', $form->id)` shows only that form's submissions).
  - `has_lead` ternary filters.
  - results page renders for a form with a rating 0–10 field and shows the NPS value (`assertSee`), and renders an empty state for a form without submissions.
  - export action is visible on the list page (`assertActionVisible(ExportAction::class)`).
- [ ] Run `php artisan test --compact tests/Feature/Filament/FormSubmissionInsightsTest.php` → PASS; commit + push marketinghub.
- [ ] Update the other apps using the package (same script approach as the user-team-sync rollout), report results.
