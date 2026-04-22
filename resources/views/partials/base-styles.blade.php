{{-- Plain CSS shipped with the plugin. No external CDN. Every rule is
     prefixed with `.ffb-scope` so the styles never affect the host page.

     Customisable tokens are consumed via `var(--ffb-*, fallback)`. The
     fallbacks match the shipped defaults, so a form with no design_tokens
     set renders identically to a form before tokens existed. --}}
<style>
    .ffb-scope { box-sizing: border-box; color: #111827; font-family: "Inter", system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; font-size: 14px; line-height: 1.5; width: 100%; max-width: var(--ffb-max-width, 42rem); margin: 0 auto; -webkit-font-smoothing: antialiased; }
    .ffb-scope *, .ffb-scope *::before, .ffb-scope *::after { box-sizing: inherit; }

    .ffb-scope .ffb-card { background: #ffffff; border: var(--ffb-card-border, 0 solid transparent); border-radius: var(--ffb-radius, 0.5rem); box-shadow: var(--ffb-card-shadow, none); padding: 2rem 1rem; }
    .ffb-scope .ffb-card--success { text-align: center; padding: 2.5rem 1rem; }

    .ffb-scope .ffb-header { margin-bottom: 1.25rem; }
    .ffb-scope .ffb-title { font-size: 1.25rem; font-weight: 700; line-height: 1.3; letter-spacing: -0.01em; margin: 0 0 0.5rem; color: #111827; }
    .ffb-scope .ffb-description { margin: 0; color: #6b7280; font-size: 0.875rem; line-height: 1.5; }

    .ffb-scope .ffb-form { display: grid; grid-template-columns: 1fr; gap: 1.25rem; }
    .ffb-scope .ffb-field { display: flex; flex-direction: column; grid-column: 1 / -1; }
    .ffb-scope .ffb-label { display: block; font-size: 0.875rem; font-weight: 500; line-height: 1.25; color: #111827; margin-bottom: 0.5rem; }
    .ffb-scope .ffb-required { color: #e02424; margin-left: 0.125rem; }

    .ffb-scope .ffb-input { display: block; width: 100%; padding: 0.625rem 0.75rem; font-size: 0.875rem; font-family: inherit; line-height: 1.25; color: #111827; background-color: var(--ffb-input-bg, #f9fafb); border: 1px solid #d1d5db; border-radius: var(--ffb-radius, 0.5rem); appearance: none; transition: border-color 120ms ease, box-shadow 120ms ease, background-color 120ms ease; }
    .ffb-scope .ffb-input::placeholder { color: #9ca3af; }
    .ffb-scope .ffb-input:hover { border-color: #9ca3af; }
    .ffb-scope .ffb-input:focus { outline: none; border-color: var(--ffb-primary, #1c64f2); background-color: #ffffff; box-shadow: 0 0 0 3px var(--ffb-primary-ring, rgba(164, 202, 254, 0.55)); }
    .ffb-scope .ffb-input:disabled { background: #f3f4f6; color: #6b7280; cursor: not-allowed; }

    .ffb-scope .ffb-textarea { min-height: 10rem; resize: vertical; line-height: 1.5; padding: 0.75rem; }
    .ffb-scope .ffb-select { background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 20 20' fill='none' stroke='%236b7280' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 8l4 4 4-4'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 0.75rem center; background-size: 1rem 1rem; padding-right: 2.25rem; }

    .ffb-scope .ffb-checkbox-row { display: inline-flex; align-items: flex-start; gap: 0.625rem; cursor: pointer; user-select: none; }
    .ffb-scope .ffb-checkbox { margin-top: 0.125rem; width: 1rem; height: 1rem; accent-color: var(--ffb-primary, #1c64f2); border-radius: 0.25rem; cursor: pointer; }
    .ffb-scope .ffb-checkbox-label { font-size: 0.875rem; line-height: 1.25; color: #111827; }

    .ffb-scope .ffb-submit { display: inline-flex; align-items: center; justify-content: center; width: 100%; padding: 0.625rem 1.25rem; font-size: 0.875rem; font-weight: 500; color: #ffffff; background: var(--ffb-primary, #1a56db); border: 1px solid transparent; border-radius: var(--ffb-radius, 0.5rem); cursor: pointer; transition: background 120ms ease, box-shadow 120ms ease; }
    .ffb-scope .ffb-submit:hover { background: var(--ffb-primary-hover, #1e429f); }
    .ffb-scope .ffb-submit:focus { outline: none; box-shadow: 0 0 0 4px var(--ffb-primary-ring, rgba(164, 202, 254, 0.6)); }
    .ffb-scope .ffb-submit:disabled { opacity: 0.7; cursor: not-allowed; background: #76a9fa; }

    .ffb-scope .ffb-error { margin: 0.25rem 0 0; color: #e02424; font-size: 0.8125rem; line-height: 1.25; }
    .ffb-scope .ffb-field.ffb-field--has-error .ffb-input { border-color: #f05252; background-color: #fdf2f2; }
    .ffb-scope .ffb-field.ffb-field--has-error .ffb-input:focus { border-color: #e02424; box-shadow: 0 0 0 3px rgba(248, 180, 180, 0.6); }

    .ffb-scope .ffb-alert { padding: 1rem; border-radius: var(--ffb-radius, 0.5rem); margin-bottom: 1rem; font-size: 0.875rem; line-height: 1.4; }
    .ffb-scope .ffb-alert--error { background: #fdf2f2; color: #9b1c1c; }

    .ffb-scope .ffb-icon-circle { margin: 0 auto 1rem; display: inline-flex; width: 3rem; height: 3rem; align-items: center; justify-content: center; border-radius: 9999px; background: #def7ec; color: #0e9f6e; }
    .ffb-scope .ffb-icon-circle svg { width: 1.5rem; height: 1.5rem; }
    .ffb-scope .ffb-heading { margin: 0 0 0.5rem; font-size: 1.25rem; font-weight: 600; color: #111827; }
    .ffb-scope .ffb-text { margin: 0; color: #6b7280; font-size: 0.875rem; line-height: 1.5; }

    .ffb-scope .ffb-honeypot { position: absolute !important; left: -9999px !important; top: auto !important; width: 1px !important; height: 1px !important; overflow: hidden !important; }

    @media (min-width: 640px) {
        .ffb-scope .ffb-form { grid-template-columns: repeat(2, minmax(0, 1fr)); column-gap: 1.5rem; }
        .ffb-scope .ffb-field--half { grid-column: span 1; }
        .ffb-scope .ffb-submit { width: var(--ffb-submit-width, auto); justify-self: var(--ffb-submit-justify, start); align-self: flex-start; grid-column: 1 / -1; }
    }
</style>
