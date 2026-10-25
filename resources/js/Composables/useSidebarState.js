import { ref } from 'vue';

/**
 * State sidebar tingkat modul (singleton).
 *
 * Setiap halaman Inertia membungkus dirinya sendiri dengan <AppLayout>, sehingga
 * komponen layout di-unmount & di-mount ulang pada setiap perpindahan menu.
 * Jika state disimpan di dalam komponen, sidebar akan reset (dropdown tertutup,
 * scroll kembali ke 0) lalu dipulihkan setelah render -> terlihat "loncat".
 *
 * Dengan menyimpan state di level modul, nilai sudah benar SEBELUM render pertama
 * sehingga tidak ada lompatan visual sama sekali.
 */

const readJson = (key, fallback) => {
    try {
        const raw = localStorage.getItem(key);
        return raw === null ? fallback : JSON.parse(raw);
    } catch (e) {
        return fallback;
    }
};

const isBrowser = typeof window !== 'undefined';

const sidebarOpen = ref(isBrowser ? readJson('sidebarOpen', true) !== false : true);
// Gunakan label menu sebagai kunci yang stabil. Implementasi sebelumnya juga
// menyimpan indeks item; indeks tersebut berubah saat hak akses atau susunan
// menu berubah sehingga submenu dapat terbuka/tertutup pada item yang salah.
const savedDropdowns = isBrowser ? readJson('sidebarOpenDropdowns', []) : [];
const openDropdowns = ref(Array.isArray(savedDropdowns)
    ? [...new Set(savedDropdowns.filter((item) => typeof item === 'string' && item.trim() !== ''))]
    : []);
const scrollTop = { value: isBrowser ? (parseInt(localStorage.getItem('sidebarScrollPos'), 10) || 0) : 0 };

const persist = (key, value) => {
    try {
        localStorage.setItem(key, typeof value === 'string' ? value : JSON.stringify(value));
    } catch (e) {}
};

export function useSidebarState() {
    const setSidebarOpen = (value) => {
        sidebarOpen.value = !!value;
        persist('sidebarOpen', sidebarOpen.value);
    };

    const toggleDropdown = (key) => {
        if (openDropdowns.value.includes(key)) {
            openDropdowns.value = openDropdowns.value.filter(item => item !== key);
        } else {
            openDropdowns.value = [...openDropdowns.value, key];
        }
        persist('sidebarOpenDropdowns', openDropdowns.value);
    };

    const ensureDropdownOpen = (key) => {
        if (!openDropdowns.value.includes(key)) {
            openDropdowns.value = [...openDropdowns.value, key];
            persist('sidebarOpenDropdowns', openDropdowns.value);
        }
    };

    const saveScrollTop = (value) => {
        scrollTop.value = value;
        persist('sidebarScrollPos', String(value));
    };

    return {
        sidebarOpen,
        openDropdowns,
        scrollTop,
        setSidebarOpen,
        toggleDropdown,
        ensureDropdownOpen,
        saveScrollTop,
    };
}
