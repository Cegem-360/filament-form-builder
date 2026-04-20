<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $form->name }} - {{ config('app.name') }}</title>

    <script src="https://cdn.tailwindcss.com"></script>
    @livewireStyles
</head>
<body class="min-h-screen bg-gray-50 antialiased">
    <div class="flex min-h-screen items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
        <livewire:filament-form-builder.public-registration-form :form="$form" />
    </div>

    @livewireScripts
</body>
</html>
