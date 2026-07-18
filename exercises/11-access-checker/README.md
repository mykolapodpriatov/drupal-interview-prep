# Kata 11 — Route access checker returning a cacheable AccessResult

**Level:** Senior / Lead
**Estimated time:** 25–35 minutes
**Focus:** Access API, `AccessResult` cacheability, cache contexts & max-age

## Problem

Implement `RoleTimeWindowAccess` — an access checker that grants a route to
accounts holding a given role, but only inside a daily time window (for
example, "editors, 09:00–17:00 UTC"). The interesting part is not the boolean
decision; it is getting the **cacheability** of that decision right so Drupal
can cache the access result without ever serving a stale answer.

`access(array $accountRoles, int $now)` returns an `AccessResult` that:

1. Is **allowed** when the account has the required role *and* `$now` falls
   inside the window.
2. Is **forbidden** when the account has the required role but `$now` is
   outside the window.
3. Is **neutral** (abstains) when the account does not hold the required role.
4. In *every* case, carries the `user.roles` cache context — the decision reads
   the account's roles, so a cached result must vary per role set.
5. In *every* case, carries a **finite** `max-age`: because the decision
   depends on the wall clock, it must expire at the next window boundary
   (never `Cache::PERMANENT`). The max-age is the number of seconds from `$now`
   until the window state next flips.

Attaching cacheability is not optional cleanup — an `AccessResult` that omits
the `user.roles` context or leaves `max-age` permanent would let Drupal serve a
cached "allowed" to the wrong user, or long after the window has closed.

## The time model

To stay free of timezone tangles, the window is expressed in whole hours of the
day and `$now` is a UNIX timestamp interpreted in UTC. Seconds-into-day is
`$now % 86400`; the window is `[startHour * 3600, endHour * 3600)`. The max-age
is the distance to the nearest boundary:

- before the window opens → seconds until `startHour`;
- inside the window → seconds until `endHour`;
- after the window closes → seconds until `startHour` on the following day.

## Stub

`tests/AccessResult.php` is a minimal stand-in for
`Drupal\Core\Access\AccessResult`: static `allowed()` / `forbidden()` /
`neutral()` (and `allowedIf(bool)`) constructors, fluent `addCacheContexts()`,
`addCacheTags()` and `setCacheMaxAge()` setters, plus getters the tests read.
`AccessResult::PERMANENT` is `-1`.

## Files

```
11-access-checker/
  README.md
  starter/src/RoleTimeWindowAccess.php   edit this
  solution/src/RoleTimeWindowAccess.php  reference implementation
  tests/RoleTimeWindowAccessTest.php     PHPUnit cases
  tests/AccessResult.php                 AccessResult value-object stub
```

Read the tests first — they assert both the decision (allowed / forbidden /
neutral) and the bubbled cache metadata (`user.roles` context and the exact
max-age).
