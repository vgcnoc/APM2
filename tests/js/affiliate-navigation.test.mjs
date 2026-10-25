import { test } from 'node:test';
import assert from 'node:assert/strict';
import { affiliateSections, affiliateHref, affiliateSection } from '../../resources/js/Composables/affiliateNavigation.js';
import { applyDeviceScale } from '../../resources/js/Composables/deviceScale.js';

test('every sidebar item has a unique personal portal destination and matching active state', () => {
    assert.equal(affiliateSections.length, 7);
    const hrefs = affiliateSections.map(item => affiliateHref(item.id));
    assert.equal(new Set(hrefs).size, 7);
    affiliateSections.forEach(item => {
        assert.ok(affiliateHref(item.id).startsWith('/affiliate/'));
        assert.equal(affiliateSection(affiliateHref(item.id)), item.id);
    });
});

test('overview is the default and unknown or operational tabs cannot become active', () => {
    for (const tab of ['', 'invalid', 'booking', 'settings']) {
        assert.equal(affiliateSection(`/affiliate/dashboard?tab=${tab}`), 'overview');
    }
    assert.equal(affiliateSection('/affiliate/dashboard?tab=commissions&commissions_page=2'), 'commissions');
    assert.equal(affiliateSection('/affiliate/dashboard', 'downlines'), 'downlines');
    assert.equal(affiliateSection('/affiliate/booking/create', 'overview'), 'booking');
});

test('workspace uses 90% scale while sales and auth retain native scale and isolated styling', () => {
    const classes = new Set();
    globalThis.document = { body: { classList: { toggle: (key, on) => on ? classes.add(key) : classes.delete(key) }, style: {} } };
    applyDeviceScale('Auth/Login');
    assert.equal(document.body.style.zoom, '100%');
    assert.equal(classes.has('auth-device-scale'), true);
    assert.equal(classes.has('workspace-ui-page'), false);
    applyDeviceScale('Affiliate/Dashboard');
    assert.equal(document.body.style.zoom, '100%');
    assert.equal(classes.has('auth-device-scale'), false);
    assert.equal(classes.has('workspace-ui-page'), false);
    applyDeviceScale('Customers/Create', { affiliatePortal: true });
    assert.equal(document.body.style.zoom, '100%');
    assert.equal(classes.has('workspace-ui-page'), false);
    applyDeviceScale('Customers/Create', { affiliatePortal: false });
    assert.equal(document.body.style.zoom, '90%');
    assert.equal(classes.has('workspace-ui-page'), true);
    applyDeviceScale('Affiliate/Dashboard');
    assert.equal(classes.has('workspace-ui-page'), false);
    applyDeviceScale('Dashboard');
    assert.equal(document.body.style.zoom, '90%');
    assert.equal(classes.has('workspace-ui-page'), true);
    applyDeviceScale('Auth/Login');
    assert.equal(document.body.style.zoom, '100%');
    assert.equal(classes.has('workspace-ui-page'), false);
    delete globalThis.document;
    assert.doesNotThrow(() => applyDeviceScale('Affiliate/Dashboard'));
});
