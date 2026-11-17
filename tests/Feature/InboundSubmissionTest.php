<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Madbox99\FilamentFormBuilder\Events\FormSubmissionProcessed;
use Madbox99\FilamentFormBuilder\Models\FormSubmission;
use Madbox99\FilamentFormBuilder\Models\RegistrationForm;
use Madbox99\FilamentFormBuilder\ValueObjects\SubmissionActions;

uses(RefreshDatabase::class);

/**
 * @param  array<string, mixed>  $attributes
 */
function inboundForm(array $attributes = []): RegistrationForm
{
    $form = RegistrationForm::factory()->create(['inbound_enabled' => true, ...$attributes]);
    $form->ensureInboundToken();

    return $form->fresh();
}

it('accepts a JSON submission', function (): void {
    $form = inboundForm();

    $this->postJson($form->getInboundUrl(), ['name' => 'Anna', 'email' => 'anna@example.com'])
        ->assertCreated()
        ->assertJson(['status' => 'ok', 'submission_id' => FormSubmission::query()->value('id')]);

    expect(FormSubmission::query()->sole()->data)->toBe(['name' => 'Anna', 'email' => 'anna@example.com'])
        ->and($form->fresh()->submissions_count)->toBe(1);
});

it('accepts a nested form-encoded submission', function (): void {
    $form = inboundForm(['inbound_field_map' => [
        ['external' => 'fields.email', 'field' => 'email'],
        ['external' => 'fields.name', 'field' => 'name'],
    ]]);

    $this->post($form->getInboundUrl(), ['fields' => ['email' => 'anna@example.com', 'name' => 'Anna']])
        ->assertCreated();

    expect(FormSubmission::query()->sole()->data)->toBe(['name' => 'Anna', 'email' => 'anna@example.com']);
});

it('keeps unmapped keys on the submission', function (): void {
    $form = inboundForm();

    $this->postJson($form->getInboundUrl(), ['name' => 'Anna', 'email' => 'anna@example.com', 'utm_source' => 'wp'])
        ->assertCreated();

    expect(FormSubmission::query()->sole()->data['_unmapped'])->toBe(['utm_source' => 'wp']);
});

it('answers every rejected form or token with the same 404', function (): void {
    $form = inboundForm();
    $inactive = inboundForm(['is_active' => false]);
    $disabled = inboundForm(['inbound_enabled' => false]);

    $responses = [
        $this->postJson(route('form-builder.form.inbound', ['missing', $form->inbound_token]), ['email' => 'a@b.hu']),
        $this->postJson($inactive->getInboundUrl(), ['email' => 'a@b.hu']),
        $this->postJson($disabled->getInboundUrl(), ['email' => 'a@b.hu']),
        $this->postJson(route('form-builder.form.inbound', [$form->slug, str_repeat('x', 64)]), ['email' => 'a@b.hu']),
    ];

    foreach ($responses as $response) {
        $response->assertNotFound();
    }

    expect(array_unique(array_map(fn ($response): string => (string) $response->getContent(), $responses)))->toHaveCount(1)
        ->and(FormSubmission::query()->count())->toBe(0);
});

it('stops accepting the old URL after the token is regenerated', function (): void {
    $form = inboundForm();
    $oldUrl = $form->getInboundUrl();

    $form->regenerateInboundToken();

    $this->postJson($oldUrl, ['name' => 'Anna', 'email' => 'anna@example.com'])->assertNotFound();
    $this->postJson($form->fresh()->getInboundUrl(), ['name' => 'Anna', 'email' => 'anna@example.com'])->assertCreated();
});

