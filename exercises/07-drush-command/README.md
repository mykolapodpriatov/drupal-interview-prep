# Kata 07 — Drush-style cleanup command

**Level:** Senior
**Estimated time:** 25–35 minutes
**Focus:** Command structure, dry-run pattern, dependency injection, age math

## Problem

Implement `CleanupStaleCommand::cleanupStale($contentType, $days, $options)` —
a Drush-style command that deletes nodes of a given content type older
than N days.

The command must:

1. Take three inputs:
   - `$contentType` (string) — node bundle to operate on.
   - `$days` (int) — max age in days; nodes older than this are stale.
   - `$options` (array) — supports `dry_run` (bool, default `false`).
2. Query the injected `EntityStorageInterface` for nodes of that bundle
   whose `createdTimestamp` is strictly less than
   `now - days * 86400`.
3. If `dry_run` is true: do **not** call delete, but return the list of
   ids that *would* be deleted.
4. If `dry_run` is false: call `EntityStorageInterface::delete($ids)`
   and return the deleted id list.
5. Validate inputs:
   - `$days < 1` → throw `\InvalidArgumentException("days must be >= 1")`.
   - `$contentType === ''` → throw `\InvalidArgumentException("content type required")`.
6. Use the injected `\DateTimeImmutable` provider (`ClockInterface`) to
   compute "now" — so the test can fix time.

## API

```php
public function __construct(
    EntityStorageInterface $storage,
    ClockInterface $clock,
) {}

public function cleanupStale(string $contentType, int $days, array $options = []): array
```

## Files

```
07-drush-command/
  README.md
  starter/src/CleanupStaleCommand.php
  starter/src/{EntityStorageInterface,ClockInterface}.php
  solution/src/...  (mirror)
  tests/CleanupStaleCommandTest.php
```
