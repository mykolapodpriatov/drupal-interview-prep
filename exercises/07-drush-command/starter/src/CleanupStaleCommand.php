<?php

declare(strict_types=1);

namespace Exercises\Kata07\Starter;

/**
 * TODO: implement cleanupStale() per kata README.
 */
final class CleanupStaleCommand
{
    public function __construct(
        private readonly EntityStorageInterface $storage,
        private readonly ClockInterface $clock,
    ) {
    }

    /**
     * @param array{dry_run?: bool} $options
     * @return list<int> ids deleted (or that would be deleted in dry run)
     */
    public function cleanupStale(string $contentType, int $days, array $options = []): array
    {
        // TODO: implement.
        return [];
    }
}
