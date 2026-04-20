<?php

declare(strict_types=1);

namespace Madbox99\FilamentFormBuilder\Actions;

use Illuminate\Http\Request;
use Madbox99\FilamentFormBuilder\Models\FormSubmission;
use Madbox99\FilamentFormBuilder\Models\RegistrationForm;

final readonly class ProcessFormSubmission
{
    public function __construct(
        private Request $request,
    ) {}

    /**
     * @param  array<string, mixed>  $formData
     */
    public function handle(RegistrationForm $form, array $formData, int|string|null $leadId = null): ?FormSubmission
    {
        $actions = $form->submission_actions;

        if (! $actions->createSubmission) {
            return null;
        }

        $tenantForeignKey = (string) config('filament-form-builder.tenant_foreign_key', 'team_id');

        /** @var FormSubmission $submission */
        $submission = FormSubmission::query()->create([
            'registration_form_id' => $form->id,
            $tenantForeignKey => $form->getAttribute($tenantForeignKey),
            'lead_id' => $leadId,
            'data' => $formData,
            'ip_address' => $this->request->ip(),
            'user_agent' => $this->request->userAgent(),
        ]);

        return $submission;
    }
}
