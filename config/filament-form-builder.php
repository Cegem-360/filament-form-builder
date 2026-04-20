<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Tenant Model
    |--------------------------------------------------------------------------
    |
    | The Eloquent model representing the "owner" of a form. Set to `null` to
    | disable tenant scoping entirely (single-tenant installation).
    |
    */
    'tenant_model' => null,

    /*
    |--------------------------------------------------------------------------
    | Tenant Foreign Key
    |--------------------------------------------------------------------------
    |
    | The column name on the registration_forms / form_submissions tables that
    | references the tenant. Defaults to `team_id` for zero-migration upgrades.
    |
    */
    'tenant_foreign_key' => 'team_id',

    /*
    |--------------------------------------------------------------------------
    | Route Configuration
    |--------------------------------------------------------------------------
    |
    | Public form routes. `/forms/{slug}` renders a full-page form; `/embed/forms/{slug}`
    | serves an iframe-friendly version; `/forms/embed.js` serves the widget loader.
    |
    */
    'routes' => [
        'enabled' => true,
        'middleware' => ['web'],
        'show_path' => 'forms/{slug}',
        'embed_path' => 'embed/forms/{slug}',
        'widget_script_path' => 'forms/embed.js',
        'throttle' => [
            'show' => '60,1',
            'embed' => '60,1',
            'script' => '120,1',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Submission rate limiting
    |--------------------------------------------------------------------------
    |
    | Per-IP limit for public form submissions (attempts per decay window).
    |
    */
    'submission_rate_limit' => [
        'attempts' => 5,
        'decay_seconds' => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | Filament Integration
    |--------------------------------------------------------------------------
    */
    'filament' => [
        'scoped_to_tenant' => true,
        'navigation_group' => 'Forms',
        'register_forms_resource' => true,
        'register_submissions_resource' => true,
    ],

];
