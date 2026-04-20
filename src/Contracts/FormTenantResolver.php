<?php

declare(strict_types=1);

namespace Madbox99\FilamentFormBuilder\Contracts;

interface FormTenantResolver
{
    /**
     * Resolve a tenant primary key from a public slug.
     */
    public function resolveTenantKeyBySlug(string $slug): int|string|null;

    /**
     * Resolve a public slug from a given tenant key. Used by admin views.
     */
    public function resolveSlugByTenantKey(int|string $tenantKey): ?string;
}
