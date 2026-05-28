# Middle — Drupal interview questions

Target audience: 2–4 years of Drupal experience. Candidates should know the
entity and plugin systems at a working level, have used services and
dependency injection at least lightly, and be comfortable with the config
deployment workflow on a real project.

This is where "I can build a site with the UI" should be transitioning into
"I understand the framework underneath the UI".

---

### Q1: Walk me through Drupal's entity API at a high level.

**Tags:** entity-api, fundamentals
**Time:** 7 min

Three layers a candidate should be able to name:

1. **Entity type** — a class (e.g. `Node`, `User`, `Taxonomy term`) annotated
   or `#[ContentEntityType]`-attributed, registered with the entity type
   manager. Defines storage, access, view builder, list builder, form
   handlers.
2. **Bundle** — a sub-grouping of an entity type, used to share base storage
   but vary fields. Content types are bundles of `node`; vocabularies are
   bundles of `taxonomy_term`.
3. **Field** — base fields are declared on the class (`baseFieldDefinitions`),
   configurable fields are attached through field config entities (`field
   storage` + `field config`).

You read and write via the entity type manager
(`Drupal::entityTypeManager()->getStorage('node')->load(123)`), not via
direct SQL.

**References:**
- <https://www.drupal.org/docs/drupal-apis/entity-api>

---

### Q2: What is the difference between a base field and a configurable field?

**Tags:** entity-api, fields
**Time:** 5 min

Base fields are defined in PHP on the entity class via
`baseFieldDefinitions()`. They exist on every bundle of that entity type
(e.g. every node has `title`, `uid`, `created`, `status`). Changing them
requires code + an update hook.

Configurable fields are added through the UI or via `field.storage.*` and
`field.field.*.*.*.yml` config. They can vary per bundle (Articles have a
`field_tags`, Pages do not). They are exported as configuration.

Rule of thumb: invariant data that belongs to the entity by definition →
base field. Editor-managed data that varies by bundle → configurable.

**References:**
- <https://www.drupal.org/docs/drupal-apis/entity-api/working-with-content-entities>

---

### Q3: What is the plugin system, briefly?

**Tags:** plugins, fundamentals
**Time:** 6 min

A plugin is a swappable, discoverable piece of behavior — blocks, field
formatters, field widgets, views handlers, condition plugins, migrate
process plugins, etc. The pattern:

- A **plugin manager** service discovers plugin classes.
- A **plugin definition** (annotation, YAML, attribute, or hook) declares
  what the plugin is.
- A **plugin instance** is created by the manager when needed, often via
  `createInstance($id)`.

Discovery in Drupal 11 is via PHP attributes (`#[Block(id: 'my_block', ...)]`)
on classes in `src/Plugin/Block/`. Older code still uses annotations.

**References:**
- <https://www.drupal.org/docs/drupal-apis/plugin-api>

---

### Q4: How does dependency injection work in a Drupal module, at the level you use it?

**Tags:** services, di
**Time:** 6 min

Expected answer for a middle:

- Define a service in `MODULE.services.yml`: id, class, arguments
  referencing other service ids.
- The container resolves arguments at instantiation, passes them to the
  constructor.
- In a controller / form / block, get the service via
  `\Drupal::service('id')` (procedural) — or, properly, via the static
  `create()` method (`ContainerInjectionInterface` or
  `ContainerFactoryPluginInterface`) so the dependencies are explicit and
  the class is testable.

Senior candidates will be expected to articulate constructor vs setter
injection, factories, and why `\Drupal::service()` should be a last
resort. Middle candidates should at least know that injection beats
service location.

**References:**
- <https://www.drupal.org/docs/drupal-apis/services-and-dependency-injection>

---

### Q5: Describe the config management workflow on a project with multiple environments.

**Tags:** configuration, deployment, workflow
**Time:** 6 min

Common answer:

1. Developer makes a change in the UI on local.
2. `drush cex` writes it to `config/sync/`.
3. Commit the YAML, open PR.
4. CI runs `drush cim` on a build environment to verify it imports cleanly.
5. After merge, the deploy pipeline runs `composer install` → `drush updb`
   → `drush cim` → `drush cr` on dev / stage / prod.

