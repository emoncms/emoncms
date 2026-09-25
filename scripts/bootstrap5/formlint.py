#!/usr/bin/env python3
# Lists form fields without form-control or form-select, and legacy form classes.
# Usage: formlint.py dirs-or-files...
import os, re, sys

EXT = (".php", ".html", ".js", ".vue")
SKIP_DIRS = {"node_modules", ".git", "archive", "bootstrap5", "bootstrap-datetimepicker-0.0.11", "flot"}
SKIP_TYPES = {"checkbox", "radio", "hidden", "button", "submit", "reset", "image", "range"}
LEGACY = re.compile(r"\b(control-group|control-label|help-inline|help-block|input-block-level|"
                    r"input-(?:mini|small|medium|large|xlarge|xxlarge)|form-horizontal|form-inline|"
                    r"form-actions|uneditable-input)\b")
TAG = re.compile(r"<(input|select|textarea)(?=[\s/>])(.*?)/?>", re.S | re.I)
# selects styled as buttons and visually hidden fields
OWN_STYLE = re.compile(r"""class\s*=\s*\\?["'][^"']*\b(btn|visually-hidden)\b""")
TYPE = re.compile(r"""\btype\s*=\s*\\?["']?([a-z-]+)""", re.I)
CREATE = re.compile(r"""createElement\(\s*["'](input|select|textarea)["']""")
# checkbox and radio labels from Bootstrap 2
LABEL = re.compile(r"""class\s*=\s*\\?["'](?:[^"']*\s)?(checkbox|radio)(?:\s[^"']*)?\\?["']""")

def php_blank(s):
    # PHP blocks can hold '>' and quotes; keep newlines so line numbers hold
    return re.sub(r"<\?(?:php|=).*?\?>", lambda m: "PHP" + "\n" * m.group(0).count("\n"), s, flags=re.S)

def files(paths):
    for p in paths:
        if os.path.isfile(p):
            yield p
            continue
        for root, dirs, fs in os.walk(p, followlinks=True):
            dirs[:] = [d for d in dirs if d not in SKIP_DIRS]
            for f in fs:
                if f.endswith(EXT):
                    yield os.path.join(root, f)

def line_of(s, pos):
    return s.count("\n", 0, pos) + 1

n = 0
seen = set()
for f in files(sys.argv[1:] or ["."]):
    real = os.path.realpath(f)
    if real in seen:
        continue
    seen.add(real)
    try:
        s = php_blank(open(f, encoding="utf-8", errors="replace").read())
    except OSError:
        continue
    for m in TAG.finditer(s):
        attrs = m.group(2)
        t = TYPE.search(attrs)
        if t and t.group(1).lower() in SKIP_TYPES:
            continue
        if "form-control" in attrs or "form-select" in attrs or OWN_STYLE.search(attrs):
            continue
        snippet = " ".join(m.group(0).split())[:110]
        print(f"{f}:{line_of(s, m.start())}: field  {snippet}")
        n += 1
    for m in CREATE.finditer(s):
        print(f"{f}:{line_of(s, m.start())}: create {m.group(0)}")
        n += 1
    for m in LEGACY.finditer(s):
        print(f"{f}:{line_of(s, m.start())}: class  {m.group(0)}")
        n += 1
    for m in LABEL.finditer(s):
        print(f"{f}:{line_of(s, m.start())}: label  {m.group(1)}")
        n += 1
print(f"{n} found", file=sys.stderr)
