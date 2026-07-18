<?php

declare(strict_types=1);

namespace Exercises\Kata10\Solution;

/**
 * Reference implementation of the stale-entity cleanup queue worker.
 *
 * Each queue item names an entity scheduled for deletion. If the entity has
 * already been removed the item is skipped; otherwise it is deleted. A
 * transient storage failure is surfaced as a RequeueException so the queue
 * runner retries the item on a later cron run instead of dropping it.
 */
final class StaleEntityCleanupWorker implements QueueWorkerInterface
{
    public function __construct(
        private readonly EntityStorageInterface $storage,
    ) {
    }

    public function processItem(mixed $data): void
    {
        $id = $this->entityId($data);
        if ($id === null) {
            // Malformed payload — nothing actionable, let the item drain.
            return;
        }

        $entity = $this->storage->load($id);
        if ($entity === null) {
            // Already gone: the cleanup is done, so skip.
            return;
        }

        try {
            $this->storage->delete($entity);
        } catch (RequeueException $e) {
            // Already a requeue signal — bubble it unchanged.
            throw $e;
        } catch (\Throwable $e) {
            // Transient backend failure — ask the queue to retry this item.
            throw new RequeueException(
                sprintf('Transient failure deleting entity %d; requeuing.', $id),
                0,
                $e,
            );
        }
    }

    /**
     * Extract an integer entity id from a queue item payload, or null.
     */
    private function entityId(mixed $data): ?int
    {
        if (is_array($data) && isset($data['entity_id']) && is_int($data['entity_id'])) {
            return $data['entity_id'];
        }

        return null;
    }
}
