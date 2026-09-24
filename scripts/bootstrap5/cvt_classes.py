#!/usr/bin/env python3
"""Convert Bootstrap 2 class names to Bootstrap 5 in class contexts only.

Contexts: class="..", class='..', class=\"..\", Vue :class string literals,
jQuery addClass/removeClass/toggleClass/hasClass arguments.
Also renames data-toggle/dismiss/target/placement attributes.
Prints every change so it can be reviewed.
"""
import re, sys

RENAME = {
    "btn-small": "btn-sm", "btn-mini": "btn-xs", "btn-large": "btn-lg", "btn-inverse": "btn-dark",
    "alert-error": "alert-danger",
    "label-important": "bg-danger", "label-warning": "bg-warning", "label-success": "bg-success",
    "label-info": "bg-info", "label-inverse": "bg-dark",
    "badge-important": "bg-danger", "badge-warning": "bg-warning", "badge-success": "bg-success",
    "badge-info": "bg-info", "badge-inverse": "bg-dark",
    "muted": "text-muted", "text-error": "text-danger",
    "pull-right": "float-end", "pull-left": "float-start",
    "text-left": "text-start", "text-right": "text-end",
    "add-on": "input-group-text",
    "table-condensed": "table-sm",
    "icon-white": "icon-white",
}
for n in range(0, 7):
    for a, b in (("mr", "me"), ("ml", "ms"), ("pr", "pe"), ("pl", "ps")):
        RENAME[f"{a}-{n}"] = f"{b}-{n}"
        for bp in ("sm", "md", "lg", "xl"):
            RENAME[f"{a}-{bp}-{n}"] = f"{b}-{bp}-{n}"

BTN_VARIANTS = {"btn-primary", "btn-info", "btn-success", "btn-warning", "btn-danger", "btn-inverse", "btn-dark",
                "btn-link", "btn-default", "btn-secondary", "btn-light", "btn-close"}
ALERT_VARIANTS = {"alert-error", "alert-danger", "alert-success", "alert-info", "alert-warning", "alert-primary"}

def convert_tokens(tokens, dynamic=False):
    """tokens: list of class names. dynamic: Vue/jQuery context where variants may be added elsewhere."""
    t = list(tokens)
    s = set(t)
    out = []
    for c in t:
        if c == "label" and not dynamic:
            out.append("badge")
            if not any(x.startswith(("label-", "bg-")) for x in s):
                out.append("bg-secondary")
            continue
        if c == "label" and dynamic:
            out.append("badge"); continue
        if c == "badge":
            out.append("badge")
            if "rounded-pill" not in s:
                out.append("rounded-pill")
            if not dynamic and not any(x.startswith(("badge-", "bg-")) for x in s):
                out.append("bg-secondary")
            continue
        if c in ("input-prepend", "input-append"):
            if "input-group" not in out and "input-group" not in s:
                out.append("input-group")
            continue
        if c in ("well",):
            out += ["bg-body-tertiary", "border", "rounded", "p-3", "mb-3"]; continue
        if c == "well-small":
            out = [x for x in out if x not in ("p-3",)] + ["p-2"]; continue
        if c == "alert-block":
            continue
        out.append(RENAME.get(c, c))
    if "btn" in out and not dynamic and not (set(out) & BTN_VARIANTS):
        out.insert(out.index("btn") + 1, "btn-default")
    if "alert" in out and not dynamic and not (set(out) & (ALERT_VARIANTS | {"alert-warning"})):
        out.insert(out.index("alert") + 1, "alert-warning")
    # remove duplicates, keep order
    seen, res = set(), []
    for c in out:
        if c not in seen:
            seen.add(c); res.append(c)
    return res

TOKEN = re.compile(r"(?<![\w{$-])-?[A-Za-z_][\w-]*")
SKIP = re.compile(r"<\?.*?\?>|\{\{.*?\}\}|\$\{[^}]*\}", re.S)

def convert_class_string(v, dynamic=False):
    # keep PHP / mustache / template parts untouched
    parts, pos = [], 0
    for m in SKIP.finditer(v):
        parts.append(("t", v[pos:m.start()])); parts.append(("x", m.group())); pos = m.end()
    parts.append(("t", v[pos:]))
    text = " ".join(p for k, p in parts if k == "t")
    toks = text.split()
    if not toks:
        return v
    new = convert_tokens(toks, dynamic)
    if new == toks:
        return v
    # rebuild: replace the plain-token segments, keep non-token parts in place
    out, used = [], False
    for k, p in parts:
        if k == "x":
            out.append(p)
        elif not used and p.strip():
            lead = re.match(r"\s*", p).group(); trail = re.search(r"\s*$", p).group()
            out.append(lead + " ".join(new) + trail); used = True
        elif p.strip():
            out.append(re.match(r"\s*", p).group())  # tokens already emitted
        else:
            out.append(p)
    return "".join(out)

ATTR = re.compile(r"""(?<![:\w-])(class\s*=\s*)(\\?["'])(.*?)(\2)""", re.S)
VCLASS = re.compile(r"""((?::|v-bind:)class\s*=\s*")([^"]*)(")""")
STRLIT = re.compile(r"""(['`])((?:(?!\1).)*)\1""")
JQ = re.compile(r"""(\.(?:addClass|removeClass|toggleClass|hasClass)\(\s*)(["'])(.*?)(\2)""")
DATA = [(re.compile(r"\bdata-(toggle|dismiss|target|placement|backdrop|keyboard|parent|content|container|trigger|html|offset|spy|ride|slide)="), r"data-bs-\1=")]

def convert(src, log):
    def attr(m):
        if re.search(r"""['"]\s*\+|\+\s*['"]""", m.group(3)):
            log.append((m.group(3), "SKIPPED: string concatenation, review by hand"))
            return m.group()
        new = convert_class_string(m.group(3))
        if new != m.group(3): log.append((m.group(3), new))
        return m.group(1) + m.group(2) + new + m.group(4)
    src = ATTR.sub(attr, src)
    def vcls(m):
        def lit(l):
            new = convert_class_string(l.group(2), dynamic=True)
            if new != l.group(2): log.append((l.group(2), new))
            return l.group(1) + new + l.group(1)
        return m.group(1) + STRLIT.sub(lit, m.group(2)) + m.group(3)
    src = VCLASS.sub(vcls, src)
    def jq(m):
        new = convert_class_string(m.group(3), dynamic=True)
        if new != m.group(3): log.append((m.group(3), new))
        return m.group(1) + m.group(2) + new + m.group(4)
    src = JQ.sub(jq, src)
    for rx, rep in DATA:
        src, n = rx.subn(rep, src)
        if n: log.append(("data-*", f"{n} attribute(s) renamed"))
    return src

if __name__ == "__main__":
    for f in sys.argv[1:]:
        src = open(f).read(); log = []
        res = convert(src, log)
        if res != src:
            open(f, "w").write(res)
        print(f"== {f}: {len(log)} change(s)")
        for a, b in log:
            print(f"   {a.strip()[:70]!r} -> {b.strip()[:70]!r}")
