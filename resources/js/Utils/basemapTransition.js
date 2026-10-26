// Keep only the current view and one already-visited view. No tile prefetching.
export function createBasemapTransition({ map, createLayer, onActivate, onState, timeout = 12000, schedule = setTimeout, cancel = clearTimeout }) {
    const entries = new Map();
    let active = null, previous = null, pending = null, timer = null, disposed = false, lastRequested = null;
    const stopTimer = () => { if (timer !== null) cancel(timer); timer = null; };
    const show = (entry, visible) => {
        const container = entry?.layer.getContainer();
        if (container) container.style.visibility = visible ? 'visible' : 'hidden';
    };
    const remove = entry => {
        entry.layer.off(entry.events);
        map.removeLayer(entry.layer);
        entries.delete(entry.key);
    };
    const prune = () => [...entries.values()].forEach(entry => {
        if (entry !== active && entry !== previous && entry !== pending) remove(entry);
    });
    const commit = entry => {
        if (disposed || pending !== entry) return;
        stopTimer();
        if (active !== entry) {
            show(active, false);
            previous = active;
            active = entry;
        }
        pending = null;
        entry.ready = true;
        entry.layer.setZIndex(2);
        show(entry, true);
        prune();
        onActivate(entry.key);
        onState({ loading: false, error: entry.errors ? 'Sebagian gambar peta gagal dimuat. Coba lagi bila diperlukan.' : '' });
    };
    const fail = entry => {
        if (disposed || pending !== entry) return;
        stopTimer();
        pending = null;
        remove(entry);
        if (active) onActivate(active.key);
        onState({ loading: false, error: 'Tampilan peta belum dapat dimuat. Silakan coba lagi.' });
    };
    const select = key => {
        lastRequested = key;
        if (disposed || pending?.key === key) return;
        stopTimer();
        pending = null;
        if (active?.key === key) {
            prune();
            onActivate(key);
            onState({ loading: false, error: '' });
            return;
        }
        let entry = entries.get(key);
        if (entry && !entry.ready && !entry.layer.isLoading()) {
            if (previous === entry) previous = null;
            remove(entry);
            entry = null;
        }
        if (!entry) {
            entry = { key, layer: createLayer(key), ready: false, successes: 0, errors: 0 };
            entry.events = {
                tileload: () => { entry.successes++; },
                tileerror: () => { entry.errors++; },
                load: () => { entry.ready = entry.successes > 0; entry.ready ? commit(entry) : fail(entry); },
            };
            entry.layer.on(entry.events);
            entries.set(key, entry);
        }
        pending = entry;
        prune();
        if (entry.ready && !entry.layer.isLoading()) { commit(entry); return; }
        onState({ loading: true, error: '', key });
        timer = schedule(() => entry.successes ? commit(entry) : fail(entry), timeout);
        entry.layer.setZIndex(3);
        map.addLayer(entry.layer);
        show(entry, !active || pending !== entry); // The first view renders progressively.
    };
    const discardInactive = () => {
        previous = null;
        prune(); // Hidden cached layers must never fetch tiles for another viewport.
    };
    const dispose = () => {
        disposed = true;
        stopTimer();
        [...entries.values()].forEach(remove);
        active = previous = pending = null;
        map.off('movestart zoomstart resize', discardInactive);
    };
    map.on('movestart zoomstart resize', discardInactive);
    return { select, retry: () => { if (lastRequested) select(lastRequested); }, dispose };
}
