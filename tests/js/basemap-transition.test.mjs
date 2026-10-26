import test from 'node:test';
import assert from 'node:assert/strict';
import { createBasemapTransition } from '../../resources/js/Utils/basemapTransition.js';

function harness() {
    const created = [], activated = [], states = [], timers = new Map();
    let timerId = 0;
    const map = {
        layers: new Set(), handlers: new Map(),
        addLayer(layer) { if (!this.layers.has(layer)) { this.layers.add(layer); layer.loading = true; } },
        removeLayer(layer) { this.layers.delete(layer); layer.loading = false; },
        on(events, listener) { events.split(' ').forEach(event => this.handlers.set(event, listener)); },
        off(events) { events.split(' ').forEach(event => this.handlers.delete(event)); },
        move() { this.handlers.get('movestart')?.(); },
    };
    const createLayer = key => {
        const layer = {
            key, loading: false, events: {}, container: { style: {} },
            on(events) { this.events = events; }, off() { this.events = {}; },
            getContainer() { return this.container; }, setZIndex(index) { this.zIndex = index; },
            isLoading() { return this.loading; },
            finish(ok = true) { this.loading = false; this.events[ok ? 'tileload' : 'tileerror']?.(); this.events.load?.(); },
        };
        created.push(layer);
        return layer;
    };
    const manager = createBasemapTransition({ map, createLayer,
        onActivate: key => activated.push(key), onState: state => states.push(state),
        schedule: callback => { timers.set(++timerId, callback); return timerId; }, cancel: id => timers.delete(id),
    });
    return { manager, map, created, activated, states, timers, visible: layer => layer.container.style.visibility === 'visible' };
}

test('keeps the old map visible until the requested view is ready', () => {
    const h = harness();
    h.manager.select('osm');
    assert.equal(h.visible(h.created[0]), true, 'first view renders progressively');
    h.created[0].finish();
    h.manager.select('satellite');
    assert.equal(h.visible(h.created[0]), true);
    assert.equal(h.visible(h.created[1]), false);
    h.created[1].finish();
    assert.equal(h.visible(h.created[0]), false);
    assert.equal(h.visible(h.created[1]), true);
    assert.equal(h.states.at(-1).loading, false);
    h.manager.dispose();
});

test('switching back reuses the already-loaded view without reattaching or downloading it', () => {
    const h = harness();
    h.manager.select('osm'); h.created[0].finish();
    h.manager.select('satellite'); h.created[1].finish();
    h.manager.select('osm'); h.manager.select('osm');
    assert.equal(h.created.length, 2);
    assert.equal(h.created[0].isLoading(), false);
    assert.equal(h.visible(h.created[0]), true);
    assert.equal(h.timers.size, 0);
    h.manager.dispose();
});

test('rapid changes cannot reveal an obsolete view or keep canceled layers alive', () => {
    const h = harness();
    h.manager.select('osm'); h.created[0].finish();
    h.manager.select('satellite');
    const canceled = h.created[1];
    h.manager.select('dark');
    canceled.finish();
    assert.equal(h.map.layers.has(canceled), false);
    assert.deepEqual(h.activated, ['osm']);
    h.created[2].finish();
    assert.deepEqual(h.activated, ['osm', 'dark']);
    h.manager.dispose();
});

test('a failed provider preserves the usable map and supports retry', () => {
    const h = harness();
    h.manager.select('osm'); h.created[0].finish();
    h.manager.select('satellite'); h.created[1].finish(false);
    assert.equal(h.visible(h.created[0]), true);
    assert.ok(h.states.at(-1).error);
    h.manager.retry(); h.created[2].finish();
    assert.equal(h.activated.at(-1), 'satellite');
    assert.equal(h.states.at(-1).error, '');
    h.manager.dispose();
});

test('panning discards hidden cached views so they cannot fetch unseen tiles', () => {
    const h = harness();
    h.manager.select('osm'); h.created[0].finish();
    h.manager.select('satellite'); h.created[1].finish();
    h.map.move();
    assert.equal(h.map.layers.size, 1);
    assert.equal(h.map.layers.has(h.created[0]), false);
    h.manager.select('osm');
    assert.equal(h.created.length, 3);
    h.manager.dispose();
});

test('timeout and disposal release pending layers, listeners and timers', () => {
    const h = harness();
    h.manager.select('osm'); h.created[0].finish();
    h.manager.select('satellite');
    [...h.timers.values()][0]();
    assert.equal(h.visible(h.created[0]), true);
    assert.equal(h.states.at(-1).loading, false);
    assert.ok(h.states.at(-1).error);
    h.manager.select('dark');
    h.manager.dispose();
    assert.equal(h.timers.size, 0);
    assert.equal(h.map.layers.size, 0);
    assert.equal(h.map.handlers.size, 0);
    h.created.at(-1).finish();
    assert.equal(h.activated.at(-1), 'osm');
});
