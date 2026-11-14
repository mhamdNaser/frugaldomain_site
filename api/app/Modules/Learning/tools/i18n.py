"""Lesson translation pipeline for the extra languages (hi, zh, tr) and the Arabic audit.

    cd api/app/Modules/Learning
    python -I tools/i18n.py extract  <course> <workdir> [lesson-slug ... | _syllabus]  # EN text only -> <slug>.src.json
    python -I tools/i18n.py merge    <locale> <course> <workdir>            # <slug>.<locale>.json -> translations/
    python -I tools/i18n.py audit    <locale|ar> [course] [--report FILE]   # mechanical checks against English
    python -I tools/i18n.py status                                          # coverage per locale and course

Why extract/merge: a translator (human or model) only ever sees a flat
{"path": "English text"} map, never code, commands, ids, icons or answers.
That is roughly half the size of a lesson file, and nothing a translator must
not touch can be broken: merge copies the English lesson and only replaces
the leaves it was given. `_syllabus` in the same workdir carries the course,
stage, unit and lesson titles.
"""
import glob
import json
import os
import re
import sys

sys.stdout.reconfigure(encoding="utf-8")

ROOT = os.path.join(os.path.dirname(os.path.abspath(__file__)), "..", "database", "seeders")
LOCALES = {"hi", "zh", "tr"}
# Leaves that are code, commands, identifiers or settings: never translated.
PROTECTED = {"id", "type", "variant", "language", "code", "filename", "highlight", "url", "media_id",
             "poster", "width", "kind", "icon", "tone", "from", "to", "at", "cmd", "output", "prompt",
             "answer", "autoplay", "speed", "slug", "status", "duration_minutes"}
SCRIPT = {"ar": r"[؀-ۿ]", "hi": r"[ऀ-ॿ]", "zh": r"[一-鿿]"}
INLINE_CODE = re.compile(r"`[^`\n]+`")
LEARN_LINK = re.compile(r"\]\((/learn/[^)]+)\)")
# Strings left in English on purpose: commands, paths, addresses, field lists.
COMMANDISH = re.compile(r"[/=\[\]|{}<>$#_:]|->|\d+\.\d+\.\d+|^\S+ -|^[a-z]")


def load(path):
    with open(path, encoding="utf8") as f:
        return json.load(f)


def save(path, data):
    os.makedirs(os.path.dirname(path), exist_ok=True)
    with open(path, "w", encoding="utf8", newline="\n") as f:
        json.dump(data, f, ensure_ascii=False, indent=2)
        f.write("\n")


def leaves(node, path=""):
    """Yield (path, text) for every translatable string leaf."""
    if isinstance(node, dict):
        for k, v in node.items():
            if k not in PROTECTED:
                yield from leaves(v, f"{path}.{k}" if path else k)
    elif isinstance(node, list):
        for i, v in enumerate(node):
            yield from leaves(v, f"{path}.{i}")
    elif isinstance(node, str) and re.search(r"[^\W\d_]", node):
        yield path, node


def set_path(node, path, value):
    *parents, last = path.split(".")
    for p in parents:
        node = node[int(p)] if isinstance(node, list) else node[p]
    if isinstance(node, list):
        node[int(last)] = value
    else:
        node[last] = value


def lesson_source(course, slug):
    d = load(os.path.join(ROOT, "lessons", course, slug + ".json"))
    return {k: d.get(k) for k in ("title", "summary", "objectives", "blocks")}


def syllabus_source(course):
    doc = load(os.path.join(ROOT, "curriculum", course + ".json"))
    c = doc["course"]
    src = {
        "category": {k: doc["category"].get(k) for k in ("name", "description")},
        "course": {k: c.get(k) for k in ("title", "subtitle", "description", "outcomes", "prerequisites")},
        "stages": {}, "units": {}, "lessons": {},
    }
    for s in doc["stages"]:
        src["stages"][s["slug"]] = {k: s.get(k) for k in ("title", "summary") if s.get(k)}
        for u in s["units"]:
            src["units"][u["slug"]] = {k: u.get(k) for k in ("title", "summary") if u.get(k)}
            for l in u["lessons"]:
                src["lessons"][l["slug"]] = l["title"]
    return src


