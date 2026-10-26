const point = item => {
    const lat = Number(item?.latitude), lng = Number(item?.longitude);
    return item?.latitude != null && item?.longitude != null && Number.isFinite(lat) && Number.isFinite(lng)
        && Math.abs(lat) <= 90 && Math.abs(lng) <= 180 && (lat !== 0 || lng !== 0) ? [lat, lng] : null;
};

export const fiberRouteKey = segment => [...segment.origin, ...segment.destination].map(value => value.toFixed(6)).join(',');

// Markers and cable endpoints must use the same anti-overlap positions.
export function fiberAssetPositions({ odcs = [], odps = [] }) {
    const cabinets = new Map(odcs.map(item => [String(item.id), point(item)]));
    const terminals = new Map(odps.map(item => {
        let location = point(item);
        if (location && [...cabinets.values()].some(origin => origin
            && Math.abs(origin[0] - location[0]) < .00008 && Math.abs(origin[1] - location[1]) < .00008)) {
            location = [location[0] + .00018, location[1] + .00018];
        }
        return [String(item.id), location];
    }));
    const terminalLocations = new Map(odps.map(item => [String(item.id), point(item)]));
    return { cabinets, terminals, terminalLocations };
}

export function fiberCustomerPosition(customer, odps = []) {
    let location = point(customer);
    const origin = odps instanceof Map ? odps.get(String(customer.odp_id)) : point(odps.find(odp => String(odp.id) === String(customer.odp_id)));
    if (origin && location && Math.abs(origin[0] - location[0]) < .00003 && Math.abs(origin[1] - location[1]) < .00003) {
        location = [location[0] + .00010, location[1] + .00010];
    }
    return location;
}

export function buildFiberSegments({ odcs = [], odps = [], customers = [], area = 'all' }) {
    const inArea = item => area === 'all' || String(item.area_id) === String(area);
    const { cabinets, terminals, terminalLocations } = fiberAssetPositions({ odcs, odps });
    const terminalNames = new Map(odps.map(item => [String(item.id), item.name]));
    const segments = [];
    odps.filter(inArea).forEach(item => {
        const origin = cabinets.get(String(item.odc_id)), destination = terminals.get(String(item.id));
        if (origin && destination) segments.push({ id: `feeder-${item.id}`, kind: 'feeder', name: `Feeder → ${item.name}`, origin, destination });
    });
    customers.filter(inArea).forEach(item => {
        const origin = terminals.get(String(item.odp_id));
        const destination = fiberCustomerPosition(item, terminalLocations);
        if (!origin || !destination) return;
        segments.push({ id: `drop-${item.id}`, kind: 'drop', name: `${terminalNames.get(String(item.odp_id)) || 'ODP'} → ${item.name}`, origin, destination });
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
