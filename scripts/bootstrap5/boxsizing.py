#!/usr/bin/env python3
"""Find (and with --fix, patch) emoncms CSS rules that set a size together with padding or border.

Such rules were written for content-box. Bootstrap 5 sets border-box on every element.
Usage: boxsizing.py [--fix] files...
"""
import re, sys

SIZE = re.compile(r"(?<![-\w])(width|height|min-width|min-height|max-width|max-height)\s*:\s*([^;}]+)")
PAD = re.compile(r"(?<![-\w])(padding(-top|-right|-bottom|-left)?|border(-top|-right|-bottom|-left)?(-width)?)\s*:\s*([^;}]+)")

def zeroish(v):
    v = v.strip().lower()
    return v in ("0", "0px", "none", "0 0", "0px 0px", "0 0 0 0", "inherit", "initial", "unset") or v.startswith(("none", "0 solid", "0px solid"))

def relevant_size(v):
    v = v.strip().lower()
    return not (v in ("auto", "none", "inherit", "initial", "unset", "0", "0px", "fit-content", "max-content", "min-content")
                or v.endswith("%") and False)

def scan(src):
    """Yield (start, end, selector, body) for rules that need content-box."""
    for m in re.finditer(r"([^{}@;][^{}]*)\{([^{}]*)\}", src):
        sel, body = m.group(1).strip(), m.group(2)
        if sel.startswith(("@", "from", "to")) or re.match(r"^[\d.]+%", sel):
            continue
        if "box-sizing" in body:
            continue
        sizes = [s for s in SIZE.findall(body) if relevant_size(s[1])]
        pads = [p for p in PAD.findall(body) if not zeroish(p[4])]
        if sizes and pads:
            yield m.start(2), m.end(2), sel, body

if __name__ == "__main__":
    fix = "--fix" in sys.argv
    def declaration(body):
        """box-sizing declaration on its own line, indented as the rule body."""
        ind = re.search(r"\n([ \t]+)\S", body)
        if "\n" not in body: return " box-sizing: content-box;"
        return "\n" + (ind.group(1) if ind else "    ") + "box-sizing: content-box;"
    files = [a for a in sys.argv[1:] if a != "--fix"]
    total = 0
    for f in files:
        if f.endswith(".php"):
            full = open(f).read(); n = 0
            def blk(m):
                global total
                inner = m.group(2); hits = list(scan(inner))
                for a_, b_, sel, body in hits: print(f"== {f}:", " ".join(sel.split())[-80:])
                if fix:
                    o, q = [], 0
                    for a_, b_, sel, body in hits:
                        o.append(inner[q:a_] + declaration(body) + body); q = b_
                    o.append(inner[q:]); inner = "".join(o)
                return m.group(1) + inner + m.group(3)
            new = re.sub(r"(<style[^>]*>)(.*?)(</style>)", blk, full, flags=re.S)
            if fix and new != full: open(f, "w").write(new)
            continue
        src = open(f).read()
        hits = list(scan(src))
        if not hits:
            continue
        print(f"== {f}: {len(hits)}")
        for a, b, sel, body in hits:
            print("   ", " ".join(sel.split())[:90])
        total += len(hits)
        if fix:
            out, pos = [], 0
            for a, b, sel, body in hits:
                out.append(src[pos:a] + declaration(body) + body)
                pos = b
            out.append(src[pos:])
            open(f, "w").write("".join(out))
    print("total", total)
