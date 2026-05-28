<?php

declare(strict_types=1);

namespace Exercises\Kata06\Solution;

interface EntityTypeManagerInterface
{
    public function getStorage(string $entityTypeId): NodeStorageInterface;
}
