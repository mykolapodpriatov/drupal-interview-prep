<?php

declare(strict_types=1);

namespace Exercises\Kata06\Starter;

interface EntityTypeManagerInterface
{
    public function getStorage(string $entityTypeId): NodeStorageInterface;
}
