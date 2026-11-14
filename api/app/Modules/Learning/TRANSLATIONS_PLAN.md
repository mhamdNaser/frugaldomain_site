# Lesson translations: hi, zh, tr (and the Arabic audit)

English is the source. Arabic keeps its `_ar` columns and the `ar` object in each
lesson file. Every further language lives in `learning_translations` and is
shipped as files:

```
database/seeders/translations/<locale>/curriculum/<course>.json      titles, descriptions, outcomes
database/seeders/translations/<locale>/lessons/<course>/<lesson>.json  one lesson body
```

The API serves the language in `X-Language`; anything not yet translated falls
back to English (a lesson body is served whole in one language or in English,
never mixed). So every phase can ship on its own.

## Deploying a phase

First time only (creates the table):

```bash
php artisan migrate --force
```

Every phase (re-running is safe; it overwrites these languages from the files):

```bash
php artisan db:seed --class="App\Modules\Learning\database\seeders\LearningTranslationsSeeder" --force
```

Arabic corrections are in the lesson files themselves; the curriculum seeder
only applies them in merge mode, which also overwrites dashboard edits of
those lessons:

```bash
LEARNING_SEED_MODE=merge php artisan db:seed --class="App\Modules\Learning\database\seeders\LearningCurriculumSeeder" --force
```

## One round (token-lean)

```bash
cd api/app/Modules/Learning
python -I tools/i18n.py extract <course> <work> <slug> <slug> ...    # English text only, no code/ids
# translators: <work>/<slug>.src.json -> <work>/<slug>.<locale>.json  (tools/TRANSLATOR_BRIEF.md)
python -I tools/i18n.py merge <locale> <course> <work>               # rebuilds full lessons, refuses missing keys
python -I tools/i18n.py audit <locale> <course>                      # structure, code, links, script share
python -I tools/i18n.py status                                       # coverage table
```

Translators never see code, commands, ids, icons or quiz answers, and merge
copies those from English, so they cannot be broken; the audit replaces a
second model review pass. Sonnet translators, 8 in parallel, 3-4 lessons each.

## Phases

| Phase | Content | Size | State |
|:--|:--|:--|:--|
| 0 | Table, API, seeder, tools; Arabic mechanical audit and fixes; all 10 syllabi in hi/zh/tr | - | done 2026-11-14 |
| 1 | networking-foundations-ccna | 41 lessons x 3 | |
| 2 | hands-on-security-labs + ot-ics-security | 44 x 3 | |
| 3 | network-automation-devops | 49 x 3 | |
| 4 | security-operations-dfir | 50 x 3 | |
| 5 | offensive-security-for-defense | 57 x 3 | |
| 6 | security-architecture-resilience | 60 x 3 | |
| 7 | enterprise-datacenter-networking | 71 x 3 | |
| 8 | systems-infrastructure-security | 106 x 3 | |
| 9 | cisco-security-certification-track | 112 x 3 | |
| A | Arabic language review (fluency, terminology), one course per round, reviewers read Arabic only | 590 | |

A round is about 30 lesson-translations (e.g. 10 lessons in all three
languages, or 30 in one language), so a phase takes 2-12 rounds. Total: 1,770
lesson-translations, roughly 60 rounds.

The 65 review-pointer lessons have no file of their own; their generated
English/Arabic text is not translated (they fall back to English).

## Arabic audit (2026-11-14)

`python -I tools/i18n.py audit ar` checks every Arabic lesson against English:
same blocks, untouched code and answers, kept `code spans` and lesson links,
untranslated English prose. First run: 363 findings in 155 lessons. Fixed:
untranslated quiz options, titles, terminal comments and animation labels,
missing fields, dropped `code spans`, and one broken English link
(`{C}incident-response-procedures`). What the audit still lists is left in
English on purpose (product names, protocol fields, commands).
