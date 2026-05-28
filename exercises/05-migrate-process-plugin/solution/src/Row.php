<?php

declare(strict_types=1);

namespace Exercises\Kata05\Solution;

final class Row
{
    /** @param array<string, mixed> $values */
    public function __construct(private array $values = [])
    {
    }

    public function get(string $name): mixed
    {
        return $this->values[$name] ?? null;
    }
}
