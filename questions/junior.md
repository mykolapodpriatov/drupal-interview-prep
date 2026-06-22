# Junior — Drupal interview questions

Target audience: 0–2 years of Drupal experience. Expect candidates to know
vocabulary, the major moving parts, basic Drush, and the day-to-day mechanics
of building a small site.

A junior is not expected to architect anything or to know plugin internals.
They *are* expected to read documentation, ask sensible follow-up questions,
and not invent answers they do not know.

---

### Q1: What is Drupal, in one sentence?

**Tags:** fundamentals
**Time:** 2 min

Drupal is an open-source PHP content management system and content management
framework — it ships with a usable site-building UI, but its real strength is
that almost every behavior is exposed as an extension point (hooks, plugins,
services, events) so you can model arbitrary content and workflows without
forking core.

A weak answer stops at "it's a CMS like WordPress". A junior should at least
mention extensibility and the fact that it is open source.

**References:**
- <https://www.drupal.org/about>

---

### Q2: What is the difference between a module, a theme, a profile, a distribution, and a recipe?

**Tags:** fundamentals, vocabulary
**Time:** 5 min

- **Module** — adds or alters behavior. Lives in `modules/` or `web/modules/`.
- **Theme** — controls presentation: templates, CSS, JS, libraries. Lives in
  `themes/`.
- **Profile** — an installation profile that defines what gets installed on
  `drush site:install`. There can be only one active per site.
- **Distribution** — a packaged combination of a profile + modules + themes,
  shipped as a single product (e.g. Acquia CMS, Thunder).
- **Recipe** — newer in Drupal 10.3+: a reusable, idempotent "apply" unit
  that installs modules, imports config, and creates content. Unlike a
  profile, a site can apply many recipes over its lifetime.

**Follow-up:** When would you reach for a recipe instead of a profile?

**References:**
- <https://www.drupal.org/docs/extending-drupal>
- <https://www.drupal.org/docs/extending-drupal/drupal-recipes>

---

### Q3: What is a content type and how does it relate to a node?

**Tags:** site-building, entities
**Time:** 3 min

A content type is the *bundle* (template) for the `node` entity. It defines
which fields a node of that bundle has, plus form display, view display, and
some default behavior. A node is an *instance* of that bundle — one row of
content in the system.

Saying "Article is a content type, an individual blog post is a node" is the
short version.

**References:**
- <https://www.drupal.org/docs/user_guide/en/planning-data-types.html>

---

### Q4: What is the difference between a region and a block?

**Tags:** site-building, theming
**Time:** 3 min

A region is a slot in the theme — declared in `THEME.info.yml`, rendered by
the page template. A block is a piece of content placed *into* a region via
Block Layout (or via configuration). Many blocks can share a region, and the
same block can appear in different regions on different themes.

**References:**
- <https://www.drupal.org/docs/user_guide/en/block-concept.html>

---

### Q5: What is Drush and what do you use it for daily?

**Tags:** tooling
**Time:** 3 min

Drush is the command-line shell for Drupal. Day-to-day commands a junior
should know:

- `drush cr` — rebuild cache.
- `drush uli` — generate a one-time login link.
- `drush cim` / `drush cex` — import / export configuration.
- `drush en module_name` / `drush pmu module_name` — enable / uninstall.
- `drush sql:dump` / `drush sql:cli` — database operations.
- `drush updb` — run database update hooks after deploys.

**References:**
- <https://www.drush.org/>

---

### Q6: What is the difference between configuration and content?

**Tags:** fundamentals, deployment
**Time:** 5 min

Configuration is "structural" data — content types, fields, views, roles,
permissions, block placements. It is intended to be authored on one
environment and deployed to others via `drush cex` / `drush cim`.

Content is what editors create — nodes, terms, users, media. It lives in the
database and typically is *not* deployed; it is migrated or seeded.

The line is occasionally fuzzy (e.g. taxonomy terms that drive site
navigation), and that's where `default_content` or recipes come in.

**References:**
- <https://www.drupal.org/docs/configuration-management>

---

### Q7: What is a hook?

**Tags:** fundamentals, extensibility
**Time:** 4 min

