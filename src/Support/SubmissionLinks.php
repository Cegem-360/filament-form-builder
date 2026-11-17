<?php

declare(strict_types=1);

namespace Madbox99\FilamentFormBuilder\Support;

use Closure;

/**
 * Links from a submission to records the host app owns. The package does not
 * know the app's lead resource, so the app registers how to build the URL:
 *
 *     SubmissionLinks::leadUrlUsing(fn (int|string $id) => LeadResource::getUrl('view', ['record' => $id]));
 */
final class SubmissionLinks
{
    /** @var (Closure(int|string): ?string)|null */
    private static ?Closure $leadUrlResolver = null;

    /**
     * @param  (Closure(int|string): ?string)|null  $resolver
     */
    public static function leadUrlUsing(?Closure $resolver): void
    {
        self::$leadUrlResolver = $resolver;
    }

    public static function leadUrl(int|string|null $leadId): ?string
    {
        if ($leadId === null || $leadId === '' || ! self::$leadUrlResolver instanceof Closure) {
            return null;
        }

        return (self::$leadUrlResolver)($leadId);
    }
}
