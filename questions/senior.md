# Senior — Drupal interview questions

Target audience: 4–7 years of Drupal experience, with hard exposure to
production. Candidates should be able to reason about cache metadata
propagation, dependency injection trade-offs, migrate pipelines, and
testing strategy without notes.

A senior is the developer the team relies on to make architecturally sound
decisions inside a single site or a small estate of sites. They are not
expected (yet) to own platform-level decisions, but they should be able to
explain the trade-offs.

---

### Q1: Walk me through what happens when Drupal renders a cached block. Where does cache metadata come from?

**Tags:** render-api, caching, deep-dive
**Time:** 10 min

Expected outline:

1. Renderer encounters `#cache` keys / contexts / tags / max-age on the
   element. If keys are present, look up the cache bin (`render`) by the
   key + contexts.
2. On hit, return the cached output and bubble the stored cache metadata
   to the parent.
3. On miss, build the element (calling the build callback / theme hook),
   then write to cache with the *bubbled* metadata from children.
4. `#cache.contexts` declares "which axes of the request affect this
   render" (user.permissions, route, languages:language_interface).
5. `#cache.tags` declares "what invalidates this cache entry"
   (node:42, config:system.site).
6. `#cache.max-age` is in seconds, with `0` meaning uncacheable.
7. Auto-placeholdering kicks in for high-cardinality contexts (e.g.
   `session`, `user`); the placeholder is rendered after the rest of
   the tree, optionally via BigPipe.

A senior should be able to draw the bubble-up arrow on a whiteboard and
name a couple of common context strings.

**References:**
- <https://www.drupal.org/docs/drupal-apis/render-api/cacheability-of-render-arrays>

---

### Q2: How do plugin annotations differ from PHP attributes, and why did Drupal move to attributes?

**Tags:** plugins, php, drupal-11
**Time:** 6 min

Annotations are doctrine-style docblock comments parsed by Drupal's
annotation reader at discovery time. Attributes are native PHP 8 syntax
on the class declaration, parsed by `Reflection*` APIs.

Drupal 11 deprecated annotation-based plugin discovery in favor of
attributes (`#[Block(id: '...', admin_label: new TranslatableMarkup('...'))]`).

Reasons:

- PHP 8 attributes are first-class language constructs — static analysis
  tools, PhpStorm, and PHPStan understand them natively.
- Doctrine annotations were a non-trivial parsing layer with their own
  bugs and edge cases.
- Performance: reflection-based attribute reads are cheaper than parsing
  docblocks.
- Symfony as a whole moved the same direction.

A senior should know the migration is a `phpstan-drupal` warning, not a
runtime break, and that contrib has been catching up for a few releases.

**References:**
- <https://www.drupal.org/node/3395575>
- <https://www.php.net/manual/en/language.attributes.overview.php>

---

### Q3: When do you use `ContainerInjectionInterface` vs `ContainerFactoryPluginInterface`?

**Tags:** di, plugins
**Time:** 6 min

- `ContainerInjectionInterface` — on a non-plugin class instantiated by
  the container or by the router (controllers, forms, access checkers).
  Has one method, `create(ContainerInterface $container): static`.
- `ContainerFactoryPluginInterface` — on a plugin class (Block, Field
  Formatter, Migrate plugin). The plugin manager calls a *different*
  signature: `create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static`,
  because plugins have a configuration/id/definition trio that needs to
  be passed to the constructor too.

Mixing them up causes "missing argument" errors when the plugin manager
tries to instantiate. A senior should know which interface goes where
without thinking.

**References:**
- <https://www.drupal.org/docs/drupal-apis/services-and-dependency-injection/dependency-injection-for-a-plugin>

---

### Q4: How would you write an event subscriber that adds a custom HTTP header to every response except admin pages?

**Tags:** events, http
**Time:** 7 min

Outline:

```php
final class CustomHeaderSubscriber implements EventSubscriberInterface {
  public function __construct(
    private readonly AdminContext $adminContext,
    private readonly RouteMatchInterface $routeMatch,
  ) {}

  public function onResponse(ResponseEvent $event): void {
    if (!$event->isMainRequest()) {
      return;
    }
    if ($this->adminContext->isAdminRoute($this->routeMatch->getRouteObject())) {
      return;
    }
    $event->getResponse()->headers->set('X-Custom', 'value');
  }

  public static function getSubscribedEvents(): array {
    return [KernelEvents::RESPONSE => ['onResponse', 0]];
  }
}
```

