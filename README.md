# Filament Form Builder

Embeddable form builder for Laravel with a [Filament v5](https://filamentphp.com/) admin panel.

Drop a `<script>` tag into any HTML page (WordPress, static site, SPA) and collect form submissions in your Filament admin.

## Features

- Filament v5 resources for forms + submissions
- Livewire-powered public form renderer (full page, iframe-friendly embed, or one-liner JS widget)
- Repeater-based field builder in the admin (text, email, phone, number, textarea, select, checkbox, date)
- Per-form submission actions (save to DB, auto-create lead, notify email addresses)
- Inbound webhook: accept submissions from external forms (WordPress, Zapier, any HTTP client) with field mapping
- `FormSubmissionProcessed` event for app-specific side effects (Lead creation, CRM push, Slack notification...)
- Pluggable multi-tenancy — any Eloquent model with a slug column works
- Route-served widget JS with ETag cache busting — no publish step

## Installation

```bash
composer require madbox-99/filament-form-builder
php artisan vendor:publish --tag=filament-form-builder-config
php artisan migrate
```

Set your tenant model in `config/filament-form-builder.php`:

```php
'tenant_model' => \App\Models\Team::class,
'tenant_foreign_key' => 'team_id',
```

Register the plugin in your Filament panel provider:

```php
use Madbox99\FilamentFormBuilder\FilamentFormBuilderPlugin;

public function panel(Panel $panel): Panel
{
    return $panel->plugins([
        FilamentFormBuilderPlugin::make(),
    ]);
}
```

## Embed a form

From the form edit page, copy one of three snippets:

**JS widget** (recommended — auto-resizes, handles redirects):
```html
<div id="ffb-form-{slug}"></div>
<script src="https://your-app.test/forms/embed.js" data-form="{slug}" async></script>
```

**Iframe**:
```html
<iframe src="https://your-app.test/embed/forms/{slug}" width="100%" height="600" frameborder="0"></iframe>
```

**Direct link**: `https://your-app.test/forms/{slug}`

## App-side integrations via event

The package dispatches `Madbox99\FilamentFormBuilder\Events\FormSubmissionProcessed`
after every successful submission. Listen to create app-specific records:

```php
use Madbox99\FilamentFormBuilder\Events\FormSubmissionProcessed;

Event::listen(FormSubmissionProcessed::class, function (FormSubmissionProcessed $event) {
    if ($event->actions->createLeadIfHasEmail && !empty($event->formData['email'])) {
        Lead::create([
            'team_id' => $event->form->team_id,
            'email' => $event->formData['email'],
            // ...
        ]);
    }

    if ($event->actions->notifyEmails !== []) {
        Notification::route('mail', $event->actions->notifyEmails)
            ->notify(new FormSubmissionReceived($event->submission));
    }
});
```

## Receive submissions from external forms (inbound webhook)

Clients can keep their own form (WordPress Contact Form 7 / WPForms / Gravity
Forms / Elementor Pro, Zapier, Make, any HTTP client) and send its submissions
to a form's secret webhook URL. They run through the same pipeline as the
embedded form: stored, counted, `FormSubmissionProcessed` dispatched.

1. On the form edit page, open **External form (webhook)**, switch on
   *Accept submissions from an external form* and save.
2. Copy the webhook URL: `POST https://your-app.test/forms/{slug}/inbound/{token}`.
   The token is secret and can be regenerated (the old URL stops working).
3. Optionally map external field names to the form's field keys
   (`your-email` → `email`). Keys equal to a field key map automatically.

Payloads may be JSON or form data. Nested keys are flattened to dot notation
(`fields[email]` → `fields.email`). Values that map to no field are kept on the
submission under `data._unmapped` and passed on the event as
`$event->unmappedData`; `$event->channel` is `'inbound'` (embedded form: `'form'`).

| Status | Meaning |
|--------|---------|
| 201 | Accepted — `{"status":"ok","submission_id":123}` (`null` when the form does not store submissions) |
| 404 | Unknown slug, inactive form, webhook switched off or wrong token (always the same response) |
| 413 | More than `inbound.max_fields` (default 100) fields |
| 422 | Validation failed (same rules as the embedded form) |
| 429 | More than `routes.throttle.inbound` (default 30/minute) requests per form and IP |

Responses are always JSON, also for form-encoded requests without an `Accept` header.
Uploaded files are ignored. Every list item counts towards `inbound.max_fields`.
Unmapped values are stored as text (lists joined with `, `), truncated to 1000
characters. Checkbox fields accept `on`/`yes`/`true`/`1` and `off`/`no`/`false`/`0`/empty.

```bash
curl -X POST 'https://your-app.test/forms/contact/inbound/<token>' \
  -H 'Content-Type: application/json' \
  -d '{"email":"anna@example.com","first_name":"Anna"}'
```

The admin panel gets a **Connect external forms** help page with screenshots and
step-by-step guides per tool (disable with `filament.register_help_page`). Its
stylesheet ships as a Filament asset, so run `php artisan filament:assets` after
updating the package.

Config keys: `routes.inbound_path`, `routes.inbound_middleware` (default `['api']`),
`routes.throttle.inbound`, `inbound.max_fields`.

## License

MIT
