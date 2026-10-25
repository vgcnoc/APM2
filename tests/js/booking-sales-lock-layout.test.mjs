import assert from 'node:assert/strict';
import test from 'node:test';
import { readFile } from 'node:fs/promises';

const bookingFormPath = new URL('../../resources/js/Pages/Customers/Create.vue', import.meta.url);

test('locked sales indicator is rendered below the account name', async () => {
    const source = await readFile(bookingFormPath, 'utf8');
    const fieldStart = source.indexOf('Petugas Sales / Karyawan Referral');
    const fieldEnd = source.indexOf('Tanggal Registrasi', fieldStart + 1);
    const field = source.slice(fieldStart, fieldEnd === -1 ? undefined : fieldEnd);

    assert.match(field, /Petugas dikunci ke akun Anda\./);
    assert.doesNotMatch(field, /absolute inset-y-0 right-0/);
});

test('booking price fields do not repeat the master data automation label', async () => {
    const source = await readFile(bookingFormPath, 'utf8');

    assert.doesNotMatch(source, /Otomatis dari Master Data/);
});