Register in `MODULE.services.yml` with the `event_subscriber` tag and
inject `router.admin_context` and `current_route_match`.

A senior should know to check `isMainRequest()` (otherwise sub-requests
like the 404 handler get the header). Bonus if they mention the
priority argument's effect when other subscribers care.

**References:**
- <https://www.drupal.org/docs/drupal-apis/events-system>

---

### Q5: Compare custom services and factories. When do you reach for a factory?

**Tags:** di, services
**Time:** 6 min

A factory is used when "the service can't simply be instantiated with
known constructor arguments from the container". Examples:

- The concrete class to instantiate depends on runtime config or
  configuration (`logger.factory` returns channel-specific loggers).
- The instance requires post-construction wiring that is not
  expressible as constructor args.
- You need to memoize / pool instances per-context.

In services.yml: `factory: ['@some_factory', 'methodName']` or
`factory: 'Fully\Qualified\Class::staticMethod'`. The container calls
that and treats the return as the service.

Most services do not need a factory. Use one when constructor arguments
aren't enough.

**References:**
- <https://symfony.com/doc/current/service_container/factories.html>

---

### Q6: Walk me through entity CRUD with proper access checks.

**Tags:** entity-api, access
**Time:** 8 min

```php
$storage = $this->entityTypeManager->getStorage('node');

// Read
$node = $storage->load($id);
if (!$node?->access('view', $account)) {
  throw new AccessDeniedHttpException();
}

// Query (with access)
$query = $storage->getQuery()
  ->accessCheck(TRUE)
  ->condition('type', 'article')
  ->condition('status', 1)
  ->sort('created', 'DESC')
  ->range(0, 10);
$ids = $query->execute();

// Update
$node->set('title', 'New title');
if ($node->access('update', $account)) {
  $node->save();
}

// Delete
$nodes = $storage->loadMultiple($ids);
$storage->delete(array_filter($nodes, fn($n) => $n->access('delete', $account)));
```

Senior signals:

- `accessCheck(TRUE)` *explicitly* — required since Drupal 9.2.
- Loading via `loadMultiple` for batches, not in a loop.
- Using `$entity->access('op', $account)` rather than reinventing
  permission checks.
- Knowing that `$storage->delete()` takes an array, not a single entity.

**References:**
- <https://www.drupal.org/docs/drupal-apis/entity-api/entity-access-api>
- <https://www.drupal.org/node/3201242>

---

### Q7: What is a config schema and what happens if it's missing?

**Tags:** configuration, schema
**Time:** 6 min

Config schema (`config/schema/MODULE.schema.yml`) declares the type and
shape of each top-level config object your module owns. It powers:

- The translation UI (knows which strings are translatable).
- Typed data on `$config->getCacheableMetadata()`.
- `KernelTestBase`'s strict-schema checks — tests fail if a value isn't
  declared.

If missing or wrong:

- Config translation does not work.
- KernelTestBase / functional tests throw `SchemaIncompleteException`.
- Some contrib modules that rely on typed config break in subtle ways.

Senior expectation: when you add a config field, you update the schema.
If a Drupal upgrade complains about schema, you fix it, not silence it.

**References:**
- <https://www.drupal.org/docs/drupal-apis/configuration-api/configuration-schemametadata>

---

### Q8: When do you write `hook_update_N` vs `hook_post_update_NAME`?

**Tags:** updates, deployment
**Time:** 6 min

- **`hook_update_N`** runs before the container is fully rebuilt. It is
  for *low-level* schema changes: adding tables, altering columns,
  manipulating raw data via the `Schema` API. You should *not* use
  entities or services in `update_N` because the container may not be
  in a consistent state.
- **`hook_post_update_NAME`** runs after `update_N` and after the
  container has been rebuilt. It is the right place to: save entities,
  update configuration via `ConfigFactory`, invoke services, batch over
  rows.

Symptom of doing it wrong: an `update_N` that calls
`Drupal::entityTypeManager()` and produces "WSOD during updates".
Convert it to `post_update_NAME`.

**References:**
- <https://www.drupal.org/docs/drupal-apis/update-api>

