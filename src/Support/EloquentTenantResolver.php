<?php

declare(strict_types=1);

namespace Madbox99\FilamentFormBuilder\Support;

use Illuminate\Database\Eloquent\Model;
use Madbox99\FilamentFormBuilder\Contracts\FormTenantResolver;

final class EloquentTenantResolver implements FormTenantResolver
{
    /**
     * @param  class-string<Model>|null  $tenantModel
     */
    public function __construct(
        private readonly ?string $tenantModel,
        private readonly string $slugColumn,
    ) {}

    public function resolveTenantKeyBySlug(string $slug): int|string|null
    {
        if ($this->tenantModel === null) {
            return null;
        }

        /** @var Model|null $tenant */
        $tenant = $this->tenantModel::query()
            ->where($this->slugColumn, $slug)
            ->first();

        return $tenant?->getKey();
    }

    public function resolveSlugByTenantKey(int|string $tenantKey): ?string
    {
        if ($this->tenantModel === null) {
            return null;
        }

        /** @var Model|null $tenant */
        $tenant = $this->tenantModel::query()->find($tenantKey);

        if (! $tenant instanceof Model) {
            return null;
        }

        $slug = $tenant->getAttribute($this->slugColumn);

        return is_string($slug) ? $slug : null;
    }
}
