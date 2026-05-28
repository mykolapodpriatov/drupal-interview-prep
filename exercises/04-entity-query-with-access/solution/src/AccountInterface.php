<?php

declare(strict_types=1);

namespace Exercises\Kata04\Solution;

interface AccountInterface
{
    public function id(): int;

    public function hasPermission(string $permission): bool;

    /** @return list<string> */
    public function roles(): array;
}
