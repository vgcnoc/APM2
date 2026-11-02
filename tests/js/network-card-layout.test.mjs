import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { parse, compileTemplate, compileStyle } from '@vue/compiler-sfc';

const base = new URL('../../resources/js/Pages/NetworkData/', import.meta.url);
const read = path => readFileSync(new URL(path, base), 'utf8');

test('network card templates and scoped styles compile', () => {
    for (const file of ['Index.vue', 'Partials/OdcGroupCard.vue', 'Partials/OdpNetworkCard.vue', 'Partials/SplitConnectionHeader.vue']) {
        const { descriptor, errors } = parse(read(file), { filename: file });
        assert.deepEqual(errors, [], file);
        assert.deepEqual(compileTemplate({ source: descriptor.template.content, filename: file, id: 'layout-test' }).errors, [], file);
        for (const style of descriptor.styles) {
            assert.deepEqual(compileStyle({ source: style.content, filename: file, id: 'data-v-layout-test', scoped: style.scoped }).errors, [], file);
        }
    }
});

test('ODC names have an explicit high-contrast override for global heading styles', () => {
    const source = read('Partials/OdcGroupCard.vue');
    assert.match(source, /\.odc-header \.odc-header__name\s*\{[^}]*color:\s*#fff\s*!important/);
    assert.match(source, /<h3 class="odc-header__name">/);
});

test('split layouts use container width and do not float connectors over cards', () => {
    const page = read('Index.vue');
    const group = read('Partials/OdcGroupCard.vue');
    assert.match(page, /@container network-split \(min-width: 700px\)/);
    assert.match(page, /\.network-split-pair__cards\s*\{[^}]*repeat\(2, minmax\(0, 1fr\)\)/);
    assert.match(group, /@container network-odc \(min-width: 640px\)/);
    for (const source of [page, group]) {
        assert.doesNotMatch(source, /absolute left-1\/2 top-12/);
        assert.match(source, /<SplitConnectionHeader/);
    }
    assert.match(read('Partials/SplitConnectionHeader.vue'), /@container split-summary \(min-width: 520px\)/);
});

test('ODP split grouping keeps parent before continuation without losing standalone devices', () => {
    const source = read('Partials/OdcGroupCard.vue');
    const start = source.indexOf('const odpDisplayUnits = computed(() => {');
    const end = source.indexOf('const getPairAvailablePorts', start);
    assert.ok(start >= 0 && end > start);
    const group = new Function('computed', 'props', `${source.slice(start, end)}; return odpDisplayUnits.value;`);
    const result = group(fn => ({ value: fn() }), {
        odcGroup: { odps: [{ id: 2, name: 'Lanjutan', parent_odp_id: 1 }, { id: 3, name: 'Tunggal' }, { id: 1, name: 'Induk' }] },
        getOdpSplitInfoFn: odp => ({ isSplit: odp.id !== 3, rootId: odp.id === 3 ? null : 1 }),
    });
    assert.deepEqual(result.map(unit => unit.items.map(item => item.odp.id)), [[1, 2], [3]]);
    assert.equal(result[0].isSplitPair, true);
    assert.equal(result[1].isSplitPair, false);
});
