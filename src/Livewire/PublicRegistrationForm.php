<?php

declare(strict_types=1);

namespace Madbox99\FilamentFormBuilder\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Js;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Madbox99\FilamentFormBuilder\Actions\ProcessFormSubmission;
use Madbox99\FilamentFormBuilder\Events\FormSubmissionProcessed;
use Madbox99\FilamentFormBuilder\Models\FormSubmission;
use Madbox99\FilamentFormBuilder\Models\RegistrationForm;
use Madbox99\FilamentFormBuilder\Support\CssSanitizer;
use Madbox99\FilamentFormBuilder\Support\FormFieldBlueprint;

final class PublicRegistrationForm extends Component
{
    /**
     * The form id is the only model reference sent to the client. Every other
     * model attribute is exposed through protected getters / view data so that
     * `submission_actions`, `notify_emails`, `redirect_url`, and tenant ids
     * never appear in the serialised Livewire snapshot.
     */
    #[Locked]
    public int $formId = 0;

    #[Locked]
    public string $formSlug = '';

    public string $formName = '';

    public ?string $formDescription = null;

    /** @var array<string, mixed> */
    public array $formData = [];

    /**
     * Honeypot. Any non-empty value is treated as bot activity and silently
     * discarded — the UI still renders the "thank you" state so bots cannot
     * tell real from rejected submissions.
     */
    public string $website = '';

    public bool $submitted = false;

    private ?RegistrationForm $formInstance = null;

    /** @var list<FormFieldBlueprint>|null */
    private ?array $blueprintCache = null;

    public function mount(RegistrationForm $form): void
    {
        if (! $form->is_active) {
            abort(404);
        }

        $this->formInstance = $form;
        $this->formId = (int) $form->id;
        $this->formSlug = (string) $form->slug;
        $this->formName = (string) $form->name;
        $this->formDescription = $form->description !== null && $form->description !== ''
            ? (string) $form->description
            : null;

        $this->initializeFormData();
    }

    public function submit(ProcessFormSubmission $processor): void
    {
        $form = $this->resolveForm();

        if ($this->website !== '') {
            // Honeypot triggered — pretend success, do nothing.
            $this->submitted = true;

            return;
        }

        $attempts = (int) config('filament-form-builder.submission_rate_limit.attempts', 5);
        $decay = (int) config('filament-form-builder.submission_rate_limit.decay_seconds', 60);
        $key = 'form-builder-submit:'.$this->formId.':'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, $attempts)) {
            $seconds = RateLimiter::availableIn($key);

            $this->addError('form', __('filament-form-builder::form.errors.rate_limit', [
                'seconds' => $seconds,
            ]));

            return;
        }

        RateLimiter::hit($key, $decay);

        $this->validate($this->buildValidationRules(), [], $this->buildValidationAttributes());

        $submission = null;

        DB::transaction(function () use ($processor, $form, &$submission): void {
            $submission = $processor->handle($form, $this->normalisedFormData());
            $form->increment('submissions_count');
        });

        FormSubmissionProcessed::dispatch(
            $form,
            $submission instanceof FormSubmission ? $submission : null,
            $this->normalisedFormData(),
            $form->submission_actions,
        );

        $this->submitted = true;

        $redirect = self::safeRedirectUrl($form->redirect_url);
        if ($redirect !== null) {
            $url = Js::from($redirect);

            $this->js(<<<JS
                (function () {
                    var detail = { url: {$url} };
                    try {
                        document.dispatchEvent(new CustomEvent('ffb:redirect', { detail: detail, bubbles: true }));
                        window.dispatchEvent(new CustomEvent('ffb:redirect', { detail: detail }));
                    } catch (e) {}
                    if (window.parent === window) {
                        window.location.href = {$url};
                    }
                })();
                JS);
        }
    }

    public function render(): View
    {
        $form = $this->resolveForm();

        $scopeSelector = '#ffb-form-'.$this->formSlug;
        $customCssRaw = $form->custom_css ?? '';
        $customCssSanitized = CssSanitizer::sanitize(
            is_string($customCssRaw) ? $customCssRaw : '',
            (int) config('filament-form-builder.custom_css.max_length', CssSanitizer::DEFAULT_MAX_LENGTH),
        );
        $customCssScoped = $customCssSanitized !== ''
            ? CssSanitizer::scope($customCssSanitized, $scopeSelector)
            : '';

        return view('filament-form-builder::livewire.public-registration-form', [
            'blueprints' => $this->blueprints(),
            'thankYouMessage' => (string) ($form->thank_you_message ?? ''),
            'containerId' => 'ffb-form-'.$this->formSlug,
            'scopeSelector' => $scopeSelector,
            'customCss' => $customCssScoped,
        ]);
    }

    public static function safeRedirectUrl(mixed $url): ?string
    {
        if (! is_string($url) || $url === '') {
            return null;
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);

        if (! is_string($scheme) || ! in_array(strtolower($scheme), ['http', 'https'], true)) {
            return null;
        }

        $host = parse_url($url, PHP_URL_HOST);
        if (! is_string($host) || $host === '') {
            return null;
        }

        return $url;
    }

    /**
     * @return list<FormFieldBlueprint>
     */
    private function blueprints(): array
    {
        if ($this->blueprintCache !== null) {
            return $this->blueprintCache;
        }

        $this->blueprintCache = FormFieldBlueprint::fromForm($this->resolveForm());

        return $this->blueprintCache;
    }

    private function resolveForm(): RegistrationForm
    {
        if ($this->formInstance instanceof RegistrationForm) {
            return $this->formInstance;
        }

        /** @var RegistrationForm $form */
        $form = RegistrationForm::query()->findOrFail($this->formId);

        if (! $form->is_active) {
            abort(404);
        }

        $this->formInstance = $form;

        return $form;
    }

    private function initializeFormData(): void
    {
        foreach ($this->blueprints() as $blueprint) {
            $this->formData[$blueprint->key] = $blueprint->defaultValue();
        }
    }

    /**
     * @return array<string, list<string>>
     */
    private function buildValidationRules(): array
    {
        $rules = [];

        foreach ($this->blueprints() as $blueprint) {
            $rules["formData.{$blueprint->key}"] = $blueprint->validationRules();
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    private function buildValidationAttributes(): array
    {
        $attributes = [];

        foreach ($this->blueprints() as $blueprint) {
            $attributes["formData.{$blueprint->key}"] = $blueprint->label;
        }

        return $attributes;
    }

    /**
     * Only keep keys known to the stored blueprint — drops any extra keys an
     * attacker might try to inject through Livewire's public `formData` array.
     *
     * @return array<string, mixed>
     */
    private function normalisedFormData(): array
    {
        $normalised = [];

        foreach ($this->blueprints() as $blueprint) {
            $normalised[$blueprint->key] = $this->formData[$blueprint->key] ?? $blueprint->defaultValue();
        }

        return $normalised;
    }
}
