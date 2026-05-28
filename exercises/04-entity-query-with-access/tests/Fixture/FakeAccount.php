<?php

declare(strict_types=1);

namespace Exercises\Kata04\Tests\Fixture;

/**
 * Implements both starter & solution AccountInterface for kata symmetry.
 */
final class FakeAccount implements
    \Exercises\Kata04\Starter\AccountInterface,
    \Exercises\Kata04\Solution\AccountInterface
{
    /**
     * @param list<string> $permissions
     * @param list<string> $roles
     */
    public function __construct(
        private readonly int $id,
        private readonly array $permissions = [],
        private readonly array $roles = ['authenticated'],
    ) {
    }

    public function id(): int
    {
        return $this->id;
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions, true);
    }

    public function roles(): array
    {
        return $this->roles;
    }
}
