# Filament Form Builder

Embeddable form builder for Laravel with a [Filament v5](https://filamentphp.com/) admin panel.

Drop a `<script>` tag into any HTML page (WordPress, static site, SPA) and collect form submissions in your Filament admin.

## Features

- Filament v5 resources for forms + submissions
- Livewire-powered public form renderer (full page, iframe-friendly embed, or one-liner JS widget)
- Repeater-based field builder in the admin (text, email, phone, number, textarea, select, checkbox, date)
- Per-form submission actions (save to DB, auto-create lead, notify email addresses)
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

## License

MIT
