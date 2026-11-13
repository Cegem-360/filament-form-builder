# Inbound webhook: accept submissions from external forms

**Status:** Draft (awaiting approval)
**Date:** 2026-11-13
**Scope:** Let a form receive submissions from forms that live outside the package (WordPress Contact Form 7 / WPForms / Gravity Forms / Elementor, Zapier / Make, any program) through a per-form secret URL, with a field mapping, so they run through the same pipeline as the embedded form (save submission → `FormSubmissionProcessed` → host app creates a Lead, sends notifications).

## Motivation

Today a client must replace their existing website form with our embedded one to get leads into the app. Many clients won't: their form is styled, wired into other plugins, or not on a website at all. They need a way to keep their form and still push submissions in. (Also supports the GINOP requirement #1 "data capture" and #7 "registration into the database".)

## Approaches considered

- **A) Per-form inbound webhook URL in this package (chosen).** Works with every form tool that can send a webhook (CF7 via a free webhook add-on; WPForms, Gravity Forms, Elementor Pro natively; Zapier/Make; any HTTP client). Lives in the package, so every project gets it.
- **B) Own WordPress plugin** forwarding submissions. Nicer for WP clients but needs A anyway and is a separate codebase to maintain. Possible follow-up.
- **C) JS snippet that captures any form submit on a page.** Zero config but fragile with AJAX forms, spam-prone, consent is hard. Rejected.

## Non-goals

- WordPress plugin (approach B).
- Matching the submission to an existing Lead (dedupe by e-mail) — separate task in the host app.
- File uploads via the webhook.
- Outbound webhooks (pushing our submissions elsewhere).

## Design

**Data (new migration on `registration_forms`):**
- `inbound_enabled` bool, default `false`
- `inbound_token` string(64), nullable, unique — random, generated when inbound is first enabled
- `inbound_field_map` json, nullable — list of `{external: string, field: string}` pairs

**Route:** `POST forms/{slug}/inbound/{token}` (path configurable as `routes.inbound_path`), `api` middleware (no CSRF/session; configurable as `routes.inbound_middleware`), throttle `routes.throttle.inbound` default `30,1`. The token is in the URL because many WordPress webhook tools cannot send custom headers.

**Controller flow (`InboundSubmissionController`):**
1. Find the form by slug; 404 unless `is_active` and `inbound_enabled` and `hash_equals(inbound_token, token)` (same 404 for every failure — no information leak).
2. Read the payload: JSON body or form-encoded/multipart. Nested arrays are flattened to dot keys (`fields.email`), so tools that nest values can still be mapped.
3. Map: an external key listed in `inbound_field_map` goes to its form field; a key that already equals a form field key maps to itself; everything else is **unmapped**.
4. Validate the mapped data with the same blueprint rules the embedded form uses (`FormFieldBlueprint::validationRules()`); failure → `422` JSON with errors.
5. Same as the Livewire submit: in a transaction `ProcessFormSubmission::handle()` + `submissions_count++`, then dispatch `FormSubmissionProcessed`.
6. Response `201 {"status":"ok","submission_id":…}` (`submission_id` null when the form doesn't store submissions).

Logic shared with `PublicRegistrationForm::submit()` (normalise → persist → dispatch) is extracted into one action so both paths stay identical.

**Event:** `FormSubmissionProcessed` gets two optional, backward-compatible constructor args: `array $unmappedData = []` and `string $channel = 'form'` (`'inbound'` for webhooks). Unmapped data is also stored on the submission (`data._unmapped`) so nothing the external form sent is lost.

**Admin (form edit page), new "External form (webhook)" section:** enable toggle; read-only webhook URL with copy button; "Regenerate token" action (confirmation, old URL stops working); Repeater for the mapping (external field name text → form field select from the form's own fields); a short help text with a `curl` example and pointers for CF7 / WPForms / Elementor. Translations: en + hu.

**Admin help page** „Külső űrlapok bekötése” (like the existing JSON schema help page): what it is for, 3-step setup, example request (JSON + form-encoded), response codes, setup per tool (CF7, WPForms, Gravity Forms, Elementor Pro, Zapier/Make), security, troubleshooting. Linked from the form's webhook section.

**Security:** secret token (64 chars, regenerable), per-IP throttle, active+enabled check, validation, payload size cap (`inbound.max_fields` default 100, values truncated as the embedded form does). No honeypot: it only works on forms we render, and webhooks are server-to-server.

**Host app (marketinghub), after a package release:**
- `CreateLeadFromFormSubmission` writes `$event->unmappedData` into the Lead's `custom_fields` (in addition to unmapped blueprint fields as today).
- `composer update madbox-99/filament-form-builder`, run the migration.

**Tests (Pest, package):** happy path JSON and form-encoded; mapping + auto-map + unmapped passthrough; nested payload flattening; 404 for wrong token / disabled inbound / inactive form; 422 on validation; throttle; token regeneration invalidates the old URL; event carries `channel` and `unmappedData`. Host app: listener puts unmapped data into `custom_fields`.

**Release:** package `v0.5.0` (new feature, migration).

## Left to the builder

Exact class names, the Filament component layout of the section, help-text wording.
