<?php

declare(strict_types=1);

namespace Madbox99\FilamentFormBuilder\ValueObjects;

final readonly class MappedInboundPayload
{
    /**
     * @param  array<string, mixed>  $mapped  values keyed by form field key
     * @param  array<string, mixed>  $unmapped  remaining values keyed by flattened external key
     */
    public function __construct(
        public array $mapped,
        public array $unmapped,
    ) {}
}
