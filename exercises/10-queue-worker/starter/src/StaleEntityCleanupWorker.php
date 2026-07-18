<?php

declare(strict_types=1);

namespace Exercises\Kata10\Starter;

/**
 * TODO: implement processItem() per the kata README.
 *
 * The Starter\QueueWorkerInterface, Starter\EntityStorageInterface and
 * Starter\RequeueException stubs are available in this namespace.
 *
 * processItem() must:
 *   - read an integer entity id from ['entity_id' => <int>] (drop malformed);
 *   - skip the item when the entity was already deleted (load() === null);
 *   - delete the entity when it still exists;
 *   - throw RequeueException when delete() fails transiently.
 */
final class StaleEntityCleanupWorker implements QueueWorkerInterface
{
    public function __construct(
        private readonly EntityStorageInterface $storage,
    ) {
    }

    public function processItem(mixed $data): void
    {
        // TODO: implement this.
    }
}