---

### Q9: Talk me through the migrate API.

**Tags:** migrate
**Time:** 10 min

Three pluggable pieces, all configurable in a migration YAML:

1. **Source plugin** — `source:` block. Iterates rows from somewhere
   (SQL, CSV, JSON, XML, REST). Returns one row at a time as an
   associative array.
2. **Process plugins** — `process:` block. A pipeline per destination
   field. Built-in process plugins: `get`, `default_value`,
   `static_map`, `migration_lookup`, `concat`, `explode`, `callback`,
   `entity_generate`. You write custom ones when none fits.
3. **Destination plugin** — `destination:` block. Receives processed
   rows and creates entities or config. Most common:
   `entity:NODE_TYPE`, `entity:user`, `config`.

Stateful bits:

- `id_map` table tracks source-id → destination-id, so
  `migration_lookup` can reference earlier migrations.
- `migration_dependencies` ensures ordering.

Tooling: `drush migrate:status`, `migrate:import`, `migrate:rollback`,
`migrate:reset-status`. For incremental imports, use `track_changes`
and high-water mark.

A senior should be able to mention `migrate_plus` for grouping
migrations and `migrate_tools` for the drush commands historically (now
mostly merged into core / drush).

**References:**
- <https://www.drupal.org/docs/drupal-apis/migrate-api>

---

### Q10: Write me the skeleton of a custom migrate process plugin.

**Tags:** migrate, plugins
**Time:** 6 min

```php
namespace Drupal\mymodule\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

#[\Drupal\migrate\Attribute\MigrateProcess(id: 'split_full_name')]
final class SplitFullName extends ProcessPluginBase {

  public function transform(
    $value,
    MigrateExecutableInterface $migrate_executable,
    Row $row,
    $destination_property,
  ): array {
    if (!is_string($value) || $value === '') {
      return ['', ''];
    }
    [$last, $first] = array_pad(array_map('trim', explode(',', $value, 2)), 2, '');
    return [$first, $last];
  }

  public function multiple(): bool {
    return TRUE;
  }
}
```

Notes a senior would surface:

- `multiple(): true` is needed when returning an array that should be
  distributed to multiple destination fields via `sub_process` /
  process pipeline.
- Don't forget the attribute (or annotation in older code).
- Always be defensive about input shape; migrate sources are messy.

**References:**
- <https://www.drupal.org/docs/drupal-apis/migrate-api/migrate-process-plugins>

---

### Q11: What is a lazy builder and when do you use one?

**Tags:** render-api, caching, performance
**Time:** 7 min

A `#lazy_builder` is a render array node with `#lazy_builder => ['callable', [args]]`
and `#create_placeholder => TRUE`. The renderer replaces it with a
placeholder during the initial render, then invokes the callable later
to fill in the placeholder.

Why: the parent render array can be cached without contamination from
the highly-variable child. Common targets: "user account links", "cart
count", "personalized greeting" — fragments with cache contexts the
parent does not want to inherit.

In Drupal 10+, lazy builders are first-class. They participate in
BigPipe streaming.

Pitfalls:

- The callable must be a method on a service or a static. Closures don't
  work because they aren't serializable across the placeholder boundary.
- Arguments must be scalar (or arrays of scalars) — no entity objects.

**References:**
- <https://www.drupal.org/docs/drupal-apis/render-api/auto-placeholdering-and-lazy-builders>

---

### Q12: How does JSON:API authentication usually look on a real project?

**Tags:** api, auth
**Time:** 6 min

Three patterns:

1. **Cookie auth (same-origin)** — JSON:API uses the regular Drupal
   session cookie. Good for SPAs served from the same Drupal site.
   Requires CSRF token (`X-CSRF-Token` from `/session/token`).
2. **OAuth2 (`simple_oauth`)** — token-based, suitable for separate
   frontend domains, mobile apps, server-to-server.
   `client_credentials` for service accounts; `authorization_code` +
   PKCE for human users.
3. **API key** — for trusted internal callers only. Cheap, no
   refresh story.

The senior signal is "I don't enable basic auth on JSON:API in
production". Bonus if they mention `consumers` module and key/secret
rotation.

**References:**
- <https://www.drupal.org/project/simple_oauth>
- <https://www.drupal.org/docs/drupal-apis/jsonapi-api>

