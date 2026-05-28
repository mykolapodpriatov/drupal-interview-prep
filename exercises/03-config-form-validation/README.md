# Kata 03 — Config form validation and submit

**Level:** Middle
**Estimated time:** 25–30 minutes
**Focus:** Drupal-style form validation patterns, business rules

## Problem

Implement `validateForm()` and `submitForm()` on `SiteSettingsForm`. The
`buildForm()` is already implemented and ships in both the starter and
solution. Your job is the validation logic and the submit handler.

In real Drupal this class would extend `ConfigFormBase`. For the kata it
extends a lightweight `FormBase` shim that mimics the relevant API
(`$form_state->setErrorByName()`, `$form_state->getValue()`,
`$form_state->setSubmitted()`, `$form_state->getStorage()`) so the kata
runs without drupal/core.

## Form fields

Already defined in `buildForm()`:

| Key             | Type   | Description                                |
|-----------------|--------|--------------------------------------------|
| `api_url`       | url    | Endpoint base URL                          |
| `timeout`       | int    | HTTP timeout in seconds                    |
| `support_email` | email  | Editor escalation address                  |
| `retry_count`   | int    | Number of retries on failure (0–10)        |

## Validation rules

`validateForm()` must call `$form_state->setErrorByName($key, $message)`
when:

1. `api_url` does not start with `https://` — message:
   `"API URL must use HTTPS."`
2. `api_url` is not a syntactically valid URL — message:
   `"API URL is not a valid URL."`
   (when both rules fail, only the HTTPS error is set — check it first.)
3. `timeout` is less than `1` or greater than `120` — message:
   `"Timeout must be between 1 and 120 seconds."`
4. `support_email` is not a valid email address — message:
   `"Support email is not valid."`
5. `retry_count` is less than `0` or greater than `10` — message:
   `"Retry count must be between 0 and 10."`

Validation must collect *all* applicable errors per submission, not
short-circuit after the first.

## Submit behavior

`submitForm()` must store the four validated values under
`$form_state->getStorage()['saved_config']` as an associative array,
casting `timeout` and `retry_count` to int.

## Files

```
03-config-form-validation/
  README.md
  starter/src/SiteSettingsForm.php   edit validateForm / submitForm
  starter/src/FormBase.php           shared form base, identical between
                                     starter and solution
  starter/src/FormStateInterface.php
  starter/src/FormState.php
  solution/src/SiteSettingsForm.php
  solution/src/FormBase.php
  solution/src/FormStateInterface.php
  solution/src/FormState.php
  tests/SiteSettingsFormTest.php
```

(The supporting classes are duplicated under starter/ and solution/ so
the autoloader is symmetric.)