Common nuances mentioned by good middles:

- Use `config_split` (or core's Configuration overrides) to keep `devel`
  / `stage_file_proxy` enabled on dev but not prod.
- Sometimes use `config_ignore` to allow editors to change specific
  config entities (e.g. site name, simple_oauth keys) without `cim`
  reverting them.

**References:**
- <https://www.drupal.org/project/config_split>
- <https://www.drupal.org/project/config_ignore>

---

### Q6: When do you use Paragraphs vs Layout Builder?

**Tags:** site-building, contrib, judgment
**Time:** 5 min

Both let editors compose pages from typed components. The trade-off:

- **Paragraphs** is *content-modeled* layout — paragraphs are entities,
  fielded like nodes, multilingual via content translation, queryable
  via Entity Query, easy to render in custom contexts (cards, JSON:API,
  search results).
- **Layout Builder** is *display-modeled* layout — it stores a layout
  per entity (or per bundle as default), with sections and blocks. It is
  closer to a page builder mental model and integrates with Layout
  Plugins.

A typical middle answer: "If the structured content is supposed to be
re-rendered outside the page (cards on a landing page, JSON:API, mobile
app), Paragraphs. If editors are designing layouts and the content is
mostly one-off, Layout Builder."

**References:**
- <https://www.drupal.org/docs/contributed-modules/paragraphs>
- <https://www.drupal.org/docs/contributed-modules/layout-builder>

---

### Q7: What does the Token module give you and when do you need it?

**Tags:** contrib, site-building
**Time:** 4 min

Token (now mostly in core, with the contrib `token` module providing the
browser UI and extra tokens) is a templating system used by other modules
to inject variable values into strings: `[node:title]`, `[user:mail]`,
`[current-date:short]`.

You need it whenever a setting takes a string that should be filled at
runtime with context-specific values: pathauto patterns, metatag
patterns, redirect destinations, webform email subjects, rules-style
config.

**References:**
- <https://www.drupal.org/project/token>

---

### Q8: How does Pathauto work and what does it not do?

**Tags:** contrib, urls
**Time:** 4 min

Pathauto subscribes to entity hooks and generates an alias from a
configurable token pattern on entity save (e.g. `articles/[node:created:custom:Y]/[node:title]`).
The alias is stored by Drupal's core path alias system (a content entity
called `path_alias`).

It does *not* do redirects from old to new — that is the Redirect
module's job. When the title of a node changes, Pathauto generates a new
alias; Redirect (configured to listen) creates a 301 from the old one.

**References:**
- <https://www.drupal.org/project/pathauto>
- <https://www.drupal.org/project/redirect>

---

### Q9: What is the Metatag module for?

**Tags:** contrib, seo
**Time:** 3 min

Metatag manages HTML head tags — `title`, `description`, Open Graph,
Twitter cards, canonical, robots — per entity bundle and per individual
entity. It uses Token for default patterns and exposes a field
(`field_metatag` or similar) for per-node overrides.

In 2026 it is still the standard answer for SEO meta on a Drupal site.

**References:**
- <https://www.drupal.org/project/metatag>

---

### Q10: When would you use Webform vs a custom form?

**Tags:** contrib, forms, judgment
**Time:** 5 min

Webform if: the form is editor-configured, submissions need to be
viewable in the admin, handlers (email, REST, Salesforce) need to be
configurable per submission. Contact forms, event registration, surveys,
job applications.

Custom form if: the form is part of an application workflow that depends
on entity state, uses custom validation logic across multiple steps, or
needs to do something Webform's handler API would have to be patched to
do.

A bad middle answer is "always Webform" or "always custom". A good one
shows judgment about who maintains the form definition (editors vs
developers).

**References:**
- <https://www.drupal.org/project/webform>

---

### Q11: Explain Drupal's caching layers from the inside out.

**Tags:** caching, performance
**Time:** 8 min

A middle-level answer should at least name:

