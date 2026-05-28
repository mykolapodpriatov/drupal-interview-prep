<?php

declare(strict_types=1);

namespace Exercises\Kata06\Solution;

final class Container
{
    /** @var array<string, object> */
    private array $services = [];

    public function set(string $id, object $service): void
    {
        $this->services[$id] = $service;
    }

    public function get(string $id): object
    {
        if (!isset($this->services[$id])) {
            throw new \RuntimeException("Service '$id' not in container.");
        }
        return $this->services[$id];
    }
}
