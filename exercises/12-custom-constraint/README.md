# Kata 12 — Custom Constraint + ConstraintValidator (entity validation)

**Level:** Senior
**Estimated time:** 25–35 minutes
**Focus:** Entity validation API, Symfony `Constraint` / `ConstraintValidator`

## Problem

Senior interviews ask how Drupal's Typed Data / entity validation API works.
The answer is a **constraint + validator pair**: the constraint is a plugin
that holds the message (and any options); the validator is the class that
reads a value and, when it is invalid, builds a `ConstraintViolation` on the
execution context.

Implement `EmbargoWindow` and `EmbargoWindowValidator` for an entity-level
rule: a **publish-on** timestamp must fall inside a **valid-from / valid-until**
window. Scheduled publishing of embargoed content is the real-world shape —
editors pick a go-live date that must stay inside a legal or contractual
window.

`validate($value, Constraint $constraint)` receives a payload:

```php
[
  'publish_on'  => 1_720_000_000, // UNIX timestamp, or null
  'valid_from'  => 1_710_000_000,
  'valid_until' => 1_730_000_000,
]
```

and must:

1. **Skip** when `$value` is not an array, or when `publish_on` is missing
   or `null`. Empty values are a different constraint (`NotNull` / `NotBlank`);
   this one only judges a date that is actually present.
2. **Accept** when `publish_on` is inside the inclusive window
   `[valid_from, valid_until]`.
3. **Reject** when `publish_on` is strictly before `valid_from` or strictly
   after `valid_until`, by building a violation from `$constraint->message`.

The violation must:

- use the constraint's `$message` (do not hard-code a string);
- replace `%value`, `%from`, and `%until` with the three timestamps;
- set the property path to `publish_on` (`atPath('publish_on')`) so the
  error lands on the field editors actually change;
- record the invalid value as the out-of-window `publish_on`.

In real Drupal this pair would live under
`Plugin/Validation/Constraint`, be discovered by a `#[Constraint]` attribute,
and be attached with `$definition->addConstraint('EmbargoWindow')` or
`hook_entity_base_field_info_alter()`. The kata keeps the Symfony shape
(`initialize()` + `$this->context->buildViolation(…)`) and drops the plugin
discovery.

## The time model

Timestamps are UNIX integers. No timezones, no `DateTime` objects. The
window is inclusive at both ends: `publish_on === valid_from` and
`publish_on === valid_until` are valid.

Default message on the constraint:

```
The publish-on date %value is outside the allowed window %from – %until.
```

## Stub

`tests/` ships a minimal stand-in for Symfony's validator primitives (the
same stack Drupal's entity validation API wraps):

- `Constraint` / `ConstraintValidator` — the bases you extend.
  `validatedBy()` defaults to `static::class . 'Validator'`.
- `ExecutionContext` — `buildViolation($message)` returns a fluent
  builder with `setParameter()`, `atPath()`, `setInvalidValue()`, and
  `addViolation()`.
- `ConstraintViolationList` — countable list of `ConstraintViolation`
  objects the tests read (`getMessage()`, `getMessageTemplate()`,
  `getParameters()`, `getPropertyPath()`, `getInvalidValue()`).

Call `$validator->initialize($context)` before `validate()`, just as the
real validator factory does.

## Files

```
12-custom-constraint/
  README.md
  starter/src/EmbargoWindow.php            constraint (message)
  starter/src/EmbargoWindowValidator.php   edit this
  solution/src/EmbargoWindow.php           reference constraint
  solution/src/EmbargoWindowValidator.php  reference validator
  tests/EmbargoWindowTest.php              PHPUnit cases
  tests/Constraint.php                     Symfony Constraint base
  tests/ConstraintValidator.php            Symfony ConstraintValidator base
  tests/ExecutionContext.php               context + violation builder
  tests/ConstraintViolationList.php        list + ConstraintViolation
```

Read the tests first — they assert both the accept/reject decision and the
shape of the `ConstraintViolation` (message, parameters, property path).
