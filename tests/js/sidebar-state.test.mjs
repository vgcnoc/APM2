import { test } from 'node:test';
import assert from 'node:assert/strict';

test('sidebar submenu state ignores legacy numeric indexes and uses stable menu labels', async () => {
    const values = new Map([
        ['sidebarOpen', 'true'],
        ['sidebarOpenDropdowns', JSON.stringify([3, 'Data Pelanggan', 7, 'Data Pelanggan', ''])],
        ['sidebarScrollPos', '0'],
    ]);

    globalThis.window = {};
    globalThis.localStorage = {
        getItem: (key) => values.get(key) ?? null,
        setItem: (key, value) => values.set(key, String(value)),
    };

    const { useSidebarState } = await import('../../resources/js/Composables/useSidebarState.js?sidebar-state-test');
    const { openDropdowns, toggleDropdown, ensureDropdownOpen } = useSidebarState();

    assert.deepEqual(openDropdowns.value, ['Data Pelanggan']);
    toggleDropdown('Data Pelanggan');
    assert.deepEqual(openDropdowns.value, []);
    ensureDropdownOpen('Data Pelanggan');
    assert.deepEqual(openDropdowns.value, ['Data Pelanggan']);
    assert.equal(values.get('sidebarOpenDropdowns'), JSON.stringify(['Data Pelanggan']));

    delete globalThis.window;
    delete globalThis.localStorage;
});