---

### Q13: How would you optimize a slow node listing page?

**Tags:** performance, caching
**Time:** 8 min

A structured answer beats individual tricks. Senior framing:

1. **Profile first.** Blackfire, Tideways, or Xdebug profiling against
   a representative request. Don't optimize what you can't measure.
2. **Database** — index missing columns, denormalize hot paths, avoid
   N+1 entity loads (use `loadMultiple`, hydrate related entities).
3. **Render cache** — make sure the listing has stable cache tags. If
   it has `user` context for no reason, fix the source.
4. **Views** — disable expensive output formatters; cache the view via
   "Time-based" or "Tag-based" caching.
5. **Page-level** — Dynamic Page Cache + BigPipe for personalized
   fragments via lazy builders.
6. **Edge** — Varnish or CDN with cache-tag-aware purger
   (`purge` + `varnish_purger` or fastly), so cache tags translate to
   surrogate keys.
7. **Heavy hitters** — Redis/Memcached for the cache bin backend if DB
   is the bottleneck on render cache reads.

Saying "I'd install Redis" without measuring is a junior answer.

**References:**
- <https://www.drupal.org/docs/develop/performance>

---

### Q14: What is Drupal's typed data API and why does it exist?

**Tags:** entity-api, data
**Time:** 6 min

Typed Data is the abstraction underneath fields and entities. Every
piece of structured data in Drupal is a `TypedDataInterface` — wrapping
a primitive, a complex value, or a list. It provides:

- A uniform interface for accessing values (`->getValue()`,
  `->setValue()`).
- Constraint validation (Symfony Validator integration).
- Computed properties (e.g. `Url::getInternalPath()`).
- Reflection of data shape (Typed Data definitions).

You touch it directly when implementing a custom field type
(`FieldItemBase` extends `TypedData`-aware classes) or when writing
validation constraints. Most day-to-day code reads/writes via Entity
API, which sits on top.

**References:**
- <https://www.drupal.org/docs/drupal-apis/typed-data-api>

---

### Q15: How do you write a custom constraint and validator?

**Tags:** validation, entity-api
**Time:** 7 min

Two classes plus a plugin attribute:

```php
#[Constraint(id: 'UniqueEmail', label: new TranslatableMarkup('Unique email'))]
final class UniqueEmailConstraint extends SymfonyConstraint {
  public string $message = 'The email %email is already taken.';
}

final class UniqueEmailValidator extends ConstraintValidator {
  public function __construct(private readonly EntityTypeManagerInterface $etm) {}

  public function validate($value, SymfonyConstraint $constraint): void {
    $count = $this->etm->getStorage('user')->getQuery()
      ->accessCheck(FALSE)
      ->condition('mail', $value->value)
      ->count()->execute();
    if ($count > 0) {
      $this->context->buildViolation($constraint->message)
        ->setParameter('%email', $value->value)
        ->addViolation();
    }
  }
}
```

Attach via `hook_entity_base_field_info_alter` or directly on a base
field definition with `->addConstraint('UniqueEmail')`.

Senior signals: knowing the constraint plugin discovery path, knowing
to inject services into the validator (not the constraint), knowing
the difference between field-level and entity-level constraints.

**References:**
- <https://www.drupal.org/docs/drupal-apis/entity-api/entity-validation-api/providing-a-custom-validation-constraint>

---

### Q16: Talk me through BigPipe's effect on perceived performance.

**Tags:** performance, render-api
**Time:** 6 min

BigPipe streams the response in chunks: the non-personalized shell
first, each placeholder later. The browser starts parsing HTML, loading
CSS and JS, and rendering the header / footer while Drupal is still
producing the expensive personalized fragments.

User-perceived metrics:

- First Contentful Paint goes from "wait for the slowest fragment" to
  "wait for the shell".
- Time-to-Interactive can improve because critical CSS / JS arrives
  earlier.

It does not reduce total work; it overlaps it with the browser's parse
and paint. It also does not help if every fragment on the page is
uncacheable and expensive — BigPipe is about latency hiding, not
compute reduction.

**References:**
- <https://www.drupal.org/project/big_pipe>

---

### Q17: What testing types does Drupal core provide and when do you use each?

**Tags:** testing
**Time:** 8 min

Four main bases:

