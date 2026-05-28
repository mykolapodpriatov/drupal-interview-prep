# Candidate guide

You're preparing for a Drupal interview. This guide tells you how to use the
question bank and katas in this repo without wasting your time.

## Pick your target level

Be honest about the role you're interviewing for, not the role you wish
you had.

| Level  | Years    | Self-test                                                 |
|--------|----------|-----------------------------------------------------------|
| Junior | 0–2      | I can build a small site in the UI. Hooks are mostly new. |
| Middle | 2–4      | I've written modules, used Drush daily, deployed config.  |
| Senior | 4–7      | I reason about cache tags. I know the plugin system.      |
| Lead   | 7+       | I make architecture calls. I've shipped through outages.  |

If you're between two levels, prepare for the higher one and let the
interview decide. Going in under-prepared is worse than over-prepared.

## Suggested study order (target = senior, ~2 weeks)

### Week 1 — recall and gap-fill

- Day 1–2: skim `questions/junior.md`. Speak each answer out loud
  *before* reading mine. Anything that takes more than 60 seconds is a
  gap; note it.
- Day 3–4: same with `questions/middle.md`.
- Day 5: review your gaps. Read the linked documentation, not blog
  posts. Drupal.org docs are accurate; random Medium posts often aren't.
- Day 6–7: re-test the gaps. Don't move on until you can speak the
  answer fluently.

### Week 2 — depth and practice

- Day 8–10: `questions/senior.md`. The hard ones. Speak them out loud.
  Don't fool yourself by reading silently — silent reading hides where
  you don't actually know.
- Day 11: pick one kata you've never solved. Read only the README and
  the tests. Implement from scratch. Time yourself.
- Day 12: pick a kata you found hard. Implement again, this time
  noting where the friction was.
- Day 13: review the lead bank (`questions/lead.md`) even if you're
  not interviewing for lead. Trade-off questions are useful at any
  level.
- Day 14: mock interview with a friend or peer. Real-time pressure
  reveals different gaps than self-study.

## How to answer interview questions well

Three habits worth practicing:

1. **Restate the question first.** "If I understand, you're asking about
   how render cache invalidation propagates" — buys you 5 seconds and
   confirms you heard correctly.
2. **Structure first, detail second.** "There are three pieces — tags,
   contexts, max-age — let me cover each." Beats stream-of-consciousness.
3. **Name your uncertainty.** "I'd guess X but I haven't shipped that in
   production." Better than confidently wrong.

If you genuinely don't know: say so. A senior who admits "I don't know,
how would you approach finding out?" is more hireable than one who
bluffs.

## How to use the katas

The katas (`exercises/01-08`) are short Drupal-flavored PHP exercises.
Each has:

- `README.md` — the problem.
- `starter/src/` — code with gaps.
- `tests/` — PHPUnit tests that fail against the starter.
- `solution/src/` — reference solution.

Workflow:

```bash
composer install
composer test:starter    # confirms tests fail against starter
# Edit starter/src/ for the kata you picked
vendor/bin/phpunit --testsuite=Kata01    # iterate
# When all green:
composer test:solution   # confirms the reference solution still passes
```

Only open `solution/` after your own version passes. Compare; note
differences.

## Common mistakes candidates make

- **Reading without speaking.** You'll feel prepared, then freeze in the
  interview. Always answer out loud.
- **Optimizing for breadth over depth.** Knowing 80 concepts at 30%
  beats fewer concepts at 90%. Wrong way around. Pick 30 concepts and
  know them cold.
- **Skipping the documentation.** Sample answers in this repo are a
  starting point. The Drupal.org reference links are the source of
  truth.
- **Ignoring the "their questions" slot.** The five minutes at the end
  where you ask the interviewer questions is graded. Prepare 3–5 real
  ones.

## Mock interview tips

- Set a timer. Real interviews have time pressure; you should practice
  with it.
- Record yourself. Painful but useful — listen for filler words,
  rambling, dead air.
- Speak more slowly than feels natural. Interviewers are taking notes.
- If you don't understand a question, ask. Better than guessing wrong.

## When you're stuck on a kata

Order of operations:

1. Re-read the test. The test is the spec.
2. Re-read the README for hints.
3. Read Drupal core for similar examples (`grep` the namespace).
4. Search drupal.org documentation.
5. Only then look at `solution/`.

The friction is the point. Easy katas don't teach anything.

## After the interview

- Write down every question you were asked, before you forget. Add the
  ones not in this repo as PRs.
- If you flubbed a question, look it up. The next interview will ask
  it.
- If you got a reject, ask for specifics. Most interviewers will share.
