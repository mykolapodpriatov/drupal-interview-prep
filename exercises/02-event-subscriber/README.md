# Kata 02 — Kernel response subscriber

**Level:** Middle / Senior
**Estimated time:** 25–30 minutes
**Focus:** Symfony event dispatcher, response manipulation, route matching

## Problem

Implement `CustomHeaderSubscriber` — a Symfony event subscriber that adds
the HTTP header `X-Tenant-Id: <tenant id>` to the response for requests on
specific routes.

The subscriber must:

1. Subscribe to the `kernel.response` event.
2. Run only on the *main* request, not sub-requests.
3. Add the header `X-Tenant-Id` with the configured tenant id **only**
   when the request's route name matches one of the configured
   `allowed_routes`. Otherwise, do not touch the response.
4. Already-set `X-Tenant-Id` on the response must be overwritten (your
   value wins).
5. The route name is read from the request attribute `_route`
   (Symfony / Drupal convention).
6. Subscribed priority must be a small positive number (>= 0) so other
   default subscribers can still run.

## Inputs

The subscriber is constructed with two arguments:

```php
public function __construct(string $tenantId, array $allowedRoutes) {}
```

`$allowedRoutes` is a list of route names (strings).

## Constraints

- Pure Symfony, no Drupal core dependency.
- No global state, no `\Drupal::` calls.
- Must implement `Symfony\Component\EventDispatcher\EventSubscriberInterface`.

## Hints

- The event class is `Symfony\Component\HttpKernel\Event\ResponseEvent`.
- The event constant is `Symfony\Component\HttpKernel\KernelEvents::RESPONSE`.
- Use `$event->isMainRequest()` to skip sub-requests.
- The request is accessible via `$event->getRequest()`.
- Read the route name with `$event->getRequest()->attributes->get('_route')`.

## Files

```
02-event-subscriber/
  README.md
  starter/src/CustomHeaderSubscriber.php   edit this
  solution/src/CustomHeaderSubscriber.php  reference
  tests/CustomHeaderSubscriberTest.php
```
