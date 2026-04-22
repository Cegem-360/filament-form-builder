/**
 * Filament Form Builder — embeddable form widget loader.
 *
 * Usage:
 *   <div id="ffb-form-{slug}"></div>
 *   <script src="https://your-app.test/forms/embed.js" data-form="{slug}" async></script>
 *
 * Mounts each <script data-form="..."> tag independently so multiple forms
 * on the same page work. Iframes are sandboxed to the minimum rights Livewire
 * needs (forms, scripts, same-origin XHR to the plugin host).
 *
 * postMessage contract (iframe → host):
 *   { type: 'ffb:resize',   height: <px>  }
 *   { type: 'ffb:redirect', url:    <str> }
 */
(function () {
    "use strict";

    if (typeof window === "undefined" || typeof document === "undefined") {
        return;
    }

    var SANDBOX = "allow-forms allow-scripts allow-same-origin";
    var SLUG_RE = /^[A-Za-z0-9_-]{1,128}$/;

    function resolveBaseUrl(script) {
        try {
            return new URL(script.src).origin;
        } catch (error) {
            return null;
        }
    }

    function ensureContainer(slug, script) {
        var containerId = "ffb-form-" + slug;
        var container = document.getElementById(containerId);

        if (!container) {
            container = document.createElement("div");
            container.id = containerId;
            if (script.parentNode) {
                script.parentNode.insertBefore(container, script);
            } else {
                document.body.appendChild(container);
            }
        }

        return container;
    }

    function mount(script) {
        var slug = script.getAttribute("data-form");

        if (!slug || !SLUG_RE.test(slug)) {
            return;
        }

        var baseUrl = resolveBaseUrl(script);
        if (!baseUrl) {
            return;
        }

        var container = ensureContainer(slug, script);
        if (container.getAttribute("data-ffb-mounted") === "1") {
            return;
        }
        container.setAttribute("data-ffb-mounted", "1");

        var iframe = document.createElement("iframe");
        iframe.src = baseUrl + "/embed/forms/" + encodeURIComponent(slug);
        iframe.style.width = "100%";
        iframe.style.border = "0";
        iframe.style.minHeight = "400px";
        iframe.style.display = "block";
        iframe.setAttribute("frameborder", "0");
        iframe.setAttribute("scrolling", "no");
        iframe.setAttribute("title", "Form");
        iframe.setAttribute("sandbox", SANDBOX);
        iframe.setAttribute("loading", "lazy");
        iframe.setAttribute("referrerpolicy", "no-referrer-when-downgrade");
        container.appendChild(iframe);

        window.addEventListener(
            "message",
            function (event) {
                if (event.source !== iframe.contentWindow) {
                    return;
                }

                var data = event.data;
                if (!data || typeof data !== "object") {
                    return;
                }

                if (
                    data.type === "ffb:resize" &&
                    typeof data.height === "number" &&
                    isFinite(data.height) &&
                    data.height > 0 &&
                    data.height < 10000
                ) {
                    iframe.style.height = data.height + "px";
                    return;
                }

                if (
                    data.type === "ffb:redirect" &&
                    typeof data.url === "string" &&
                    data.url.length > 0 &&
                    data.url.length < 2048 &&
                    /^https?:\/\//i.test(data.url)
                ) {
                    window.location.href = data.url;
                }
            },
            false
        );
    }

    var scripts = document.querySelectorAll("script[data-form]");
    for (var i = 0; i < scripts.length; i++) {
        mount(scripts[i]);
    }
})();
