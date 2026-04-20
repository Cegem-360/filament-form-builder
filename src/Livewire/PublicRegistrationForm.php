<?php

declare(strict_types=1);

namespace Madbox99\FilamentFormBuilder\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Js;
use Livewire\Component;
use Madbox99\FilamentFormBuilder\Actions\ProcessFormSubmission;
use Madbox99\FilamentFormBuilder\Events\FormSubmissionProcessed;
use Madbox99\FilamentFormBuilder\Models\FormSubmission;
use Madbox99\FilamentFormBuilder\Models\RegistrationForm;

final class PublicRegistrationForm extends Component
{
    public RegistrationForm $registrationForm;

    /** @var array<string, mixed> */
    public array $formData = [];

    public bool $submitted = false;

    public function mount(RegistrationForm $form): void
    {
        if (! $form->is_active) {
            abort(404);
        }

        $this->registrationForm = $form;
        $this->initializeFormData();
    }

    public function submit(ProcessFormSubmission $processor): void
    {
        $attempts = (int) config('filament-form-builder.submission_rate_limit.attempts', 5);
        $decay = (int) config('filament-form-builder.submission_rate_limit.decay_seconds', 60);
        $key = 'form-builder-submit:' . request()->ip();

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

        DB::transaction(function () use ($processor, &$submission): void {
            $submission = $processor->handle($this->registrationForm, $this->formData);
            $this->registrationForm->increment('submissions_count');
        });

        FormSubmissionProcessed::dispatch(
            $this->registrationForm,
            $submission instanceof FormSubmission ? $submission : null,
            $this->formData,
            $this->registrationForm->submission_actions,
        );

        $this->submitted = true;

        if ($this->registrationForm->redirect_url !== null && $this->registrationForm->redirect_url !== '') {
            $url = Js::from($this->registrationForm->redirect_url);

            $this->js(<<<JS
                window.dispatchEvent(new CustomEvent('marketinghub:redirect', { detail: { url: {$url} } }));
                if (window.parent === window) { window.location.href = {$url}; }
                JS);
        }
    }

    public function render(): View
    {
        return view('filament-form-builder::livewire.public-registration-form');
    }

    /**
     * @return iterable<array{normalized: string, original: string, field: array<string, mixed>}>
     */
    private function eachField(): iterable
    {
        $fields = $this->registrationForm->fields ?? [];

        foreach ($fields as $field) {
            $original = (string) ($field['name'] ?? '');
            $normalized = $this->normalizeFieldName($original);

            if ($normalized === '') {
                continue;
            }

            yield ['normalized' => $normalized, 'original' => $original, 'field' => $field];
        }
    }

    private function initializeFormData(): void
    {
        foreach ($this->eachField() as ['normalized' => $name, 'field' => $field]) {
            $this->formData[$name] = ($field['type'] ?? 'text') === 'checkbox' ? false : '';
        }
    }

    /**
     * @return array<string, list<string>>
     */
    private function buildValidationRules(): array
    {
        $rules = [];

        foreach ($this->eachField() as ['normalized' => $name, 'field' => $field]) {
            $fieldRules = [];

            if (! empty($field['required'])) {
                $fieldRules[] = ($field['type'] ?? 'text') === 'checkbox' ? 'accepted' : 'required';
            } else {
                $fieldRules[] = 'nullable';
            }

            $fieldRules = match ($field['type'] ?? 'text') {
                'email' => [...$fieldRules, 'email:rfc'],
                'phone' => [...$fieldRules, 'string', 'max:50'],
                'number' => [...$fieldRules, 'numeric'],
                'date' => [...$fieldRules, 'date'],
                'textarea' => [...$fieldRules, 'string', 'max:5000'],
                'checkbox' => $fieldRules,
                default => [...$fieldRules, 'string', 'max:255'],
            };

            $rules["formData.{$name}"] = $fieldRules;
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    private function buildValidationAttributes(): array
    {
        $attributes = [];

        foreach ($this->eachField() as ['normalized' => $name, 'original' => $original]) {
            $attributes["formData.{$name}"] = $original;
        }

        return $attributes;
    }

    private function normalizeFieldName(string $name): string
    {
        return str($name)->lower()->snake()->toString();
    }
}
