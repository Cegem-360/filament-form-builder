<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Madbox99\FilamentFormBuilder\Actions\ExportFormAsJson;
use Madbox99\FilamentFormBuilder\Models\RegistrationForm;

uses(RefreshDatabase::class);

it('generates a 64 char token once', function (): void {
    $form = RegistrationForm::factory()->create();

    $first = $form->ensureInboundToken();
    $second = $form->fresh()->ensureInboundToken();

    expect($first)->toHaveLength(64)
        ->and($second)->toBe($first)
        ->and($form->fresh()->inbound_token)->toBe($first);
});

it('regenerates the token', function (): void {
    $form = RegistrationForm::factory()->create();
    $old = $form->ensureInboundToken();

    $new = $form->regenerateInboundToken();

    expect($new)->toHaveLength(64)->not->toBe($old)
        ->and($form->fresh()->inboundTokenMatches($old))->toBeFalse()
        ->and($form->fresh()->inboundTokenMatches($new))->toBeTrue();
});

it('does not match when no token is set', function (): void {
    $form = RegistrationForm::factory()->create();

    expect($form->inboundTokenMatches(''))->toBeFalse()
        ->and($form->inboundTokenMatches('anything'))->toBeFalse()
        ->and($form->getInboundUrl())->toBeNull();
});

it('casts inbound settings', function (): void {
    $form = RegistrationForm::factory()->create([
        'inbound_enabled' => 1,
        'inbound_field_map' => [['external' => 'your-email', 'field' => 'email']],
    ]);

    $fresh = $form->fresh();

    expect($fresh->inbound_enabled)->toBeTrue()
        ->and($fresh->inbound_field_map)->toBe([['external' => 'your-email', 'field' => 'email']]);
});

it('defaults inbound to disabled', function (): void {
    expect(RegistrationForm::factory()->create()->fresh()->inbound_enabled)->toBeFalse();
});

it('keeps inbound settings out of the JSON export', function (): void {
    $form = RegistrationForm::factory()->create([
        'inbound_enabled' => true,
        'inbound_field_map' => [['external' => 'your-email', 'field' => 'email']],
    ]);
    $form->ensureInboundToken();

    $payload = (new ExportFormAsJson)->execute($form->fresh());
    $encoded = (string) json_encode($payload);

    expect(array_filter(array_keys($payload), fn (string $key): bool => str_starts_with($key, 'inbound')))->toBe([])
        ->and($encoded)->not->toContain((string) $form->fresh()->inbound_token);
});

it('generates a token when a form is saved with inbound enabled', function (): void {
    $created = RegistrationForm::factory()->create(['inbound_enabled' => true]);
    $edited = RegistrationForm::factory()->create();
    $edited->update(['inbound_enabled' => true]);

    expect($created->fresh()->inbound_token)->toHaveLength(64)
        ->and($edited->fresh()->inbound_token)->toHaveLength(64)
        ->and(RegistrationForm::factory()->create()->fresh()->inbound_token)->toBeNull();
});
