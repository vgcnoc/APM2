// Compatibility for existing pages that use light Tailwind utilities.
// Exact class names preserve images, inline swatches, saturated actions and print output.
module.exports = function darkUtilities() {
    const e = name => name.replaceAll(':', '\\:').replaceAll('/', '\\/');
    const root = 'html.dark body:not(.auth-device-scale)';
    const rules = {};
    const opacity = ['', '/5', '/10', '/20', '/25', '/30', '/40', '/50', '/60', '/70', '/75', '/80', '/85', '/90', '/95', '/100'];
    const add = (classes, declaration, state = '') => {
        rules[`${root} :is(${classes.map(name => `.${e(name)}`).join(',')})${state}`] = declaration;
    };
    const backgrounds = (name, value, alphas = opacity) => {
        add(alphas.map(alpha => `bg-${name}${alpha}`), { backgroundColor: value });
        for (const variant of ['hover', 'focus', 'disabled']) {
            add(alphas.map(alpha => `${variant}:bg-${name}${alpha}`), { backgroundColor: value }, `:${variant}`);
        }
    };
    const gradient = (name, value) => {
        add(opacity.map(a => `from-${name}${a}`), {
            '--tw-gradient-from': `${value} var(--tw-gradient-from-position)`,
            '--tw-gradient-to': 'rgb(19 30 46 / 0) var(--tw-gradient-to-position)',
            '--tw-gradient-stops': 'var(--tw-gradient-from), var(--tw-gradient-to)',
        });
        add(opacity.map(a => `via-${name}${a}`), {
            '--tw-gradient-to': 'rgb(19 30 46 / 0) var(--tw-gradient-to-position)',
            '--tw-gradient-stops': `var(--tw-gradient-from), ${value} var(--tw-gradient-via-position), var(--tw-gradient-to)`,
        });
        add(opacity.map(a => `to-${name}${a}`), { '--tw-gradient-to': `${value} var(--tw-gradient-to-position)` });
    };

    // White overlays are intentionally excluded; banner decoration keeps its transparency.
    add(['bg-white', ...['/50', '/60', '/70', '/75', '/80', '/85', '/90', '/95', '/100'].map(a => `bg-white${a}`)], { backgroundColor: 'var(--apm-surface)' });
    add(opacity.map(a => `hover:bg-white${a}`), { backgroundColor: 'var(--apm-raised)' }, ':hover');
    gradient('white', 'var(--apm-surface)');
    for (const family of ['gray', 'slate', 'zinc', 'neutral', 'stone']) {
        for (const [shade, token] of [[50, 'recessed'], [100, 'raised'], [200, 'hover'], [800, 'raised'], [900, 'surface'], [950, 'canvas']]) {
            backgrounds(`${family}-${shade}`, `var(--apm-${token})`, shade >= 800 ? [''] : opacity);
            if (shade <= 200) gradient(`${family}-${shade}`, `var(--apm-${token})`);
        }
        for (const shade of [50, 100, 200, 300, 700, 800]) {
            for (const property of ['border', 'divide']) {
                add(opacity.map(alpha => `${property}-${family}-${shade}${alpha}`), { borderColor: 'var(--apm-border)' }, property === 'divide' ? ' > :not([hidden]) ~ :not([hidden])' : '');
            }
            if (shade <= 300) add(opacity.map(a => `ring-${family}-${shade}${a}`), { '--tw-ring-color': 'var(--apm-border)' });
        }
        for (const [shades, token] of [[[950, 900, 800], 'text'], [[700, 600], 'secondary'], [[500, 400], 'muted']]) {
            add(shades.map(shade => `text-${family}-${shade}`), { color: `var(--apm-${token})` });
            add(shades.map(shade => `hover:text-${family}-${shade}`), { color: 'var(--apm-text)' }, ':hover');
        }
    }

    const accents = {
        blue: '124 179 255', sky: '104 200 246', cyan: '91 212 229',
        indigo: '166 180 255', violet: '189 167 255', purple: '204 165 250',
        emerald: '88 216 174', green: '116 214 156', teal: '89 213 198',
        amber: '240 197 111', yellow: '230 211 119', orange: '244 174 115',
        rose: '249 147 170', red: '250 151 151', pink: '239 153 205',
        lime: '185 218 127', fuchsia: '224 166 243',
        brown: '216 178 149',
    };
    for (const [family, rgb] of Object.entries(accents)) {
        for (const shade of [50, 100, 200]) {
            const alpha = shade === 200 ? '.15' : '.09';
            backgrounds(`${family}-${shade}`, `rgb(${rgb} / ${alpha})`);
            gradient(`${family}-${shade}`, `rgb(${rgb} / ${alpha})`);
            add(opacity.map(a => `border-${family}-${shade}${a}`), { borderColor: `rgb(${rgb} / .22)` });
        }
        add([500, 600, 700, 800, 900, 950].map(shade => `text-${family}-${shade}`), { color: `rgb(${rgb})` });
    }
    const css = Object.entries(rules).map(([selector, declarations]) => {
        const values = Object.entries(declarations).map(([property, value]) => `${property.replace(/[A-Z]/g, letter => `-${letter.toLowerCase()}`)}:${value}`).join(';');
        return `${selector}{${values}}`;
    }).join('\n');
    return {
        postcssPlugin: 'apm-dark-utilities',
        OnceExit(stylesheet) {
            const file = stylesheet.source?.input?.file?.replaceAll('\\', '/');
            if (file?.endsWith('/resources/css/app.css')) {
                stylesheet.append(`@media screen {${css}}`);
            }
        },
    };
};
