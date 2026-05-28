<?php

declare(strict_types=1);

namespace Exercises\Kata06\Starter;

interface NodeStorageInterface
{
    public function countByBundle(string $bundle): int;

    /** @return list<string> */
    public function knownBundles(): array;
}
