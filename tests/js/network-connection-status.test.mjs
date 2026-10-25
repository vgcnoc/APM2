import test from 'node:test';
import assert from 'node:assert/strict';
import { connectionPresentation } from '../../resources/js/Utils/networkConnectionStatus.js';

const now = Date.parse('2026-10-25T12:00:00Z');
const row = status => ({ connection_status: status, connection_checked_at: '2026-10-25T11:59:50Z', connection_source: 'routeros' });

test('fresh measured states keep online green and offline red', () => {
    assert.equal(connectionPresentation(row('online'), now).color, '#10b981');
    assert.equal(connectionPresentation(row('offline'), now).color, '#f43f5e');
});

test('subscription active and missing observations never become online', () => {
    assert.equal(connectionPresentation({ status: 'active' }, now).status, 'unknown');
    assert.equal(connectionPresentation(undefined, now).status, 'unknown');
    assert.equal(connectionPresentation(row('invalid'), now).status, 'unknown');
});

test('expired and invalid timestamps become unknown rather than retaining stale colors', () => {
    assert.equal(connectionPresentation(row('online'), now + 90000).status, 'unknown');
    assert.equal(connectionPresentation({ ...row('offline'), connection_checked_at: null }, now).status, 'unknown');
    assert.equal(connectionPresentation(row('online'), now - 90000).status, 'unknown');
});
