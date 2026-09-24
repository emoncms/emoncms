#!/usr/bin/env python3
"""Rename Bootstrap 2 classes in CSS selectors (css files and <style> blocks). Declarations and comments are left alone."""
import re, sys

MAP = [
    (r"\.input-prepend\.input-append|\.input-append\.input-prepend", ".input-group"),
    (r"\.input-prepend|\.input-append", ".input-group"),
    (r"\.add-on", ".input-group-text"),
    (r"\.btn-small", ".btn-sm"),
    (r"\.btn-mini", ".btn-xs"),
    (r"\.btn-large", ".btn-lg"),
    (r"\.btn-inverse", ".btn-dark"),
    (r"\.alert-error", ".alert-danger"),
    (r"\.muted", ".text-muted"),
    (r"\.text-error", ".text-danger"),
    (r"\.pull-right", ".float-end"),
    (r"\.pull-left", ".float-start"),
    (r"\.table-condensed", ".table-sm"),
]
RX = [(re.compile(a + r"(?![\w-])"), b) for a, b in MAP]

def sel_convert(sel):
    for rx, rep in RX:
        sel = rx.sub(rep, sel)
    return sel

def css(src, log):
    out, pos = [], 0
    for m in re.finditer(r"([^{}]*)\{", src):
        chunk = m.group(1)
        parts = re.split(r"(/\*.*?\*/)", chunk, flags=re.S)
        new = "".join(p if p.startswith("/*") else sel_convert(p) for p in parts)
        if new != chunk:
            log.append((" ".join(chunk.split())[-80:], " ".join(new.split())[-80:]))
        out.append(src[pos:m.start()] + new + "{")
        pos = m.end()
    out.append(src[pos:])
    return "".join(out)

for f in sys.argv[1:]:
    s = open(f).read(); log = []
    if f.endswith(".css"):
        n = css(s, log)
    else:
        n = re.sub(r"(<style[^>]*>)(.*?)(</style>)", lambda m: m.group(1) + css(m.group(2), log) + m.group(3), s, flags=re.S)
    if n != s:
        open(f, "w").write(n)
    print(f"== {f}: {len(log)} selector(s)")
    for a, b in log:
        print("  ", a, "->", b)
