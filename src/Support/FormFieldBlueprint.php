<?php

declare(strict_types=1);

namespace Madbox99\FilamentFormBuilder\Support;

use Madbox99\FilamentFormBuilder\Models\RegistrationForm;

/**
 * Normalised, render-ready representation of a single form field, derived from
 * the Filament Builder JSON stored in `registration_forms.fields`.
 *
 * The public form never iterates the raw JSON — it goes through this
 * blueprint so the rendering, validation, and submission paths all agree on
 * the same shape and the same allowed field types.
 *
 * @phpstan-type Options list<array{label:string,value:string}>
 */
final readonly class FormFieldBlueprint
{
    public const TYPE_TEXT = 'text_input';

    public const TYPE_EMAIL = 'email';

    public const TYPE_PHONE = 'phone';

    public const TYPE_NUMBER = 'number';

    public const TYPE_TEXTAREA = 'textarea';

    public const TYPE_SELECT = 'select';

    public const TYPE_CHECKBOX = 'checkbox';

    public const TYPE_CHECKBOX_LIST = 'checkbox_list';

    public const TYPE_RADIO = 'radio';

    public const TYPE_DATE = 'date';

    /** A numbered scale (0–10 or 1–10); a 0–10 scale is reported as NPS. */
    public const TYPE_RATING = 'rating';

    public const RATING_DEFAULT_MIN = 1;

    public const RATING_DEFAULT_MAX = 10;

    public const TYPES = [
        self::TYPE_TEXT,
        self::TYPE_EMAIL,
        self::TYPE_PHONE,
        self::TYPE_NUMBER,
        self::TYPE_TEXTAREA,
        self::TYPE_SELECT,
        self::TYPE_CHECKBOX,
        self::TYPE_CHECKBOX_LIST,
        self::TYPE_RADIO,
        self::TYPE_DATE,
        self::TYPE_RATING,
    ];

    public const WIDTH_FULL = 'full';

    public const WIDTH_HALF = 'half';

    public const WIDTHS = [self::WIDTH_FULL, self::WIDTH_HALF];

    /**
     * Field types where admins may choose half-width. Textareas and checkboxes
     * are always full-width: narrow textareas invite short answers, and
     * consent-style checkboxes read better at full width.
     */
    private const WIDTH_ELIGIBLE_TYPES = [
        self::TYPE_TEXT,
        self::TYPE_EMAIL,
        self::TYPE_PHONE,
        self::TYPE_NUMBER,
        self::TYPE_SELECT,
        self::TYPE_RADIO,
        self::TYPE_DATE,
        self::TYPE_RATING,
    ];

    public static function supportsWidthChoice(string $type): bool
    {
        return in_array($type, self::WIDTH_ELIGIBLE_TYPES, true);
    }

    /**
     * @param  Options  $options
     */
    public function __construct(
        public string $type,
        public string $key,
        public string $label,
        public string $placeholder,
        public bool $required,
        public ?int $maxLength,
        public int|float|null $min,
        public int|float|null $max,
        public array $options,
        public string $width = self::WIDTH_FULL,
    ) {}

    /**
     * @return list<self>
     */
    public static function fromForm(RegistrationForm $form): array
    {
        /** @var list<array<string, mixed>> $blocks */
        $blocks = $form->fields ?? [];

        $blueprints = [];
        $keysSeen = [];

        foreach ($blocks as $index => $block) {
            $blueprint = self::fromBlock($block, $index);

            if ($blueprint === null) {
                continue;
            }

            if (isset($keysSeen[$blueprint->key])) {
                continue;
            }

            $keysSeen[$blueprint->key] = true;
            $blueprints[] = $blueprint;
        }

        return $blueprints;
    }

    /**
     * @param  array<string, mixed>  $block
     */
    private static function fromBlock(array $block, int $index): ?self
    {
        $type = is_string($block['type'] ?? null) ? $block['type'] : '';
        if (! in_array($type, self::TYPES, true)) {
            return null;
        }

        /** @var array<string, mixed> $data */
        $data = is_array($block['data'] ?? null) ? $block['data'] : [];

        $label = self::stringValue($data['label'] ?? '', 255);
        $rawKey = self::stringValue($data['name'] ?? '', 64);
        $key = self::normaliseKey($rawKey !== '' ? $rawKey : $label);

        if ($key === '') {
            $key = 'field_'.($index + 1);
        }

        $placeholder = self::stringValue($data['placeholder'] ?? '', 255);
        $required = (bool) ($data['required'] ?? false);

        $maxLength = self::intOrNull($data['max_length'] ?? null);
        $min = self::numericOrNull($data['min'] ?? null);
        $max = self::numericOrNull($data['max'] ?? null);

        $options = [];
        $hasOptions = in_array($type, [self::TYPE_SELECT, self::TYPE_RADIO, self::TYPE_CHECKBOX_LIST], true);
        if ($hasOptions && is_array($data['options'] ?? null)) {
            foreach ($data['options'] as $option) {
                if (! is_array($option)) {
                    continue;
                }

                $optLabel = self::stringValue($option['label'] ?? '', 255);
                $optValue = self::stringValue($option['value'] ?? $optLabel, 255);

                if ($optLabel === '' && $optValue === '') {
                    continue;
                }

                $options[] = [
                    'label' => $optLabel !== '' ? $optLabel : $optValue,
                    'value' => $optValue !== '' ? $optValue : $optLabel,
                ];
            }
        }

        if ($type === self::TYPE_RATING) {
            $min = $min !== null && (int) $min <= 0 ? 0 : self::RATING_DEFAULT_MIN;
            $max = $max === null ? self::RATING_DEFAULT_MAX : max($min + 1, min(10, (int) $max));
        }

        $width = self::WIDTH_FULL;
        if (self::supportsWidthChoice($type)) {
            $candidate = is_string($data['width'] ?? null) ? $data['width'] : '';
            if (in_array($candidate, self::WIDTHS, true)) {
                $width = $candidate;
            }
        }

        return new self(
            type: $type,
            key: $key,
            label: $label !== '' ? $label : $key,
            placeholder: $placeholder,
            required: $required,
            maxLength: $maxLength,
            min: $min,
            max: $max,
            options: $options,
            width: $width,
        );
    }

    /**
     * @return list<string>
     */
    public function validationRules(): array
    {
        $rules = [];

        if ($this->required) {
            $rules[] = match ($this->type) {
                self::TYPE_CHECKBOX => 'accepted',
                self::TYPE_CHECKBOX_LIST => 'required',
                default => 'required',
            };
        } else {
            $rules[] = 'nullable';
        }

        $rules = match ($this->type) {
            self::TYPE_EMAIL => [...$rules, 'string', 'email:rfc', 'max:254'],
            self::TYPE_PHONE => [...$rules, 'string', 'max:50', 'regex:/^[\d\s\+\-\(\)\.]+$/'],
            self::TYPE_NUMBER => array_values(array_filter([
                ...$rules,
                'numeric',
                $this->min !== null ? 'min:'.$this->min : null,
                $this->max !== null ? 'max:'.$this->max : null,
            ])),
            self::TYPE_DATE => [...$rules, 'date'],
            self::TYPE_RATING => [...$rules, 'integer', 'between:'.$this->min.','.$this->max],
            self::TYPE_TEXTAREA => [...$rules, 'string', 'max:'.($this->maxLength ?? 5000)],
            self::TYPE_CHECKBOX => [...$rules, 'boolean'],
            self::TYPE_SELECT, self::TYPE_RADIO => [
                ...$rules,
                'string',
                'max:255',
                $this->options === [] ? 'string' : 'in:'.implode(
                    ',',
                    array_map(static fn (array $option): string => (string) $option['value'], $this->options),
                ),
            ],
            self::TYPE_CHECKBOX_LIST => array_values(array_filter([
                ...$rules,
                'array',
                $this->required ? 'min:1' : null,
            ])),
            default => [...$rules, 'string', 'max:'.($this->maxLength ?? 255)],
        };

        return $rules;
    }

    /**
     * Per-item rules for array-valued fields (e.g. checkbox_list). The
     * Livewire form registers these under "formData.<key>.*".
     *
     * @return list<string>
     */
    public function arrayItemValidationRules(): array
    {
        if ($this->type !== self::TYPE_CHECKBOX_LIST) {
            return [];
        }

        $rules = ['string', 'max:255'];

        if ($this->options !== []) {
            $rules[] = 'in:'.implode(
                ',',
                array_map(static fn (array $option): string => (string) $option['value'], $this->options),
            );
        }

        return $rules;
    }

    /**
     * True for a 0–10 rating, the scale an NPS score is computed from.
     */
    public function isNpsScale(): bool
    {
        return $this->type === self::TYPE_RATING && $this->min === 0 && $this->max === 10;
    }

    /**
     * Brings a validated value to the type it is stored as. Ratings arrive as
     * strings from radio inputs and external forms but are stored as ints.
     */
    public function castValue(mixed $value): mixed
    {
        if ($this->type === self::TYPE_RATING && is_numeric($value)) {
            return (int) $value;
        }

        return $value;
    }

    public function defaultValue(): mixed
    {
        return match ($this->type) {
            self::TYPE_CHECKBOX => false,
            self::TYPE_CHECKBOX_LIST => [],
            self::TYPE_RATING => null,
            default => '',
        };
    }

    private static function stringValue(mixed $value, int $maxLength): string
    {
        if (! is_scalar($value)) {
            return '';
        }

        $string = trim((string) $value);

        if (strlen($string) > $maxLength) {
            $string = substr($string, 0, $maxLength);
        }

        return $string;
    }

    private static function normaliseKey(string $input): string
    {
        $input = str((string) $input)->ascii()->lower()->toString();
        $input = (string) preg_replace('/[^a-z0-9]+/i', '_', $input);
        $input = trim($input, '_');

        if (strlen($input) > 64) {
            $input = substr($input, 0, 64);
        }

        return $input;
    }

    private static function intOrNull(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (int) $value : null;
    }

    private static function numericOrNull(mixed $value): int|float|null
    {
        if (! is_numeric($value)) {
            return null;
        }

        $float = (float) $value;
        $int = (int) $float;

        return ((float) $int) === $float ? $int : $float;
    }
}
