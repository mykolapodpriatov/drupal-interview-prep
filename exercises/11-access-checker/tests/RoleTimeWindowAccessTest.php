<?php

declare(strict_types=1);

namespace Exercises\Kata11\Tests;

use PHPUnit\Framework\TestCase;

final class RoleTimeWindowAccessTest extends TestCase
{
    private function mode(): string
    {
        return getenv('KATA_MODE') ?: 'starter';
    }

    /**
     * Build the checker for an editors-only, 09:00–17:00 UTC window and run it.
     *
     * @param list<string> $accountRoles
     */
    private function decide(array $accountRoles, int $now): AccessResult
    {
        $cls = $this->mode() === 'solution'
            ? \Exercises\Kata11\Solution\RoleTimeWindowAccess::class
            : \Exercises\Kata11\Starter\RoleTimeWindowAccess::class;

        $checker = new $cls('editor', 9, 17);

        return $checker->access($accountRoles, $now);
    }

    public function testAllowedForRoleInsideWindow(): void
    {
        // 12:00 UTC = 43200 seconds into the day (inside 09:00–17:00).
        $result = $this->decide(['editor'], 43200);
        self::assertTrue($result->isAllowed());
    }

    public function testForbiddenForRoleOutsideWindow(): void
    {
        // 20:00 UTC = 72000 seconds into the day (after the window).
        $result = $this->decide(['editor'], 72000);
        self::assertTrue($result->isForbidden());
    }

    public function testNeutralWhenRoleMissing(): void
    {
        $result = $this->decide(['authenticated'], 43200);
        self::assertTrue($result->isNeutral());
    }

    public function testAttachesUserRolesCacheContext(): void
    {
        $result = $this->decide(['editor'], 43200);
        self::assertContains('user.roles', $result->getCacheContexts());
    }

    public function testMaxAgeExpiresAtWindowEnd(): void
    {
        // Noon → window ends at 17:00, i.e. 5h = 18000s away.
        $result = $this->decide(['editor'], 43200);
        self::assertSame(18000, $result->getCacheMaxAge());
    }

    public function testMaxAgeBeforeWindowCountsToOpening(): void
    {
        // 08:00 UTC = 28800s → window opens at 09:00, i.e. 3600s away.
        $result = $this->decide(['editor'], 28800);
        self::assertTrue($result->isForbidden());
        self::assertSame(3600, $result->getCacheMaxAge());
    }

    public function testMaxAgeIsNeverPermanent(): void
    {
        $result = $this->decide(['editor'], 72000);
        self::assertNotSame(AccessResult::PERMANENT, $result->getCacheMaxAge());
        self::assertGreaterThan(0, $result->getCacheMaxAge());
    }
}
