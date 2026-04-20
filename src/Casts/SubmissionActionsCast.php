<?php

declare(strict_types=1);

namespace Madbox99\FilamentFormBuilder\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Database\Eloquent\SerializesCastableAttributes;
use Illuminate\Database\Eloquent\Model;
use Madbox99\FilamentFormBuilder\ValueObjects\SubmissionActions;

/**
 * @implements CastsAttributes<SubmissionActions, SubmissionActions|array<string, mixed>|null>
 */
final class SubmissionActionsCast implements CastsAttributes, SerializesCastableAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): SubmissionActions
    {
        $decoded = is_string($value) ? json_decode($value, true, flags: JSON_THROW_ON_ERROR) : $value;

        return SubmissionActions::fromArray(is_array($decoded) ? $decoded : null);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): string
    {
        $actions = $value instanceof SubmissionActions
            ? $value
            : SubmissionActions::fromArray(is_array($value) ? $value : null);

        return json_encode($actions->toArray(), JSON_THROW_ON_ERROR);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function serialize(Model $model, string $key, mixed $value, array $attributes): array
    {
        if ($value instanceof SubmissionActions) {
            return $value->toArray();
        }

        return SubmissionActions::fromArray(is_array($value) ? $value : null)->toArray();
    }
}
