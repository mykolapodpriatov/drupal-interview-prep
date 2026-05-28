# Lead — Drupal interview questions

Target audience: 7+ years of Drupal experience, with ownership of a site
estate or a multi-team Drupal practice. Questions skew toward architecture,
team practice, dependency / vendor strategy, and trade-off reasoning.

A lead is rarely the person typing code on the critical path. They are the
person whose calls everyone else lives with for the next 18 months. The
questions reflect that.

---

### Q1: You're asked to architect a Drupal-backed site that has 200k daily logged-in users and 2M anonymous. Sketch the architecture.

**Tags:** architecture, performance
**Time:** 15 min

A defensible answer:

- **Edge** — Cloudflare or Fastly with cache-tag-aware purger. Anonymous
  pages cached at the edge, with surrogate keys keyed to Drupal cache
  tags. Anonymous traffic should mostly not hit origin.
- **Origin** — autoscaling PHP-FPM behind nginx, 4–8 instances. Stateless.
  Code from immutable container images, configuration via env vars.
- **Database** — managed Postgres or MySQL with read replicas. Drupal
  database driver split for read/write where applicable, though most
  Drupal traffic shouldn't need it given good caching.
- **Cache** — Redis cluster for the `cache.*` bins and the `flood`
  service. Memcached if Redis is overkill for the volume.
- **Sessions** — Redis-backed sessions.
- **Search** — Solr or Elasticsearch via `search_api`. Not Drupal's
  database search.
- **Media** — S3-backed, with `s3fs` or `flysystem`. CDN-fronted.
- **Queue worker** — separate fleet running `drush queue:run` on
  systemd timers; never let cron-on-request handle queues.
- **Cron** — external scheduler (systemd, k8s CronJob, EventBridge).
  No "run cron on every 100th request" pattern at this scale.
- **Observability** — Sentry / Rollbar for PHP errors, OpenTelemetry
  for traces, Prometheus or Datadog for metrics, ELK / Loki for logs.

A lead should also raise multi-region, RPO/RPO targets, disaster recovery
exercises, and the cost of all of the above.

**References:**
- <https://www.drupal.org/docs/develop/performance>

---

### Q2: Monorepo vs polyrepo for a Drupal estate with 12 sites that share custom modules?

**Tags:** architecture, devops
**Time:** 10 min

Lead-level trade-offs:

- **Monorepo** — every site + every shared module in one git repo.
  Atomic cross-cutting changes, single CI configuration. Cost: every
  developer pulls every site, CI matrix gets large, ownership lines
  blur.
- **Polyrepo with shared library** — each site is its own repo, shared
  modules in a private Composer repository (Satis, Packagist private,
  GitHub Packages). Clean ownership; cross-cutting changes require
  release + bump in each site.
- **Polyrepo with submodules / `composer-merge-plugin`** — middle
  ground. Avoid unless you have a specific reason; submodule UX is
  rough.

For 12 sites with shared modules, I'd usually go polyrepo + private
Packagist + semver on the shared modules. Easier ownership and easier
CI per site. Monorepo wins around 30–50 sites or when a "platform
team" model exists.

**References:**
- <https://www.drupal.org/docs/develop/using-composer/manage-dependencies>

---

### Q3: When does decoupled / headless Drupal stop making sense?

**Tags:** architecture, headless
**Time:** 8 min

Reasons to *not* go headless:

- The team is mostly Drupal devs, the frontend would be a learning
  curve and a hire risk.
- The editor experience matters more than the visitor experience and
  Drupal's render layer already gives you previews / layout / blocks
  for free.
- Content is mostly editorial pages, not application-style flows. The
  upside is small.
- SEO, accessibility, and link previews are critical and you don't
  have JS-rendering crawler budget to spend.

Reasons to go headless:

- Multi-client (web + mobile + screens) from one backend.
- Frontend team is JS-native and Drupal-rendered HTML is friction.
- High interactivity / app-shell pattern.

A lead avoids hype-driven answers ("everyone is going headless"). The
real question is "what is the actual frontend workload and who owns
it".

**References:**
- <https://www.drupal.org/about/strategic-initiatives/decoupled>

---

### Q4: Multisite vs separate Drupal installs?

