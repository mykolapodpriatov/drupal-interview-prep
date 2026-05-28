# Contributing

Thanks for considering a contribution. This repo is meant to stay useful for
two audiences:

- Candidates preparing for a Drupal interview.
- Interviewers calibrating a question bank for a specific seniority level.

Keep that in mind when proposing changes — questions should be answerable,
have a clear seniority tier, and not be trivia.

## Adding a question

1. Pick the right file under `questions/` based on the seniority signal the
   question is supposed to elicit. If a junior could answer it confidently
   without looking anything up, it does not belong in `senior.md`.
2. Use the question template (see below).
3. Number questions sequentially within the file. If you insert in the middle,
   renumber the rest in the same PR.
4. Add at least one reference link. Prefer Drupal.org and core change records
   over third-party blog posts.

### Question template

```markdown
### Q12: How does Drupal's render API cache fragments of a page?

**Tags:** caching, render-arrays, performance
**Time:** 5 min

<Sample answer or "what a good answer covers". 1–3 short paragraphs. Bullets
are fine when listing distinct points.>

**Follow-up:** <Optional, one or two sentences.>

**References:**
- <https://www.drupal.org/docs/...>
- <https://www.drupal.org/node/...>
```

## Adding a code kata

Each kata lives under `exercises/NN-short-name/` and must contain:

- `README.md` — problem statement, constraints, hints.
- `starter/src/` — incomplete code under `Exercises\NN\` namespace.
- `solution/src/` — reference solution under `Exercises\NN\Solution\` namespace.
- `tests/` — PHPUnit tests under `Exercises\NN\Tests\` that pass against the
  solution and fail against the starter.

Run `composer test` to verify before opening a PR. CI enforces the same.

## Style

- Markdown: one sentence per line where practical, max ~100 char lines.
- PHP: PSR-12, declare strict types, type all params and returns.
- Drupal class references must match Drupal 10.3+ / 11 namespaces.