def extract(course, workdir, slugs):
    os.makedirs(workdir, exist_ok=True)
    save(os.path.join(workdir, "_syllabus.src.json"), dict(leaves(syllabus_source(course))))
    if slugs == ["_syllabus"]:  # titles only
        slugs = []
    elif not slugs:
        slugs = sorted(os.path.basename(p)[:-5] for p in glob.glob(os.path.join(ROOT, "lessons", course, "*.json")))
    for slug in slugs:
        save(os.path.join(workdir, f"{slug}.src.json"), dict(leaves(lesson_source(course, slug))))
    print(f"extracted {len(slugs)} lessons + _syllabus into {workdir}")


def merge_one(source, src_map, translated):
    missing = [k for k in src_map if not str(translated.get(k) or "").strip()]
    extra = [k for k in translated if k not in src_map]
    if missing or extra:
        return None, f"missing {missing[:5]} extra {extra[:5]}"
    out = json.loads(json.dumps(source))
    for k, v in translated.items():
        set_path(out, k, v)
    return out, None


def merge(locale, course, workdir):
    if locale not in LOCALES:
        sys.exit(f"unknown locale {locale}; add it to LOCALES here and in LearningTranslation::LOCALES")
    done, failed = 0, []
    for path in sorted(glob.glob(os.path.join(workdir, f"*.{locale}.json"))):
        slug = os.path.basename(path)[: -len(f".{locale}.json")]
        src_map = load(os.path.join(workdir, f"{slug}.src.json"))
        try:
            translated = load(path)
        except json.JSONDecodeError as e:
            failed.append(f"{slug}: invalid JSON ({e})")
            continue
        if slug == "_syllabus":
            out, err = merge_one(syllabus_source(course), src_map, translated)
            target = os.path.join(ROOT, "translations", locale, "curriculum", course + ".json")
        else:
            out, err = merge_one(lesson_source(course, slug), src_map, translated)
            target = os.path.join(ROOT, "translations", locale, "lessons", course, slug + ".json")
        if err:
            failed.append(f"{slug}: {err}")
            continue
        save(target, out)
        done += 1
    for f in failed:
        print("✗", f)
    print(f"merged {done}, failed {len(failed)}")
    sys.exit(1 if failed else 0)


def compare(en, tr, where, locale, problems):
    """Structural and protected-leaf checks of one translated lesson against English."""
    eb, tb = en.get("blocks") or [], tr.get("blocks") or []
    if [(b.get("id"), b.get("type")) for b in eb] != [(b.get("id"), b.get("type")) for b in tb]:
        problems.append(("structure", where, "blocks do not mirror English (ids/types/order)"))
        return
    for key in ("title", "summary", "objectives"):
        if not tr.get(key):
            problems.append(("missing", where, key))
    if len(tr.get("objectives") or []) != len(en.get("objectives") or []):
        problems.append(("structure", where, "objectives count differs"))

    def walk(a, b, path):
        if isinstance(a, dict):
            if not isinstance(b, dict):
                problems.append(("structure", where, f"{path} shape"))
                return
            for k, v in a.items():
                walk(v, b.get(k), f"{path}.{k}")
        elif isinstance(a, list):
            if not isinstance(b, list) or len(a) != len(b):
                problems.append(("structure", where, f"{path} list length"))
                return
            for i, (x, y) in enumerate(zip(a, b)):
                walk(x, y, f"{path}.{i}")
        elif path.rsplit(".", 1)[-1] in PROTECTED or not isinstance(a, str):
            if a != b and not (path.endswith(".code") and locale == "ar"):
                problems.append(("protected", where, f"{path} changed"))
        elif not a.strip():
            pass  # empty in English too
        elif not isinstance(b, str) or not b.strip():
            problems.append(("missing", where, path))
        else:
            if a == b and len(a.split()) >= 4 and not COMMANDISH.search(a):
                problems.append(("untranslated", where, f"{path}: {a[:60]}"))
            if sorted(INLINE_CODE.findall(a)) != sorted(INLINE_CODE.findall(b)):
                lost = set(INLINE_CODE.findall(a)) - set(INLINE_CODE.findall(b))
                if lost:
                    problems.append(("inline-code", where, f"{path}: lost {sorted(lost)[:3]}"))
            if sorted(LEARN_LINK.findall(a)) != sorted(LEARN_LINK.findall(b)):
                problems.append(("link", where, f"{path}: lesson links differ"))

    for i, (x, y) in enumerate(zip(eb, tb)):
        walk(x, y, f"blocks.{i}")

    if locale in SCRIPT:
        text = " ".join(t for _, t in leaves({"b": tb}))
        letters = re.findall(r"[^\W\d_]", text)
        native = re.findall(SCRIPT[locale], text)
        if letters and len(native) / len(letters) < 0.55:
            problems.append(("script", where, f"only {100 * len(native) // len(letters)}% of letters in {locale} script"))