1. **`UnitTestBase`** — pure PHPUnit, no container, no DB. For code that
   does not touch Drupal services. Fast.
2. **`KernelTestBase`** — bootstraps a minimal container and a real DB
   (sqlite in memory by default). Use for testing services, hooks,
   entity logic without a full HTTP stack. The right default for most
   module tests.
3. **`BrowserTestBase`** — full Drupal install, HTTP requests via Mink
   + Goutte. Good for permissions, routing, form submission. Slow but
   real.
4. **`WebDriverTestBase`** — Mink + Selenium/Chromedriver. For JS
   behavior, Ajax, drag-and-drop. Slowest, most fragile.

Senior signals:

- Prefer KernelTestBase whenever you can avoid the browser.
- Know about ExistingSite tests (drupal-test-traits) for SaaS / shared-
  hosting style sites where setup is too expensive to repeat.
- Treat WebDriverTestBase as a last resort, especially on CI.

**References:**
- <https://www.drupal.org/docs/automated-testing>

---

### Q18: When would you write a custom cache context?

**Tags:** caching, render-api
**Time:** 6 min

A custom cache context is needed when render cache should vary on
something Drupal core does not already encode. Examples:

- An A/B test bucket assigned per request.
- A user attribute (membership tier) not represented by a role.
- A geo bucket from a geolocation header set by your CDN.

Implementation: a service implementing
`CacheContextInterface` (or `CalculatedCacheContextInterface` for
contexts that take a parameter), tagged `cache.context` in
`services.yml`. Then use it as `'my_module.membership_tier'` in
`#cache.contexts`.

Pitfall: contexts with high cardinality multiply cache entries.
`user` is already a high-cardinality context; don't invent more like
it without thinking about the cache bin growth.

**References:**
- <https://www.drupal.org/docs/drupal-apis/cache-api/cache-contexts>

---

### Q19: What's the difference between config translation and locale translation?

**Tags:** i18n
**Time:** 5 min

- **Locale** — translates `t()` strings in code (UI strings shipped by
  modules and themes). Translations come from translation files on
  localize.drupal.org or custom .po imports. Stored in
  `locales_source` / `locales_target`.
- **Config translation** — translates configuration values authored on
  the site (content type labels, view titles, custom field labels).
  Stored as language-specific config overrides.

Both are needed on a multilingual site. A common bug: a view title
declared in YAML with `label: 'Latest News'` is *config*, not *t()*,
so a `t('Latest News')` translation will not affect it.

**References:**
- <https://www.drupal.org/docs/multilingual-guide>

---

### Q20: How do you handle a slow migrate import on millions of rows?

**Tags:** migrate, performance
**Time:** 7 min

Tactics:

- Use a source that streams (SQL with primary key chunking, JSON line-
  by-line) — not one that loads everything in memory.
- Tune `migrate_plus` `track_changes` so re-runs are incremental.
- Disable entity hooks you don't need for the migration window
  (search indexing, pathauto, metatag generation) and run them in a
  separate post-process pass.
- Run multiple migrations in parallel where they don't depend on each
  other (`drush migrate:import` per migration, in separate processes).
- Tune `BATCH_SIZE` / `--limit` so a single drush invocation finishes
  in a sensible window.
- Profile a single batch. If the bottleneck is on entity save, look at
  field hooks; if it's on the source, look at the source query.

Senior signal: they mention measuring before tuning.

**References:**
- <https://www.drupal.org/docs/drupal-apis/migrate-api/migrate-performance>

---

### Q21: Walk me through a `#cache.tags` invalidation story.

**Tags:** caching
**Time:** 7 min

When an entity is saved, Drupal invalidates its cache tags via
`Cache::invalidateTags([$entity->getCacheTagsToInvalidate()])`. The
default node returns `node:42` plus `node_list` and the bundle list.

Anywhere that render array carried `#cache.tags => ['node:42']` is now
stale. The render cache itself keys on tags-as-version (the cache_tags
table tracks an integer invalidation counter per tag). On read, the
entry is compared to current counters; if any tag is newer, the entry
is invalid.

This is why bubbling matters: a node teaser deep inside a view's
render array bubbles its `node:42` up; if the article is unpublished,
the entire view's cache entry becomes stale.

