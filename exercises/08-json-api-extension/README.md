# Kata 08 — JSON:API field enhancer: phone number formatter

**Level:** Senior
**Estimated time:** 25–30 minutes
**Focus:** Plugin shape (configurable + serializable), transform pipeline,
edge cases

## Problem

Implement `PhoneFormatterEnhancer` — a JSON:API style "resource field
enhancer" plugin that transforms a phone-number value on the output
representation only. The input (incoming PATCH/POST body) is passed
through untouched.

The plugin must:

1. Accept configuration:
   - `format` — one of `e164`, `national`, `pretty`. Default `e164`.
   - `default_country_code` — string (e.g. `"+1"`). Default `"+1"`.
2. Expose two methods:
   - `transformOutput(mixed $value): mixed`
   - `transformInput(mixed $value): mixed`
3. `transformInput()` is a pass-through (return as-is).
4. `transformOutput()` behavior, given a string of digits with optional
   `+` prefix and spaces / dashes / parens:
   - `e164` → strip all non-digit characters except a leading `+`. If
     the value starts with neither a `+` nor a country-code-prefix
     digit, prepend `default_country_code`. Result must always start
     with `+`.
   - `national` → keep digits only (no `+`, no `default_country_code`).
   - `pretty` → if the cleaned digits look like a 10-digit North
     American number after stripping a leading `1`, format as
     `(NNN) NNN-NNNN`. Otherwise fall back to `e164`.
5. Empty or non-string input must pass through unchanged
   (`null` → `null`, `''` → `''`, `[1,2]` → `[1,2]`).
6. `getConfigurationSchema()` returns the JSON schema of allowed
   configuration — a fixed structure (see tests).

## Files

```
08-json-api-extension/
  README.md
  starter/src/PhoneFormatterEnhancer.php
  solution/src/PhoneFormatterEnhancer.php
  tests/PhoneFormatterEnhancerTest.php
```