A hook is a function with a specific naming pattern that Drupal invokes at
defined extension points. You implement `hook_form_alter()` by writing a
function named `MYMODULE_form_alter()` in `MYMODULE.module`. The module
system discovers it automatically.

Hooks are the original Drupal extension mechanism. Many things that used to
be hooks have moved to events, plugins, or services, but hooks are still
the simplest way to "tap into" a lifecycle moment.

**Follow-up:** Drupal 11 introduced OOP hooks (`#[Hook]` attributes on
class methods). The mechanism is the same; the location is just nicer.

**References:**
- <https://www.drupal.org/docs/develop/creating-modules/understanding-hooks>

---

### Q8: What is Twig and why does Drupal use it?

**Tags:** theming
**Time:** 3 min

Twig is the template engine Drupal uses for all HTML rendering since Drupal
8. It is sandboxed (template files cannot run arbitrary PHP), encourages a
data-vs-presentation split, and provides autoescaping by default — which
removes a whole class of XSS bugs that the old PHPTemplate engine allowed.

**References:**
- <https://twig.symfony.com/doc/3.x/>
- <https://www.drupal.org/docs/theming-drupal/twig-in-drupal>

---

### Q9: What is a basic git workflow on a Drupal project?

**Tags:** tooling, workflow
**Time:** 4 min

Typical answer: feature branches off `main` / `develop`, PRs reviewed by at
least one other developer, CI runs before merge, configuration changes
committed via `drush cex` and not edited by hand, no `vendor/` in the repo
(Composer manages it), `settings.local.php` ignored.

Bonus points if they mention that the `web/sites/default/files/` directory
should not be in git either.

**References:**
- <https://www.drupal.org/docs/develop/git>

---

### Q10: What role does Composer play in a modern Drupal project?

**Tags:** tooling, dependency-management
**Time:** 4 min

Composer is the dependency manager. A modern Drupal site is a Composer
project — `composer.json` declares Drupal core, contrib modules, themes,
patches, and PHP libraries. `composer install` reconstructs `vendor/` and
`web/`. `composer update drupal/core-recommended -W` is how you take a core
security release.

If a candidate suggests downloading modules as zip files from drupal.org and
extracting them into `modules/contrib/`, that's a red flag in 2026.

**References:**
- <https://www.drupal.org/docs/develop/using-composer>

---

### Q11: What is a render array?

**Tags:** render-api, fundamentals
**Time:** 4 min

A render array is an associative PHP array that describes *what* to render,
not *how*. Keys prefixed with `#` are properties the renderer reads
(`#theme`, `#markup`, `#cache`, `#attached`); keys without `#` are children
to render in order.

The renderer walks the tree, resolves theme hooks, applies cache metadata,
and produces a `Drupal\Core\Render\Markup` object. Junior candidates do not
need to know the bubbling algorithm — they just need to know that returning
an array from a controller is normal.

**References:**
- <https://www.drupal.org/docs/drupal-apis/render-api>

---

### Q12: How do you create a new content type?

**Tags:** site-building
**Time:** 3 min

UI path: `/admin/structure/types/add`. Configure name, description, default
fields, then add fields via Manage Fields. Configure form display and view
display.

Code path: write a YAML config file in `config/install/` of a custom module
(`node.type.MACHINE_NAME.yml`) plus the field storage and field instance
YAMLs. Or use a recipe.

A junior who has only ever done it through the UI is fine; just not one who
panics when shown a `node.type.*.yml` file.

**References:**
- <https://www.drupal.org/docs/user_guide/en/structure-content-type.html>

---

### Q13: What is the role of `settings.php`?

**Tags:** configuration
**Time:** 3 min

`settings.php` holds environment-specific bootstrap info: database
credentials, hash salt, trusted host patterns, reverse-proxy settings,
config sync directory path. It is read very early in the request lifecycle,
before the container is built.

Best practice: commit `settings.php` with safe defaults and `include`
`settings.local.php` from it, where `settings.local.php` is gitignored and
contains environment-specific overrides.

**References:**
- <https://www.drupal.org/docs/installing-drupal/step-3-create-settingsphp>

---

### Q14: What is a view (Views module)?

**Tags:** site-building
**Time:** 4 min