- **Render cache** — caches the output of cacheable render arrays, keyed
  by cache contexts and invalidated by cache tags.
- **Dynamic Page Cache** — caches authenticated-user pages (excluding
  uncacheable fragments).
- **Page Cache** — caches whole pages for anonymous users.
- **Cache bins** — `default`, `render`, `dynamic_page_cache`, `data`,
  `entity`, `discovery`, `bootstrap`, `menu`, `static`. Each can use a
  different backend.
- **Cache backends** — database by default, often replaced with Redis or
  Memcached in production.

Senior depth adds cache tag bubbling, max-age handling, BigPipe,
placeholder strategy, varnish vs CDN considerations.

**References:**
- <https://www.drupal.org/docs/drupal-apis/cache-api>

---

### Q12: How would you build a view programmatically for a "Latest news" block?

**Tags:** views, render-api
**Time:** 5 min

Two answers, both valid:

1. **YAML approach** — build the view in the UI, export config, ship
   `views.view.latest_news.yml` in `config/install/` of a custom module.
   This is the maintainable path 90% of the time.

2. **Code approach** — `Views::getView('latest_news')->buildRenderable('block_1')`
   from a controller or block plugin, attach the result to a render
   array. Useful when the view's display arguments must be set
   dynamically.

A middle who only knows option 1 is fine; a middle who only knows
option 2 is suspicious (probably skipped learning the config workflow).

**References:**
- <https://www.drupal.org/docs/contributed-modules/views>

---

### Q13: What is a custom block (`Block` plugin) vs a custom block content type?

**Tags:** plugins, blocks
**Time:** 5 min

- A **Block plugin** is PHP code: a class in `src/Plugin/Block/`, with
  `build()` returning a render array. Used for system blocks, derived
  blocks, anything where you need code to decide what to render.
- A **custom block type** (`block_content` bundle) is editor-facing
  content: fielded, reusable, placeable via Block Layout. Used when you
  want editors to author the block content but a developer to place it
  in regions.

They are not in competition — a custom block plugin can render a
custom block content entity.

**References:**
- <https://www.drupal.org/docs/drupal-apis/block-api/block-api-overview>

---

### Q14: What is a Single Directory Component (SDC), in one minute?

**Tags:** theming, sdc
**Time:** 4 min

SDC, stable since Drupal 10.3, lets a theme or module ship a component
as a single directory: `component.component.yml` (schema), `component.twig`
(template), `component.css`, `component.js`, optionally a stories file.
Drupal autodiscovers them and you reference them in Twig with
`{% include 'theme:component-name' %}` or in render arrays with
`#type => 'component'`.

It is the closest Drupal has come to a first-class component model,
and it works well with Storybook via the contrib SDC Storybook module.

**References:**
- <https://www.drupal.org/docs/develop/theming-drupal/using-single-directory-components>

---

### Q15: How do you implement a custom REST endpoint in Drupal 10/11?

**Tags:** api, http
**Time:** 6 min

Three reasonable answers:

1. **JSON:API + filters** — for entity CRUD, JSON:API already exposes
   everything. Often the right answer.
2. **Controller + route** — for non-entity custom logic. Define
   `MODULE.routing.yml` with a path, declare a controller method, return
   a `CacheableJsonResponse`. Cheapest path for read-only endpoints.
3. **REST resource plugin** — `src/Plugin/rest/resource/`. Older
   pattern, still works, useful when you want the auth and serialization
   wiring from `rest` module for free.

For Drupal 10+, options 1 and 2 are usually preferred over 3.

**References:**
- <https://www.drupal.org/docs/drupal-apis/jsonapi-api>
- <https://www.drupal.org/docs/drupal-apis/routing-system>

---

### Q16: What is the difference between `hook_form_alter` and `hook_form_FORM_ID_alter`?

**Tags:** forms, hooks
**Time:** 3 min

`hook_form_alter($form, $form_state, $form_id)` runs for every form on
the site. You inspect `$form_id` to decide whether to act. Cheap to
write, more expensive at runtime.