**Tags:** architecture, multitenancy
**Time:** 8 min

Multisite (the core multisite feature) shares code across sites, each
with its own database. Pros: one deploy, less ops. Cons: any site can
take all sites down with a code bug, can't upgrade independently,
modules can't be selectively enabled per site safely.

Separate installs: full independence, separate codebases or branches.
Pros: failure isolation, per-site upgrade pacing. Cons: more ops, more
deploys, drift between sites.

A pattern that often beats both: shared base distribution (or recipe
set) + separate installs per site that pull in the base via Composer.
You get shared code without shared-fate.

A lead should also surface the "Drupal multisite is half-deprecated
in spirit" point — most agencies have moved to separate installs.

**References:**
- <https://www.drupal.org/docs/multisite-drupal>

---

### Q5: Describe a CI/CD pipeline you would set up for a Drupal project from scratch.

**Tags:** devops, cicd
**Time:** 12 min

Stages on every PR:

1. **Composer validate** + lockfile sanity.
2. **PHPCS** (Drupal coding standards) on changed files.
3. **PHPStan** (drupal/phpstan-drupal) at level 5 minimum.
4. **PHPUnit unit + kernel suite** in parallel, against a real database
   container.
5. **Functional / WebDriver** smoke set against a built site (Docker /
   ddev-style container, with config imported).
6. **Behat** for cross-cutting user journey tests, if the project has
   them.
7. **Security scan** — `composer audit` (drupal/core-security-advisories
   path), plus container scan if shipping Docker images.
8. **Build artifact** — Composer install with `--no-dev`, produce a
   tarball or container image, push to registry.

Deploy stages:

- Auto-deploy to dev on merge to main.
- Manual promote to stage, then prod.
- Each environment runs the same post-deploy sequence:
  `composer install --no-dev` → `drush updb -y` →
  `drush cim -y` → `drush cr -y`. Smoke tests run after.
- Rollback strategy: redeploy the previous artifact + DB rollback plan
  for `updb` (snapshots before deploy).

A lead should also discuss DB migrations that aren't reversible — and
the discipline that requires (deploy data-migrating updates with a
feature flag separating read and write).

**References:**
- <https://www.drupal.org/docs/develop/development-tools/continuous-integration>

---

### Q6: How do you run a code review culture that scales past 5 developers?

**Tags:** team, process
**Time:** 8 min

A lead-shaped answer covers:

- **Author quality first.** Reviewers are not free; expensive senior
  reviewers especially. A culture that catches its own mistakes before
  review beats a culture that "leans on reviewers".
- **PR size budget.** Anything above ~400 lines should be split or
  paired-reviewed live. Big PRs don't get reviewed; they get nodded
  through.
- **Review SLA.** Acknowledge within X hours, decision within Y.
  Whatever the number, make it explicit.
- **Reviewer rotation.** Don't let a single senior become the
  bottleneck. Pair junior + senior on reviews to spread context.
- **What blocks merge vs what comments.** A small set of "blocking"
  categories (security, data loss, regression) — everything else is
  suggestion.
- **Automate the boring.** PHPCS + PHPStan run in CI, reviewers should
  not be commenting on indentation.

The unspoken signal: a lead who sounds like "I want every PR to go
through me" is *not* what you want.

**References:**
- <https://google.github.io/eng-practices/review/>

---

### Q7: How do you mentor a middle-tier developer toward senior level?

**Tags:** team, mentoring
**Time:** 8 min

Useful talking points:

- **Diagnose, don't prescribe.** Find out what they actually struggle
  with — caching, testing strategy, architecture decisions — before
  recommending courses.
- **Trade actual work for actual responsibility.** Give them tasks
  where the trade-off space is wider than they're comfortable with,
  and review the trade-off, not just the code.
