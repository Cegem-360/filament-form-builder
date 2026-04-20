<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $form->name }}</title>

    <script src="https://cdn.tailwindcss.com"></script>
    @livewireStyles
</head>
<body class="bg-transparent antialiased" data-marketinghub-embed>
    <div class="px-4 py-6" id="marketinghub-embed-root">
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
                        type: 'marketinghub:resize',
                        height: height
                    }, '*');
                } catch (error) {
                    // parent may be cross-origin
                }
            }

            function sendRedirect(url) {
                try {
                    window.parent.postMessage({
                        type: 'marketinghub:redirect',
                        url: url
                    }, '*');
                } catch (error) {
                    window.location.href = url;
                }
            }

            var root = document.getElementById('marketinghub-embed-root') || document.body;

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

            document.addEventListener('marketinghub:redirect', function (event) {
                if (event && event.detail && typeof event.detail.url === 'string') {
                    sendRedirect(event.detail.url);
                }
            }, false);
        })();
    </script>
</body>
</html>
