"""Give lesson links in translated lessons the translated lesson title.

Translators leave `[Centralized SIEM Lab](/learn/<course>/<slug>)` in English. When a
link's text is exactly the English title of the lesson it points at, this swaps in that
lesson's title from translations/<locale>/curriculum/<course>.json (translated in phase 0).
Any other link text is left alone.

    python -I -X utf8 tools/link_titles.py <locale> [<locale> ...]
"""
import glob
import json
import os
import re
import sys

ROOT = os.path.join(os.path.dirname(os.path.abspath(__file__)), "..", "database", "seeders")
LINK = re.compile(r"\[([^\]]+)\]\((/learn/([a-z0-9-]+)/([a-z0-9-]+)|\{C\}([a-z0-9-]+))\)")


def load(path):
    with open(path, encoding="utf8") as f:
        return json.load(f)


def save(path, data):  # same format as tools/i18n.py
    with open(path, "w", encoding="utf8", newline="\n") as f:
        json.dump(data, f, ensure_ascii=False, indent=2)
        f.write("\n")


def english_titles():
    out = {}
    for path in glob.glob(os.path.join(ROOT, "lessons", "*", "*.json")):
        course = os.path.basename(os.path.dirname(path))
        out[(course, os.path.basename(path)[:-5])] = load(path).get("title", "")
    return out


def fix_text(text, course, en, tr):
    def swap(m):
        label, target_course, slug = m.group(1), m.group(3) or course, m.group(4) or m.group(5)
        title = tr.get((target_course, slug))
        if title and label.strip().lower() == en.get((target_course, slug), "").strip().lower():
            return f"[{title}]({m.group(2)})"
        return m.group(0)

    return LINK.sub(swap, text)


def walk(node, fn):
    if isinstance(node, str):
        return fn(node)
    if isinstance(node, list):
        return [walk(v, fn) for v in node]
    if isinstance(node, dict):
        return {k: walk(v, fn) for k, v in node.items()}
    return node


def run(locale, en):
    tr = {}
    for path in glob.glob(os.path.join(ROOT, "translations", locale, "curriculum", "*.json")):
        course = os.path.basename(path)[:-5]
        for slug, title in (load(path).get("lessons") or {}).items():
            tr[(course, slug)] = title if isinstance(title, str) else title.get("title")
    changed = 0
    for path in glob.glob(os.path.join(ROOT, "translations", locale, "lessons", "*", "*.json")):
        course = os.path.basename(os.path.dirname(path))
        data = load(path)
        fixed = walk(data, lambda s: fix_text(s, course, en, tr))
        if fixed != data:
            save(path, fixed)
            changed += 1
    print(f"{locale}: {changed} lesson files updated")


def selftest():
    en = {("c", "a"): "Alpha Lab"}
    tr = {("c", "a"): "Alfa Laboratuvarı"}
    assert fix_text("See [Alpha Lab](/learn/c/a).", "c", en, tr) == "See [Alfa Laboratuvarı](/learn/c/a)."
    assert fix_text("See [alpha lab]({C}a).", "c", en, tr) == "See [Alfa Laboratuvarı]({C}a)."
    assert fix_text("See [the lab](/learn/c/a).", "c", en, tr) == "See [the lab](/learn/c/a)."  # not the title
    assert fix_text("See [Alpha Lab](/learn/c/zz).", "c", en, tr) == "See [Alpha Lab](/learn/c/zz)."  # unknown


if __name__ == "__main__":
    selftest()
    titles = english_titles()
    for loc in sys.argv[1:] or ["hi", "zh", "tr"]:
        run(loc, titles)