def audit(locale, course, report):
    problems, checked = [], 0
    for path in sorted(glob.glob(os.path.join(ROOT, "lessons", course or "*", "*.json"))):
        c, slug = os.path.basename(os.path.dirname(path)), os.path.basename(path)[:-5]
        en = load(path)
        if locale == "ar":
            tr = en.get("ar")
        else:
            tp = os.path.join(ROOT, "translations", locale, "lessons", c, slug + ".json")
            tr = load(tp) if os.path.exists(tp) else None
        if not tr:
            continue
        checked += 1
        compare(en, tr, f"{c}/{slug}", locale, problems)
    kinds = {}
    for kind, _, _ in problems:
        kinds[kind] = kinds.get(kind, 0) + 1
    lessons = len({w for _, w, _ in problems})
    print(f"{locale}: {checked} lessons checked, {len(problems)} findings in {lessons} lessons {kinds}")
    if report:
        os.makedirs(os.path.dirname(os.path.abspath(report)), exist_ok=True)
        with open(report, "w", encoding="utf8") as f:
            for kind, where, msg in sorted(problems, key=lambda p: (p[1], p[0])):
                f.write(f"{kind}\t{where}\t{msg}\n")
        print(f"report: {report}")
    else:
        for kind, where, msg in problems[:40]:
            print(f"✗ [{kind}] {where}: {msg}")


def status():
    for course in sorted(os.path.basename(p)[:-5] for p in glob.glob(os.path.join(ROOT, "curriculum", "*.json"))):
        total = len(glob.glob(os.path.join(ROOT, "lessons", course, "*.json")))
        row = [f"{course:<40} en {total:>3}"]
        for loc in sorted(LOCALES):
            n = len(glob.glob(os.path.join(ROOT, "translations", loc, "lessons", course, "*.json")))
            syl = "S" if os.path.exists(os.path.join(ROOT, "translations", loc, "curriculum", course + ".json")) else "-"
            row.append(f"{loc} {syl}{n:>3}")
        print("  ".join(row))


if __name__ == "__main__":
    args = sys.argv[1:]
    report = None
    if "--report" in args:
        i = args.index("--report")
        report = args[i + 1]
        del args[i:i + 2]
    cmd = args[0] if args else ""
    if cmd == "extract" and len(args) >= 3:
        extract(args[1], args[2], args[3:])
    elif cmd == "merge" and len(args) == 4:
        merge(args[1], args[2], args[3])
    elif cmd == "audit" and len(args) >= 2:
        audit(args[1], args[2] if len(args) > 2 else None, report)
    elif cmd == "status":
        status()
    else:
        sys.exit(__doc__)
