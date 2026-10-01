# User guide screenshots

Captures the screenshots in `docs/img` from a running Emoncms, so they can be taken again after UI changes.

## Setup

1. Install the dependencies:

   ```
   cd scripts/docs-screenshots
   npm install
   npx playwright install chromium
   ```

   Or set `CHROME_PATH` to an installed Chrome, for example `/usr/bin/google-chrome`.

2. Use a local account for the screenshots. The default is `test`. A separate account keeps personal details out of the screenshots.

3. Load the demo data set into the account. It needs the [testdataset](https://github.com/emoncms/testdataset) module in `/opt/emoncms/modules/testdataset` with `phpfina.zip` unzipped:

   ```
   sudo ./load-dataset.sh <userid>
   ```

   This loads a year of heat pump, solar and household data, ending today. Run it again before a capture to bring the data up to date.

   For an account already set up by the testdataset module's own `scripts/settings.php`, run its scripts from the module folder instead:

   ```
   cd /opt/emoncms/modules/testdataset
   sudo php scripts/add_feeds_to_account.php
   sudo php scripts/post_process.php
   ```

## Capture

```
node capture.mjs
```

Options:

- `--only <prefix>`: capture the shots whose name starts with the prefix, for example `--only feeds`.
- `--list`: list the shots.
- `--no-seed`: do not post the seed input values.

Environment:

| Variable | Default |
|---|---|
| `EMONCMS_URL` | `http://localhost/emoncms` |
| `EMONCMS_USER` | `test` |
| `EMONCMS_PASS` | `test` |
| `CHROME_PATH` | Playwright's Chromium |
| `OUTDIR` | `docs/img` |

Before capturing, the script posts the `seed` values in `manifest.json` as inputs, so that the **Inputs** page shows recent data.

## Manifest

Each entry in `shots` is one image:

| Field | Meaning |
|---|---|
| `name` | Output file name without extension. Use `<page>-<n>-<subject>` |
| `page` | User guide page that uses the image |
| `url` | Emoncms path, relative to `EMONCMS_URL` |
| `waitFor` | Selector to wait for after loading |
| `actions` | Steps to run before the capture |
| `element` | Selector to capture. Without it, the viewport is captured |
| `fullPage` | Capture the whole page |
| `viewport`, `maxWidth`, `settle` | Override the defaults |

Actions, one per object: `click`, `check`, `hover`, `fill` (`[selector, text]`), `select` (`[selector, value]`), `press`, `waitFor`, `wait` (ms), `eval` (JavaScript).

Do not add actions that save changes. A capture should leave the account as it was, apart from the seed inputs.

## Conventions

- Viewport 1200 × 750 at 2x, resized to 1600 px wide. WebP at quality 80.
- Light theme.
- Crop dialogs to the dialog with `element`.
- Hardware photos, emoncms.org pages and third party interfaces are not captured by this script.
