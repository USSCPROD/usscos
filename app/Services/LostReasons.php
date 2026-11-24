<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Why deals are lost — the list the pipeline offers and the report groups by.
 *
 * Here rather than in a database enum so adding one needs no migration, and here rather
 * than in the admin lookup tables because the list is short, changes rarely, and every
 * entry has to mean something consistent for a year or the report comparing years is
 * meaningless.
 *
 * The list is deliberately not just commercial. "Could not supply in time" and "Could not
 * meet the spec" are the two that would change how the business runs rather than how it
 * prices, and lumping them into "other" is how an operations problem gets reported as a
 * pricing problem for three years.
 *
 * Changing a LABEL is safe — the code is what is stored. Changing or removing a CODE
 * orphans the deals already recorded against it, which is why unknownLabel() exists rather
 * than a report that silently drops them.
 */
final class LostReasons
{
    private const REASONS = [
        'price'       => 'Price — we were too expensive',
        'competitor'  => 'Lost to a competitor',
        'budget'      => 'No budget or funding not approved',
        'timing'      => 'Postponed, or the timing was wrong',
        'no_decision' => 'Went quiet, no decision made',
        'in_house'    => 'They did it in-house',
        'supply'      => 'We could not supply in time',
        'spec'        => 'We could not meet the spec',
        'other'       => 'Something else',
    ];

    /** @return array<string,string> code => label */
    public static function all(): array
    {
        return self::REASONS;
    }

    public static function isValid(string $code): bool
    {
        return array_key_exists($code, self::REASONS);
    }

    /**
     * The label for a stored code, including codes no longer offered.
     *
     * A report must not drop a deal because the reason it was lost for has since been
     * renamed out of the list - that would quietly reduce the count of losses.
     */
    public static function label(?string $code): string
    {
        if ($code === null || $code === '') {
            return 'No reason recorded';
        }
        return self::REASONS[$code] ?? ('Retired reason: ' . $code);
    }
}