- **Pairing on hard reviews.** Have them review your PRs (yes, the
  lead's PRs) as well as their peers'. Articulating disagreement is
  the senior skill.
- **Tech-lead a sub-project.** A two-week feature where they own the
  architecture and present it to the team.
- **Run debrief on incidents.** When something breaks, walk them
  through what *information* you used to find the problem, not just
  what you fixed.

Lead signal: they distinguish "skills" from "scope". Senior is mostly
about scope.

**References:**
- <https://staffeng.com/>

---

### Q8: What does "performance budget" mean on a Drupal project and how do you enforce it?

**Tags:** performance, process
**Time:** 8 min

Per-page numeric targets:

- Time-to-first-byte < 200ms (cached) / < 800ms (uncached) on origin.
- Largest Contentful Paint < 2.5s on a 4G connection.
- Total payload < 1 MB transferred, < 250 KB JS.
- < 5 origin requests per page after the first paint.

Enforcement:

- Lighthouse CI on a representative sample of pages.
- Custom performance smoke tests in CI hitting representative URLs,
  asserting TTFB.
- A "perf review" gate for PRs that touch listing pages or large
  templates.
- A regular (monthly?) walk-through of the slowest 20 pages from real
  monitoring.

A lead should mention that perf budgets that are not enforced in CI
will drift in 6 months. Pick numbers that are reachable, then defend
them.

**References:**
- <https://web.dev/articles/performance-budgets-101>

---

### Q9: How do you handle accessibility audits and remediation?

**Tags:** accessibility, process
**Time:** 8 min

Practical layers:

- **Automated** — axe-core integrated into PR-level tests, lighthouse
  accessibility score gated in CI. Catches roughly 30% of issues.
- **Manual** — keyboard nav, screen reader (NVDA / VoiceOver), focus
  management. Catches the other 70%.
- **Annual audit** — third-party accessibility audit (WCAG 2.2 AA, or
  EN 301 549 if EU). Generates a remediation backlog.
- **Authoring** — editorial training so editors know about alt text,
  heading order, link text. The CMS can enforce some of this (alt-text
  required, heading-level constraints in CKEditor).

A lead should note that accessibility is *editorial* as much as
*developer-side*. The CMS can lay a trap or block one — and you choose.

**References:**
- <https://www.drupal.org/about/features/accessibility>
- <https://www.w3.org/WAI/standards-guidelines/wcag/>

---

### Q10: How do you build a security culture around Drupal specifically?

**Tags:** security, process
**Time:** 10 min

Concrete practices:

- **Subscribe to the Drupal Security Advisories** (RSS, mailing list,
  drupal/core-security-advisories Composer plugin). Treat SA-CORE
  releases as critical-path.
- **Automated dependency scanning** — `composer audit` on PR + nightly,
  including drupal.org's security advisory feed.
- **SAST** — `phpstan-drupal` with security rules, optionally Psalm.
- **Permission review on every release** — diff `user.role.*.yml`,
  surface anything new.
- **Update windows** — agreed maintenance windows for security
  releases. The team knows that an SA dropped on Wednesday afternoon
  means deploy Thursday.
- **Secret hygiene** — secrets in env / vault, never in git, never in
  config exports. `.gitignore` checked.
- **Threat modeling** for any new exposed endpoint. CSRF, auth, rate
  limit, input validation, logging.

The lead signal: they talk about it as an *ongoing practice*, not a
one-off audit.

**References:**
- <https://www.drupal.org/security>

---

### Q11: Upgrade strategy from Drupal 7 to Drupal 11 in 2026?

**Tags:** upgrades, migration
**Time:** 12 min

Reality check: Drupal 7 entered community-supported end-of-life in
January 2025. Sites still on 7 in 2026 are technical-debt sites.

Recommended path:

1. **Audit** — site age, traffic, value, custom code volume, contrib
   modules. Decide rebuild vs migrate.
2. **If rebuild**: it is almost always faster to do a fresh Drupal 11
   site + a `migrate` pipeline from D7 source to new D11 destination.
   The "upgrade in place from 7 to 8/9/10/11" path is theoretical, not
   practical, in 2026.
3. **If migrate**: stand up Drupal 11 with the target content model.
   Wire D7 source plugins to D11 destinations via `migrate_drupal_ui`
   for a starting point, then refine.
4. **Iterate** — pilot with a representative subset of content, audit,
   tune, then full content load on cutover weekend.
5. **Decommission** — keep D7 read-only for a defined cooling period,
   then archive.

A lead should also discuss budget reality — a 10-year-old D7 site
with hundreds of custom features is a 6+ month project, not a
weekend.

**References:**
- <https://www.drupal.org/docs/upgrading-drupal>

---

### Q12: Dependency management strategy for a multi-site Drupal practice?

**Tags:** dependencies, devops
**Time:** 8 min

Layers:

- **Pin to minor** in `composer.json` (`^10.3` for core, `^2.0` for
  contrib) — get patch / security releases for free, deliberate
  about minor bumps.
- **Renovate or Dependabot** for automated PRs on patch / minor
  updates. CI green = candidate for merge.
- **Audit before merge** — `composer audit` mandatory, `composer
  why-not` for blocked upgrades.
- **Coordinated quarterly upgrades** for major versions of contrib;
  test in dev, stage, then prod.
- **Internal contrib mirror** for air-gapped or compliance-bound
  environments.
- **Patch management** via `cweagans/composer-patches`. Patches are
  named and dated, and each one has a drupal.org issue link in the
  comment.

A lead should also surface "and we have a process for what happens
when a contrib module is abandoned" — fork to internal repo, file
issue, decide migrate-away timeline.

**References:**
- <https://getcomposer.org/doc/04-schema.md#version>

---

### Q13: When do you write a contrib module vs keep something custom?

**Tags:** community, judgment
**Time:** 7 min

Reasonable answer:

- **Custom, never contrib**: business logic, content models, client-
  specific UX, anything tied to internal systems, anything you'd be
  embarrassed by.
- **Contrib candidate**: something useful to others, generic, well-
  scoped. Examples: a field formatter that does something common, a
  Drush command for a popular workflow, an integration with a
  third-party API not yet covered.
- **Contrib obligation**: if your fork of a contrib module fixes a bug
  others would hit — open the issue, push the fix back. Don't carry
  patches forever; you become the maintenance burden.

A lead should also mention the cost of being a maintainer — you sign
up for issue triage, security obligations, release management. That's
why most teams' "useful internal libraries" never make it to contrib.

**References:**
- <https://www.drupal.org/docs/develop/coding-standards>

---

### Q14: How do you manage technical debt on a Drupal site?

**Tags:** debt, process
**Time:** 9 min

A defensible framework:

- **Inventory.** Maintain a list — module-by-module, hot-file basis —
  of known debt. Reviewed quarterly. Not "everything is broken", just
  the stuff you'd actually fix.
- **Pay-as-you-go.** Every feature PR allocates ~20% of its budget to
  clean-up on adjacent code. Boy Scout rule.
- **Dedicated remediation.** One sprint per quarter (or one developer
  per sprint) on debt — module upgrades, dead code removal, test
  coverage.
- **Block on debt.** Some debt is a blocker for the next feature; pay
  it then, not later.
- **Surface debt to product.** Translate "we have to rewrite the
  paragraphs migration" into business risk language ("editor velocity
  is dropping, two days per article instead of two hours"). Product
  decides priority with full information.

A lead should also distinguish "debt" (deliberate trade-offs taken
under time pressure) from "mess" (accidents of inexperience). Different
remediation strategies.

**References:**
- <https://martinfowler.com/bliki/TechnicalDebt.html>

---

### Q15: How do you work with content editors when they think "the developers won't let us"?

**Tags:** stakeholders, editors
**Time:** 8 min

Common signal of dysfunction; common interventions:

- **Pair on a release.** Sit with editors during a content launch.
  Watch where they actually struggle. The list of "stupid restrictions
  developers put on the CMS" is usually shorter than they say and
  longer than developers think.
- **Editor advisory.** One editor as the primary feedback channel,
  rotated. Avoids the "every editor's complaint becomes a ticket"
  problem.
- **Investment in CKEditor / Media library / Paragraphs UX.** This is
  the single biggest lever and is usually under-invested.
- **Trade-offs explicit.** When a request is real but expensive, name
  the cost. Don't hide behind "Drupal doesn't do that".
- **Train the trainers.** Editors who hit limitations might be hitting
  knowledge gaps. Recurring training is cheap and high ROI.

Worst-lead answer: "editors should learn Drupal". Editors should
*not* have to learn Drupal; that's the lead's job to insulate.

**References:**
- <https://www.drupal.org/docs/user_guide/en/index.html>

---

### Q16: How do you evaluate a contrib module before adopting it?

**Tags:** vendor-selection, contrib
**Time:** 8 min

A reasonable checklist on drupal.org's project page:

- **Usage statistics** — installs across active versions. Sub-100
  installs is a red flag unless the use case is niche.
- **Release cadence** — last stable release within the last 12 months
  ideally. "Last release in 2019" is a smell unless the module is
  feature-complete.
- **Issue queue health** — open vs closed ratio, response time to
  recent issues, presence of a maintainer.
- **Security coverage** — yes/no in the project page. No coverage
  means *you* are responsible for monitoring its security.
- **Major version compatibility** — D10 ready? D11 ready? An
  "experimental D11 patch in an issue" is not the same as a stable
  release.
- **Maintainers** — one bus-factor maintainer is risk; a couple of
  active co-maintainers is healthy.
- **Code quality** — skim the source. PSR-12? PHPStan-clean? Tests?

If a module fails 3+ checks and you adopt it anyway, you are taking on
its maintenance. Budget for it.

**References:**
- <https://www.drupal.org/project/usage>

---

### Q17: A vendor wants to put their proprietary contrib-shaped module into your site. How do you evaluate?

**Tags:** vendor-selection, security
**Time:** 8 min

Concerns to surface:

- **License compatibility** — must be GPLv2-or-later compatible to
  link with Drupal core. Otherwise it cannot legally live in
  `modules/`.
- **Source availability** — can you read it? Can you patch it? If
  it's encoded (ioncube), that's a serious red flag.
- **Update mechanism** — how do you get updates? Via Composer? Direct
  zip? Hosted phone-home?
- **Security responsibility** — who notifies you of vulnerabilities?
  Is the vendor on the security team's radar?
- **Lock-in** — if you stop paying, do you keep the code? Or does it
  brick the site?
- **Dependencies it brings** — Composer dependencies, contrib
  dependencies, JavaScript loaded from vendor CDN.

A lead should be willing to *say no* to a vendor proposal that fails
these. The path of "we'll figure it out later" leads to vendor lock-in
nobody noticed they signed up for.

**References:**
- <https://www.drupal.org/licensing/faq>

---

### Q18: What's your stance on Layout Builder for a large content site?

**Tags:** site-building, architecture
**Time:** 7 min

Pragmatic positions:

- **Default layouts in code**, per content type. Editors *override*
  per-entity only when they need to. This keeps the variance under
  control.
- **Custom layout plugins** when the layouts ship as part of a design
  system. Don't let editors design their own grid.
- **Custom block plugins** for anything not in core's stock set.
  Discoverable by editors but constrained.
- **Save the world from `inline_block`** — inline blocks created via
  Layout Builder are content, not config. They are not exported and
  not reusable. Use sparingly.
- **Performance** — Layout Builder pages have more render branches
  than a typical "node + body" page. Cache tags must be correct.
- **Test** — full kernel coverage of layout plugin behavior, snapshot
  tests of representative pages.

For a small marketing site, Layout Builder is fine. For 100k+ pages
where consistency matters, Paragraphs or SDC-built sections often beat
Layout Builder.

**References:**
- <https://www.drupal.org/docs/contributed-modules/layout-builder>

---

### Q19: How do you decide between sticking with a Drupal version (LTS) and tracking the latest?

**Tags:** upgrades, strategy
**Time:** 7 min

Trade-offs:

- **LTS posture** — fewer surprises, less churn, but you fall behind
  on contrib that has moved to newer minor versions.
- **Track latest** — earlier access to features (SDC, recipes,
  attribute-based plugins), but you pay the upgrade tax more often.

Recommended default: track latest minor (10.3 → 10.4 → 11.0 etc) on a
predictable cadence — quarterly assessment, deploy if the contrib
ecosystem has caught up. Stay one minor behind absolute bleeding edge
for production, but never two majors behind.

A lead should also discuss the security clock — Drupal core security
support windows are fixed and well-documented. Don't get caught a week
before EOL.

**References:**
- <https://www.drupal.org/docs/understanding-drupal/drupal-core-releases>

---

### Q20: Your team has a "we'll write tests later" backlog of 200 PRs over the last year. What do you do?

**Tags:** testing, team
**Time:** 8 min

Honest answer for a lead:

- **Stop the bleeding first.** New PRs from this week onward require
  tests for new code paths. Non-negotiable, ratchet only.
- **Cover the critical paths.** Use real production data — what URLs
  drive value? Write functional smoke tests for those. 80% coverage
  on the 20% of pages that matter.
- **Refactor toward testability.** Where the code is hard to test
  because of poor structure, extract services. Tests come for free
  after the refactor.
- **Skip the chase.** Don't try to backfill tests on the 200 PRs.
  Cover the new code and the regressions you actually see.
- **Measure.** Track test count, coverage, and CI duration over time.
  Showing improvement to the team is a morale lever.

The senior trap is to spend three sprints on "let's add tests to all
existing code". The lead's job is to surface that as bad ROI and
redirect.

**References:**
- <https://martinfowler.com/articles/practical-test-pyramid.html>

---

### Q21: When does your team need to write a Drupal distribution vs a recipe set?

**Tags:** distributions, recipes, strategy
**Time:** 7 min

Distributions in 2026 are largely *out*. They tied you to a profile
choice at install time, were monolithic, and aged poorly across core
upgrades. The "ship a starter site" niche has migrated to recipes,
which are composable, applied any time, and live alongside whatever
the rest of your codebase is doing.

Times a distribution might still make sense:

- A turnkey product (e.g. Lightning, when it existed, or Open
  Restaurant) where the install experience is the value.
- A regulated vertical with a tight delivery model.

For most agencies in 2026, "we have a starter recipe set we apply on
every new site" is the right answer.

**References:**
- <https://www.drupal.org/docs/extending-drupal/drupal-recipes>

---

### Q22: How do you measure developer productivity on a Drupal team without falling into "lines of code"?

**Tags:** team, metrics
**Time:** 8 min

Metrics that are *not* productivity:

- Lines of code, commits per week, PR count.

Metrics that *correlate* with team health:

- Cycle time — first commit to merge.
- PR review latency — open to first review.
- Change failure rate — % of deploys that caused a rollback.
- Time to restore — incident detection to resolution.
- Tests in CI — pass rate, flake rate, duration trend.

Plus qualitative signals:

- Retrospective themes — do the same problems repeat?
- Onboarding speed — how long until a new dev's first PR?
- Burnout signals — overtime, weekend pages, churn.

A lead should distinguish DORA-style outcome metrics from output
metrics, and be honest that "productivity" is mostly proxy.

**References:**
- <https://dora.dev/>

---

### Q23: Walk me through how you'd run an incident review after a Drupal site outage.

**Tags:** ops, process
**Time:** 8 min

A defensible structure:

1. **Timeline** — from first signal to resolution. Times, what was
   tried, what worked.
2. **Impact** — users affected, duration, downstream consequences.
3. **Root cause** — what failed first, not just what failed last.
4. **Contributing factors** — what made the response slower than it
   could have been? Missing dashboards, runbook gaps, alert fatigue.
5. **What went well** — important, otherwise the review feels punitive.
6. **Action items** — concrete, owned, deadlined. Not "we should be
   more careful".
7. **Blameless framing** — discuss decisions in the context the
   responders had at the time, not what we know now.

A lead should also commit to the followup — incident reviews where
action items don't ship are theater.

**References:**
- <https://sre.google/sre-book/postmortem-culture/>

---

### Q24: How do you handle a content editor who refuses to follow editorial workflow?

**Tags:** team, stakeholders
**Time:** 6 min

Step-by-step (and notice it is not "make the CMS more permissive"):

1. **Find out why.** "Refuses" rarely means "willfully sabotages".
   More likely they had a deadline, the workflow felt redundant, or
   they got conflicting guidance from another stakeholder.
2. **Surface to their manager.** This is a people-management issue,
   not a developer issue.
3. **Audit the workflow itself.** If multiple editors are routing
   around it, the workflow is wrong, not the editors. Simplify.
4. **Document the consequence.** What broke because the workflow was
   skipped? Make that observable.
5. **Permissions as last resort.** Removing the bypass capability is
   the lever you have, but it is also the one that escalates the
   conflict. Pull it only after non-technical interventions fail.

A lead does not respond to editor conflict with code changes.

**References:**
- <https://www.drupal.org/docs/contributed-modules/workflows>

---

### Q25: Sketch a content model for a publication with 200 staff editors and 500 articles per week.

**Tags:** content-modeling, scale
**Time:** 10 min

Outline:

- **Article** (node bundle) with: headline, subhead, deck, body
  (Paragraphs), authors (entity reference, multivalue, to a custom
  `author` entity, not a `user`), tags (taxonomy), section (taxonomy),
  publication date, status (`workflows`).
- **Author** (custom entity type) — name, bio, headshot, social links,
  bylines pull from this so changing an author's name updates every
  article. Decoupled from `user` so freelance authors don't have site
  accounts.
- **Section** (taxonomy) — hierarchical. Drives navigation, RSS, and
  topic-page generation.
- **Editorial workflow** — Draft → Review → Approved → Scheduled →
  Published, with `workflows` + `content_moderation`.
- **Versioning** — every revision stored, with revision log message
  required.
- **Embargo / schedule** — `scheduler` contrib for future-dated
  publish, possibly `scheduler_content_moderation_integration`.
- **Editors** — `role_delegation`, separate roles per section if
  sections are siloed.
- **API** — JSON:API exposure for mobile / syndication.

A lead should also call out: editorial UX investment (CKEditor
toolbar, media library, paragraph types), training plan, and the
50-article-per-day publish load implications on cache invalidation.

**References:**
- <https://www.drupal.org/docs/drupal-apis/entity-api>
- <https://www.drupal.org/project/workflows>

---

### Q26: How do you make sure your team doesn't reinvent contrib?

**Tags:** team, judgment
**Time:** 6 min

Mechanisms:

- **Architecture review on any "we'll write a module to do X".** The
  reviewer asks "what's on drupal.org for X?" before approving.
- **Internal wiki of "modules we have already evaluated"** with
  verdicts. Less re-evaluation per project.
- **Quarterly "what contrib is interesting" session.** Half an hour,
  five team members each present one module they have used or want to
  try.
- **Default to contrib.** New developers must explicitly justify *not*
  using a known contrib answer. Custom modules are the exception.

A lead avoids the "we always custom-build because contrib is
unreliable" cargo-culting and the "we always grab contrib because
it's free" cargo-culting equally.

**References:**
- <https://www.drupal.org/project/usage>

---

### Q27: How do you handle a Drupal core security advisory that lands at 3pm on a Friday?

**Tags:** ops, security
**Time:** 6 min

Practiced answer:

1. Read the SA. Severity (Critical / Highly Critical / Moderately
   Critical), affected versions, exploit conditions.
2. Determine exposure. Which of our sites run affected versions?
   Which are accessible from the internet?
3. Branch off `main`, bump core, run CI.
4. If exploit conditions are external (anonymous can trigger), deploy
   within hours, even on Friday evening. If only authenticated /
   admin, schedule for Monday with monitoring active.
5. Communicate to stakeholders ahead of the deploy. Don't surprise
   them.
6. Post-deploy: monitor logs, smoke-test critical flows, document the
   timeline.

A lead should also know that this is one of the cases where
"deploying on Friday" is the right call — the alternative is a
weekend of "is it being exploited yet".

**References:**
- <https://www.drupal.org/security>

---

### Q28: Why is this hire? Why does your team need a lead-tier Drupal engineer specifically?

**Tags:** judgment, communication
**Time:** 8 min

This is a *reverse* question. A candidate worth hiring asks it of the
interviewer. What you want to hear (when the candidate asks):

- The team has a real architectural decision space (multi-site, multi-
  region, headless transition, vendor consolidation).
- The role has actual authority — not just a senior-with-a-different-
  title.
- The hire is replacing or augmenting someone, and the candidate
  understands the gap they are filling.
- The team is hiring for a lead because they are growing, not because
  the previous lead burned out and quit.

If those are not true, the lead role is misnamed and the candidate
should reconsider. A lead candidate who never pushes back on the
framing of the role is a tell.

**References:**
- <https://staffeng.com/guides/learn-how-the-business-works/>