Views is a query builder + display builder in core. You define a query
(entity type, filters, sorts, relationships) and one or more displays
(page, block, REST export, feed). The output can use a field list, a
rendered entity, or a custom row template.

Almost every list of content on a Drupal site is a view.

**References:**
- <https://www.drupal.org/docs/user_guide/en/views-chapter.html>

---

### Q15: How do you give the right permissions to a role?

**Tags:** site-building, security
**Time:** 3 min

`/admin/people/permissions` lists every permission contributed by every
enabled module, with roles as columns. Check the boxes for what a role
should be able to do.

The corollary, which a junior should at least intuit: never give the
"administer site configuration" permission to anonymous or to a content
editor role.

**References:**
- <https://www.drupal.org/docs/user_guide/en/user-roles.html>

---

### Q16: What is a media entity and how does it differ from a file?

**Tags:** entities, media
**Time:** 4 min

A file (`file` entity) is the raw upload — a row in the `file_managed`
table pointing at a path. A media entity (`media` entity, with bundles
like Image, Video, Document, Remote Video) wraps a file (or an external
URL) with metadata fields, plus a reusable reference so editors can pick
the same asset twice.

You almost always reference media entities from content types via Entity
Reference, not raw file fields.

**References:**
- <https://www.drupal.org/docs/core-modules-and-themes/core-modules/media-module>

---

### Q17: What does `drush cr` actually do?

**Tags:** caching, tooling
**Time:** 3 min

`drush cr` (cache rebuild) flushes every Drupal cache bin, then rebuilds
the auxiliary caches that the site needs to boot: the service container,
the router, plugin/discovery definitions, and the theme registry. The
content-style caches — render cache, dynamic page cache, page cache,
and the compiled Twig templates — are simply **emptied (invalidated)**,
not rebuilt; they refill lazily on the next request. After a deploy, it
is the safest "make the new code visible" command.

It is not the same as `drush cc all` (which existed in Drupal 7 and is
gone in 10+).

**References:**
- <https://www.drush.org/12.x/commands/cache_rebuild/>

---

### Q18: Where does Drupal store configuration on disk vs in the database?

**Tags:** configuration, deployment
**Time:** 4 min

At runtime, active configuration lives in the `config` table (key/value).
On disk in the repository it lives in `config/sync/` (or wherever
`$settings['config_sync_directory']` points). Exporting (`cex`) writes
the active config to disk; importing (`cim`) reads disk and overwrites
the active config.

This is why "I edited a view in the UI on production" is a deploy
hazard — the next `cim` will overwrite it.

**References:**
- <https://www.drupal.org/docs/configuration-management/managing-your-sites-configuration>

---

### Q19: What is a block and how is it different from a node?

**Tags:** site-building
**Time:** 3 min

A block is a small piece of placed content (login form, recent comments,
a menu, a banner). Blocks can be system-provided (`Block` plugin) or
content blocks (`block_content` entities, fielded like nodes).

Nodes are the primary editorial content of the site and have URLs of
their own. Blocks live inside layouts, not at their own URL.

**References:**
- <https://www.drupal.org/docs/user_guide/en/block-concept.html>

---

### Q20: What is a taxonomy term?

**Tags:** entities, site-building
**Time:** 3 min

A taxonomy term is an entity that lives in a vocabulary. Vocabularies are
bundles of the `taxonomy_term` entity, same idea as content types for
nodes. Terms are typically used for categorization (tags, categories,
hierarchical navigation).

**References:**
- <https://www.drupal.org/docs/user_guide/en/structure-taxonomy.html>

---

### Q21: What is a paragraph (Paragraphs module)?

**Tags:** site-building, contrib
**Time:** 4 min

Paragraphs is a contrib module that adds a `paragraph` entity, used to
build structured "page builder"-style content. Instead of one giant body
field, a node has a Paragraphs reference field, and editors add typed
paragraphs (text, image, two-column, quote, CTA) in any order.

It is the most common alternative to Layout Builder for editorial
flexibility.

**References:**
- <https://www.drupal.org/project/paragraphs>

---

### Q22: What is the difference between `drush updb` and `drush cim`?

**Tags:** deployment, tooling
**Time:** 4 min

