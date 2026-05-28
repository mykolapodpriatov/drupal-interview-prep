# Kata 01 — Render array builder for a teaser card

**Level:** Middle / Senior
**Estimated time:** 25–35 minutes
**Focus:** Render API, cache metadata, structuring output

## Problem

Implement a `TeaserCardBuilder` class that, given a node-like input
(`NodeData` value object), returns a render array shaped like a typical
Drupal teaser card.

The render array must:

1. Be a `#type => 'container'` with a sensible `#attributes['class']`
   array including `teaser-card` and `teaser-card--{bundle}`.
2. Contain three child elements: `title`, `summary`, `meta`, in that order.
3. Each child must be a render array with `#markup` for simple text, or a
   nested container for structured data.
4. The root array must carry correct `#cache` metadata:
   - `tags`: at least `node:{id}` and the bundle list tag (`node_list:{bundle}`).
   - `contexts`: `user.permissions` (because access affects what is shown)
     and `languages:language_interface`.
   - `max-age`: `Cache::PERMANENT` (= `-1`).
5. When `NodeData::isSticky()` returns `true`, the root `#attributes['class']`
   must include `is-sticky`.
6. When `NodeData::isPublished()` is `false`, the root must include
   `is-unpublished` class **and** `node_list:unpublished` cache tag.

## Inputs

The `NodeData` value object (provided in `tests/Fixture/NodeData.php`) has:

```php
public function __construct(
  public readonly int $id,
  public readonly string $bundle,
  public readonly string $title,
  public readonly string $summary,
  public readonly bool $published,
  public readonly bool $sticky,
  public readonly ?string $authorName,
  public readonly int $createdTimestamp,
) {}
```

## Constraints

- No Drupal core dependency. The kata uses a `Cache` constant stub
  (`Cache::PERMANENT = -1`).
- Pure PHP — your class must not call `\Drupal::` anything.
- Output must be deterministic given the same input.

## Hints

- A real Drupal `Cache::PERMANENT` is `-1`. Hardcode it in the starter
  if you need to.
- Structure the `meta` child as a container with `author` and `date`
  inside.
- Read the tests first. The tests are the spec.

## Files

```
01-render-array-builder/
  README.md                          this file
  starter/src/TeaserCardBuilder.php  edit this
  solution/src/TeaserCardBuilder.php reference implementation
  tests/TeaserCardBuilderTest.php    PHPUnit cases
  tests/Fixture/NodeData.php         input value object
```
