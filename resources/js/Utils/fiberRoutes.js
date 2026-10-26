const point = item => {
    const lat = Number(item?.latitude), lng = Number(item?.longitude);
    return item?.latitude != null && item?.longitude != null && Number.isFinite(lat) && Number.isFinite(lng)
        && Math.abs(lat) <= 90 && Math.abs(lng) <= 180 && (lat !== 0 || lng !== 0) ? [lat, lng] : null;
};

export const fiberRouteKey = segment => [...segment.origin, ...segment.destination].map(value => value.toFixed(6)).join(',');

export function buildFiberSegments({ odcs = [], odps = [], customers = [], area = 'all' }) {
    const inArea = item => area === 'all' || String(item.area_id) === String(area);
    const cabinets = new Map(odcs.map(item => [String(item.id), point(item)]));
    const terminals = new Map(odps.map(item => [String(item.id), point(item)]));
    const segments = [];
    odps.filter(inArea).forEach(item => {
        const origin = cabinets.get(String(item.odc_id)), destination = point(item);
        if (origin && destination) segments.push({ id: `feeder-${item.id}`, kind: 'feeder', name: `Feeder → ${item.name}`, origin, destination });
    });
    customers.filter(inArea).forEach(item => {
        const origin = terminals.get(String(item.odp_id));
        let destination = point(item);
        if (!origin || !destination) return;
        // Match the customer marker's visual offset when it shares the ODP location.
        if (Math.abs(origin[0] - destination[0]) < .00002 && Math.abs(origin[1] - destination[1]) < .00002) destination = [destination[0] + .00010, destination[1] + .00010];
        segments.push({ id: `drop-${item.id}`, kind: 'drop', name: `Drop → ${item.name}`, origin, destination });
    });
    return segments;
}

export function fiberRoadGeometry(segment, response) {
    const coordinates = response?.geometry?.coordinates;
    if (response?.source !== 'osrm' || response?.status !== 'success' || response?.geometry?.type !== 'LineString'
        || !Array.isArray(coordinates) || coordinates.length < 2) return null;
    if (coordinates.some(p => !Array.isArray(p) || p.length < 2 || !Number.isFinite(p[0]) || !Number.isFinite(p[1]) || Math.abs(p[0]) > 180 || Math.abs(p[1]) > 90)) return null;
    // OSRM snaps to nearby roads; join the road to the exact device coordinates.
    return [segment.origin, ...coordinates.map(([lng, lat]) => [lat, lng]), segment.destination];
}
