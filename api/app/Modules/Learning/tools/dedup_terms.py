# "X (X)" -> "X" in translated files of one locale (acronym terms repeated in brackets).
import glob, json, re, sys
workdir, locale = sys.argv[1], sys.argv[2]
pat = re.compile(r"^(.+?)\s*[(（]\s*\1\s*[)）]$")
for p in glob.glob(f"{workdir}/*.{locale}.json"):
    d = json.load(open(p, encoding="utf8"))
    fixed = {k: pat.sub(r"\1", v) for k, v in d.items() if isinstance(v, str) and pat.match(v)}
    if fixed:
        d.update(fixed)
        json.dump(d, open(p, "w", encoding="utf8"), ensure_ascii=False, indent=2)
        print(p.rsplit("/", 1)[-1], fixed)
assert pat.sub(r"\1", "GRE over IPsec (GRE over IPsec)") == "GRE over IPsec"
assert not pat.match("单臂路由 (Router on a stick)")