it('rejects a submission missing a required field', function (): void {
    $this->postJson(inboundForm()->getInboundUrl(), ['name' => 'Anna'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);

    expect(FormSubmission::query()->count())->toBe(0);
});

it('rejects an array given for a scalar field', function (): void {
    $this->post(inboundForm()->getInboundUrl(), ['name' => 'Anna', 'email' => ['a@b.hu', 'c@d.hu']], ['Accept' => 'application/json'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('rejects a payload with too many fields', function (): void {
    $payload = ['name' => 'Anna', 'email' => 'anna@example.com'];
    foreach (range(1, 99) as $i) {
        $payload["extra_{$i}"] = 'x';
    }

    $this->postJson(inboundForm()->getInboundUrl(), $payload)->assertStatus(413);

    expect(FormSubmission::query()->count())->toBe(0);
});

it('dispatches the event without storing when submissions are off', function (): void {
    Event::fake([FormSubmissionProcessed::class]);
    $form = inboundForm(['submission_actions' => new SubmissionActions(createSubmission: false)]);

    $this->postJson($form->getInboundUrl(), ['name' => 'Anna', 'email' => 'anna@example.com'])
        ->assertCreated()
        ->assertJson(['status' => 'ok', 'submission_id' => null]);

    Event::assertDispatched(FormSubmissionProcessed::class, fn (FormSubmissionProcessed $event): bool => $event->channel === 'inbound'
        && $event->formData['email'] === 'anna@example.com');
});

it('throttles more than 30 requests a minute', function (): void {
    $form = inboundForm();

    foreach (range(1, 30) as $i) {
        $this->postJson($form->getInboundUrl(), ['name' => 'Anna', 'email' => "anna{$i}@example.com"])->assertCreated();
    }

    $this->post($form->getInboundUrl(), ['name' => 'Anna', 'email' => 'anna31@example.com'])
        ->assertTooManyRequests()
        ->assertJsonStructure(['message']);
});

it('builds the inbound URL from the route', function (): void {
    $form = inboundForm();

    expect($form->getInboundUrl())->toBe(url("forms/{$form->slug}/inbound/{$form->inbound_token}"));
});

it('answers validation errors with JSON even for form-encoded requests', function (): void {
    $this->post(inboundForm()->getInboundUrl(), ['name' => 'Anna'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('answers a 404 with JSON even for form-encoded requests', function (): void {
    $this->post(route('form-builder.form.inbound', ['missing', str_repeat('x', 64)]), ['email' => 'a@b.hu'])
        ->assertNotFound()
        ->assertJsonStructure(['message']);
});

it('ignores uploaded files instead of failing', function (): void {
    $this->post(inboundForm()->getInboundUrl(), [
        'name' => 'Anna',
        'email' => 'anna@example.com',
        'cv' => UploadedFile::fake()->create('cv.pdf', 10),
    ])->assertCreated();

    expect(FormSubmission::query()->sole()->data)->not->toHaveKey('_unmapped');
});

it('counts list items towards the field limit', function (): void {
    $this->postJson(inboundForm()->getInboundUrl(), [
        'name' => 'Anna',
        'email' => 'anna@example.com',
        'tags' => range(1, 150),
    ])->assertStatus(413);
});

it('truncates long unmapped values and flattens unmapped lists to text', function (): void {
    $this->postJson(inboundForm()->getInboundUrl(), [
        'name' => 'Anna',
        'email' => 'anna@example.com',
        'notes' => str_repeat('x', 5000),
        'tags' => ['a', 'b'],
    ])->assertCreated();

    $unmapped = FormSubmission::query()->sole()->data['_unmapped'];

    expect(mb_strlen($unmapped['notes']))->toBe(1000)
        ->and($unmapped['tags'])->toBe('a, b');
});

it('accepts the usual checkbox values from external forms', function (string $sent, bool $stored): void {
    $form = inboundForm(['fields' => [
        ['type' => 'email', 'data' => ['label' => 'Email', 'name' => 'email', 'required' => true]],
        ['type' => 'checkbox', 'data' => ['label' => 'Newsletter', 'name' => 'newsletter']],
    ]]);

    $this->post($form->getInboundUrl(), ['email' => 'anna@example.com', 'newsletter' => $sent])->assertCreated();

    expect(FormSubmission::query()->sole()->data['newsletter'])->toBe($stored);
})->with([
    'on' => ['on', true],
    'yes' => ['yes', true],
    'true' => ['true', true],
    '1' => ['1', true],
    'off' => ['off', false],
    '0' => ['0', false],
    'empty' => ['', false],
]);

it('still requires a required consent checkbox', function (): void {
    $form = inboundForm(['fields' => [
        ['type' => 'email', 'data' => ['label' => 'Email', 'name' => 'email', 'required' => true]],
        ['type' => 'checkbox', 'data' => ['label' => 'Consent', 'name' => 'consent', 'required' => true]],
    ]]);

    $this->post($form->getInboundUrl(), ['email' => 'anna@example.com', 'consent' => 'no'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['consent']);
    $this->post($form->getInboundUrl(), ['email' => 'anna@example.com', 'consent' => 'on'])->assertCreated();
});

it('stores a numeric-string rating as an integer', function (): void {
    $form = inboundForm(['fields' => [
        ['type' => 'email', 'data' => ['name' => 'email', 'label' => 'Email', 'required' => true]],
        ['type' => 'rating', 'data' => ['name' => 'score', 'label' => 'Score', 'min' => 0, 'max' => 10]],
    ]]);

    $this->postJson($form->getInboundUrl(), ['email' => 'anna@example.com', 'score' => '8'])->assertCreated();

    expect(FormSubmission::query()->sole()->data['score'])->toBe(8);
});

it('rejects a rating outside the scale', function (): void {
    $form = inboundForm(['fields' => [
        ['type' => 'rating', 'data' => ['name' => 'score', 'label' => 'Score', 'min' => 0, 'max' => 10]],
    ]]);

    $this->postJson($form->getInboundUrl(), ['score' => '11'])->assertUnprocessable();
});

it('stores an empty optional rating as null', function (): void {
    $form = inboundForm(['fields' => [
        ['type' => 'email', 'data' => ['name' => 'email', 'label' => 'Email', 'required' => true]],
        ['type' => 'rating', 'data' => ['name' => 'score', 'label' => 'Score']],
    ]]);

    $this->postJson($form->getInboundUrl(), ['email' => 'anna@example.com', 'score' => ''])->assertCreated();

    expect(FormSubmission::query()->sole()->data['score'])->toBeNull();
});

it('keeps numeric field keys when unmapped values are stored too', function (): void {
    $form = inboundForm([
        'fields' => [
            ['type' => 'text_input', 'data' => ['name' => '1', 'label' => 'Név', 'required' => true]],
            ['type' => 'email', 'data' => ['name' => '2', 'label' => 'Email', 'required' => true]],
            ['type' => 'text_input', 'data' => ['name' => '5', 'label' => 'Tárgy']],
        ],
        'inbound_field_map' => [
            ['external' => 'your-name', 'field' => '1'],
            ['external' => 'your-email', 'field' => '2'],
            ['external' => 'your-subject', 'field' => '5'],
        ],
    ]);

    $this->postJson($form->getInboundUrl(), ['your-name' => 'Anna', 'your-email' => 'anna@example.com', 'your-subject' => 'Hello', 'wp_form' => 'CF7'])
        ->assertCreated();

    $data = FormSubmission::query()->sole()->data;

    expect($data['1'])->toBe('Anna')
        ->and($data['2'])->toBe('anna@example.com')
        ->and($data['5'])->toBe('Hello')
        ->and($data)->not->toHaveKey('0')
        ->and($data['_unmapped'])->toBe(['wp_form' => 'CF7']);
});
