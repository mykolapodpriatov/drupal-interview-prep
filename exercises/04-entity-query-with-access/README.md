# Kata 04 — Entity query with access and pagination

**Level:** Senior
**Estimated time:** 30–40 minutes
**Focus:** Entity query semantics, access checks, deterministic ordering

## Problem

Implement `ArticleFinder::findVisibleArticles(AccountInterface $account, int $page, int $perPage)`.

The method must return a `Result` object with:

- `items` — list of `ArticleData` the account is allowed to view, sorted
  by `createdTimestamp` DESCENDING, then by `id` DESCENDING as a stable
  tie-breaker, paginated.
- `total` — total visible count (before pagination), for the given account.
- `page` — the page returned (1-indexed).
- `perPage` — the page size.

## Inputs

- `Repository` — a tiny in-memory store of `ArticleData` injected into
  `ArticleFinder`'s constructor. Exposes `all(): array<ArticleData>`.
- `AccountInterface` — has `id()`, `hasPermission(string $perm): bool`,
  and `roles(): array<string>`.
- `ArticleData` — value object with `id`, `bundle`, `title`,
  `createdTimestamp`, `published`, `ownerId`.

## Access rules

An account can view an article if any of these is true:

1. It has the permission `bypass node access` (admin behavior).
2. The article is `published === true` AND the account has
   permission `access content`.
3. The article's `ownerId` equals the account's `id` AND the
   account has permission `view own unpublished content`.

Otherwise, the article is hidden.

## Pagination

- `page` is 1-indexed. `page < 1` must be clamped to `1`.
- `perPage` must be between 1 and 100. Out-of-range values clamp to the
  nearest valid bound.
- If a requested page is past the end of results, return an empty
  `items` array but the actual `total`.

## Constraints

- No Drupal core dependency. Pure PHP.
- Deterministic ordering — equal timestamps must tie-break by id DESC.
- Do not mutate the repository.

## Files

```
04-entity-query-with-access/
  README.md
  starter/src/ArticleFinder.php
  starter/src/{ArticleData,Repository,AccountInterface,Result}.php
  solution/src/...  (mirror)
  tests/ArticleFinderTest.php
```

(Supporting classes are duplicated between starter and solution so the
PSR-4 layout is symmetric; only `ArticleFinder` differs in the
implementation.)
