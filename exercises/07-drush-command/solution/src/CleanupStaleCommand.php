<?php

declare(strict_types=1);

namespace Exercises\Kata07\Solution;

final class CleanupStaleCommand
{
    public function __construct(
        private readonly EntityStorageInterface $storage,
        private readonly ClockInterface $clock,
    ) {
    }

    /**
     * @param array{dry_run?: bool} $options
     * @return list<int>
     */
    public function cleanupStale(string $contentType, int $days, array $options = []): array
    {
        if ($contentType === '') {
            throw new \InvalidArgumentException('content type required');
        }
        if ($days < 1) {
            throw new \InvalidArgumentException('days must be >= 1');
        }

        $cutoff = $this->clock->now()->getTimestamp() - ($days * 86400);
        $ids = $this->storage->findStaleIds($contentType, $cutoff);

        $dryRun = (bool) ($options['dry_run'] ?? false);
        if (!$dryRun && $ids !== []) {
            $this->storage->delete($ids);
        }

        return array_values($ids);
    }
}
