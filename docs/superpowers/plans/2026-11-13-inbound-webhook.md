# Inbound Webhook Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let a registration form accept submissions from external forms (WordPress, Zapier, any HTTP client) through a per-form secret URL with field mapping, running the same pipeline as the embedded form.

**Architecture:** New columns on `registration_forms` hold the inbound switch, token and field map. A new `InboundSubmissionController` maps the payload (`InboundPayloadMapper`), validates it with the blueprint rules, and hands it to a new `SubmitRegistrationForm` action that both the controller and the Livewire form use (persist → count → dispatch `FormSubmissionProcessed`). The host app (marketinghub) then writes unmapped data into the Lead's `custom_fields`.

**Tech Stack:** Laravel 11–13, Filament 4/5, Livewire 3/4, Pest, Orchestra Testbench (package); Laravel 13 / Filament 5 / Pest 4 (host app).

**Spec:** `docs/superpowers/specs/2026-11-13-inbound-webhook-design.md` (package repo `~/Herd/filament-form-builder`)

## Global Constraints

- Package repo: `~/Herd/filament-form-builder`; host app: `~/Herd/marketinghub`. Tasks 1–7 are in the package, Task 8 in the host app.
- Route: `POST forms/{slug}/inbound/{token}`, config `routes.inbound_path`, middleware config `routes.inbound_middleware` default `['api']`, throttle config `routes.throttle.inbound` default `'30,1'`.
- Token: 64 random chars (`Str::random(64)`), compared with `hash_equals`; generated when inbound is first enabled; regenerable.
- Every rejection of the form/token (unknown slug, inactive form, inbound disabled, wrong token) → identical `404`.
- Validation failure → `422` JSON `{"message": …, "errors": {…}}`; success → `201` JSON `{"status":"ok","submission_id": int|null}`.
- Payload cap: config `inbound.max_fields` default `100` flattened keys; more → `413`.
- Unmapped data stored on the submission under `data._unmapped`.
- `FormSubmissionProcessed` new optional ctor args, appended last: `array $unmappedData = []`, `string $channel = 'form'` (`'inbound'` for webhooks).
- Translations in both `resources/lang/en/form.php` and `resources/lang/hu/form.php`.
- No honeypot on the inbound route. No new composer dependencies.
- Package release: `v0.5.0`.
- Every PHP file `declare(strict_types=1);`, run `vendor/bin/pint` before each commit.

## Review Focus

- WordPress tools send `application/x-www-form-urlencoded` with nested keys (`fields[email]`) → must map as `fields.email`. Test in Task 3 + Task 4.
- A form with inbound enabled must not leak its token through JSON export (`FormBlueprint::fromModel`) or import. Test in Task 1.
- Regenerating the token must make the old URL 404 immediately. Test in Task 4.
- A form whose `submission_actions.createSubmission` is false must still return 201 with `submission_id: null` and still dispatch the event (lead creation must work). Test in Task 4.
- A payload value that is an array for a scalar field (e.g. `email[]=a`) must fail validation with 422, not throw 500. Test in Task 4.

---

### Task 1: Inbound columns, model helpers, config

**Files:**
- Create: `database/migrations/2026_11_13_000000_add_inbound_webhook_to_registration_forms.php`
- Modify: `src/Models/RegistrationForm.php`, `config/filament-form-builder.php`
- Test: `tests/Feature/InboundModelTest.php`

**Interfaces:**
- Produces: columns `inbound_enabled` (bool, default false), `inbound_token` (string 64, nullable, unique), `inbound_field_map` (json, nullable; cast `array`, list of `{external: string, field: string}`).
- Produces on `RegistrationForm`: `ensureInboundToken(): string` (sets + saves a token if null, returns it), `regenerateInboundToken(): string` (new token, saved), `getInboundUrl(): ?string` (null when token null; `route('form-builder.form.inbound', [slug, token])`), `inboundTokenMatches(string $token): bool` (`hash_equals`, false when null).
- Produces config: `routes.inbound_path` = `'forms/{slug}/inbound/{token}'`, `routes.inbound_middleware` = `['api']`, `routes.throttle.inbound` = `'30,1'`, `inbound.max_fields` = `100`.

