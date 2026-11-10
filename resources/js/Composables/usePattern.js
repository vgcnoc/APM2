import { ref } from 'vue';

export const AVAILABLE_PATTERNS = [
    {
        id: 'violet-wave',
        name: 'Velvet Violet 3D',
        category: 'aesthetic',
        subtitle: 'Corak pita satin ungu 3D & neon lilac elegan',
        badge: 'Favorit',
        icon: '🟣',
        accentColor: '#9333ea',
        image: '/images/patterns/violet-wave.jpg',
        lightPreview: 'linear-gradient(135deg, #f5e8ff, #d8b4fe 40%, #a855f7 70%, #7e22ce)',
        darkPreview: 'linear-gradient(135deg, #2e0854, #581c87 40%, #9333ea 70%, #3b0764)',
    },
    {
        id: 'azure-stream',
        name: 'Azure Ribbon 3D',
        category: 'aesthetic',
        subtitle: 'Pita geometris biru azure, ocean & sky cyan',
        badge: 'Segar',
        icon: '🌊',
        accentColor: '#0284c7',
        image: '/images/patterns/azure-stream.png',
        lightPreview: 'linear-gradient(135deg, #e0f2fe, #7dd3fc 40%, #0284c7 70%, #0369a1)',
        darkPreview: 'linear-gradient(135deg, #082f49, #0369a1 40%, #0284c7 70%, #0c4a6e)',
    },
    {
        id: 'royal-batik',
        name: 'Royal Terracotta Batik',
        category: 'aesthetic',
        subtitle: 'Motif ornamen damask batik & ruby amber hangat',
        badge: 'Klasik',
        icon: '👑',
        accentColor: '#c2410c',
        image: '/images/patterns/royal-batik-full.jpg',
        lightPreview: 'linear-gradient(135deg, #ffedd5, #fb923c 45%, #c2410c 75%, #7c2d12)',
        darkPreview: 'linear-gradient(135deg, #431407, #7c2d12 40%, #c2410c 70%, #1c0805)',
    },
    {
        id: 'gold-wood',
        name: 'Golden Wood & Amber',
        category: 'aesthetic',
        subtitle: 'Corak serat kayu emas & estetika hangat alami',
        badge: 'Eksklusif',
        icon: '🪵',
        accentColor: '#b45309',
        image: '/images/patterns/golden-wood-seamless.png',
        lightPreview: 'linear-gradient(135deg, #fef3c7, #f59e0b 40%, #b45309 70%, #78350f)',
        darkPreview: 'linear-gradient(135deg, #351a08, #78350f 40%, #b45309 70%, #1e1005)',
    },
    {
        id: 'emerald-jade',
        name: 'Emerald Jade Luxury',
        category: 'aesthetic',
        subtitle: 'Gradien kemewahan hijau zamrud & tekstur honeycomb',
        badge: 'Mewah',
        icon: '🌿',
        accentColor: '#059669',
        lightPreview: 'linear-gradient(135deg, #d1fae5, #34d399 40%, #059669 70%, #064e3b)',
        darkPreview: 'linear-gradient(135deg, #064e3b, #047857 40%, #10b981 70%, #022c22)',
    },
    {
        id: 'sunset-coral',
        name: 'Sunset Coral Glow',
        category: 'aesthetic',
        subtitle: 'Gradien senja lembayung koral, peach & rose gold',
        badge: 'Hangat',
        icon: '🌅',
        accentColor: '#e11d48',
        lightPreview: 'linear-gradient(135deg, #ffe4e6, #fb7185 40%, #e11d48 70%, #881337)',
        darkPreview: 'linear-gradient(135deg, #4c0519, #881337 40%, #e11d48 70%, #1c060b)',
    },
    {
        id: 'carbon-stealth',
        name: 'Obsidian Carbon Fiber',
        category: 'tech',
        subtitle: 'Tekstur anyaman serat karbon abu gelap & titanium',
        badge: 'NOC Pro',
        icon: '🛡️',
        accentColor: '#475569',
        lightPreview: 'linear-gradient(135deg, #f1f5f9, #94a3b8 40%, #475569 70%, #1e293b)',
        darkPreview: 'linear-gradient(135deg, #1e293b, #0f172a 40%, #334155 70%, #020617)',
    },
    {
        id: 'neon-cyber',
        name: 'Cyberpunk Terminal',
        category: 'tech',
        subtitle: 'Layar dark matrix dengan aksen neon teal-cyan',
        badge: 'Cyber',
        icon: '⚡',
        accentColor: '#0d9488',
        lightPreview: 'linear-gradient(135deg, #ccfbf1, #2dd4bf 40%, #0d9488 70%, #115e59)',
        darkPreview: 'linear-gradient(135deg, #042f2e, #115e59 40%, #14b8a6 70%, #021a18)',
    },
    {
        id: 'dots',
        name: 'Tech Matrix Dots',
        category: 'tech',
        subtitle: 'Grid titik digital & ambient glow indigo',
        badge: 'Default',
        icon: '⁝⁝',
        accentColor: '#6366f1',
        lightPreview: 'radial-gradient(#6366f1 2px, transparent 2px)',
        darkPreview: 'radial-gradient(#818cf8 2px, transparent 2px)',
    },
    {
        id: 'grid',
        name: 'Blueprint Grid',
        category: 'tech',
        subtitle: 'Garis grid arsitektur NOC & Server Network',
        badge: 'Teknis',
        icon: '▦',
        accentColor: '#3b82f6',
        lightPreview: 'linear-gradient(to right, #93c5fd 1px, transparent 1px), linear-gradient(to bottom, #93c5fd 1px, transparent 1px)',
        darkPreview: 'linear-gradient(to right, #1e3a8a 1px, transparent 1px), linear-gradient(to bottom, #1e3a8a 1px, transparent 1px)',
    },
    {
        id: 'circuit',
        name: 'Cyber Circuit',
        category: 'tech',
        subtitle: 'Motif geometris sirkuit fiber optik',
        badge: 'Modern',
        icon: '◈',
        accentColor: '#6366f1',
        lightPreview: 'linear-gradient(135deg, #c7d2fe 25%, transparent 25%), linear-gradient(225deg, #c7d2fe 25%, transparent 25%)',
        darkPreview: 'linear-gradient(135deg, #3730a3 25%, transparent 25%), linear-gradient(225deg, #3730a3 25%, transparent 25%)',
    },
    {
        id: 'mesh',
        name: 'Aurora Glow Mesh',
        category: 'clean',
        subtitle: 'Gradien gelombang ambient mewah & halus',
        badge: 'Artistik',
        icon: '✦',
        accentColor: '#8b5cf6',
        lightPreview: 'radial-gradient(at 0% 0%, #c7d2fe, transparent 60%), radial-gradient(at 100% 100%, #fbcfe8, transparent 60%)',
        darkPreview: 'radial-gradient(at 0% 0%, #4338ca, transparent 60%), radial-gradient(at 100% 100%, #831843, transparent 60%)',
    },
    {
        id: 'clean',
        name: 'Polos Minimalis',
        category: 'clean',
        subtitle: 'Warna latar flat standar tanpa motif',
        badge: 'Klasik',
        icon: '○',
        accentColor: '#64748b',
        lightPreview: '#f8fafc',
        darkPreview: '#0b0718',
    },
];

export const PATTERN_CATEGORIES = [
    { id: 'all', label: 'Semua Corak', icon: '🎨' },
    { id: 'aesthetic', label: '🌟 3D & Estetika', icon: '🌟' },
    { id: 'tech', label: '⚡ NOC & Cyber', icon: '⚡' },
    { id: 'clean', label: '🧼 Minimalis', icon: '🧼' },
];

const currentPattern = ref('royal-batik');
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
        currentPattern.value = valid ? saved : 'royal-batik';
    } catch {
        currentPattern.value = 'royal-batik';
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
        categories: PATTERN_CATEGORIES,
        setPattern,
        initPattern,
    };
}
