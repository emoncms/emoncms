// Create the saved state that some screenshots need: an input process list,
// virtual feeds and a schedule. Safe to run again: existing items are reused.
// Usage: node setup-account.mjs
// Settings from environment: EMONCMS_URL, EMONCMS_USER, EMONCMS_PASS

import { readFile } from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const here = path.dirname(fileURLToPath(import.meta.url));
const manifest = JSON.parse(await readFile(path.join(here, 'manifest.json'), 'utf8'));

const base = (process.env.EMONCMS_URL || 'http://localhost/emoncms').replace(/\/$/, '') + '/';
const username = process.env.EMONCMS_USER || 'test';
const password = process.env.EMONCMS_PASS || 'test';

const auth = await (await fetch(base + 'user/auth.json', {
    method: 'POST',
    body: new URLSearchParams({ username, password })
})).json();
if (!auth.success) throw new Error('Login failed for ' + username);
const headers = { Authorization: 'Bearer ' + auth.apikey_write };

async function api(route, params = {}, body = null) {
    const url = base + route + '?' + new URLSearchParams(params);
    const res = await fetch(url, body
        ? { method: 'POST', headers, body: new URLSearchParams(body) }
        : { headers });
    const text = await res.text();
    try { return JSON.parse(text); } catch { return text; }
}

// Seed inputs, so that emontx4/P1 exists
for (const seed of manifest.seed || []) {
    await api('input/post.json', {}, { node: seed.node, fulljson: JSON.stringify(seed.values) });
}

// Process lists are posted as JSON: [{fn, args}]
const plist = steps => JSON.stringify(steps.map(([fn, ...args]) => ({ fn, args })));

let feeds = await api('feed/list.json');
function feedId(tag, name) {
    const f = feeds.find(f => f.tag === tag && f.name === name);
    return f ? f.id : null;
}
function feedByName(name) {
    const f = feeds.find(f => f.name === name);
    if (!f) throw new Error('Feed not found: ' + name + '. Load the dataset first.');
    return f.id;
}
async function ensureFeed(tag, name, engine, options = {}) {
    let id = feedId(tag, name);
    if (!id) {
        const r = await api('feed/create.json', { tag, name, engine, options: JSON.stringify(options) });
        if (!r.success) throw new Error('Create feed ' + name + ': ' + JSON.stringify(r));
        id = r.feedid;
        feeds = await api('feed/list.json');
        console.log('created feed ' + tag + ':' + name);
    }
    return id;
}

// Input process list: emontx4 P1 logged to use and use_kwh
const use = await ensureFeed('emontx4', 'use', 5, { interval: 10 });
const useKwh = await ensureFeed('emontx4', 'use_kwh', 5, { interval: 10 });
const inputs = await api('input/list.json');
const p1 = inputs.find(i => i.nodeid === 'emontx4' && i.name === 'P1');
if (!p1) throw new Error('Input emontx4/P1 not found');
const r1 = await api('input/process/set.json', { inputid: p1.id }, {
    processlist: plist([['process__log_to_feed', use], ['process__power_to_kwh', useKwh]])
});
if (r1.success === false && !/not updated/.test(r1.message || '')) console.warn('Process list for P1: ' + JSON.stringify(r1));

// Schedule: weekday peak 16:00 to 19:00
let schedules = await api('schedule/list.json');
let peak = schedules.find(s => s.name === 'Peak');
if (!peak) {
    const r = await api('schedule/create.json');
    const id = r.id ?? r;
    await api('schedule/set.json', { id, fields: JSON.stringify({ name: 'Peak', expression: 'Mon-Fri | 16:00-19:00' }) });
    schedules = await api('schedule/list.json');
    peak = schedules.find(s => s.name === 'Peak');
    console.log('created schedule Peak');
}

// Virtual feeds
const virtual = [
    ['outsideT_F', [
        ['process__source_feed_data_time', feedByName('heatpump_outsideT')],
        ['process__scale', 1.8],
        ['process__offset', 32]
    ]],
    ['heatpump_cop', [
        ['process__source_feed_data_time', feedByName('heatpump_heat')],
        ['process__divide_by_source_feed', feedByName('heatpump_elec')]
    ]],
    ['use_peak', [
        ['process__source_feed_data_time', feedId('power', 'use') || feedByName('use')],
        ['schedule__if_not_schedule_zero', peak.id]
    ]],
    ['use_offpeak', [
        ['process__source_feed_data_time', feedId('power', 'use') || feedByName('use')],
        ['schedule__if_schedule_zero', peak.id]
    ]]
];
for (const [name, list] of virtual) {
    const id = await ensureFeed('virtual', name, 7);
    const r = await api('feed/process/set.json', { id }, { processlist: plist(list) });
    if (!r || r.success === false && !/not updated/.test(r.message || '')) {
        console.warn('Process list for ' + name + ': ' + JSON.stringify(r));
    }
}

console.log('Account ready: ' + username);
