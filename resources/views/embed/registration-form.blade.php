<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">

    <title>{{ $form->name }}</title>

    @include('filament-form-builder::partials.base-styles')
    @livewireStyles
</head>
<body class="ffb-body" data-ffb-embed>
    <div id="ffb-embed-root" class="ffb-embed-wrapper">
        <livewire:filament-form-builder.public-registration-form :form="$form" />
    </div>

    @livewireScripts

    <script>
        (function () {
            if (window.parent === window) {
                return;
            }

            var lastHeight = 0;

            function sendHeight() {
                try {
                    var height = Math.max(
                        document.body.scrollHeight,
                        document.documentElement.scrollHeight,
                        document.body.offsetHeight,
                        document.documentElement.offsetHeight
                    );

                    if (height === lastHeight) {
                        return;
                    }

                    lastHeight = height;

                    window.parent.postMessage({
                        type: 'ffb:resize',
                        height: height
                    }, '*');
                } catch (error) {
                    // parent may be cross-origin
                }
            }

            function sendRedirect(url) {
                try {
                    window.parent.postMessage({
                        type: 'ffb:redirect',
                        url: url
                    }, '*');
                } catch (error) {
                    window.location.href = url;
                }
            }

            var root = document.getElementById('ffb-embed-root') || document.body;

            if (typeof ResizeObserver !== 'undefined') {
                new ResizeObserver(sendHeight).observe(root);
            } else if (typeof MutationObserver !== 'undefined') {
                new MutationObserver(sendHeight).observe(root, {
                    childList: true,
                    subtree: true,
                    attributes: true,
                    characterData: true
                });
            }

            sendHeight();

            // Listen on both document and window so the bridge works regardless
            // of where the CustomEvent is dispatched from.
            function handleRedirectEvent(event) {
                if (event && event.detail && typeof event.detail.url === 'string') {
                    sendRedirect(event.detail.url);
                }
            }

            document.addEventListener('ffb:redirect', handleRedirectEvent, false);
            window.addEventListener('ffb:redirect', handleRedirectEvent, false);
        })();
    </script>
    <style>
        body.ffb-body { margin: 0; padding: 0; background: transparent; -webkit-font-smoothing: antialiased; }
        .ffb-embed-wrapper { padding: 1rem; }
    </style>
</body>
</html>
