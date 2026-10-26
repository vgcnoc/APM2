import test from 'node:test';
import assert from 'node:assert/strict';
import { buildFiberSegments, fiberRoadGeometry, fiberRouteKey } from '../../resources/js/Utils/fiberRoutes.js';

const data = {
    odcs: [{ id: 1, name: 'ODC', latitude: -7, longitude: 106.7 }],
    odps: [
        { id: 2, odc_id: 1, name: 'ODP A', area_id: 1, latitude: -7.01, longitude: 106.71 },
        { id: 3, odc_id: 1, name: 'ODP B', area_id: 2, latitude: -7.02, longitude: 106.72 },
        { id: 4, odc_id: 1, name: 'Invalid', area_id: 1, latitude: null, longitude: null },
    ],
    customers: [{ id: 5, odp_id: 2, name: 'Customer', area_id: 1, latitude: -7.011, longitude: 106.711 }],
};

test('builds feeder and drop links only for the selected area and valid endpoints', () => {
    const links = buildFiberSegments({ ...data, area: '1' });
    assert.deepEqual(links.map(link => link.id), ['feeder-2', 'drop-5']);
    assert.deepEqual(links[1].origin, [-7.01, 106.71]);
    assert.equal(buildFiberSegments(data).length, 3);
});

test('keeps a customer link when its ODP belongs to a different area', () => {
    const links = buildFiberSegments({ ...data, customers: [{ ...data.customers[0], area_id: 9 }], area: '9' });
    assert.deepEqual(links.map(link => link.id), ['drop-5']);
});

test('road geometry converts coordinates and connects the road to the devices', () => {
    const segment = buildFiberSegments(data)[0];
    const geometry = fiberRoadGeometry(segment, { source: 'osrm', status: 'success', geometry: { type: 'LineString', coordinates: [[106.7001, -7.0001], [106.708, -7.0001], [106.7101, -7.0101]] } });
    assert.deepEqual(geometry[0], segment.origin);
    assert.deepEqual(geometry[2], [-7.0001, 106.708]);
    assert.deepEqual(geometry.at(-1), segment.destination);
    assert.ok(geometry.length > 2);
});

test('never labels fallback, malformed or out-of-range geometry as a road', () => {
    const segment = buildFiberSegments(data)[0];
    assert.equal(fiberRoadGeometry(segment, { source: 'straight_line', status: 'fallback' }), null);
    for (const coordinates of [[], [[106, -7]], [[NaN, -7], [106, -7]], [[106, -7], [200, -7]]]) {
        assert.equal(fiberRoadGeometry(segment, { source: 'osrm', status: 'success', geometry: { type: 'LineString', coordinates } }), null);
    }
});

test('cache keys change when endpoint coordinates change', () => {
    const segment = buildFiberSegments(data)[0];
    assert.notEqual(fiberRouteKey(segment), fiberRouteKey({ ...segment, destination: [-7.03, 106.73] }));
});
