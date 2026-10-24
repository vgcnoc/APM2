import { ref } from 'vue';

const isDarkMode = ref(typeof document !== 'undefined' && document.documentElement.classList.contains('dark'));
let initialized = false;
let preference = null;
let media = null;

function renderTheme() {
    isDarkMode.value = preference === 'dark' || (!preference && !!media?.matches);
    if (typeof document !== 'undefined') {
        document.documentElement.classList.toggle('dark', isDarkMode.value);
        document.documentElement.style.colorScheme = isDarkMode.value ? 'dark' : 'light';
    }
}

export function initTheme() {
    if (initialized || typeof window === 'undefined') return;
    initialized = true;
    try {
        const saved = window.localStorage.getItem('apm_theme');
        preference = ['dark', 'light'].includes(saved) ? saved : null;
    } catch { /* The selected theme still works when storage is unavailable. */ }
    media = window.matchMedia('(prefers-color-scheme: dark)');
    media.addEventListener('change', () => { if (!preference) renderTheme(); });
    window.addEventListener('storage', event => {
        if (event.key === 'apm_theme' || event.key === null) {
            preference = ['dark', 'light'].includes(event.newValue) ? event.newValue : null;
            renderTheme();
        }
    });
    renderTheme();
}

export function applyTheme() {
    initTheme();
    if (initialized) renderTheme();
}

export function toggleTheme() {
    initTheme();
    preference = isDarkMode.value ? 'light' : 'dark';
    try { window.localStorage.setItem('apm_theme', preference); } catch { /* Keep the session choice. */ }
    renderTheme();
}

export function useTheme() {
    return { isDarkMode, toggleTheme, initTheme };
}
