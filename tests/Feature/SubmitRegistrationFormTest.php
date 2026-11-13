<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Madbox99\FilamentFormBuilder\Actions\SubmitRegistrationForm;
use Madbox99\FilamentFormBuilder\Events\FormSubmissionProcessed;
use Madbox99\FilamentFormBuilder\Models\FormSubmission;
use Madbox99\FilamentFormBuilder\Models\RegistrationForm;
use Madbox99\FilamentFormBuilder\ValueObjects\SubmissionActions;

uses(RefreshDatabase::class);

it('stores the submission, counts it and dispatches the event', function (): void {
    Event::fake([FormSubmissionProcessed::class]);
    $form = RegistrationForm::factory()->create();

    $submission = resolve(SubmitRegistrationForm::class)->handle($form, ['name' => 'Anna', 'email' => 'anna@example.com']);

    expect($submission)->toBeInstanceOf(FormSubmission::class)
        ->and($submission->data)->toBe(['name' => 'Anna', 'email' => 'anna@example.com'])
        ->and($form->fresh()->submissions_count)->toBe(1);

    Event::assertDispatched(FormSubmissionProcessed::class, fn (FormSubmissionProcessed $event): bool => $event->channel === 'form'
        && $event->formData === ['name' => 'Anna', 'email' => 'anna@example.com']
        && $event->unmappedData === []
        && $event->submission?->is($submission));
});

it('stores unmapped data under _unmapped and passes it on the event', function (): void {
    Event::fake([FormSubmissionProcessed::class]);
    $form = RegistrationForm::factory()->create();

    $submission = resolve(SubmitRegistrationForm::class)->handle(
        $form,
        ['name' => 'Anna', 'email' => 'anna@example.com'],
        ['utm' => 'x'],
        'inbound',
    );

    expect($submission?->data['_unmapped'])->toBe(['utm' => 'x']);

    Event::assertDispatched(FormSubmissionProcessed::class, fn (FormSubmissionProcessed $event): bool => $event->channel === 'inbound'
        && $event->unmappedData === ['utm' => 'x']
        && ! array_key_exists('_unmapped', $event->formData));
});

it('dispatches even when submissions are not stored', function (): void {
    Event::fake([FormSubmissionProcessed::class]);
    $form = RegistrationForm::factory()->create([
        'submission_actions' => new SubmissionActions(createSubmission: false),
    ]);

    $submission = resolve(SubmitRegistrationForm::class)->handle($form, ['name' => 'Anna', 'email' => 'anna@example.com']);

    expect($submission)->toBeNull()
        ->and(FormSubmission::query()->count())->toBe(0);

    Event::assertDispatched(FormSubmissionProcessed::class, fn (FormSubmissionProcessed $event): bool => $event->submission === null);
});
