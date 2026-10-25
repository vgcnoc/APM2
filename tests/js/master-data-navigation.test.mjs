import assert from 'node:assert/strict';
import test from 'node:test';
import { readFile } from 'node:fs/promises';

const layoutPath = new URL('../../resources/js/Layouts/AppLayout.vue', import.meta.url);

test('subscription plans are available within Master Data', async () => {
    const source = await readFile(layoutPath, 'utf8');

    const masterDataStart = source.indexOf("label: 'Master Data'");
    const masterDataEnd = source.indexOf("label: 'Hak Akses Pengguna'", masterDataStart);
    const masterData = source.slice(masterDataStart, masterDataEnd);

    assert.ok(masterDataStart >= 0, 'Master Data navigation must exist');
    assert.match(masterData, /'\/internet-packages'/);
    assert.match(masterData, /label: 'Paket Langganan & Tarif'/);
    assert.match(masterData, /permission: 'menu_internet_packages'/);
});

test('subscription plans no longer render as a separate top-level menu', async () => {
    const source = await readFile(layoutPath, 'utf8');

    assert.doesNotMatch(source, /type: 'link', href: '\/internet-packages'/);
});
