# Translator brief (hi, zh, tr)

Input: `<slug>.src.json`, a flat JSON object `"path": "English text"` from a
networking/cybersecurity lesson. Output: `<slug>.<locale>.json` next to it with
EXACTLY the same keys, each value the translation as a plain string. Write it
with a Python script (UTF-8, `ensure_ascii=False`); never add or drop keys.

Every value is translated in full: headings, paragraphs, tables, quiz
questions, options and explanations, key terms and definitions, animation
labels and captions, terminal comments. Do not shorten, summarise or add.

Keep exactly:
- Markdown: `##` headings, `**bold**`, `*italic*`, tables (`|` and the `:--` row), list markers, `\n`.
- `code spans`, URLs, `/learn/...` link targets, file paths, commands, IPs, numbers, units.
- Acronyms, products, protocols, certifications and exam codes in Latin (Cisco, CCNA, VLAN, OSPF, SIEM, Zero Trust, "200-301").
- In key terms, keep the English term in parentheses after the translated one: `"स्पैनिंग ट्री (Spanning Tree)"`.
- A value that is wholly a command, config line or product name stays as is.

Per language:
- **hi**: Devanagari, modern standard Hindi of Indian tech education; English technical terms stay in Latin where Indian learners use them ("VLAN कॉन्फ़िगर करना").
- **zh**: Simplified Chinese (zh-CN), mainland textbook terms (防火墙, 交换机, 路由器, 访问控制列表 (ACL)); full-width punctuation in Chinese sentences.
- **tr**: Standard Turkish of Turkish IT education, neutral formal register; suffix after an acronym with an apostrophe (VLAN'lar, OSPF'in); güvenlik duvarı, anahtar, yönlendirici.

Verify before finishing:

```
python -I -c "import json,sys;a=json.load(open(sys.argv[1],encoding='utf8'));b=json.load(open(sys.argv[2],encoding='utf8'));assert set(a)==set(b) and all(str(v).strip() for v in b.values());print('ok')" <slug>.src.json <slug>.<locale>.json
```
