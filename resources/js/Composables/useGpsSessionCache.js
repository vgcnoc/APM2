import { ref } from 'vue';

// Module-level state so GPS verification persists across Inertia page switches
// without firing redundant geolocation + HTTP ping calls on every mount.
export const cachedGpsActive = ref(null);
export const cachedGpsLocationName = ref('');
export const cachedGpsAccuracy = ref(null);
let lastVerifiedAt = 0;

export function isGpsRecentlyVerified(maxAgeMs = 55000) {
    return cachedGpsActive.value === true && (Date.now() - lastVerifiedAt) < maxAgeMs;
}

export function markGpsVerified(locationName, accuracy) {
    cachedGpsActive.value = true;
    cachedGpsLocationName.value = locationName || '';
    cachedGpsAccuracy.value = accuracy ?? null;
    lastVerifiedAt = Date.now();
}

export function markGpsOffline() {
    cachedGpsActive.value = false;
    lastVerifiedAt = 0;
}

