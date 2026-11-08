"""Validate the shipped learning content and report progress.

    cd api
    python -I app/Modules/Learning/tools/validate_lessons.py            # check everything
    python -I app/Modules/Learning/tools/validate_lessons.py --progress # plus per-stage progress

Checks every lesson file in database/seeders/lessons against the syllabi in
database/seeders/curriculum and the block vocabulary the renderer understands
(see ../README.md). Exit code 1 when anything is wrong.
"""
import glob
import json
import os
import re
import sys

sys.stdout.reconfigure(encoding="utf-8")

BASE = os.path.join(os.path.dirname(os.path.abspath(__file__)), "..", "database", "seeders")
TYPES = {"text", "callout", "code", "terminal", "image", "video", "animation", "quiz", "keyterms", "divider"}
CALLOUTS = {"note", "tip", "info", "warning", "danger", "lab"}
LANGS = {"bash", "shell", "powershell", "python", "javascript", "json", "yaml", "sql", "php", "cisco",
         "nginx", "apache", "ini", "dockerfile", "hcl", "http", "plain"}
ICONS = set("laptop desktop server router switch firewall cloud user attacker database phone lock key shield "
            "mail globe wifi file bug eye alert cpu ap log".split())
TONES = {"default", "secure", "success", "warning", "danger", "attack", "blocked"}
LINK = re.compile(r"\(/learn/([\w-]+)/([\w-]+)\)")


def load_syllabi():
    syllabi = {}
    for path in sorted(glob.glob(os.path.join(BASE, "curriculum", "*.json"))):
        with open(path, encoding="utf8") as f:
            doc = json.load(f)
        syllabi[doc["course"]["slug"]] = doc
    return syllabi


def check_blocks(blocks, where, slugs, problems):
    seen = set()
    for i, b in enumerate(blocks):
        at = f"{where} block {i + 1} ({b.get('id')})"
        t = b.get("type")
        if t not in TYPES:
            problems.append(f"{at}: unknown type {t!r}")
            continue
        if b.get("id") in seen:
            problems.append(f"{at}: duplicate id")
        seen.add(b.get("id"))
        for text in (b.get("markdown") or "",):
            if t == "text" and len(re.findall(r"^## ", text, re.M)) > 1:
                problems.append(f"{at}: more than one ## section in a text block")
            for course, lesson in LINK.findall(text):
                if lesson not in slugs.get(course, ()):
                    problems.append(f"{at}: link to unknown lesson /learn/{course}/{lesson}")
        if t == "callout" and b.get("variant") not in CALLOUTS:
            problems.append(f"{at}: callout variant {b.get('variant')!r}")
        if t == "code" and b.get("language") not in LANGS:
            problems.append(f"{at}: code language {b.get('language')!r}")
        if t == "terminal" and not b.get("lines"):
            problems.append(f"{at}: terminal without lines")
        if t == "quiz":
            for q, question in enumerate(b.get("questions") or []):
                answers = question.get("answer")
                answers = answers if isinstance(answers, list) else [answers]
                n = len(question.get("options") or [])
                if n < 2 or any(not isinstance(a, int) or a < 0 or a >= n for a in answers):
                    problems.append(f"{at}: question {q + 1} answer out of range")
                if not question.get("explanation"):
                    problems.append(f"{at}: question {q + 1} has no explanation")
        if t == "animation":
            data = b.get("data") or {}
            kind = b.get("kind")
            steps = data.get("steps") or []
            for x in (data.get("actors") or []) + (data.get("nodes") or []):
                if x.get("icon") and x["icon"] not in ICONS:
                    problems.append(f"{at}: icon {x['icon']!r}")
            for s in steps:
                if s.get("tone") and s["tone"] not in TONES:
                    problems.append(f"{at}: tone {s['tone']!r}")
            if kind == "sequence":
                ids = {a.get("id") for a in data.get("actors") or []}
                if any(s.get("from") not in ids or s.get("to") not in ids for s in steps):
                    problems.append(f"{at}: sequence step refers to an unknown actor")
            elif kind == "flow":
                ids = {nd.get("id") for nd in data.get("nodes") or []}
                if any(s.get("at") not in ids for s in steps):
                    problems.append(f"{at}: flow step refers to an unknown node")
            elif kind == "layers":
                n = len(data.get("layers") or [])
                if any(not isinstance(s.get("layer"), int) or not 0 <= s["layer"] < n for s in steps):
                    problems.append(f"{at}: layers step index out of range")
            elif kind == "cycle":
                if not data.get("items"):
                    problems.append(f"{at}: cycle without items")
            else:
                problems.append(f"{at}: animation kind {kind!r}")
            if kind != "cycle" and not steps:
                problems.append(f"{at}: animation without steps")


def main():
    syllabi = load_syllabi()
    slugs = {c: {l["slug"] for s in d["stages"] for u in s["units"] for l in u["lessons"]} for c, d in syllabi.items()}
    problems = []
    files = sorted(glob.glob(os.path.join(BASE, "lessons", "*", "*.json")))

    for path in files:
        course = os.path.basename(os.path.dirname(path))
        slug = os.path.basename(path)[:-5]
        where = f"{course}/{slug}"
        try:
            with open(path, encoding="utf8") as f:
                d = json.load(f)
        except json.JSONDecodeError as error:
            problems.append(f"{where}: invalid JSON ({error})")
            continue
        if course not in slugs or slug not in slugs[course]:
            problems.append(f"{where}: not in the syllabus")
        if d.get("slug") != slug:
            problems.append(f"{where}: slug field does not match the file name")
        for key in ("title", "summary", "objectives", "blocks"):
            if not d.get(key):
                problems.append(f"{where}: missing {key}")
        check_blocks(d.get("blocks") or [], where, slugs, problems)
        ar = d.get("ar")
        if not ar:
            problems.append(f"{where}: no Arabic version (ar)")
            continue
        for key in ("title", "summary", "objectives", "blocks"):
            if not ar.get(key):
                problems.append(f"{where}: ar.{key} missing")
        en_shape = [(b.get("id"), b.get("type")) for b in d.get("blocks") or []]
        ar_shape = [(b.get("id"), b.get("type")) for b in ar.get("blocks") or []]
        if en_shape != ar_shape:
            problems.append(f"{where}: ar.blocks do not mirror blocks (ids/types/order)")
        check_blocks(ar.get("blocks") or [], where + " [ar]", slugs, problems)

    for p in problems:
        print("✗", p)
    print(f"\n{len(files)} lesson files, {len(problems)} problems")

    if "--progress" in sys.argv:
        written = {(os.path.basename(os.path.dirname(p)), os.path.basename(p)[:-5]) for p in files}
        total = done = review = 0
        for course, doc in syllabi.items():
            print(f"\n{course}")
            for stage in doc["stages"]:
                lessons = [l for u in stage["units"] for l in u["lessons"]]
                w = sum((course, l["slug"]) in written for l in lessons)
                r = sum("review_of" in l and (course, l["slug"]) not in written for l in lessons)
                total += len(lessons)
                done += w
                review += r
                print(f"  {stage['title']:<55} {w:>3} written  {r:>3} review  {len(lessons) - w - r:>3} to write")
        print(f"\nTotal {total}: {done} written, {review} review pointers, {total - done - review} to write")

    sys.exit(1 if problems else 0)


if __name__ == "__main__":
    main()
