# drupal-interview-prep

[![CI](https://github.com/mykolapodpriatov/drupal-interview-prep/actions/workflows/ci.yml/badge.svg)](https://github.com/mykolapodpriatov/drupal-interview-prep/actions/workflows/ci.yml)

A curated bank of Drupal interview questions and runnable PHP code katas,
organized by seniority. Targets Drupal 10.3+ / 11 and PHP 8.3+.

This is not a quiz site and not a marketing brochure. It is the resource I
wish I had had when I was on either side of a Drupal interview — concrete
questions with sample answers, plus exercises with failing tests that the
candidate has to make pass.

## Who this is for

- **Candidates** preparing for a Drupal-focused interview. Skim the level you
  are targeting, attempt the answer out loud before reading mine, then drill
  the katas.
- **Interviewers** building or calibrating a question set. Pick a handful of
  questions across two adjacent levels (e.g. middle + senior) and one or two
  katas as the live-coding portion.

## Levels

| Level  | Years    | Focus                                                     |
|--------|----------|-----------------------------------------------------------|
| Junior | 0–2      | Vocabulary, core concepts, Drush, basic site building.    |
| Middle | 2–4      | Entity API, plugins, services, common contrib modules.    |
| Senior | 4–7      | Render API internals, DI patterns, migrate, testing.      |
| Lead   | 7+       | Architecture, team practice, upgrades, vendor selection.  |

Years are heuristics, not gates. A two-year dev with hard infra exposure can
hold a senior conversation; a six-year dev who only ever built sitebuilder
sites might struggle with the senior bank. Use the questions, not the column.

## Structure

```
questions/
  junior.md     ~28 questions
  middle.md     ~28 questions
  senior.md     ~35 questions
  lead.md       ~28 questions

exercises/
  01-render-array-builder/
  02-event-subscriber/
  03-config-form-validation/
  04-entity-query-with-access/
  05-migrate-process-plugin/
  06-block-plugin/
  07-drush-command/
  08-json-api-extension/
  09-cache-metadata/

docs/
  interviewer-guide.md
  candidate-guide.md
```

Each kata has `starter/`, `solution/`, and `tests/`. Tests fail against
starter code and pass against the reference solution.

## How to use

### As a candidate

1. Read [`docs/candidate-guide.md`](docs/candidate-guide.md).
2. Self-quiz on the questions for your target level. Speak the answer out
   loud, *then* read mine.
3. Clone the repo, run `composer install`, then `composer test:starter`. All
   exercise tests should fail.
4. Pick a kata, edit `starter/src/`, re-run tests, iterate.
5. Compare your solution against `solution/src/` only after yours passes.

### As an interviewer

1. Read [`docs/interviewer-guide.md`](docs/interviewer-guide.md).
2. Pick 4–6 questions from your target level (and one tier above as stretch).
3. Optionally use a kata as the live-coding round — they are short enough to
   fit in a 45-minute slot.

## Setup

```bash
composer install
composer test:solution   # verifies reference solutions still pass
composer test:starter    # confirms starter code still fails
```

PHP 8.3 or later required.

## Contributing

PRs welcome. See [CONTRIBUTING.md](CONTRIBUTING.md) for the question template
and kata layout.

## Disclaimer

Sample answers reflect my own opinion of what a strong candidate would say at
each level. They are not the only valid answers, and they are not endorsed by
the Drupal Association. If a sample answer is wrong or outdated, please open
an issue.

## License

MIT — see [LICENSE](LICENSE).