A senior should be able to explain why a Cache Tag Purger (varnish,
cloudflare) is required for edge caching to invalidate in sync.

**References:**
- <https://www.drupal.org/docs/drupal-apis/cache-api/cache-tags>

---

### Q22: Compare hook implementations in `.module` files vs OOP hooks in Drupal 11.

**Tags:** drupal-11, hooks
**Time:** 6 min

Drupal 11 introduced OOP hooks via `#[Hook]` attributes on methods of a
class in `src/Hook/`. The mechanism is the same — the module system
discovers them — but the location is a class, not a global function.

Benefits:

- Hooks become regular methods, with DI via constructor.
- Testable with PHPUnit without a full bootstrap.
- IDEs and static analysis tools handle them as regular methods.
- The `.module` file shrinks to "include only what you must".

Procedural hook implementations still work and will for a long time
(deprecation will be gradual). New code in 2026 should prefer OOP
hooks for non-trivial logic.

**References:**
- <https://www.drupal.org/node/3442349>

---

### Q23: What does `accessCheck(TRUE)` actually do on an entity query?

**Tags:** entity-api, access, security
**Time:** 6 min

It adds query tags (`entity_query_access`,
`{$entity_type}_access`) to the underlying SQL query. The entity type's
access handler — and any module subscribing to
`hook_query_TAG_alter` — appends conditions restricting rows the
current user can `view`.

For nodes specifically, it integrates with the `node_access` table
(grants system).

Since Drupal 9.2, you must explicitly call `accessCheck(TRUE)` or
`accessCheck(FALSE)` — there is no default. Passing neither throws
`QueryException`. The "FALSE" path is the "admin / cron / migrate"
escape hatch and must be used carefully.

A senior should know that the access check happens at query time
(filtered SQL), not after-load filtering. Big difference for pagination.

**References:**
- <https://www.drupal.org/node/3201242>

---

### Q24: What is a Drupal recipe and how does it differ from an install profile in 2026?

**Tags:** recipes, deployment
**Time:** 6 min

A recipe is a directory with `recipe.yml` declaring:

- Modules to install.
- Config to import (with optional `actions:` blocks to mutate existing
  config).
- Content to create.
- Other recipes to apply first.

Differences from an install profile:

- **Plural, not singular.** A site has exactly one install profile, but
  any number of recipes can have been applied over its lifetime.
- **Applied at any time**, not just at install. You can `drush recipe`
  on a five-year-old site.
- **Idempotent**: applying the same recipe twice should not change
  anything the second time.
- **Composable**: recipes can depend on each other.

Use cases: shipping a vertical product feature (events, blog, support
center), encoding an editorial UX preset, distributing a reusable
content model across many sites.

**References:**
- <https://www.drupal.org/docs/extending-drupal/drupal-recipes>

---

### Q25: How would you implement a custom rate limiter for a JSON:API endpoint?

**Tags:** api, performance, security
**Time:** 7 min

Two reasonable answers:

1. **At the edge** — Cloudflare, Varnish, nginx, or your API gateway
   already do rate limiting per IP / per token. Use it whenever
   possible.
2. **In Drupal** — an event subscriber on `KernelEvents::REQUEST` that
   keys on consumer + endpoint, increments a counter in a `cache.data`
   bin or Redis with TTL, and returns `429 Too Many Requests` if over
   threshold. `flood` service (`Drupal::flood()`) is built for this
   pattern and saves you from rolling your own.

A senior should propose option 1 first and 2 only when there is no
edge. Bonus: they mention sliding windows vs fixed windows.

**References:**
- <https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Flood>

---

### Q26: When do you use `KernelTestBase` over `BrowserTestBase`?

**Tags:** testing
**Time:** 6 min

KernelTestBase whenever the unit of behavior can be exercised without
HTTP: services, hooks, entity logic, plugin behavior, validation.

BrowserTestBase only when the test needs the full request/response
loop: routing + permissions, multi-step form submission, theming
output, admin UI flows.

Time difference is dramatic — a KernelTestBase test runs in 50–500ms,
a BrowserTestBase test in 5–30s. On a large suite, that decides
whether developers run tests locally at all.

Senior signal: a willingness to refactor code so it is testable at
kernel level (extracting logic out of controllers into services).

**References:**
- <https://www.drupal.org/docs/automated-testing/phpunit-in-drupal>

