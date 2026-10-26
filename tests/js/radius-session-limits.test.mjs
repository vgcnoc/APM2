import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

test('generated MikroTik profiles fall back to one session, not unlimited', () => {
    const source = readFileSync(new URL('../../resources/js/Pages/Radius/Nas/Index.vue', import.meta.url), 'utf8');
    assert.doesNotMatch(source, /shared-users=unlimited/);
    assert.match(source, /user profile add name=\$\{p.name\}.*shared-users=1/);
    assert.match(source, /user profile set \[find default=yes\].*shared-users=1/);
});
