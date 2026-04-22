<?php

declare(strict_types=1);

use Madbox99\FilamentFormBuilder\Livewire\PublicRegistrationForm;

it('rejects javascript: URLs', function (): void {
    expect(PublicRegistrationForm::safeRedirectUrl('javascript:alert(1)'))->toBeNull();
});

it('rejects data: URLs', function (): void {
    expect(PublicRegistrationForm::safeRedirectUrl('data:text/html,<script>alert(1)</script>'))->toBeNull();
});

it('rejects vbscript: URLs', function (): void {
    expect(PublicRegistrationForm::safeRedirectUrl('vbscript:msgbox(1)'))->toBeNull();
});

it('rejects ftp:// URLs', function (): void {
    expect(PublicRegistrationForm::safeRedirectUrl('ftp://example.com'))->toBeNull();
});

it('rejects scheme-less URLs', function (): void {
    expect(PublicRegistrationForm::safeRedirectUrl('//evil.example'))->toBeNull();
    expect(PublicRegistrationForm::safeRedirectUrl('/path'))->toBeNull();
});

it('accepts http://', function (): void {
    expect(PublicRegistrationForm::safeRedirectUrl('http://example.com/thanks'))
        ->toBe('http://example.com/thanks');
});

it('accepts https://', function (): void {
    expect(PublicRegistrationForm::safeRedirectUrl('https://example.com/thanks'))
        ->toBe('https://example.com/thanks');
});

it('rejects empty and null', function (): void {
    expect(PublicRegistrationForm::safeRedirectUrl(''))->toBeNull();
    expect(PublicRegistrationForm::safeRedirectUrl(null))->toBeNull();
});
