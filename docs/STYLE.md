# User guide style

Style for `docs/user_guide/`, published at docs.openenergymonitor.org/emoncms. The writing rules in `CLAUDE.md` also apply.

## Publishing

- The guide is built with Sphinx and MyST as part of the OpenEnergyMonitor docs site.
- File names are public URLs. Do not rename a page. To retire one, replace its content with a short pointer and add `orphan: true` front matter.
- Page order and sections are set in `index.rst`.
- Link to other guide pages as `page.md` and to other parts of the docs site as `../section/page.md`.
- Use MyST admonitions (```` ```{note} ````, ```` ```{tip} ````, ```` ```{warning} ````). Do not use raw HTML for notes or code.
- Use tables in place of definition lists. The `deflist` extension is not enabled.
- Check a change with `sphinx-build` against the docs repo before publishing.

## Writing

- "Emoncms" in prose, `emoncms` in URLs and code. Hardware keeps its own casing: emonPi, emonBase, emonTx4.
- Start each page with one sentence on what it helps the reader do.
- Steps in the imperative: "Click **Save**."
- UI labels in **bold**, matching the label text exactly.
- Menu paths as **Setup > Feeds**.
- Paths, commands, feed names and input names in `code`.
- Sentence case headings.
- Background in a short paragraph or admonition before the steps.

## Page template

```
# Page title

One sentence on what the page helps you do.

## Before you start
- Prerequisite, with link.

## Steps
1. Step.
   ![Alt text](img/page-step1.png)
2. Step.

## Result
What you should see.

## Related
- [Page](page.md)
```

Reference pages, such as Feeds and Admin, use one section per screen area in place of steps.

## Screenshots

- Capture with `scripts/docs-screenshots`. Add each screenshot to its `manifest.json`.
- Name images `<page>-<n>-<subject>.webp`, for example `feeds-1-list.webp`.
- One screenshot per step at most. Leave it out if the step is obvious.
- Alt text describes the content.
- Remove images that no page references.
