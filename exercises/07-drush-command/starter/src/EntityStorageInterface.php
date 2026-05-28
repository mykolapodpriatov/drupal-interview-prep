<?php

declare(strict_types=1);

namespace Exercises\Kata07\Starter;

interface EntityStorageInterface
{
    /**
     * @return list<int> node ids matching the criteria.
     */
    public function findStaleIds(string $bundle, int $beforeTimestamp): array;

    /**
     * @param list<int> $ids
     */
    public function delete(array $ids): void;
}
