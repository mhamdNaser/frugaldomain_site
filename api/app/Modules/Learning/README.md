# Learning module

The site's learning platform (`/learn` on the site, **Learning** in the dashboard).

```
Category        Cybersecurity, Networking, Network Automation & DevOps, AI, …
└─ Course       a complete programme, e.g. "Cisco Security Certification Track"
   └─ Stage     a phase / certification, e.g. "Cisco CCST Cybersecurity (100-160)"
      └─ Unit   a module of related lessons, e.g. "Network Security Basics"
         └─ Lesson   one page: objectives + an ordered list of content blocks
```

## Database

| Table | What |
|:--|:--|
| `learning_categories` | Top-level subjects. `status` = `active` \| `hidden`. |
| `learning_courses` | `status` = `draft` \| `published`. `outcomes`, `prerequisites`, `tags` are JSON lists. |
| `learning_stages` | Ordered within a course. `code` holds an exam code such as `200-301`. |
| `learning_units` | Ordered within a stage. |
| `learning_lessons` | `blocks` (JSON) is the lesson body. `status` = `draft` \| `published`. A draft is listed in the public syllabus as *coming soon* but never served. |
| `learning_media` | Images and videos uploaded from the dashboard, served at `/api/learn/media/{id}.{ext}`. |

Slugs of stages, units and lessons are unique **within a course**; course and
category slugs are global. Lesson URLs are `/learn/{course}/{lesson}`.

## API

Public: `GET /api/learn/categories`, `GET /api/learn/courses?category=`,
`GET /api/learn/courses/{slug}`, `GET /api/learn/courses/{slug}/lessons/{lesson}`,
`GET /api/learn/media/{id}.{ext}`, `GET /api/learn/courses/{slug}/cover`.

Dashboard (`auth:sanctum`, role `admin|super-admin`), under `/api/admin/learn`:
CRUD for `categories`, `courses`, `stages`, `units`, `lessons`; `POST reorder`
(`{type, parent_id, ids}` – also moves items to a new parent);
`lessons/{id}/duplicate`; `media` upload/list/delete; `courses/{id}/cover`;
`courses/{id}/export` and `POST import` (`mode` = `replace` | `merge` | `fill`).

## The course file (`frugal-course@1`)

Export, import and the seeder all use one JSON shape:

```json
{
  "format": "frugal-course@1",
  "category": { "slug": "cybersecurity", "name": "Cybersecurity", "name_ar": "الأمن السيبراني", "icon": "shield", "color": "#0891b2" },
  "course": { "slug": "my-course", "title": "My course", "level": "beginner", "language": "en", "status": "published" },
  "stages": [
    { "slug": "stage-1", "title": "Stage 1", "code": "100-160", "summary": "…",
      "units": [
        { "slug": "unit-1", "title": "Unit 1",
          "lessons": [
            { "slug": "first-lesson", "title": "First lesson", "summary": "…", "duration_minutes": 20,
              "objectives": ["…"], "status": "published", "blocks": [ … ] }
          ] }
      ] }
  ]
}
```

## Shipped curriculum

`database/seeders/curriculum/*.json` is the syllabus generated from
`Cybersecurity_Roadmap.pptx` (30 phases, 643 topics, 12 career paths) split
into 10 courses across three categories. `database/seeders/lessons/<course>/<lesson>.json`
holds the content of each written lesson (`summary`, `duration_minutes`,
`objectives`, `blocks`). Run:

```bash
php artisan migrate
php artisan db:seed --class="App\Modules\Learning\database\seeders\LearningCurriculumSeeder"
```

The seeder only adds what is missing and fills lessons that are still empty,
so it is safe to re-run after editing lessons in the dashboard. To ship a new
lesson, add its content file and run it again.

## Lesson blocks

Every block has `type` and an optional stable `id`. Unknown fields are dropped
on save. Markdown is GitHub-flavoured (tables, task lists); raw HTML is not
rendered. Links starting with `/learn/` stay inside the site.