---

### Q27: Explain the role of the `config_split` module on a real project.

**Tags:** configuration, environments
**Time:** 6 min

`config_split` lets you maintain environment-specific config sets
without duplicating the full config tree. You declare splits ("dev",
"prod") each with:

- A `folder` — where split config is stored.
- `module:` lists of modules to include/exclude per split.
- `complete_split_list:` of config items that exist only on this
  environment.

At runtime, `config_split` reads the active environment (from
`settings.local.php` or env var), composes the merged config, and
serves it. Exports / imports respect the split.

Typical use: keep `devel`, `views_ui`, `stage_file_proxy` in the dev
split, keep `simple_oauth` keys / `purge` config in the prod split.

A senior should know about `config_split_status` (override status of
modules) as a lighter-weight alternative for the simplest cases.

**References:**
- <https://www.drupal.org/project/config_split>

---

### Q28: How does `decoupled` / headless Drupal change the answer to "where does rendering happen"?

**Tags:** architecture, headless
**Time:** 7 min

In a coupled Drupal site, Drupal renders HTML. In a headless setup,
Drupal serves data (JSON:API or GraphQL) and a separate client (Next.js,
Nuxt, Astro, mobile app) renders.

What you lose by going headless:

- Render API / Twig / SDC do not run for the user.
- Layout Builder, Views displays, page caching are all "your problem"
  on the frontend.
- BigPipe is irrelevant.

What you gain:

- Decoupled performance budgets (frontend cached at the edge, Drupal
  serves cold data).
- Multi-client (web + mobile + screens) from one content backend.
- Frontend team can move at JS pace.

A senior should be able to discuss the *progressive decoupling* middle
ground — keeping the editor UX and Drupal-rendered admin while the
public site is headless.

**References:**
- <https://www.drupal.org/about/strategic-initiatives/decoupled>

---

### Q29: When do you reach for `hook_entity_type_alter` and what's the risk?

**Tags:** entity-api, alter
**Time:** 5 min

`hook_entity_type_alter` lets a module mutate the definition of an
entity type that another module owns — swap out the access handler,
the form handler, the storage class, the list builder, the route
provider.

The risk: you are altering a contract you do not own. The upstream
module can break your override on any release. You are coupled at the
class-name level.

Use cases that justify it:

- Adding a custom access handler that integrates with your tenancy
  model.
- Swapping in a custom list builder for the admin overview.

Use cases that do not:

- "I want to change the label" — that's `entity_keys` alter at most,
  or you should be using config.
- "I want to add a field" — that's `hook_entity_base_field_info` or
  configuration field.

**References:**
- <https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Entity%21entity.api.php>

---

### Q30: What is the role of cache tags vs cache contexts vs max-age?

**Tags:** caching
**Time:** 6 min

- **Cache tags** — what invalidates this entry. Imperative, event-driven.
  When `node:42` is saved, all caches tagged `node:42` become stale.
- **Cache contexts** — what makes this entry vary. Declarative, request-
  axis-driven. `user.permissions` means "store a different variant per
  permission set".
- **max-age** — how long this entry is valid in absolute time.
  `Cache::PERMANENT` is the default; `0` means uncacheable.

Combine them as needed. A node teaser has tags `node:42`, contexts
`user.permissions` (for view access), max-age permanent (it doesn't
expire on time; it expires on save).

The render system bubbles all three up. Senior expectation: knowing
that an uncacheable child does not "poison" the parent if the parent
auto-placeholders, but does if it doesn't.

**References:**
- <https://www.drupal.org/docs/drupal-apis/render-api/cacheability-of-render-arrays>

---

### Q31: How do you make a deprecated function or service in a custom module without breaking dependents?

**Tags:** deprecation, api-design
**Time:** 6 min

Pattern:

1. Annotate with `@deprecated in 2.x.x and is removed from 3.0.0. Use ...`
2. Trigger `@trigger_error(... , E_USER_DEPRECATED)` at the top of
   the deprecated function / method.
3. Document the replacement in the deprecation message.
4. Add a row to your module's `MODULE.deprecations.yml` if you ship
   one; mention it in `CHANGELOG.md`.

For services: keep the service id in `services.yml` and route it to
a new class via `class:` alias, with a deprecation message via
`deprecated: ...` key.

