#!/usr/bin/env python3
"""Convert Bootstrap 2 modal markup to Bootstrap 5 in the given files.

- .modal: drop hide, data-backdrop/keyboard -> data-bs-*
- wrap children in .modal-dialog > .modal-content
- close button -> .btn-close, moved to the end of .modal-header
- header h3 gets .modal-title
- data-dismiss="modal" -> data-bs-dismiss="modal"
"""
import re, sys

OPEN_MODAL = re.compile(r'<div\b[^>]*\bclass="([^"]*\bmodal\b[^"]*)"[^>]*>')
DIV_TOKEN = re.compile(r"<div\b|</div\s*>")

def match_close(s, start):
    """Index of the </div> that closes the <div opened at start."""
    depth = 0
    for m in DIV_TOKEN.finditer(s, start):
        if m.group().startswith("</"):
            depth -= 1
            if depth == 0:
                return m.start()
        else:
            depth += 1
    raise ValueError("unbalanced div at %d" % start)

CLOSE_BTN = re.compile(r'\s*<button\b[^>]*\bclass="close"[^>]*>.*?</button>', re.S)

def convert_header(h):
    """h is the full .modal-header element."""
    m = CLOSE_BTN.search(h)
    if m:
        btn = m.group().strip()
        attrs = re.search(r"<button\b([^>]*)>", btn).group(1)
        attrs = attrs.replace('class="close"', 'class="btn-close"')
        attrs = re.sub(r'\s*aria-hidden="true"', "", attrs)
        if "aria-label" not in attrs:
            attrs += ' aria-label="Close"'
        new_btn = "<button%s></button>" % attrs
        h = h[:m.start()] + h[m.end():]
        end = h.rfind("</div>")
        # keep indentation of the header's children
        indent = re.search(r"\n(\s*)\S", h[h.index(">") + 1:])
        ind = indent.group(1) if indent else "    "
        close_ind = re.search(r"([ \t]*)$", h[:end]).group(1)
        h = h[:end].rstrip() + "\n" + ind + new_btn + "\n" + close_ind + h[end:]
    h = re.sub(r"<h3\b(?![^>]*modal-title)([^>]*)>", lambda m: "<h3" + add_class(m.group(1), "modal-title") + ">", h, count=1)
    return h

def add_class(attrs, cls):
    if 'class="' in attrs:
        return re.sub(r'class="([^"]*)"', lambda m: 'class="%s %s"' % (m.group(1), cls), attrs, count=1)
    return attrs + ' class="%s"' % cls

def convert(s):
    out, pos, n = [], 0, 0
    while True:
        m = OPEN_MODAL.search(s, pos)
        if not m:
            break
        classes = m.group(1).split()
        if "modal" not in classes or "modal-dialog" in s[m.end():m.end() + 200]:
            out.append(s[pos:m.end()]); pos = m.end(); continue
        close = match_close(s, m.start())
        tag = m.group()
        new_classes = " ".join(c for c in classes if c != "hide")
        tag = tag.replace('class="%s"' % m.group(1), 'class="%s"' % new_classes)
        tag = re.sub(r'\bdata-(backdrop|keyboard)=', r"data-bs-\1=", tag)
        tag = re.sub(r'\s*role="dialog"', "", tag)
        inner = s[m.end():close]
        # header
        hm = re.search(r'<div\b[^>]*\bclass="[^"]*\bmodal-header\b[^"]*"[^>]*>', inner)
        if hm:
            he = match_close(inner, hm.start()) + len("</div>")
            inner = inner[:hm.start()] + convert_header(inner[hm.start():he]) + inner[he:]
        base_indent = re.search(r"([ \t]*)$", s[:m.start()]).group(1)
        inner = re.sub(r"\n", "\n    ", inner.rstrip())  # indent by two levels (4+4)
        inner = re.sub(r"\n    ", "\n        ", inner)
        wrapped = ("\n%s    <div class=\"modal-dialog\">\n%s        <div class=\"modal-content\">%s\n%s        </div>\n%s    </div>\n%s"
                   % (base_indent, base_indent, inner, base_indent, base_indent, base_indent))
        out.append(s[pos:m.start()] + tag + wrapped)
        pos = close
        n += 1
    out.append(s[pos:])
    s = "".join(out)
    s = re.sub(r'\bdata-dismiss="modal"', 'data-bs-dismiss="modal"', s)
    return s, n

if __name__ == "__main__":
    for f in sys.argv[1:]:
        src = open(f).read()
        res, n = convert(src)
        open(f, "w").write(res)
        print(f, n, "modal(s)")
