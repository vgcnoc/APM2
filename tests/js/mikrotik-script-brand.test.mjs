import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';

const source = readFileSync('resources/js/Pages/Radius/Nas/Index.vue', 'utf8');

test('generated MikroTik script uses VIZBILL identities', () => {
    for (const value of [
        'VIZBILL RADIUS',
        'VIZBILLRADIUS',
        'VIZBILLPOOL',
        'VIZBILLISOLIR',
        'VIZBILLBYPASS',
        'VIZBILL-VPN-',
        'VIZBILLVPN',
        'vizbillfailovervpn',
        'STATIC ROUTE BY VIZBILL',
        'USER FOR VIZBILL BILLING API',
    ]) {
        assert.match(source, new RegExp(value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')));
    }
});

test('generated script removes legacy APM2 entries before adding VIZBILL entries', () => {
    for (const legacyCleanup of [
        'comment~"APM2"',
        'comment~"APM2RADIUS"',
        'name~"APM2-VPN"',
        'name="APM2VPN"',
        'STATIC ROUTE BY APM2',
    ]) {
        assert.match(source, new RegExp(legacyCleanup.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')));
    }
});