`hook_form_FORM_ID_alter($form, $form_state, $form_id)` only runs for
that specific form. Drupal does the dispatch for you. Always prefer this
when you only care about one form.

**References:**
- <https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Form%21form.api.php/function/hook_form_alter>

---

### Q17: What does `_format=json` in a URL do?

**Tags:** api, http
**Time:** 3 min

It is Drupal's content negotiation hint. Routes can declare which
response formats they support via `requirements: _format: 'json|xml'`.
When the client passes `?_format=json` (or an `Accept: application/json`
header), the routing system picks the json-capable controller / view
display.

The standard JSON:API endpoint uses this for the `?_format=api_json`
variants in older code.

**References:**
- <https://www.drupal.org/docs/drupal-apis/routing-system>

---

### Q18: When would you reach for a custom field type vs a field formatter vs a field widget?

**Tags:** fields, plugins
**Time:** 5 min

- **Field type** — when the data shape is not represented by any
  existing field type. E.g. a typed pair of `lat/lng` values that should
  be stored together in two columns.
- **Field widget** — when the data is fine in an existing field type but
  editors need a different input UI (e.g. a slider on a number field).
- **Field formatter** — when the storage is fine but the rendered output
  should differ (e.g. a date formatter that shows "2 days ago").

90% of the time the right answer is formatter or widget, not a new
field type.

**References:**
- <https://www.drupal.org/docs/drupal-apis/entity-api/fieldtypes-fieldwidgets-and-fieldformatters>

---

### Q19: What does `hook_entity_presave` give you that `hook_entity_insert` does not?

**Tags:** hooks, entities
**Time:** 4 min

`hook_entity_presave($entity)` runs before the entity is written to
storage. You can mutate fields on `$entity` and they will be saved.

`hook_entity_insert($entity)` runs after the insert. The entity is
already in the database; mutating it requires another `save()` call,
which causes recursion if not guarded.

Rule of thumb: derived values that should be persisted go in
`presave`. Side effects (send an email, enqueue a job, log) go in
`insert` or `update`.

**References:**
- <https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Entity%21entity.api.php>

---

### Q20: How do permissions interact with entity access?

**Tags:** access, security, entity-api
**Time:** 6 min

Three layers, in order of evaluation:

1. **Permissions** — coarse-grained, role-based. "Edit any article" is a
   permission. Granted to roles via the permissions form.
2. **Entity access handlers** — per entity type, decides if a given user
   can `view` / `update` / `delete` a given entity. Default node
   handler checks publication status and node grants.
3. **Node access grants** — a row-level access system for nodes
   specifically. Modules like Group, Domain Access, or content_access
   contribute grants that filter which nodes a user can see.

Permission alone is not sufficient for "show me only my own articles";
that requires either a query alter or a custom access handler.

**References:**
- <https://www.drupal.org/docs/drupal-apis/entity-api/entity-access-api>

---

### Q21: What is `hook_entity_query_alter` (`hook_query_TAG_alter`) used for?

**Tags:** queries, access
**Time:** 5 min

`hook_query_TAG_alter` is invoked by `db_select` / entity query when the
query has been built with `->addTag('TAG')`. Common tags:
`node_access` (filter to only viewable nodes), `entity_query_access`
(generic entity query access), or a custom tag your own service
declares.

You alter the query to add conditions — typically used to enforce
row-level visibility based on the current user's roles or some external
permission system.

**References:**
- <https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Database%21database.api.php/function/hook_query_alter>

---

### Q22: Walk me through wiring up a simple service.

**Tags:** services, di
**Time:** 5 min

1. Create `MODULE.services.yml`:
   ```yaml
   services:
     mymodule.greeter:
       class: Drupal\mymodule\Greeter
       arguments: ['@string_translation', '@logger.factory']
   ```
2. Create `src/Greeter.php` with a constructor that accepts those
   dependencies (typed against interfaces, not concrete classes).
3. From a controller, inject `mymodule.greeter` via `create()` rather
   than calling `\Drupal::service('mymodule.greeter')`.