`drush updb` runs database update hooks (`hook_update_N`,
`hook_post_update_NAME`) — schema changes, data migrations, anything that
core or contrib modules need to do when their code changes.

`drush cim` imports configuration from `config/sync/` to the database —
changes to content types, views, fields, etc.

Standard deploy order: pull code → `composer install` → `drush updb` →
`drush cim` → `drush cr`. Running them out of order is a common cause of
broken deploys.

**References:**
- <https://www.drupal.org/docs/configuration-management/deploying-configuration-changes>

---

### Q23: What is a library in Drupal (asset library)?

**Tags:** theming, frontend
**Time:** 4 min

A library is a declared bundle of CSS and JS, registered in
`MODULE.libraries.yml` or `THEME.libraries.yml`. It has versions,
dependencies, and CSS grouping (base, layout, component, etc).

You attach libraries to render arrays via `#attached['library']` so they
are only loaded on pages that need them — instead of dumping every
script into every page.

**References:**
- <https://www.drupal.org/docs/develop/theming-drupal/adding-assets-css-js-to-a-drupal-theme-via-librariesyml>

---

### Q24: What is the difference between `print` and `{{ }}` in a Twig template?

**Tags:** theming, twig
**Time:** 3 min

`{{ thing }}` is a Twig print tag — it outputs the value, autoescaped.
`{% ... %}` is a control tag — `if`, `for`, `set`. There is no bare
`print` in Twig syntax; `{{ }}` is the print.

In Drupal-specific Twig: if `thing` is a render array, `{{ thing }}`
calls the renderer on it. If it is a string, it autoescapes and prints.

**References:**
- <https://twig.symfony.com/doc/3.x/templates.html>

---

### Q25: What is a menu in Drupal and what does Menu Link Content do?

**Tags:** site-building
**Time:** 4 min

A menu is an ordered, hierarchical list of links — main navigation,
footer, user menu, etc. Each link is either a "menu link content" entity
(editor-created at `/admin/structure/menu/MENU/add`) or a
`menu_link.definition` discovered from a module's
`MODULE.links.menu.yml`.

The combination lets you ship default menu items in code and let editors
add more in the UI.

**References:**
- <https://www.drupal.org/docs/user_guide/en/menu-concept.html>

---

### Q26: How do you debug "white screen of death" on a Drupal site?

**Tags:** debugging, ops
**Time:** 5 min

Honest answer for a junior:

1. Check `web/sites/default/files/php/twig` is writable and has space.
2. Tail the PHP error log (`php-fpm` log or `error.log`).
3. Check `web/sites/default/files/...` and recent watchdog (`drush
   watchdog:show`).
4. Enable `$config['system.logging']['error_level'] = 'verbose';` in
   `settings.local.php` to render the actual exception instead of
   suppressing it.
5. Disable opcache for a single request if you suspect a stale cache.

Saying "I check error_log first" earns the points; saying "I run `drush
cr` and hope" does not.

**References:**
- <https://www.drupal.org/docs/develop/debugging>

---

### Q27: What is a watchdog entry and how do you read it?

**Tags:** debugging, ops
**Time:** 3 min

`watchdog` is Drupal's structured log channel. Modules log via the
`logger.factory` service, with a channel name and a severity. Entries
are stored by dblog (in the `watchdog` table) and viewable at
`/admin/reports/dblog` or via `drush watchdog:show --count=50
--severity=error`.

On real sites, dblog is usually replaced or supplemented by syslog or
Monolog routing to an external log aggregator.

**References:**
- <https://www.drupal.org/docs/core-modules-and-themes/core-modules/database-logging-module>

---

### Q28: When would you say "this should be a contrib module" vs "this should be a custom module"?

**Tags:** judgment, modules
**Time:** 4 min

Contrib if: the feature is generic (SEO sitemap, redirect, captcha,
webform), well-maintained on drupal.org, and the security team covers
it.

Custom if: the feature encodes your specific business logic, your
content model, your editorial workflow, or anything you would be
embarrassed to publish.

The wrong instinct for a junior is to write a custom module that
duplicates pathauto, redirect, or metatag. The right instinct is to
search drupal.org first.

**References:**
- <https://www.drupal.org/project/usage>
