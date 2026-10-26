import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const read = path => readFileSync(new URL(`../../resources/js/${path}`, import.meta.url), 'utf8');

test('customer suspended badges are labeled ISOLIR without changing the internal status', () => {
    assert.match(read('Components/StatusBadge.vue'), /suspended:\s*\{\s*label:\s*'ISOLIR'/);
    assert.match(read('Pages/Customers/Edit.vue'), /value="suspended">Isolir<\/option>/);
});

test('isolir submenu has its own active destination and is excluded from all customers', () => {
    const layout = read('Layouts/AppLayout.vue');
    assert.match(layout, /href: '\/customers\/isolir', label: 'Pelanggan Isolir'.*permission: 'menu_customers_all'/);
    const prefixes = layout.match(/const otherPrefixes = (\[[^;]+\]);/)[1];
    assert.ok(JSON.parse(prefixes.replaceAll("'", '"')).includes('/customers/isolir'));
});

test('isolir search and filters keep the dedicated list instead of returning to all customers', () => {
    const page = read('Pages/Customers/Index.vue');
    assert.match(page, /:searchRoute="customerListRoute"/);
    assert.match(page, /customerListRoute = computed\(\(\) => props.isolirOnly \? '\/customers\/isolir' : '\/customers'\)/);
    assert.match(page, /router.get\(customerListRoute.value,/);
    assert.match(page, /v-if="!row.connection_disabled \|\| row.status === 'suspended'"/);
});
