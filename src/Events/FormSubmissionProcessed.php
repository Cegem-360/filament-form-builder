<?php

declare(strict_types=1);

namespace Madbox99\FilamentFormBuilder\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Madbox99\FilamentFormBuilder\Models\FormSubmission;
use Madbox99\FilamentFormBuilder\Models\RegistrationForm;
use Madbox99\FilamentFormBuilder\ValueObjects\SubmissionActions;

/**
 * Dispatched after a public form submission has been persisted.
 *
 * Host apps can listen to this event to run app-specific side effects
 * (e.g. create a Lead record, send notification emails, trigger a CRM
 * webhook) without the package needing to know about those concerns.
 */
final readonly class FormSubmissionProcessed
{
    use Dispatchable;

    /**
     * @param  array<string, mixed>  $formData
     */
    public function __construct(
        public RegistrationForm $form,
        public ?FormSubmission $submission,
        public array $formData,
        public SubmissionActions $actions,
    ) {}
}