- [ ] **Step 1: Write failing tests** — `it('generates a 64 char token once')` (`ensureInboundToken()` twice returns the same 64-char string); `it('regenerates the token')` (new value ≠ old, old no longer matches via `inboundTokenMatches`); `it('does not match when no token is set')`; `it('casts inbound_field_map to array')`; `it('keeps inbound settings out of the JSON export')` (enable + token + map, `ExportFormAsJson::execute()` result has no key starting with `inbound_`). `getInboundUrl` is tested in Task 4 once the route exists.
- [ ] **Step 2: Run** `vendor/bin/pest tests/Feature/InboundModelTest.php` → FAIL (unknown column/method).
- [ ] **Step 3: Implement** the migration (guard with `Schema::hasTable` / `hasColumn` like `2026_04_22_120000_add_design_tokens_to_registration_forms.php`), casts (`inbound_enabled` boolean, `inbound_field_map` array), the four model methods, the config keys with doc comments.
- [ ] **Step 4: Run** the test file → PASS; run the full suite `vendor/bin/pest` → PASS.
- [ ] **Step 5: Commit** `feat(inbound): inbound webhook columns, token helpers and config`

### Task 2: Shared submission pipeline + event extension

**Files:**
- Create: `src/Actions/SubmitRegistrationForm.php`
- Modify: `src/Events/FormSubmissionProcessed.php`, `src/Livewire/PublicRegistrationForm.php:102-114`
- Test: `tests/Feature/SubmitRegistrationFormTest.php`

**Interfaces:**
- Consumes: `ProcessFormSubmission::handle(RegistrationForm, array $formData, $leadId = null): ?FormSubmission`.
- Produces: `SubmitRegistrationForm::handle(RegistrationForm $form, array $formData, array $unmappedData = [], string $channel = 'form'): ?FormSubmission` — in a DB transaction calls `ProcessFormSubmission::handle($form, $unmappedData === [] ? $formData : [...$formData, '_unmapped' => $unmappedData])` and `$form->increment('submissions_count')`, then dispatches `FormSubmissionProcessed($form, $submission, $formData, $form->submission_actions, $unmappedData, $channel)`.
- Produces: `FormSubmissionProcessed` public readonly `array $unmappedData = []`, `string $channel = 'form'`.

- [ ] **Step 1: Write failing tests** — `it('stores the submission, counts it and dispatches the event')` (`Event::fake`, assert `submissions_count` 1, event `channel === 'form'`, `formData` equal); `it('stores unmapped data under _unmapped and passes it on the event')` (channel `'inbound'`, submission `data['_unmapped'] === ['utm' => 'x']`, event `unmappedData === ['utm' => 'x']`, event `formData` has no `_unmapped`); `it('dispatches even when submissions are not stored')` (createSubmission false → returns null, event dispatched with `submission === null`).
- [ ] **Step 2: Run** → FAIL (class missing).
- [ ] **Step 3: Implement** the action and event args; replace the transaction + dispatch block in `PublicRegistrationForm::submit()` with `$submission = $submitter->handle($form, $this->normalisedFormData())` (inject `SubmitRegistrationForm $submitter` into `submit()` in place of `ProcessFormSubmission`).
- [ ] **Step 4: Run** `vendor/bin/pest` → all PASS.
- [ ] **Step 5: Commit** `refactor: shared SubmitRegistrationForm action; event carries channel and unmapped data`

### Task 3: Payload mapper

**Files:**
- Create: `src/Support/InboundPayloadMapper.php`, `src/ValueObjects/MappedInboundPayload.php`
- Test: `tests/Unit/InboundPayloadMapperTest.php`

**Interfaces:**
- Produces: `MappedInboundPayload` (final readonly) with `array $mapped` (form field key → value) and `array $unmapped` (flattened external key → value).
- Produces: `InboundPayloadMapper::map(RegistrationForm $form, array $payload): MappedInboundPayload` and `InboundPayloadMapper::flatten(array $payload): array` (public static; nested assoc arrays → dot keys; a list value (`array_is_list`) stays as the value, so checkbox lists survive).
- Rules: explicit `inbound_field_map` entry wins; otherwise a flattened key equal to a blueprint key maps to itself; map entries pointing to a field not in `FormFieldBlueprint::fromForm($form)` are ignored; everything else → unmapped. When two external keys map to the same field the first in payload order wins, the later one goes to unmapped.

