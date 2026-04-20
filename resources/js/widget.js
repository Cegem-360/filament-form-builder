/**
 * Filament Form Builder — embeddable form widget.
 *
 * Usage:
 *   <div id="marketinghub-form-{slug}"></div>
 *   <script src="https://your-app.test/forms/embed.js" data-form="{slug}" async></script>
 *
 * Reads the `data-form` attribute, finds or creates a placeholder div with id
 * `marketinghub-form-{slug}`, injects an iframe pointing to /embed/forms/{slug},
 * auto-resizes via postMessage, and forwards redirect events from the iframe.
 */
(function () {
    "use strict";

    if (typeof window === "undefined" || typeof document === "undefined") {
        return;
    }

    var scripts = document.querySelectorAll("script[data-form]");
    if (!scripts || scripts.length === 0) {
        return;
    }

    var script = scripts[scripts.length - 1];
    var slug = script.getAttribute("data-form");

    if (!slug) {
        return;
    }

    var baseUrl;
    try {
        baseUrl = new URL(script.src).origin;
    } catch (error) {
        return;
    }

    var containerId = "marketinghub-form-" + slug;
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

    if (container.getAttribute("data-marketinghub-mounted") === "1") {
        return;
    }
    container.setAttribute("data-marketinghub-mounted", "1");

    var iframe = document.createElement("iframe");
    iframe.src = baseUrl + "/embed/forms/" + encodeURIComponent(slug);
    iframe.style.width = "100%";
    iframe.style.border = "0";
    iframe.style.minHeight = "400px";
    iframe.style.display = "block";
    iframe.setAttribute("frameborder", "0");
    iframe.setAttribute("scrolling", "no");
    iframe.setAttribute("allow", "clipboard-write");
    iframe.setAttribute("title", "Form");
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
                data.type === "marketinghub:resize" &&
                typeof data.height === "number" &&
                data.height > 0
            ) {
                iframe.style.height = data.height + "px";
                return;
            }

            if (
                data.type === "marketinghub:redirect" &&
                typeof data.url === "string" &&
                data.url.length > 0
            ) {
                window.location.href = data.url;
            }
        },
        false,
    );
})();
