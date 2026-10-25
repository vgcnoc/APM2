import { ref } from 'vue';

export const AVAILABLE_PATTERNS = [
    {
        id: 'dots',
        name: 'Tech Matrix Dots',
        subtitle: 'Grid titik digital & ambient glow',
        badge: 'Rekomendasi',
        icon: '⁝⁝',
    },
    {
        id: 'grid',
        name: 'Blueprint Grid',
        subtitle: 'Garis grid arsitektur NOC & Server',
        badge: 'Teknis',
        icon: '▦',
    },
    {
        id: 'mesh',
        name: 'Aurora Glow Mesh',
        subtitle: 'Gradien gelombang ambient mewah',
        badge: 'Artistik',
        icon: '✦',
    },
    {
        id: 'circuit',
        name: 'Cyber Circuit',
        subtitle: 'Motif geometris sirkuit fiber optik',
        badge: 'Modern',
        icon: '◈',
    },
    {
        id: 'clean',
        name: 'Polos Minimalis',
        subtitle: 'Warna latar flat standar',
        badge: 'Klasik',
        icon: '○',
    },
];

const currentPattern = ref('dots');
let initialized = false;

function applyPatternToDOM(patternId) {
    if (typeof document !== 'undefined') {
        document.documentElement.setAttribute('data-bg-pattern', patternId);
    }
}

export function initPattern() {
    if (initialized || typeof window === 'undefined') return;
    initialized = true;

    try {
        const saved = window.localStorage.getItem('apm_bg_pattern');
        const valid = AVAILABLE_PATTERNS.some(p => p.id === saved);
        currentPattern.value = valid ? saved : 'dots';
    } catch {
        currentPattern.value = 'dots';
    }

    applyPatternToDOM(currentPattern.value);

    window.addEventListener('storage', (event) => {
        if (event.key === 'apm_bg_pattern') {
            const val = event.newValue;
            if (AVAILABLE_PATTERNS.some(p => p.id === val)) {
                currentPattern.value = val;
                applyPatternToDOM(val);
            }
        }
    });
}

export function setPattern(patternId) {
    initPattern();
    if (!AVAILABLE_PATTERNS.some(p => p.id === patternId)) return;
    currentPattern.value = patternId;
    try {
        window.localStorage.setItem('apm_bg_pattern', patternId);
    } catch {}
    applyPatternToDOM(patternId);
}

export function usePattern() {
    return {
        currentPattern,
        patterns: AVAILABLE_PATTERNS,
        setPattern,
        initPattern,
    };
}
