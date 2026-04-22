{{-- Plain CSS shipped with the plugin. No external CDN. Every rule is
     prefixed with `.ffb-scope` so the styles never affect the host page. --}}
<style>
    .ffb-scope { box-sizing: border-box; color: #0f172a; font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; font-size: 15px; line-height: 1.5; width: 100%; max-width: 36rem; margin: 0 auto; }
    .ffb-scope *, .ffb-scope *::before, .ffb-scope *::after { box-sizing: inherit; }
    .ffb-scope .ffb-card { background: #ffffff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 2rem; box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04); }
    .ffb-scope .ffb-card--success { text-align: center; }
    .ffb-scope .ffb-header { margin-bottom: 1.5rem; }
    .ffb-scope .ffb-title { font-size: 1.5rem; font-weight: 600; margin: 0 0 0.5rem; color: #0f172a; }
    .ffb-scope .ffb-description { margin: 0; color: #475569; }
    .ffb-scope .ffb-form { display: flex; flex-direction: column; gap: 1.25rem; }
    .ffb-scope .ffb-field { display: flex; flex-direction: column; gap: 0.375rem; }
    .ffb-scope .ffb-label { font-size: 0.875rem; font-weight: 500; color: #334155; }
    .ffb-scope .ffb-required { color: #dc2626; margin-left: 0.125rem; }
    .ffb-scope .ffb-input { display: block; width: 100%; padding: 0.5rem 0.75rem; font-size: inherit; font-family: inherit; line-height: 1.4; color: #0f172a; background-color: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04); transition: border-color 120ms ease, box-shadow 120ms ease; }
    .ffb-scope .ffb-input:focus { outline: none; border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.18); }
    .ffb-scope .ffb-input:disabled { background: #f1f5f9; cursor: not-allowed; }
    .ffb-scope .ffb-textarea { min-height: 6rem; resize: vertical; }
    .ffb-scope .ffb-checkbox-row { display: inline-flex; align-items: flex-start; gap: 0.5rem; cursor: pointer; user-select: none; }
    .ffb-scope .ffb-checkbox { margin-top: 0.2rem; width: 1rem; height: 1rem; accent-color: #2563eb; }
    .ffb-scope .ffb-checkbox-label { font-size: 0.875rem; color: #334155; }
    .ffb-scope .ffb-submit { display: inline-flex; align-items: center; justify-content: center; width: 100%; padding: 0.6rem 1rem; font-size: 0.95rem; font-weight: 600; color: #ffffff; background: #2563eb; border: none; border-radius: 8px; cursor: pointer; transition: background 120ms ease, transform 60ms ease; }
    .ffb-scope .ffb-submit:hover { background: #1d4ed8; }
    .ffb-scope .ffb-submit:active { transform: translateY(1px); }
    .ffb-scope .ffb-submit:disabled { opacity: 0.6; cursor: not-allowed; background: #64748b; }
    .ffb-scope .ffb-error { margin: 0.25rem 0 0; color: #dc2626; font-size: 0.8rem; }
    .ffb-scope .ffb-alert { padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1rem; font-size: 0.875rem; }
    .ffb-scope .ffb-alert--error { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
    .ffb-scope .ffb-icon-circle { margin: 0 auto 1rem; display: inline-flex; width: 3rem; height: 3rem; align-items: center; justify-content: center; border-radius: 9999px; background: #dcfce7; color: #16a34a; }
    .ffb-scope .ffb-icon-circle svg { width: 1.5rem; height: 1.5rem; }
    .ffb-scope .ffb-heading { margin: 0 0 0.5rem; font-size: 1.25rem; font-weight: 600; color: #0f172a; }
    .ffb-scope .ffb-text { margin: 0; color: #475569; }
    .ffb-scope .ffb-honeypot { position: absolute !important; left: -9999px !important; top: auto !important; width: 1px !important; height: 1px !important; overflow: hidden !important; }
</style>
