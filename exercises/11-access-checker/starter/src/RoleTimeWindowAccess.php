<?php

declare(strict_types=1);

namespace Exercises\Kata11\Starter;

use Exercises\Kata11\Tests\AccessResult;

/**
 * TODO: implement access() per the kata README.
 *
 * Grant access to accounts holding $requiredRole during the daily window
 * [$startHour, $endHour):
 *   - allowed  when the role is held and $now is inside the window;
 *   - forbidden when the role is held but $now is outside the window;
 *   - neutral  when the role is not held.
 *
 * In every case attach the 'user.roles' cache context and a finite max-age
 * (seconds until the next window boundary) via the AccessResult setters.
 */
final class RoleTimeWindowAccess
{
    public function __construct(
        private readonly string $requiredRole,
        private readonly int $startHour,
        private readonly int $endHour,
    ) {
    }

    /**
     * @param list<string> $accountRoles
     * @param int $now
     */
    public function access(array $accountRoles, int $now): AccessResult
    {
        // TODO: decide allowed / forbidden / neutral and bubble cacheability.
        return AccessResult::neutral();
    }
}
