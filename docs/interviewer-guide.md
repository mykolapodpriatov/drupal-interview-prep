# Interviewer guide

This guide is for technical interviewers using this question bank to assess
Drupal candidates. Pair it with the bank itself (`questions/`) and one or
two katas from `exercises/` if you do a live coding round.

## Time budget

Typical Drupal technical interview slot in 2026:

| Block                | Time     | What                                                |
|----------------------|----------|-----------------------------------------------------|
| Warm-up              | 5 min    | Intro, what they're working on currently            |
| Questions, target    | 20 min   | 4–6 questions at the target seniority level         |
| Questions, stretch   | 10 min   | 2–3 questions one tier above target                 |
| Live coding (opt)    | 30 min   | One kata from `exercises/`                          |
| Their questions      | 10 min   | Critical signal, don't skip                         |
| Buffer               | 5 min    | Wrap, what happens next                             |

Total: 60–80 min. Below 45 min is too short for a senior or lead loop.

## Question selection

- Pick from the candidate's *target* level, not their *years*. Seven years
  of pure sitebuilder work does not produce a senior backend answer.
- Mix topics. Don't ask five caching questions in a row; you'll learn one
  thing five times.
- Include at least one "judgment" question (`Tags: judgment`) at every
  level above junior — these reveal scope and trade-off thinking, which
  is where seniority actually lives.
- Stretch questions are for *signal*, not for grading. A middle who fluents
  on a senior question is high signal; a senior who flounders on a lead
  question is fine.

## What to probe

**Junior signals**

- Vocabulary alignment. Do they use Drupal terms correctly?
- Working knowledge of Drush, Composer, config workflow.
- Honest "I don't know" when they don't, instead of inventing.
- Curiosity. Do they ask what the code does?

**Middle signals**

- Comfortable across entity, plugin, services, hooks without panicking.
- Has done a real config deploy on a real project — describes the
  workflow concretely, not abstractly.
- Has used 3–5 common contrib modules and can compare them.
- Starts thinking about testing, even if not extensively.

**Senior signals**

- Can reason about caching propagation without notes.
- Discusses DI patterns explicitly — knows ContainerInjectionInterface
  vs ContainerFactoryPluginInterface and why.
- Has implemented at least one custom plugin type or service factory.
- Talks about migrate, BigPipe, and JSON:API from experience, not
  documentation.
- Has opinions on testing strategy that match the team they're joining.

**Lead signals**

- Talks about *people* and *systems* as much as code.
- Articulates trade-offs without being asked to.
- Has lived through at least one production incident and learned from
  it.
- Can pushback on the role framing — knows what kind of lead role is
  *not* worth taking.
- Comfortable with "I'd want to see the data before committing".

## Anti-patterns to watch for

- **Pattern-matched answers.** Memorized definitions delivered fast and
  shallow. Follow up with "and when does that go wrong?"
- **`Drupal::service()` everywhere.** Even at middle level, this is a
  smell. Senior+ should always describe DI via `create()`.
- **No mention of cache metadata** at senior+. Render API answers without
  cache tags / contexts / max-age are incomplete.
- **"It depends" with no follow-through.** Real seniority gives the answer
  *plus* the conditions under which the answer changes. "It depends" alone
  is a stall.
- **Hype-driven architecture** at lead level. "Headless is better" or
  "monorepos are better" with no trade-off discussion.

## Scoring

A simple rubric per question:

| Score | Meaning                                                          |
|-------|------------------------------------------------------------------|
| 0     | Did not attempt or fundamentally misunderstood the concept       |
| 1     | Partial answer, missing key terminology or steps                 |
| 2     | Solid working knowledge, matches expected level                  |
| 3     | Exceeds expected level, brings up nuance or trade-off unprompted |

Use the average over 5–8 questions plus the live-coding result. Single
questions are noisy; the aggregate is the signal.

For a hire decision: target level requires average ~2.0, stretch level
requires *at least one* 3-score answer.

## Live coding (using `exercises/`)

If you have 30+ minutes:

1. Pick a kata at the candidate's target level (or one below for warm-up).
2. Share the `README.md` and the failing tests. Do not share the
   `solution/`.
3. They have 25 min to make the tests pass. Pair-program; you may answer
   questions about Drupal APIs but not about the kata logic.
4. Score on: did they read the tests first, did they navigate the API,
   did they ask sensible questions, was their code reasonable.

Speed is not the metric. A candidate who finishes 80% of a kata
thoughtfully beats one who finishes 100% by copy-pasting.

## Their questions matter

A candidate who has no questions for you is either over-prepared (read
the whole company wiki and exhausted curiosity) or under-engaged. Both
are weak signals.

Good questions at this seniority level:

- Junior: "How does the team onboard new developers?"
- Middle: "How is config deployment handled on your projects?"
- Senior: "What does your testing pyramid look like in CI?"
- Lead: "Who owns architectural decisions in your team today, and how
  does that change with this hire?"