- [ ] **Step 1: Write failing tests** — `it('maps explicitly mapped keys')` (`your-email` → `email`); `it('auto maps keys equal to form field keys')`; `it('flattens nested payloads')` (`['fields' => ['email' => 'a@b.hu']]` with map `fields.email`→`email`); `it('keeps list values intact')` (`['interests' => ['a','b']]`); `it('puts unknown keys into unmapped')`; `it('ignores map entries pointing to unknown fields')`; `it('keeps the first value when two keys target one field')`. Build forms with `RegistrationForm::factory()` and a `fields` blocks array containing `text` field `email` and `checkbox_list` field `interests`.
- [ ] **Step 2: Run** → FAIL.
- [ ] **Step 3: Implement.**
- [ ] **Step 4: Run** → PASS.
- [ ] **Step 5: Commit** `feat(inbound): payload mapper with flattening and field map`

### Task 4: Inbound route and controller

**Files:**
- Create: `src/Http/Controllers/InboundSubmissionController.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/InboundSubmissionTest.php`

**Interfaces:**
- Consumes: Task 1 model methods + config, Task 2 `SubmitRegistrationForm::handle(..., channel: 'inbound')`, Task 3 `InboundPayloadMapper::map()`.
- Produces: route name `form-builder.form.inbound`, registered in its own `Route::middleware(config inbound_middleware)` group before the `{slug}` catch-alls, `->middleware('throttle:'.$throttle['inbound'] ?? '30,1')`.

- [ ] **Step 1: Write failing tests** — JSON happy path → 201, `status ok`, numeric `submission_id`, `FormSubmission` row with mapped data; form-encoded nested `fields[email]` with map `fields.email` → 201; unmapped key lands in `data._unmapped`; 404 for: unknown slug, inactive form, `inbound_enabled` false, wrong token (assert all four bodies identical); after `regenerateInboundToken()` the old URL → 404 and the new one → 201; required field missing → 422 with `errors.email`; array given for a scalar field → 422; 101 flattened keys → 413; `createSubmission` false → 201 with `submission_id` null and event dispatched; 31st request in a minute → 429; event `channel === 'inbound'`; `getInboundUrl()` equals the route URL.
- [ ] **Step 2: Run** → FAIL.
- [ ] **Step 3: Implement** `__invoke(Request $request, string $slug, string $token): JsonResponse`: find form by slug (no tenant scope — route is public), 404 unless `is_active && inbound_enabled && inboundTokenMatches($token)`; payload = `$request->all()` (covers JSON and form-encoded); 413 if `count(InboundPayloadMapper::flatten($payload)) > config('filament-form-builder.inbound.max_fields', 100)`; map; validate `$mapped` with `Validator::make` using each blueprint's `validationRules()` / `arrayItemValidationRules()` (same keys as `PublicRegistrationForm::buildValidationRules()` without the `formData.` prefix) → on fail `422`; build normalised data (every blueprint key, `defaultValue()` when missing); `SubmitRegistrationForm::handle($form, $normalised, $unmapped, 'inbound')`; return 201.
- [ ] **Step 4: Run** `vendor/bin/pest` → PASS.
- [ ] **Step 5: Commit** `feat(inbound): POST forms/{slug}/inbound/{token} endpoint`

### Task 5: Admin section

**Files:**
- Create: `src/Filament/Resources/RegistrationForms/Schemas/Sections/InboundWebhookSection.php`
- Modify: `src/Filament/Resources/RegistrationForms/Schemas/RegistrationFormForm.php` (add after `SubmissionActionsSection::make()`), `resources/lang/en/form.php`, `resources/lang/hu/form.php`
- Test: none in the package (no Filament panel in testbench) — covered in Task 7.

**Interfaces:**
- Consumes: Task 1 model methods.
- Section contents: `Toggle::make('inbound_enabled')` (live; on enabling an existing record call `ensureInboundToken()`); read-only `TextInput` showing `getInboundUrl()` with copy (reuse the `ffb-copy-to-clipboard` asset like the embed modal), visible when enabled and the record exists (on create show helper text "save first"); `Action` `regenerateInboundToken` with `requiresConfirmation()`; `Repeater::make('inbound_field_map')` with `TextInput external` (required, max 255) and `Select field` (options = blueprint keys → labels of the current record, required); a `Text`/placeholder help block: `curl -X POST <url> -H 'Content-Type: application/json' -d '{"email":"a@b.hu"}'` plus one line each for CF7 (add-on "CF7 to Webhook"), WPForms / Gravity Forms (Webhooks add-on), Elementor Pro (Actions After Submit → Webhook).
- Translation keys under `sections.inbound`, `fields.inbound_enabled|inbound_url|inbound_field_map|inbound_external|inbound_field`, `actions.regenerate_inbound_token`, `helpers.inbound_*`. Hungarian wording: section title „Külső űrlap (webhook)”.
- Also on the create page: `CreateRegistrationForm` — when `inbound_enabled` is true after create, call `ensureInboundToken()` in `afterCreate()`.

