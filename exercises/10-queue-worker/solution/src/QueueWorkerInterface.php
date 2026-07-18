<?php

declare(strict_types=1);

namespace Exercises\Kata10\Solution;

/**
 * Minimal stub of Drupal\Core\Queue\QueueWorkerInterface.
 */
interface QueueWorkerInterface
{
    /**
     * Work on a single queue item.
     *
     * @param mixed $data
     *   The data that was passed to the queue when the item was created.
     */
    public function processItem(mixed $data): void;
}
