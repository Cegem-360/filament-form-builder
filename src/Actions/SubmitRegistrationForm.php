<?php

declare(strict_types=1);

namespace Madbox99\FilamentFormBuilder\Actions;

use Illuminate\Support\Facades\DB;
use Madbox99\FilamentFormBuilder\Events\FormSubmissionProcessed;
use Madbox99\FilamentFormBuilder\Models\FormSubmission;
use Madbox99\FilamentFormBuilder\Models\RegistrationForm;

/**
 * The one submission pipeline shared by the embedded Livewire form and the
 * inbound webhook: persist (when the form stores submissions), count, then
 * dispatch `FormSubmissionProcessed` for host-app side effects.
 */
final readonly class SubmitRegistrationForm
{
    public function __construct(
        private ProcessFormSubmission $processor,
    ) {}

    /**
     * @param  array<string, mixed>  $formData  validated values keyed by form field key
     * @param  array<string, mixed>  $unmappedData  extra values an external form sent
     */
    public function handle(RegistrationForm $form, array $formData, array $unmappedData = [], string $channel = 'form'): ?FormSubmission
    {
        $storedData = $unmappedData === [] ? $formData : [...$formData, '_unmapped' => $unmappedData];

        $submission = DB::transaction(function () use ($form, $storedData): ?FormSubmission {
            $submission = $this->processor->handle($form, $storedData);
            $form->increment('submissions_count');

            return $submission;
        });

        FormSubmissionProcessed::dispatch(
            $form,
            $submission,
            $formData,
            $form->submission_actions,
            $unmappedData,
            $channel,
        );

        return $submission;
    }
}
