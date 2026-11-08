# Brief for lesson writers (learning platform)

You write lesson content as JSON files for a Laravel + React learning platform. Change only the files named in your task.

## Spec — read first
`api/app/Modules/Learning/README.md` — sections "Two languages", "Lesson blocks", "Animations". The renderer understands only those block types, fields, animation kinds, icons and tones. Follow them exactly.

Finished bilingual examples to match in depth and structure:
- `api/app/Modules/Learning/database/seeders/lessons/cisco-security-certification-track/firewalls-and-network-filtering.json`
- `api/app/Modules/Learning/database/seeders/lessons/cisco-security-certification-track/incident-handling-and-soc-playbooks.json`
- `api/app/Modules/Learning/database/seeders/lessons/networking-foundations-ccna/tcp-udp-icmp-and-arp.json`

Syllabi (slugs and Arabic titles): `api/app/Modules/Learning/database/seeders/curriculum/*.json`. Use the lesson's `title` and `title_ar` from the syllabus as the file's `title` and `ar.title`. Cross-links use `/learn/<course-slug>/<lesson-slug>`; link only to slugs that exist there, and prefer lessons that are already written (a file exists in `lessons/`).

## File
`api/app/Modules/Learning/database/seeders/lessons/<course-slug>/<lesson-slug>.json`

```json
{ "slug": "...", "title": "...", "summary": "1–2 sentences", "duration_minutes": 25,
  "objectives": ["Explain ...", "..."], "blocks": [ ... ],
  "ar": { "title": "...", "summary": "...", "objectives": ["..."], "blocks": [ ... ] } }
```
`ar.blocks` mirrors `blocks` exactly: same order, same `type`, same `id`, same code/commands/output/ids/icons/tones/answer indexes. Only human-readable strings are translated.

## Language quality
- **English:** correct, polished, natural technical English, like a well-edited textbook. Clear, short sentences, no filler. **American spelling** (behavior, organization, defense).
- **Arabic:** Modern Standard Arabic (فصحى), fluent, not word-for-word. Use the established Arabic technical term with the English in parentheses the first time, e.g. «جدار الحماية (Firewall)», «نظام كشف التسلل (IDS)». Keep product, protocol and certification names, commands, code and file names in Latin script. Arabic comma «،» and question mark «؟». Wrap commands/identifiers in backticks so they stay left-to-right. Reuse the terms already used in written lessons (e.g. «جدار الحماية», «الأجهزة الطرفية», «مركز العمليات الأمنية (SOC)»).

## Lesson anatomy (Cisco NetAcad + W3Schools style)
1. `text` — short scenario: why this matters.
2. Several `text` blocks, **one `## Section` per block**, 2–4 short paragraphs, lists, markdown tables for comparisons, `###` subsections when useful.
3. At least one `animation` that shows a real process (sequence / flow / layers / cycle), 4–10 steps, each with a clear `note` (sequence) or `caption`.
4. At least one `code` or `terminal` block: realistic, defender-oriented commands/config with annotated output.
5. `callout` tip/warning/note where learners go wrong; one `callout` with variant `lab`, title "Try it yourself" / «جرّب بنفسك», with a safe hands-on exercise.
6. `keyterms` (6–10 terms).
7. `quiz` titled "Check your understanding" / «تحقّق من فهمك», 4–5 questions mixing single answer and choose-all-that-apply (array answers), every question with an explanation.
8. Final `text` with `## Summary` / `## الخلاصة`.

English prose 1,000–1,500 words. No weaponized exploit code, no real malware; offensive topics stay conceptual and are paired with defenses; scanning only on systems you own or are authorized to test. No images or external URLs. Example data uses documentation ranges (192.0.2.0/24, 198.51.100.0/24, 203.0.113.0/24) and `example.*` domains. Short unique block ids ("intro", "osi-anim", "quiz").

## Validate before reporting
Work in your own scratch folder (one per writer; never reuse or run another writer's scripts). Then, from the `api` folder:

```bash
python -I app/Modules/Learning/tools/validate_lessons.py
```

It must report 0 problems. Report back briefly: files written, and anything you were unsure about.
