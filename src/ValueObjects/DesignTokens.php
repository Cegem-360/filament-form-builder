<?php

declare(strict_types=1);

namespace Madbox99\FilamentFormBuilder\ValueObjects;

/**
 * Per-form design tokens that drive the public form's look.
 *
 * Stored as JSON on `registration_forms.design_tokens`. Every token has a
 * default that matches the current base stylesheet — a form with NULL
 * tokens renders identically to a form before this feature existed.
 *
 * Unknown or invalid values are coerced to their default rather than
 * throwing, so hand-edited JSON can't break rendering.
 */
final readonly class DesignTokens
{
    public const KEY_PRIMARY_COLOR = 'primary_color';

    public const KEY_RADIUS = 'radius';

    public const KEY_CARD_TREATMENT = 'card_treatment';

    public const KEY_INPUT_BACKGROUND = 'input_background';

    public const KEY_SUBMIT_ALIGNMENT = 'submit_alignment';

    public const KEY_MAX_WIDTH = 'max_width';

    public const DEFAULT_PRIMARY_COLOR = '#1a56db';

    public const RADIUS_NONE = 'none';

    public const RADIUS_SM = 'sm';

    public const RADIUS_MD = 'md';

    public const RADIUS_LG = 'lg';

    public const RADII = [self::RADIUS_NONE, self::RADIUS_SM, self::RADIUS_MD, self::RADIUS_LG];

    public const CARD_FLAT = 'flat';

    public const CARD_BORDERED = 'bordered';

    public const CARD_SHADOW = 'shadow';

    public const CARD_TREATMENTS = [self::CARD_FLAT, self::CARD_BORDERED, self::CARD_SHADOW];

    public const INPUT_BG_GRAY = 'gray';

    public const INPUT_BG_WHITE = 'white';

    public const INPUT_BACKGROUNDS = [self::INPUT_BG_GRAY, self::INPUT_BG_WHITE];

    public const SUBMIT_LEFT = 'left';

    public const SUBMIT_CENTER = 'center';

    public const SUBMIT_FULL = 'full';

    public const SUBMIT_ALIGNMENTS = [self::SUBMIT_LEFT, self::SUBMIT_CENTER, self::SUBMIT_FULL];

    public const WIDTH_NARROW = 'narrow';

    public const WIDTH_DEFAULT = 'default';

    public const WIDTH_WIDE = 'wide';

    public const MAX_WIDTHS = [self::WIDTH_NARROW, self::WIDTH_DEFAULT, self::WIDTH_WIDE];

    public function __construct(
        public string $primaryColor = self::DEFAULT_PRIMARY_COLOR,
        public string $radius = self::RADIUS_MD,
        public string $cardTreatment = self::CARD_FLAT,
        public string $inputBackground = self::INPUT_BG_GRAY,
        public string $submitAlignment = self::SUBMIT_LEFT,
        public string $maxWidth = self::WIDTH_DEFAULT,
    ) {}

    /**
     * @param  array<string, mixed>|null  $data
     */
    public static function fromArray(?array $data): self
    {
        $data ??= [];

        return new self(
            primaryColor: self::normalisePrimaryColor($data[self::KEY_PRIMARY_COLOR] ?? null),
            radius: self::normaliseEnum($data[self::KEY_RADIUS] ?? null, self::RADII, self::RADIUS_MD),
            cardTreatment: self::normaliseEnum($data[self::KEY_CARD_TREATMENT] ?? null, self::CARD_TREATMENTS, self::CARD_FLAT),
            inputBackground: self::normaliseEnum($data[self::KEY_INPUT_BACKGROUND] ?? null, self::INPUT_BACKGROUNDS, self::INPUT_BG_GRAY),
            submitAlignment: self::normaliseEnum($data[self::KEY_SUBMIT_ALIGNMENT] ?? null, self::SUBMIT_ALIGNMENTS, self::SUBMIT_LEFT),
            maxWidth: self::normaliseEnum($data[self::KEY_MAX_WIDTH] ?? null, self::MAX_WIDTHS, self::WIDTH_DEFAULT),
        );
    }

    /**
     * @return array{primary_color:string,radius:string,card_treatment:string,input_background:string,submit_alignment:string,max_width:string}
     */
    public function toArray(): array
    {
        return [
            self::KEY_PRIMARY_COLOR => $this->primaryColor,
            self::KEY_RADIUS => $this->radius,
            self::KEY_CARD_TREATMENT => $this->cardTreatment,
            self::KEY_INPUT_BACKGROUND => $this->inputBackground,
            self::KEY_SUBMIT_ALIGNMENT => $this->submitAlignment,
            self::KEY_MAX_WIDTH => $this->maxWidth,
        ];
    }

    public function isDefault(): bool
    {
        return $this->primaryColor === self::DEFAULT_PRIMARY_COLOR
            && $this->radius === self::RADIUS_MD
            && $this->cardTreatment === self::CARD_FLAT
            && $this->inputBackground === self::INPUT_BG_GRAY
            && $this->submitAlignment === self::SUBMIT_LEFT
            && $this->maxWidth === self::WIDTH_DEFAULT;
    }

    /**
     * Emit `--ffb-*` declarations for inclusion inside a scoped selector.
     * Returns an empty string if every token is at its default — the base
     * stylesheet fallbacks already produce identical output.
     */
    public function toCssDeclarations(): string
    {
        if ($this->isDefault()) {
            return '';
        }

        return implode(' ', [
            '--ffb-primary: '.$this->primaryColor.';',
            '--ffb-primary-hover: '.self::darken($this->primaryColor, 12).';',
            '--ffb-primary-ring: '.self::rgba($this->primaryColor, 0.35).';',
            '--ffb-radius: '.self::radiusValue($this->radius).';',
            '--ffb-card-border: '.self::cardBorder($this->cardTreatment).';',
            '--ffb-card-shadow: '.self::cardShadow($this->cardTreatment).';',
            '--ffb-input-bg: '.self::inputBgValue($this->inputBackground).';',
            '--ffb-submit-width: '.self::submitWidth($this->submitAlignment).';',
            '--ffb-submit-justify: '.self::submitJustify($this->submitAlignment).';',
            '--ffb-max-width: '.self::maxWidthValue($this->maxWidth).';',
        ]);
    }

    private static function normaliseEnum(mixed $value, array $allowed, string $default): string
    {
        return is_string($value) && in_array($value, $allowed, true) ? $value : $default;
    }

    private static function normalisePrimaryColor(mixed $value): string
    {
        if (is_string($value) && preg_match('/^#[0-9a-f]{6}$/i', $value) === 1) {
            return strtolower($value);
        }

        return self::DEFAULT_PRIMARY_COLOR;
    }

    private static function radiusValue(string $radius): string
    {
        return match ($radius) {
            self::RADIUS_NONE => '0',
            self::RADIUS_SM => '0.25rem',
            self::RADIUS_LG => '0.75rem',
            default => '0.5rem',
        };
    }

    private static function cardBorder(string $treatment): string
    {
        return $treatment === self::CARD_BORDERED ? '1px solid #e5e7eb' : '0 solid transparent';
    }

    private static function cardShadow(string $treatment): string
    {
        return $treatment === self::CARD_SHADOW
            ? '0 1px 3px 0 rgba(17, 24, 39, 0.08), 0 1px 2px -1px rgba(17, 24, 39, 0.05)'
            : 'none';
    }

    private static function inputBgValue(string $bg): string
    {
        return $bg === self::INPUT_BG_WHITE ? '#ffffff' : '#f9fafb';
    }

    private static function submitWidth(string $alignment): string
    {
        return $alignment === self::SUBMIT_FULL ? '100%' : 'auto';
    }

    private static function submitJustify(string $alignment): string
    {
        return match ($alignment) {
            self::SUBMIT_CENTER => 'center',
            self::SUBMIT_FULL => 'stretch',
            default => 'start',
        };
    }

    private static function maxWidthValue(string $width): string
    {
        return match ($width) {
            self::WIDTH_NARROW => '32rem',
            self::WIDTH_WIDE => '48rem',
            default => '42rem',
        };
    }

    private static function rgba(string $hex, float $alpha): string
    {
        [$r, $g, $b] = self::hexToRgb($hex);
        $alpha = max(0.0, min(1.0, $alpha));

        return sprintf('rgba(%d, %d, %d, %s)', $r, $g, $b, rtrim(rtrim(number_format($alpha, 2, '.', ''), '0'), '.'));
    }

    private static function darken(string $hex, int $percent): string
    {
        $percent = max(0, min(100, $percent));
        $factor = 1 - ($percent / 100);

        [$r, $g, $b] = self::hexToRgb($hex);

        return sprintf(
            '#%02x%02x%02x',
            (int) round($r * $factor),
            (int) round($g * $factor),
            (int) round($b * $factor),
        );
    }

    /**
     * @return array{int, int, int}
     */
    private static function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        return [
            (int) hexdec(substr($hex, 0, 2)),
            (int) hexdec(substr($hex, 2, 2)),
            (int) hexdec(substr($hex, 4, 2)),
        ];
    }
}