| `type` | Fields |
|:--|:--|
| `text` | `markdown` |
| `callout` | `variant` (`note` `tip` `info` `warning` `danger` `lab`), `title`?, `markdown` |
| `code` | `language`, `code`, `filename`?, `caption`?, `highlight`? (1-based line numbers) |
| `terminal` | `title`?, `prompt`? (default `$`), `lines`: `[{ cmd?, output?, prompt?, comment? }]`, `caption`? |
| `image` | `url` or `media_id`, `alt`, `caption`?, `width`? (`narrow` `normal` `wide`) |
| `video` | `url` (YouTube, Vimeo or an uploaded .mp4/.webm), `caption`?, `poster`? |
| `animation` | `kind`, `title`?, `caption`?, `data`, `autoplay`?, `speed`? – see below |
| `quiz` | `title`?, `questions`: `[{ question, options[], answer, explanation? }]` – `answer` is a 0-based index, or an array of indexes for "choose all that apply" |
| `keyterms` | `title`?, `items`: `[{ term, definition }]` |
| `divider` | – |

Code languages with highlighting: `bash` `shell` `powershell` `python`
`javascript` `json` `yaml` `sql` `php` `cisco` (IOS CLI) `nginx` `apache`
`ini` `dockerfile` `hcl` `http` `plain`.

### Animations

Animations are data, not code: the player animates the steps one at a time
with play / pause / step / restart controls, and shows each step's caption.

**`sequence`** – messages between parties (handshakes, protocols, auth flows).

```json
{ "kind": "sequence", "title": "TCP three-way handshake",
  "data": {
    "actors": [ { "id": "c", "label": "Client", "sub": "10.0.0.5", "icon": "laptop" },
                { "id": "s", "label": "Server", "sub": "10.0.0.80:443", "icon": "server" } ],
    "steps": [ { "from": "c", "to": "s", "label": "SYN", "detail": "seq=100", "note": "The client asks to open a connection." },
               { "from": "s", "to": "c", "label": "SYN-ACK", "detail": "seq=300 ack=101", "tone": "secure" },
               { "from": "c", "to": "c", "label": "Connection established", "tone": "success" } ] } }
```

**`flow`** – a token moving through stages (packet filtering, pipelines, kill chains).

```json
{ "kind": "flow",
  "data": {
    "token": "Packet",
    "nodes": [ { "id": "in", "label": "Internet", "icon": "cloud" },
               { "id": "fw", "label": "Firewall", "sub": "ACL 101", "icon": "firewall" },
               { "id": "web", "label": "Web server", "icon": "server" } ],
    "steps": [ { "at": "in", "caption": "A packet arrives for TCP/443." },
               { "at": "fw", "caption": "Rule 10 permits HTTPS.", "tone": "success" },
               { "at": "web", "caption": "Delivered." },
               { "at": "in", "token": "TCP/23", "caption": "A Telnet packet arrives." },
               { "at": "fw", "token": "TCP/23", "caption": "No rule matches: implicit deny.", "tone": "danger" } ] } }
```

**`layers`** – a stack with a growing/shrinking packet (OSI, encapsulation, defense in depth).

```json
{ "kind": "layers",
  "data": {
    "layers": [ { "label": "Application", "sub": "L7" }, { "label": "Transport", "sub": "L4" },
                { "label": "Network", "sub": "L3" }, { "label": "Data link", "sub": "L2" } ],
    "steps": [ { "layer": 0, "packet": ["Data"], "caption": "The browser produces an HTTP request." },
               { "layer": 1, "packet": ["TCP", "Data"], "caption": "TCP adds ports and sequence numbers." },
               { "layer": 2, "packet": ["IP", "TCP", "Data"], "caption": "IP adds source and destination addresses." } ] } }
```

**`cycle`** – a lifecycle (incident response, risk management, PDCA).

```json
{ "kind": "cycle",
  "data": {
    "center": "Incident response",
    "items": [ { "label": "Preparation", "sub": "Plans, tools, training" },
               { "label": "Detection & analysis", "sub": "Is this an incident?" } ],
    "steps": [ { "item": 0, "caption": "…" } ] } }
```

`steps` is optional for `cycle`; without it each item is a step and its `sub`
is the caption.

Icons: `laptop` `desktop` `server` `router` `switch` `firewall` `cloud` `user`
`attacker` `database` `phone` `lock` `key` `shield` `mail` `globe` `wifi`
`file` `bug` `eye` `alert` `cpu` `ap` `log`.

Tones: `default` `secure` `success` `warning` `danger` `attack` `blocked`.
