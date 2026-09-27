#!/usr/bin/env python3
# csscount.py [-v]: rule, selector and declaration counts for the hand written CSS (same file set
# as cssbytes.sh), the <style> blocks in views and bootstrap.min.css. -v lists each file.
# A rule is a block with a selector list. Keyframe frames and at-rule preludes are not rules,
# but their declarations count.
import os, re, sys
root = os.path.normpath(os.path.join(os.path.dirname(__file__), "..", ".."))
os.chdir(root)
VENDOR = re.compile(r"bootstrap\.min\.css|bootstrap2-icons\.css|svg-icons\.css|montserrat\.css")
SKIP = ("node_modules", "vendor", "tools", "scripts", ".git")
STYLE = re.compile(r"<style[^>]*>(.*?)</style>", re.S)

def files(exts):
    for d, dirs, fs in os.walk(".", followlinks=True):
        dirs[:] = [x for x in dirs if x not in SKIP]
        for f in fs:
            if f.endswith(exts): yield os.path.join(d, f)

def read(p):
    return open(p, encoding="utf-8", errors="replace").read()

def count(css):
    css = re.sub(r"/\*.*?\*/", "", css, flags=re.S)
    rules = selectors = decls = 0
    stack, buf, quote, paren = [], [], None, 0
    for ch in css:
        if quote:
            if ch == quote: quote = None
            buf.append(ch); continue
        if ch in "\"'": quote = ch; buf.append(ch); continue
        if ch == "(": paren += 1
        elif ch == ")": paren = max(0, paren - 1)
        if paren or ch not in "{};": buf.append(ch); continue
        text = "".join(buf).strip(); buf = []
        if ch == "{":
            frame = any(s.startswith("@keyframes") or s.startswith("@-webkit-keyframes") for s in stack)
            stack.append(text)
            if text and not text.startswith("@") and not frame:
                rules += 1
                selectors += len(re.split(r",(?![^(]*\))", text))
        else:
            if text and stack: decls += 1
            if ch == "}" and stack: stack.pop()
    return rules, selectors, decls

hand = {p: read(p) for p in files((".css",)) if not VENDOR.search(p)}
blocks = {p: "\n".join(STYLE.findall(read(p))) for p in files((".php", ".html"))}
blocks = {p: t for p, t in blocks.items() if t}
bootstrap = {"Lib/bootstrap5/css/bootstrap.min.css": read("Lib/bootstrap5/css/bootstrap.min.css")}

def row(name, files):
    r = s = d = 0
    for t in files.values():
        a, b, c = count(t); r += a; s += b; d += c
    print(f"{name:22}{len(files):>6}{r:>7}{s:>10}{d:>13}")
    if "-v" in sys.argv:
        for p, t in sorted(files.items(), key=lambda x: -count(x[1])[2]):
            a, b, c = count(t); print(f"  {p[2:]:52}{a:>7}{b:>10}{c:>13}")

print(f"{'':22}{'files':>6}{'rules':>7}{'selectors':>10}{'declarations':>13}")
row("hand written .css", hand)
row("<style> blocks", blocks)
row("bootstrap.min.css", bootstrap)
