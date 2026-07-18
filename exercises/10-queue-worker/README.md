# Kata 10 — Queue worker: stale-entity cleanup (skip, delete, requeue)

**Level:** Senior
**Estimated time:** 25–35 minutes
**Focus:** Queue API, cron processing, `RequeueException` semantics

## Problem

Implement `StaleEntityCleanupWorker` — a queue worker plugin that drains a
queue of "delete this stale entity" jobs. Each queue item carries the id of an
entity that has been scheduled for removal.

The worker's `processItem()` must:

1. Read the entity id from the item payload (`['entity_id' => <int>]`). A
   malformed item (no integer `entity_id`) is not actionable and is simply
   dropped without error.
2. Load the entity. If it has **already been deleted** (storage returns
   `null`), *skip* the item — the cleanup is effectively done, so return
   without touching storage.
3. If the entity still exists, **delete** it.
4. If deletion fails for a **transient** reason (the storage backend throws —
   e.g. a temporary lock or a dropped DB connection), do **not** lose the job:
   throw a `RequeueException` so Drupal's queue runner puts the item back and
   retries it on a later cron run.

This mirrors the real contract of `Drupal\Core\Queue\QueueWorkerInterface`,
where throwing `RequeueException` re-queues the current item while any other
exception would leak the item (and log a failure).

## Stub interfaces

To avoid pulling in drupal/core we ship minimal stubs in `src/`:

- `QueueWorkerInterface::processItem(mixed $data): void` — the plugin contract.
- `EntityStorageInterface::load(int $id): ?object` — the entity, or `null` if
  it no longer exists.
- `EntityStorageInterface::delete(object $entity): void` — remove it; may throw
  on a transient backend error.
- `RequeueException` — a stand-in for `Drupal\Core\Queue\RequeueException`,
  thrown to signal "retry this item later".

## Files

```
10-queue-worker/
  README.md
  starter/src/*       (stubs + StaleEntityCleanupWorker — edit the worker)
  solution/src/*      (mirror + reference implementation)
  tests/StaleEntityCleanupWorkerTest.php
```

Read the test first — it is the spec. It asserts three paths: an existing
entity is deleted, a missing entity is skipped (delete never called), and a
transient delete failure is surfaced as a `RequeueException`.
