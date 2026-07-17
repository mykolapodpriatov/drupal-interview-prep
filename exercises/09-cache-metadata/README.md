# Kata 09 — Cache metadata bubbling

**Level:** Senior
**Estimated time:** 25–35 minutes
**Focus:** Render API, cache metadata, bubbling / merge semantics

## Problem

When Drupal renders a tree of render arrays, each child contributes its own
cacheability, and the parent's cacheability becomes the *merge* of its own
plus every child's. Implement a `CacheMetadataBubbler` that reproduces that
merge.

Given a parent render array and a list of child render arrays, `bubble()`
returns the parent with its `#cache` replaced by the merged cacheability of
the parent and all children.

The merge rules (identical to Drupal's `CacheableMetadata::merge()`):

1. **`tags`** — the *union* of all cache tags, de-duplicated and sorted.
2. **`contexts`** — the *union* of all cache contexts, de-duplicated and
   sorted.
3. **`max-age`** — the *most restrictive* (smallest) max-age, where
   `Cache::PERMANENT` (`-1`) means "no time constraint" and therefore never
   wins against a finite age. `0` (uncacheable) beats everything.

The returned `#cache` must always carry all three keys (`tags`, `contexts`,
`max-age`), even when every input is empty.

## Inputs

A render array is a plain associative array. Only its `#cache` key matters
here; a missing `#cache` — or a missing `tags` / `contexts` / `max-age`
inside it — means "no constraint contributed":

```php
$parent = [
  '#type' => 'container',
  '#cache' => [
    'tags' => ['config:system.site'],
    'contexts' => ['languages:language_interface'],
    'max-age' => Cache::PERMANENT,
  ],
];

$children = [
  ['#cache' => ['tags' => ['node:1'], 'max-age' => 3600]],
  ['#cache' => ['tags' => ['node:1', 'user:5'], 'contexts' => ['user.permissions']]],
];
```

## Constraints

- No Drupal core dependency. The kata uses local `Cache` and
  `CacheableMetadata` stubs (`Cache::PERMANENT = -1`).
- Pure PHP — your class must not call `\Drupal::` anything.
- Output must be deterministic given the same input (hence the sorting).
- Non-`#cache` keys on the parent must be preserved untouched.

## Hints

- `Cache::PERMANENT` is `-1`. Treat it as "+infinity" when taking a minimum.
- The `CacheableMetadata` stub (`tests/CacheableMetadata.php`) can extract
  cacheability from a render array (`fromRenderArray()`) and render it back
  (`toCacheArray()`) — the merge math in between is yours to write.
- `array_unique` + `sort` gives you deterministic tag / context unions.
- Read the tests first. The tests are the spec.

## Files

```
09-cache-metadata/
  README.md                              this file
  starter/src/CacheMetadataBubbler.php   edit this
  solution/src/CacheMetadataBubbler.php  reference implementation
  tests/CacheMetadataBubblerTest.php     PHPUnit cases
  tests/Cache.php                        Cache::PERMANENT stub
  tests/CacheableMetadata.php            cacheability value-object stub
```
