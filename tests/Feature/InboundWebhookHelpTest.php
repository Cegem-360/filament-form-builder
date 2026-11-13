<?php

declare(strict_types=1);

use Madbox99\FilamentFormBuilder\Filament\Pages\InboundWebhookHelp;

it('exposes a navigation label and title via translations', function (): void {
    expect(InboundWebhookHelp::getNavigationLabel())->toBeString()->not->toBeEmpty()
        ->not->toBe('filament-form-builder::form.inbound_help.navigation_label')
        ->and((string) (new InboundWebhookHelp)->getTitle())->not->toBeEmpty();
});

it('builds a valid example payload', function (): void {
    $payload = json_decode((new InboundWebhookHelp)->getExamplePayloadJson(), true);

    expect($payload)->toBeArray()->toHaveKey('email', 'anna@example.com');
});

it('lists every response code the endpoint returns', function (): void {
    expect(array_keys((new InboundWebhookHelp)->getResponseCodes()))->toBe([201, 404, 413, 422, 429]);
});

it('renders the curl example with the inbound path', function (): void {
    expect((new InboundWebhookHelp)->getCurlExample())
        ->toContain('/inbound/')
        ->toContain('Content-Type: application/json')
        ->toContain('anna@example.com');
});

it('lists a guide with an https link for every tool', function (): void {
    $guides = InboundWebhookHelp::toolGuides();

    expect($guides)->toHaveCount(6);

    foreach ($guides as $guide) {
        expect($guide['url'])->toStartWith('https://')
            ->and($guide['name'])->not->toBeEmpty()
            ->and($guide['steps'])->not->toBeEmpty();
    }
});

it('only serves whitelisted help images', function (): void {
    expect(InboundWebhookHelp::image('01-enable'))->toStartWith('data:image/png;base64,');

    InboundWebhookHelp::image('../../composer');
})->throws(InvalidArgumentException::class);

it('builds no setup links without a Filament panel', function (): void {
    expect((new InboundWebhookHelp)->getSetupLinks())->toBe(['step1' => [], 'step2' => [], 'step3' => [], 'troubleshooting' => []]);
});

it('links the WordPress plugin download', function (): void {
    expect((new InboundWebhookHelp)->getWordPressPluginUrl())->toBe(route('form-builder.wordpress-plugin'));
});
