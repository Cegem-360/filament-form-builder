<?php

declare(strict_types=1);

namespace Madbox99\FilamentFormBuilder\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Database\Eloquent\SerializesCastableAttributes;
use Illuminate\Database\Eloquent\Model;
use Madbox99\FilamentFormBuilder\ValueObjects\DesignTokens;

/**
 * @implements CastsAttributes<DesignTokens, DesignTokens|array<string, mixed>|null>
 */
final class DesignTokensCast implements CastsAttributes, SerializesCastableAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): DesignTokens
    {
        $decoded = is_string($value) ? json_decode($value, true, flags: JSON_THROW_ON_ERROR) : $value;

        return DesignTokens::fromArray(is_array($decoded) ? $decoded : null);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        $tokens = $value instanceof DesignTokens
            ? $value
            : DesignTokens::fromArray(is_array($value) ? $value : null);

        return json_encode($tokens->toArray(), JSON_THROW_ON_ERROR);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function serialize(Model $model, string $key, mixed $value, array $attributes): array
    {
        if ($value instanceof DesignTokens) {
            return $value->toArray();
        }

        return DesignTokens::fromArray(is_array($value) ? $value : null)->toArray();
    }
}