- [ ] **Step 1: Implement** the section, translations and `afterCreate` hook.
- [ ] **Step 2: Run** `vendor/bin/pest` → PASS (no regressions); `vendor/bin/pint`.
- [ ] **Step 3: Commit** `feat(inbound): admin section for webhook URL, token and field mapping`

### Task 6: Admin help page „Külső űrlapok bekötése”

**Files:**
- Create: `src/Filament/Pages/InboundWebhookHelp.php`, `resources/views/pages/inbound-webhook-help.blade.php`
- Modify: `src/FilamentFormBuilderPlugin.php:42` (`$panel->pages([FormJsonSchemaHelp::class, InboundWebhookHelp::class])`), `src/Filament/Resources/RegistrationForms/Schemas/Sections/InboundWebhookSection.php` (link „Részletes útmutató” → `InboundWebhookHelp::getUrl()`), both lang files (`inbound_help.*` keys)
- Test: `tests/Feature/InboundWebhookHelpTest.php`

**Interfaces:**
- Follows `FormJsonSchemaHelp` exactly (same navigation icon style `Heroicon::QuestionMarkCircle` → use `Heroicon::ArrowDownOnSquare`, same view namespace).
- Produces: `InboundWebhookHelp::getNavigationLabel()`, `getTitle()`, `getExamplePayloadJson(): string` (pretty JSON of `['email' => 'anna@example.com', 'first_name' => 'Anna', 'utm_source' => 'wordpress']`), `getCurlExample(): string` (`curl -X POST https://your-app.test/forms/{slug}/inbound/{token} -H 'Content-Type: application/json' -d '<payload>'`), `getResponseCodes(): array<int, string>` (201, 404, 413, 422, 429 → translated meaning).
- Page sections (Filament `x-filament::section`, same markup style as `form-json-schema-help.blade.php`), Hungarian wording decided by the builder, content fixed:
  1. Mire jó — saját űrlap megtartása, a beküldések leadként érkeznek.
  2. Beállítás 3 lépésben — webhook bekapcsolása az űrlapon, cím másolása, mezőpárosítás (példa: `your-email` → `email`; azonos nevű mezők maguktól párosulnak; a párosítatlan mezők is megmaradnak).
  3. Példa kérés — JSON és form-encoded (`fields[email]=…` → `fields.email`), `getCurlExample()`.
  4. Válaszkódok táblázat — `getResponseCodes()`.
  5. Bekötés eszközönként — Contact Form 7 („CF7 to Webhook” bővítmény), WPForms és Gravity Forms (Webhooks bővítmény), Elementor Pro (Actions After Submit → Webhook), Zapier / Make (Webhooks by Zapier → POST).
  6. Biztonság — a cím titkos, újragenerálással azonnal érvénytelen a régi; percenként 30 kérés; max. 100 mező.
  7. Hibaelhárítás — 404: rossz/újragenerált cím vagy kikapcsolt webhook; 422: kötelező mező hiányzik vagy rossz párosítás; a beküldések a „Beküldések” listában látszanak.

**Images, links, descriptions (added on request):**
- **Flow diagram:** inline SVG in the view (`resources/views/pages/partials/inbound-flow.blade.php`): „Külső űrlap (WordPress, Zapier…)” → „Titkos webhook cím” → „Mezőpárosítás + ellenőrzés” → „Beküldés + lead”. Uses `currentColor`/Tailwind text classes so it works in light and dark mode.
- **Screenshots of our own admin UI:** `resources/images/inbound/01-enable.png` (webhook toggle + URL on the form edit page), `02-mapping.png` (field mapping repeater), `03-submissions.png` (an inbound submission in the Submissions list). Captured after Task 5 in the browser on `marketinghub.test` with demo data (no real customer data), max 1400 px wide, optimised PNG. Rendered as `data:` URIs via `InboundWebhookHelp::image(string $name): string` (base64 of the file in `resources/images/inbound/`, whitelist of the three names) — no publish step, consistent with the package's route-served assets.
- **Links (open in new tab, `rel="noopener"`):** official plugin / docs page per tool — Contact Form 7 webhook add-on (wordpress.org plugin page), WPForms Webhooks addon docs, Gravity Forms Webhooks add-on docs, Elementor Pro „Webhook” action docs, Zapier „Webhooks by Zapier”, Make „Webhooks” module. Every URL checked to return 200 before commit; kept in one `InboundWebhookHelp::toolGuides(): array<int, array{name: string, url: string, steps: list<string>}>` method so they are easy to update.
- **Per-tool description:** 3–5 numbered steps each (where to find the webhook setting in that tool, paste the URL, choose POST + JSON or form data, map field names), written in Hungarian and English.
- **Tests add:** `it('only serves whitelisted help images')` (`image('01-enable')` starts with `data:image/png;base64,`, unknown name throws `InvalidArgumentException`); `it('lists a guide with an https link for every tool')` (6 entries, each `url` starts with `https://`, `steps` non-empty).

