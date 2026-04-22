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

    public const TYPE_DATE = 'date';

    public const TYPES = [
        self::TYPE_TEXT,
        self::TYPE_EMAIL,
        self::TYPE_PHONE,
        self::TYPE_NUMBER,
        self::TYPE_TEXTAREA,
        self::TYPE_SELECT,
        self::TYPE_CHECKBOX,
        self::TYPE_DATE,
    ];

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
        if ($type === self::TYPE_SELECT && is_array($data['options'] ?? null)) {
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
        );
    }

    /**
     * @return list<string>
     */
    public function validationRules(): array
    {
        $rules = [];

        if ($this->required) {
            $rules[] = $this->type === self::TYPE_CHECKBOX ? 'accepted' : 'required';
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
            self::TYPE_TEXTAREA => [...$rules, 'string', 'max:'.($this->maxLength ?? 5000)],
            self::TYPE_CHECKBOX => [...$rules, 'boolean'],
            self::TYPE_SELECT => [
                ...$rules,
                'string',
                'max:255',
                $this->options === [] ? 'string' : 'in:'.implode(
                    ',',
                    array_map(static fn (array $option): string => (string) $option['value'], $this->options),
                ),
            ],
            default => [...$rules, 'string', 'max:'.($this->maxLength ?? 255)],
        };

        return $rules;
    }

    public function defaultValue(): mixed
    {
        return $this->type === self::TYPE_CHECKBOX ? false : '';
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
