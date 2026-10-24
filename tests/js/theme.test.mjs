import { test } from 'node:test';
import assert from 'node:assert/strict';
import 'vue';

let moduleId = 0;
async function environment({ saved = null, systemDark = false, blocked = false } = {}) {
    let stored = saved;
    const listeners = {};
    const media = { matches: systemDark, addEventListener: (_, listener) => { listeners.system = listener; } };
    const classes = new Set();
    globalThis.document = { documentElement: { classList: { contains: key => classes.has(key), toggle: (key, on) => on ? classes.add(key) : classes.delete(key) }, style: {} } };
    globalThis.window = {
        localStorage: {
            getItem() { if (blocked) throw new Error('unavailable'); return stored; },
            setItem(_, value) { if (blocked) throw new Error('unavailable'); stored = value; },
        },
        matchMedia: () => media,
        addEventListener: (name, listener) => { listeners[name] = listener; },
    };
    const theme = await import(`../../resources/js/Composables/useTheme.js?test=${++moduleId}`);
    theme.initTheme();
    return { theme, media, listeners, classes, stored: () => stored };
}

test('saved preference overrides the operating system and remains stable across navigation', async () => {
    const { theme, classes, media, listeners, stored } = await environment({ saved: 'light', systemDark: true });
    assert.equal(classes.has('dark'), false);
    theme.toggleTheme();
    assert.equal(classes.has('dark'), true);
    assert.equal(stored(), 'dark');
    theme.applyTheme();
    media.matches = false;
    listeners.system();
    assert.equal(classes.has('dark'), true);
});

test('system preference changes are followed when no manual theme is selected', async () => {
    const { media, listeners, classes } = await environment({ systemDark: true });
    assert.equal(classes.has('dark'), true);
    media.matches = false;
    listeners.system();
    assert.equal(classes.has('dark'), false);
});

test('theme changes synchronize across tabs and clearing a preference restores system theme', async () => {
    const { listeners, classes } = await environment({ saved: 'light', systemDark: true });
    listeners.storage({ key: 'apm_theme', newValue: 'dark' });
    assert.equal(classes.has('dark'), true);
    listeners.storage({ key: 'apm_theme', newValue: 'light' });
    assert.equal(classes.has('dark'), false);
    listeners.storage({ key: null, newValue: null });
    assert.equal(classes.has('dark'), true);
});

test('theme toggle still works when the browser blocks local storage', async () => {
    const { theme, classes } = await environment({ blocked: true });
    theme.toggleTheme();
    assert.equal(classes.has('dark'), true);
    theme.applyTheme();
    assert.equal(classes.has('dark'), true);
    theme.toggleTheme();
    assert.equal(classes.has('dark'), false);
});