- [ ] **Step 1: Write failing tests** — `it('exposes a navigation label and title via translations')`; `it('builds a valid example payload')` (`json_decode` → array with key `email`); `it('lists every response code the endpoint returns')` (keys exactly `[201, 404, 413, 422, 429]`); `it('renders the curl example with the inbound path')` (contains `/inbound/` and `Content-Type: application/json`).
- [ ] **Step 2: Run** → FAIL.
- [ ] **Step 3: Implement** page, view, flow diagram, `image()` / `toolGuides()`, plugin registration, section link, translations (en + hu).
- [ ] **Step 3b: Capture** the three screenshots in the browser (after Task 5 works on `marketinghub.test` via a path repository / local symlink of the package), save to `resources/images/inbound/`, verify every guide URL returns 200.
- [ ] **Step 4: Run** `vendor/bin/pest` → PASS; `vendor/bin/pint`.
- [ ] **Step 5: Commit** `feat(inbound): admin help page for external form integration`

### Task 7: Docs and release

**Files:**
- Modify: `README.md` (new "Receive submissions from external forms" section: URL, payload formats, mapping, 201/404/413/422/429 responses, WordPress pointers), spec status → Implemented.

- [ ] **Step 1: Write** the README section.
- [ ] **Step 2: Commit** `docs: inbound webhook`, `git push`, `git tag v0.5.0 && git push --tags` (ask the user before pushing/tagging).

### Task 8: Host app (marketinghub)

**Files:**
- Modify: `composer.lock` (via `composer update madbox-99/filament-form-builder`), `app/Listeners/CreateLeadFromFormSubmission.php`
- Test: `tests/Feature/Listeners/CreateLeadFromFormSubmissionTest.php` (create), `tests/Feature/Filament/RegistrationFormResourceTest.php` (extend)

**Interfaces:**
- Consumes: `FormSubmissionProcessed::$unmappedData` (Task 2), `RegistrationForm::getInboundUrl()` (Task 1), route `form-builder.form.inbound` (Task 4), `InboundWebhookHelp` page (Task 6).

- [ ] **Step 1: Update** the package (`composer update madbox-99/filament-form-builder` → v0.5.0), `php artisan migrate`.
- [ ] **Step 2: Write failing tests** — listener: `it('stores unmapped inbound data in the lead custom fields')` (dispatch event with `unmappedData ['utm_source' => 'wp']`, lead `custom_fields['utm_source'] === 'wp'`; existing unmapped blueprint fields still present, blueprint fields win on key clash); end-to-end: `it('creates a lead from an inbound webhook')` (form with `createLeadIfHasEmail`, inbound enabled, POST JSON to the inbound URL → 201, `Lead` with that e-mail and `team_id` of the form). Resource: `it('shows the inbound url after enabling the webhook')` (edit page, fill `inbound_enabled` true, save, record has a token, `assertSee($record->getInboundUrl())`), `it('regenerates the inbound token')` (call `regenerateInboundToken` action → token changed), `it('renders the inbound webhook help page')` (`get(InboundWebhookHelp::getUrl(tenant: $team))->assertOk()`, page is reachable for a panel user — check Shield permissions; if `HasPageShield`-style gating blocks package pages, grant it in the existing page-permission migration pattern).
- [ ] **Step 3: Run** → FAIL.
- [ ] **Step 4: Implement** in the listener: merge `$event->unmappedData` into `$customFields` before blueprint values (`[...$event->unmappedData, ...$customFields]`).
- [ ] **Step 5: Run** `php artisan test --compact --parallel` → PASS; `vendor/bin/rector process`, `vendor/bin/pint --dirty --format agent`.
- [ ] **Step 6: Commit** `feat(forms): külső űrlapok webhookon keresztül (filament-form-builder v0.5.0), a párosítatlan mezők a lead custom_fields-be` — push/deploy only after the user says so.
