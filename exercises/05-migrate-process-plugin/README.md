# Kata 05 — Migrate process plugin: split_full_name

**Level:** Senior
**Estimated time:** 20–30 minutes
**Focus:** Migrate process plugin signature, edge cases, multiple return values

## Problem

Implement a process plugin that splits a `"Lastname, Firstname"` source
string into a two-element list `[firstName, lastName]`. The shape mirrors
what Drupal's `MigrateProcess` plugins do (with the difference that this
kata uses a minimal `ProcessPluginBase` shim, not drupal/core).

## API

```php
class SplitFullName extends ProcessPluginBase {
  public function transform(
    mixed $value,
    MigrateExecutableInterface $executable,
    Row $row,
    string $destinationProperty,
  ): array;

  public function multiple(): bool;
}
```

## Behavior

1. `transform("Smith, Jane", …)` returns `["Jane", "Smith"]`.
2. `transform("  Smith  ,  Jane  ", …)` returns `["Jane", "Smith"]` —
   whitespace is trimmed around both parts.
3. `transform("Smith, Jane, MD", …)` returns `["Jane, MD", "Smith"]` —
   only the first comma splits.
4. `transform("Madonna", …)` returns `["Madonna", ""]` — no comma means
   single name treated as first name only.
5. `transform("", …)`, `transform(null, …)`, or any non-string returns
   `["", ""]`.
6. `multiple()` returns `true` so the migrate engine knows to
   distribute the two-element return across two destination fields.

## Files

```
05-migrate-process-plugin/
  README.md
  starter/src/{ProcessPluginBase,MigrateExecutableInterface,Row,SplitFullName}.php
  solution/src/...  (mirror)
  tests/SplitFullNameTest.php
```
