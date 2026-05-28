# Kata 06 — Block plugin: published node count by content type

**Level:** Senior
**Estimated time:** 25–35 minutes
**Focus:** Block plugin shape, dependency injection, cache tags

## Problem

Implement `NodeCountBlock` — a block plugin that renders a list of
content type machine names with their published node counts.

The block must:

1. Accept a configured list of content type machine names via
   `$configuration['content_types']` (a list of strings). If empty or
   missing, default to *all* known types from the entity type manager.
2. Render a `#type => 'container'` with one child per content type:
   `<container> [type_machine_name] [count] </container>`.
3. Include cache tags `node_list:<bundle>` for each rendered bundle.
4. Set cache contexts to `['user.permissions']` (the count is access-
   filtered conceptually; in this kata the test asserts the metadata).
5. Use dependency injection — the `EntityTypeManagerInterface` must
   be injected via `create()` (the static factory). No `\Drupal::`.
6. Implement `ContainerFactoryPluginInterface::create()` correctly,
   accepting `$container`, `$configuration`, `$plugin_id`,
   `$plugin_definition` and constructing the instance.

## Stub interfaces

To avoid pulling in drupal/core we ship a minimal stub:

- `EntityTypeManagerInterface::getStorage(string)` returns a
  `NodeStorageInterface`.
- `NodeStorageInterface::countByBundle(string $bundle): int` returns
  the published count.
- `NodeStorageInterface::knownBundles(): array<string>` returns all
  known content type machine names.

A `Container` stub is also provided with `get(string)` for service
lookup.

## Files

```
06-block-plugin/
  README.md
  starter/src/*       (stubs + NodeCountBlock)
  solution/src/*      (mirror)
  tests/NodeCountBlockTest.php
```