Senior signal: they distinguish between API deprecation (caller-facing,
needs `@trigger_error`) and internal refactor (no caller-facing
change, no deprecation needed).

**References:**
- <https://www.drupal.org/core/deprecation>

---

### Q32: How would you debug a "Configuration import failed" on a deploy?

**Tags:** debugging, deployment, configuration
**Time:** 7 min

Step-by-step:

1. Read the error message. The exception usually names the config
   entity (`field.field.node.article.field_foo`) and the property that
   conflicts.
2. Diff `config/sync/` against the live site:
   `drush config:status` lists changed/missing items.
3. Common cause 1: a module is required by the imported config but
   not enabled (`core.extension.yml` not updated). Fix by enabling and
   re-exporting.
4. Common cause 2: schema mismatch. The new config has a property the
   schema does not know about, or vice versa. Update the schema.
5. Common cause 3: a config entity references something that no longer
   exists (a missing role, a missing field, a missing taxonomy
   vocabulary). Restore or re-create.
6. Common cause 4: a `hook_update_N` should have run before `cim` but
   didn't. Verify deploy order is `updb` then `cim`.

If the import is genuinely partially-applied, `drush config:import` is
idempotent — fix the cause, re-run.

**References:**
- <https://www.drupal.org/docs/configuration-management/troubleshooting-the-configuration-system>

---

### Q33: When should a custom module hook into `hook_cron` vs use the queue API?

**Tags:** queues, cron
**Time:** 6 min

- `hook_cron` runs once per cron pass on the request thread. Fine for
  housekeeping tasks that are small and predictable (purge a counter,
  rebuild an index, send a digest).
- Queue API runs items individually, each in its own (short) request.
  Suited for work that can be sliced into chunks, retried on failure,
  and parallelized. Backed by `database`, but pluggable to Redis,
  RabbitMQ, SQS.

If a `hook_cron` implementation takes more than a few seconds, convert
it to a queue. The queue worker can be invoked by cron (`drush
queue:run`), by a systemd timer, or by a long-running consumer.

**References:**
- <https://www.drupal.org/docs/drupal-apis/queue-api>

---

### Q34: Outline an automated testing strategy for a mid-size Drupal site.

**Tags:** testing, strategy
**Time:** 8 min

A reasonable shape:

- **PHPStan-drupal** in CI on every PR, level 5–7. Catches a huge class
  of bugs for free.
- **Drupal Coder + PHPCS** for style — non-blocking but reported.
- **Unit tests** for pure logic helpers and services (PHPUnit, no
  bootstrap).
- **Kernel tests** for entity logic, custom plugins, hooks, services
  with the container. The bulk of the suite.
- **Functional tests** (BrowserTestBase) for critical user journeys
  only: login, checkout, content publish, admin access. Keep this
  small.
- **WebDriver tests** (Chromedriver via Selenium) only when JS
  behavior is the SUT. Smoke set only.
- **Visual regression** (Percy, BackstopJS) for theme work.
- **Lighthouse / accessibility** in CI for marketing pages.

Senior framing includes: test pyramid, time budget per category, what
fails the build vs what just reports.

**References:**
- <https://www.drupal.org/docs/automated-testing>

---

### Q35: Walk me through securely exposing a contributor-friendly form to anonymous users.

**Tags:** security, forms
**Time:** 7 min

Concerns to address out loud:

- **CSRF**: standard Drupal forms have CSRF tokens automatically; raw
  REST endpoints need them explicit.
- **Spam**: honeypot (`honeypot` module) is cheap and friction-free;
  captcha (`captcha` / `recaptcha`) is heavier but more robust.
- **Validation**: server-side first, always. Treat the client as
  hostile.
- **Rate limit**: `flood` service or edge limits per IP.
- **Storage**: if submissions go into entities, use a custom entity
  type with a clean schema and access handler — not a generic node
  bundle that admins might forget is anonymous-writable.
- **Notifications**: don't email arbitrary strings rendered as HTML to
  staff inboxes — use a sanitizing template.
- **Audit**: log every submission with the IP and a fingerprint.

Saying "I'd use Webform" is not wrong, but the senior signal is being
able to enumerate the threats and the mitigations on a blank slate.

**References:**
- <https://www.drupal.org/docs/security-in-drupal>
