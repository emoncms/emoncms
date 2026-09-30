// Capture user guide screenshots from a running Emoncms.
// Usage: node capture.mjs [--only <name-prefix>] [--list] [--no-seed]
// Settings from environment: EMONCMS_URL, EMONCMS_USER, EMONCMS_PASS, EMONCMS_ADMIN_USER, EMONCMS_ADMIN_PASS, CHROME_PATH, OUTDIR

import { chromium } from 'playwright';
import sharp from 'sharp';
import { readFile, mkdir, writeFile } from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const here = path.dirname(fileURLToPath(import.meta.url));
const manifest = JSON.parse(await readFile(path.join(here, 'manifest.json'), 'utf8'));
const defaults = manifest.defaults;

const base = (process.env.EMONCMS_URL || 'http://localhost/emoncms').replace(/\/$/, '') + '/';
const username = process.env.EMONCMS_USER || 'test';
const password = process.env.EMONCMS_PASS || 'test';
const outdir = path.resolve(here, process.env.OUTDIR || defaults.outdir);

const args = process.argv.slice(2);
const only = args.includes('--only') ? args[args.indexOf('--only') + 1] : null;

let shots = manifest.shots;
if (only) shots = shots.filter(s => s.name.startsWith(only));

if (args.includes('--list')) {
    for (const s of shots) console.log(`${s.name}\t${s.page}\t${s.url}`);
    process.exit(0);
}

const browser = await chromium.launch({
    executablePath: process.env.CHROME_PATH || undefined
});
const context = await browser.newContext({
    viewport: defaults.viewport,
    deviceScaleFactor: defaults.scale,
    colorScheme: 'light',
    locale: 'en-GB',
    timezoneId: defaults.timezone
});
const userPage = await context.newPage();
let page = userPage;

// API keys, requested before login. user/auth.json answers only without a session
const keys = await (await context.request.post(base + 'user/auth.json', {
    form: { username, password }
})).json();

// Log in once. The session cookie is shared by every shot.
const auth = await context.request.post(base + 'user/login.json', {
    form: { username, password, rememberme: 0 }
});
const login = await auth.json().catch(() => ({}));
if (!login.success) {
    console.error('Login failed for ' + username + ': ' + JSON.stringify(login));
    await browser.close();
    process.exit(1);
}


// Seed live values so inputs and feeds show as recently updated
const seeds = args.includes('--no-seed') ? [] : (manifest.seed || []);
for (const seed of seeds) {
    const res = await context.request.post(base + 'input/post.json', {
        headers: { Authorization: 'Bearer ' + keys.apikey_write },
        form: { node: seed.node, fulljson: JSON.stringify(seed.values) }
    });
    if (!res.ok()) console.warn('Seed failed for node ' + seed.node);
}

// Names in URLs, such as {feed:use} or {app:myheatpump}, resolve to ids in this account
const bearer = { Authorization: 'Bearer ' + keys.apikey_write };
const ids = { feed: {}, app: {} };
for (const f of await (await context.request.get(base + 'feed/list.json', { headers: bearer })).json()) ids.feed[f.name] = f.id;
for (const a of await (await context.request.get(base + 'app/list.json', { headers: bearer })).json().catch(() => [])) ids.app[a.app] = a.id;

// Separate context for pages seen before login
const guest = await browser.newContext({
    viewport: defaults.viewport,
    deviceScaleFactor: defaults.scale,
    colorScheme: 'light',
    locale: 'en-GB',
    timezoneId: defaults.timezone
});
const guestPage = await guest.newPage();

// Admin shots use a separate login, from EMONCMS_ADMIN_USER and EMONCMS_ADMIN_PASS
let adminPage = null;
if (process.env.EMONCMS_ADMIN_USER) {
    const admin = await browser.newContext({
        viewport: defaults.viewport,
        deviceScaleFactor: defaults.scale,
        colorScheme: 'light',
        locale: 'en-GB',
        timezoneId: defaults.timezone
    });
    const r = await (await admin.request.post(base + 'user/login.json', {
        form: { username: process.env.EMONCMS_ADMIN_USER, password: process.env.EMONCMS_ADMIN_PASS || '', rememberme: 0 }
    })).json().catch(() => ({}));
    if (r.success) adminPage = await admin.newPage();
    else console.warn('Admin login failed, admin shots skipped');
}

await mkdir(outdir, { recursive: true });

let failed = 0;
for (const shot of shots) {
    if (shot.admin && !adminPage) {
        console.log('skip ' + shot.name + ' (set EMONCMS_ADMIN_USER)');
        continue;
    }
    try {
        await capture(shot);
        console.log('ok   ' + shot.name);
    } catch (err) {
        failed++;
        console.error('FAIL ' + shot.name + ': ' + err.message.split('\n')[0]);
    }
}

await browser.close();
process.exit(failed ? 1 : 0);

function resolve(url) {
    return url.replace(/\{(feed|app):([^}]+)\}/g, (m, type, name) => {
        if (ids[type][name] === undefined) throw new Error('No ' + type + ' named ' + name);
        return ids[type][name];
    });
}

async function capture(shot) {
    page = shot.loggedOut ? guestPage : shot.admin ? adminPage : userPage;
    await page.setViewportSize(shot.viewport || defaults.viewport);
    await page.goto(base + resolve(shot.url), { waitUntil: 'networkidle' });
    if (shot.waitFor) await page.waitForSelector(shot.waitFor, { state: 'visible' });

    for (const action of shot.actions || []) await run(action);
    await page.waitForTimeout(shot.settle ?? defaults.settle);

    let png;
    if (shot.element) {
        const el = page.locator(shot.element).first();
        await el.waitFor({ state: 'visible' });
        png = await el.screenshot({ animations: 'disabled', caret: 'hide' });
    } else {
        // Actions such as ticking a box can scroll the page. Show it from the top.
        await page.evaluate(() => {
            for (const el of [document.scrollingElement, ...document.querySelectorAll('*')]) {
                if (el && el.scrollTop > 0) el.scrollTop = 0;
            }
        });
        await page.waitForTimeout(200);
        png = await page.screenshot({ animations: 'disabled', caret: 'hide', fullPage: !!shot.fullPage });
    }

    const file = path.join(outdir, shot.name + '.webp');
    const img = sharp(png);
    if (shot.maxWidth || defaults.maxWidth) {
        img.resize({ width: shot.maxWidth || defaults.maxWidth, withoutEnlargement: true });
    }
    await writeFile(file, await img.webp({ quality: defaults.quality }).toBuffer());
}

async function run(action) {
    const [type, value] = Object.entries(action)[0];
    switch (type) {
        case 'click': await page.locator(value).first().click(); break;
        case 'check': await page.locator(value).first().check(); break;
        case 'hover': await page.locator(value).first().hover(); break;
        case 'fill': await page.locator(value[0]).first().fill(value[1]); break;
        case 'select': await page.locator(value[0]).first().selectOption(value[1]); break;
        case 'selectLabel': await page.locator(value[0]).first().selectOption({ label: value[1] }); break;
        case 'hide': await page.locator(value).evaluateAll(els => els.forEach(e => { e.style.display = 'none'; })); break;
        case 'press': await page.keyboard.press(value); break;
        case 'waitFor': await page.waitForSelector(value, { state: 'visible' }); break;
        case 'wait': await page.waitForTimeout(value); break;
        case 'eval': await page.evaluate(value); break;
        default: throw new Error('Unknown action: ' + type);
    }
}
