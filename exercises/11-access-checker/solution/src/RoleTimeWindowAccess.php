<?php

declare(strict_types=1);

namespace Exercises\Kata11\Solution;

use Exercises\Kata11\Tests\AccessResult;

/**
 * Reference implementation of the role + time-window access checker.
 *
 * Grants access to accounts holding $requiredRole during the daily window
 * [$startHour, $endHour). Every result carries the 'user.roles' cache context
 * (the decision reads the account's roles) and a finite max-age that expires
 * at the next window boundary (the decision reads the wall clock).
 */
final class RoleTimeWindowAccess
{
    private const SECONDS_PER_DAY = 86400;
    private const SECONDS_PER_HOUR = 3600;

    public function __construct(
        private readonly string $requiredRole,
        private readonly int $startHour,
        private readonly int $endHour,
    ) {
    }

    /**
     * @param list<string> $accountRoles
     *   The role machine names held by the account.
     * @param int $now
     *   The current UNIX timestamp, interpreted in UTC.
     */
    public function access(array $accountRoles, int $now): AccessResult
    {
        $secondsIntoDay = $now % self::SECONDS_PER_DAY;
        $maxAge = $this->secondsUntilNextBoundary($secondsIntoDay);

        if (!in_array($this->requiredRole, $accountRoles, true)) {
            // Not our concern: abstain — but the abstention still varies by role.
            $result = AccessResult::neutral();
        } else {
            $inWindow = $secondsIntoDay >= ($this->startHour * self::SECONDS_PER_HOUR)
                && $secondsIntoDay < ($this->endHour * self::SECONDS_PER_HOUR);
            $result = AccessResult::allowedIf($inWindow);
        }

        // Bubble the cacheability: vary by role, and never cache past the
        // moment the window state next changes.
        $result->addCacheContexts(['user.roles']);
        $result->setCacheMaxAge($maxAge);

        return $result;
    }

    /**
     * Seconds from the current point in the day until the window state next
     * flips (its start or its end, whichever comes first).
     */
    private function secondsUntilNextBoundary(int $secondsIntoDay): int
    {
        $start = $this->startHour * self::SECONDS_PER_HOUR;
        $end = $this->endHour * self::SECONDS_PER_HOUR;

        if ($secondsIntoDay < $start) {
            return $start - $secondsIntoDay;
        }
        if ($secondsIntoDay < $end) {
            return $end - $secondsIntoDay;
        }

        // Past the window: valid until the start of the next day's window.
        return (self::SECONDS_PER_DAY - $secondsIntoDay) + $start;
    }
}
