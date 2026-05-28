<?php

declare(strict_types=1);

namespace Exercises\Kata07\Starter;

interface ClockInterface
{
    public function now(): \DateTimeImmutable;
}