Bonus: the candidate mentions `LoggerChannelFactoryInterface::get('mymodule')`
instead of injecting a generic logger.

**References:**
- <https://www.drupal.org/docs/drupal-apis/services-and-dependency-injection>

---

### Q23: When do you write an event subscriber vs a hook?

**Tags:** events, hooks
**Time:** 5 min

Symfony events (KernelEvents, entity events in newer Drupal) are the
modern equivalent of some hooks. Hooks remain the right answer when the
extension point only exists as a hook (most entity lifecycle, form
alter, theme registry).

Subscribe to a Symfony event when you need to act on the request /
response cycle — `KernelEvents::REQUEST`, `KernelEvents::RESPONSE`,
`KernelEvents::EXCEPTION`. Hooks have no equivalent for those moments.

**References:**
- <https://www.drupal.org/docs/drupal-apis/events-system>

---

### Q24: How do you make a custom module's configuration translatable?

**Tags:** i18n, configuration
**Time:** 4 min

Three pieces:

1. Declare the config schema in `config/schema/MODULE.schema.yml`. Mark
   user-facing strings with `type: label` (or `type: text`).
2. Enable Configuration Translation core module.
3. Translate via UI at `/admin/config/regional/config-translation`.

Without the schema, the translation UI cannot discover what is
translatable.

**References:**
- <https://www.drupal.org/docs/drupal-apis/configuration-api/configuration-schemametadata>

---

### Q25: What's the difference between content translation and config translation?

**Tags:** i18n
**Time:** 4 min

- **Content translation** — translates content entities (nodes,
  taxonomy terms, custom block content, paragraphs). Each translation
  is a row in the entity's data table.
- **Config translation** — translates configuration strings (content
  type labels, view titles, block labels, custom field labels).
  Translations are stored as language-specific config overrides.

A site usually needs both. Forgetting the config side is a classic
"the language switcher works but the UI is still in English" bug.

**References:**
- <https://www.drupal.org/docs/multilingual-guide>

---

### Q26: What is BigPipe?

**Tags:** performance, render-api
**Time:** 4 min

BigPipe is a core module that streams the page in chunks. The
"non-personalized" outer shell is sent immediately; then each
auth-dependent or expensive placeholder (`#lazy_builder`) is streamed
as its own chunk as soon as it is rendered, replacing a placeholder
the browser already received.

Effect: time-to-first-byte for the chrome of the page becomes the
time-to-first-byte for the whole page. The dynamic bits stop being
the bottleneck.

It works because Drupal's render system already knows how to
auto-placeholder uncacheable fragments.

**References:**
- <https://www.drupal.org/project/big_pipe>

---

### Q27: What is a configuration override and when do you use one?

**Tags:** configuration, environments
**Time:** 5 min

A config override is a runtime mutation of configuration values without
changing the underlying config storage. Two flavors:

- **`$config` array overrides** in `settings.php` — quick, environment
  specific, not portable across modules:
  `$config['system.site']['name'] = 'Dev environment';`
- **Module-provided overrides** via `ConfigFactoryOverrideInterface` —
  reusable, used by modules like `config_split` or `domain_config`.

Overrides are read-only at runtime, they do not affect `drush cim` /
`drush cex` output.

**References:**
- <https://www.drupal.org/docs/drupal-apis/configuration-api/configuration-override-system>

---

### Q28: Have you used Drupal recipes? When would you reach for one?

**Tags:** recipes, deployment
**Time:** 5 min

Drupal recipes (core since 10.3) are reusable units that install
modules, import config, and create content via the recipe applicator.
Compared to profiles, they are not exclusive (multiple recipes can be
applied to one site) and they can be applied at any time, not just at
install.

Use them for:

- A "starter blog" feature pack you want to drop into multiple sites.
- A standard content model your agency reuses (article + author +
  category).
- A reusable editorial UX setup (Pathauto + Metatag + Redirect with
  sensible defaults).

They are not a replacement for custom modules; they are a replacement
for "Notion doc explaining the 14 things to do after install".

**References:**
- <https://www.drupal.org/docs/extending-drupal/drupal-recipes>
