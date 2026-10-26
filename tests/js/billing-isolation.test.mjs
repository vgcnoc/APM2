import assert from 'node:assert/strict';
import test from 'node:test';
import { billingIsolationDate } from '../../resources/js/Utils/billingIsolation.js';

const date = value => { const [y, m, d] = value.split('-').map(Number); return new Date(y, m - 1, d); };
const day = value => `${value.getFullYear()}-${String(value.getMonth() + 1).padStart(2, '0')}-${String(value.getDate()).padStart(2, '0')}`;
const settings = { isolate_day: 13, isolate_time: '20:44' };

test('monthly isolation previews use the actual invoice issue date and configured clock', () => {
    const target = billingIsolationDate(date('2026-10-01'), settings);
    assert.equal(day(target), '2026-10-13');
    assert.equal(target.getHours(), 20); assert.equal(target.getMinutes(), 44);
    assert.equal(day(billingIsolationDate(date('2026-10-22'), settings)), '2026-11-13');
    assert.equal(day(billingIsolationDate(date('2026-12-31'), settings)), '2027-01-13');
});

test('short months and leap years clamp rather than overflow', () => {
    assert.equal(day(billingIsolationDate(date('2027-02-01'), { isolate_day: 31 })), '2027-02-28');
    assert.equal(day(billingIsolationDate(date('2028-02-01'), { isolate_day: 31 })), '2028-02-29');
    assert.equal(day(billingIsolationDate(date('2026-01-31'), { isolate_day: 30 })), '2026-02-28');
});

test('legacy zero days is preserved until a monthly isolation date is configured', () => {
    assert.equal(day(billingIsolationDate(date('2026-10-01'), { isolate_days: 0 })), '2026-10-01');
});
