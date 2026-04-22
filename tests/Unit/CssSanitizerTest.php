<?php

declare(strict_types=1);

use Madbox99\FilamentFormBuilder\Support\CssSanitizer;

describe('CssSanitizer::sanitize', function (): void {
    it('strips </style> breakout', function (): void {
        $css = 'body{color:red;}</style><script>alert(1)</script>';
        expect(CssSanitizer::sanitize($css))->not->toContain('</style>')
            ->and(CssSanitizer::sanitize($css))->not->toContain('<script');
    });

    it('strips @import', function (): void {
        $css = '@import url("https://evil.example/x.css"); body{color:red;}';
        expect(CssSanitizer::sanitize($css))->not->toContain('@import');
    });

    it('strips javascript: url() values', function (): void {
        $css = 'body{background:url(javascript:alert(1));}';
        expect(CssSanitizer::sanitize($css))->not->toContain('javascript:');
    });

    it('strips data: url() values', function (): void {
        $css = 'body{background:url(data:text/html,hello);}';
        expect(CssSanitizer::sanitize($css))->not->toContain('data:');
    });

    it('strips remote url() values', function (): void {
        $css = 'body{background:url(https://evil.example/x.png);}';
        expect(CssSanitizer::sanitize($css))->not->toContain('evil.example');
    });

    it('strips protocol-relative url() values', function (): void {
        $css = 'body{background:url(//evil.example/x.png);}';
        expect(CssSanitizer::sanitize($css))->not->toContain('evil.example');
    });

    it('strips expression() declarations', function (): void {
        $css = 'body{width:expression(alert(1));}';
        expect(CssSanitizer::sanitize($css))->not->toContain('expression(');
    });

    it('strips behavior:', function (): void {
        $css = 'body{behavior:url(xss.htc);}';
        expect(CssSanitizer::sanitize($css))->not->toContain('behavior:');
    });

    it('strips -moz-binding', function (): void {
        $css = 'body{-moz-binding:url(xss.xml);}';
        expect(CssSanitizer::sanitize($css))->not->toContain('-moz-binding');
    });

    it('strips HTML comments that could hide payloads', function (): void {
        $css = 'body{color:red;}<!-- </style><script>alert(1)</script> -->';
        expect(CssSanitizer::sanitize($css))
            ->not->toContain('<!--')
            ->not->toContain('</style>')
            ->not->toContain('<script');
    });

    it('clamps output length', function (): void {
        $css = str_repeat('a', 100);
        expect(strlen(CssSanitizer::sanitize($css, 20)))->toBeLessThanOrEqual(20);
    });

    it('keeps safe css intact', function (): void {
        $css = '.ffb-input { color: #333; border: 1px solid #ccc; padding: 4px 8px; }';
        expect(CssSanitizer::sanitize($css))->toContain('color: #333');
    });
});

describe('CssSanitizer::scope', function (): void {
    it('scopes simple selectors to the prefix', function (): void {
        $css = '.ffb-input { color: red; }';
        $scoped = CssSanitizer::scope($css, '#ffb-form-x');
        expect($scoped)->toContain('#ffb-form-x .ffb-input');
    });

    it('scopes multiple selectors in a list', function (): void {
        $css = 'h1, h2 { color: red; }';
        $scoped = CssSanitizer::scope($css, '#ffb-form-x');
        expect($scoped)->toContain('#ffb-form-x h1')
            ->and($scoped)->toContain('#ffb-form-x h2');
    });

    it('neutralises body / html / :root selectors', function (): void {
        $css = 'body { display: none; } html { background: red; } :root { color: red; }';
        $scoped = CssSanitizer::scope($css, '#ffb-form-x');
        expect($scoped)->not->toContain('body ')
            ->and($scoped)->not->toContain('html ')
            ->and($scoped)->not->toContain(':root');
    });

    it('scopes @media inner rules', function (): void {
        $css = '@media (min-width: 600px) { .ffb-input { color: blue; } }';
        $scoped = CssSanitizer::scope($css, '#ffb-form-x');
        expect($scoped)->toContain('@media')
            ->and($scoped)->toContain('#ffb-form-x .ffb-input');
    });

    it('preserves @keyframes', function (): void {
        $css = '@keyframes spin { from { transform: rotate(0); } to { transform: rotate(360deg); } }';
        $scoped = CssSanitizer::scope($css, '#ffb-form-x');
        expect($scoped)->toContain('@keyframes spin');
    });

    it('drops comments safely', function (): void {
        $css = '/* hidden */ .x { color: red; } /* nope */';
        $scoped = CssSanitizer::scope($css, '#ffb-form-x');
        expect($scoped)->not->toContain('hidden')
            ->and($scoped)->toContain('.x');
    });

    it('ignores unknown at-rules', function (): void {
        $css = '@unknown-at { .x { color: red; } } .y { color: blue; }';
        $scoped = CssSanitizer::scope($css, '#ffb-form-x');
        expect($scoped)->not->toContain('@unknown-at')
            ->and($scoped)->toContain('.y');
    });

    it('handles strings with braces', function (): void {
        $css = '.x::before { content: "{weird}"; }';
        $scoped = CssSanitizer::scope($css, '#ffb-form-x');
        expect($scoped)->toContain('#ffb-form-x .x::before')
            ->and($scoped)->toContain('"{weird}"');
    });
});
