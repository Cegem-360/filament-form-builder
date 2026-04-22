<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $form->name }} @if (config('app.name')) - {{ config('app.name') }} @endif</title>

    @include('filament-form-builder::partials.base-styles')
    @livewireStyles

    <style>
        body.ffb-body { margin: 0; padding: 0; background: #f8fafc; min-height: 100vh; -webkit-font-smoothing: antialiased; }
        .ffb-page-wrapper { display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 2rem 1rem; }
    </style>
</head>
<body class="ffb-body">
    <div class="ffb-page-wrapper">
        <livewire:filament-form-builder.public-registration-form :form="$form" />
    </div>

    @livewireScripts
</body>
</html>
