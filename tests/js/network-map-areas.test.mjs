import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

// Execute the page's real area renderer, including its Leaflet mounting order.
const source = readFileSync(new URL('../../resources/js/Pages/NetworkMap/Index.vue', import.meta.url), 'utf8');
const start = source.indexOf('const toggleAreasLayer = () => {');
const end = source.indexOf('// ═══ FIBER TOPOLOGY LINES', start);
assert.ok(start >= 0 && end > start, 'area renderer must exist');
const createRenderer = new Function('map', 'L', 'areaLayerGroup', 'areaBadgeLayerGroup',
    'layers', 'props', 'selectedAreaFilter', 'displayedBasemap', 'escapePopup',
    'updateAreaBadgesVisibility', `${source.slice(start, end)}\nreturn toggleAreasLayer;`);

function harness() {
    const circles = [], badges = [];
    let visibilityUpdates = 0;
    const areaLayerGroup = {
        clearLayers() { circles.splice(0).forEach(circle => { circle.mounted = false; }); },
        addLayer(circle) { circle.mounted = true; circles.push(circle); },
    };
    const areaBadgeLayerGroup = {
        clearLayers() { badges.splice(0); },
        addLayer(badge) { badges.push(badge); },
    };
    const L = {
        circle(position, options) {
            return { position, options, mounted: false, bindTooltip() {},
                getBounds() {
                    // Leaflet Circle.getBounds depends on _map and projected coordinates.
                    assert.equal(this.mounted, true, 'circle must be mounted before reading its bounds');
                    return { getNorth: () => position[0] + 0.01 };
                },
            };
        },
        divIcon: options => options,
        marker: (position, options) => ({ position, options }),
    };
    const layers = { value: { areas: true } };
    const selectedAreaFilter = { value: 'all' };
    const props = { areas: [
        { id: 1, name: 'Utara', latitude: '-6.998', longitude: '106.72', radius_meters: 1400 },
        { id: 2, name: 'Selatan', latitude: '-7.018', longitude: '106.727' },
        { id: 3, name: 'Belum dipetakan', latitude: null, longitude: null },
    ] };
    const render = createRenderer({}, L, areaLayerGroup, areaBadgeLayerGroup, layers, props,
        selectedAreaFilter, { value: 'osm' }, value => value, () => { visibilityUpdates++; });
    return { render, circles, badges, layers, selectedAreaFilter, visibilityUpdates: () => visibilityUpdates };
}

test('initial GIS rendering mounts each area before calculating its boundary label', () => {
    const h = harness();
    assert.doesNotThrow(h.render);
    assert.equal(h.circles.length, 2);
    assert.equal(h.badges.length, 2);
    assert.deepEqual(h.badges[0].position, [-6.988, 106.72]);
    assert.equal(h.badges[0].options.pane, 'gis-area-labels');
    assert.equal(h.badges[0].options.interactive, false);
    assert.equal(h.visibilityUpdates(), 1);
});

test('redrawing and filtering areas does not use detached circles or accumulate layers', () => {
    const h = harness();
    h.render();
    h.render();
    assert.equal(h.circles.length, 2);
    assert.equal(h.badges.length, 2);
    h.selectedAreaFilter.value = '2';
    h.render();
    assert.equal(h.circles.length, 1);
    assert.equal(h.badges.length, 1);
    assert.equal(h.circles[0].options.radius, 1500);
    assert.match(h.badges[0].options.icon.html, /Selatan/);
});

test('hiding and restoring areas preserves the mounting lifecycle', () => {
    const h = harness();
    h.render();
    h.layers.value.areas = false;
    h.render();
    assert.equal(h.circles.length, 0);
    assert.equal(h.badges.length, 0);
    h.layers.value.areas = true;
    h.render();
    assert.equal(h.circles.length, 2);
    assert.equal(h.visibilityUpdates(), 3);
});
