import { router } from '@inertiajs/vue3';

/**
 * Helper terpusat untuk mengambil data user yang sedang login di Inertia.
 */
export function getAuthUser(vnode = null) {
    // 1. Dari cache window.__pageProps yang sinkron dengan Inertia router
    if (window.__pageProps?.auth?.user) {
        return window.__pageProps.auth.user;
    }

    // 2. Dari router.page.props bawaan @inertiajs/vue3
    if (router.page?.props?.auth?.user) {
        return router.page.props.auth.user;
    }

    // 3. Dari context vnode jika tersedia
    const vnodeProps = vnode?.appContext?.config?.globalProperties?.$page?.props;
    if (vnodeProps?.auth?.user) {
        return vnodeProps.auth.user;
    }

    // 4. Fallback dari elemen DOM #app data-page
    try {
        const appEl = document.getElementById('app');
        if (appEl?.dataset?.page) {
            const parsed = JSON.parse(appEl.dataset.page);
            if (parsed?.props?.auth?.user) {
                window.__pageProps = parsed.props;
                return parsed.props.auth.user;
            }
        }
    } catch (e) {}

    return null;
}

/**
 * Pengecekan izin granular (menu, card, tab, filter, button, feature)
 */
export function checkUserPermission(bindingValue, bindingArg, user = null) {
    if (!bindingValue) return true;

    // Jika user tidak diteruskan, coba ambil otomatis
    if (!user) {
        user = getAuthUser();
    }

    // Jika user belum terhidrasi (misal SSR atau first tick sebelum auth siap),
    // default IZINKAN agar komponen tidak hilang secara salah
    if (!user) {
        return true;
    }

    // Super Admin / Admin bypass penuh (semua card, aksi, filter, menu selalu aktif)
    const role = String(user.role || '').toLowerCase();
    const roles = Array.isArray(user.roles) 
        ? user.roles.map(r => String(r).toLowerCase()) 
        : (user.roles ? Object.values(user.roles).map(r => String(r).toLowerCase()) : []);
    
    if (
        role === 'admin' || 
        role === 'super admin' || 
        role === 'superadmin' || 
        roles.includes('admin') || 
        roles.includes('super admin') || 
        roles.includes('superadmin')
    ) {
        return true;
    }

    const userPerms = Array.isArray(user.permissions) 
        ? user.permissions 
        : (user.permissions ? Object.values(user.permissions) : []);

    if (Array.isArray(bindingValue)) {
        if (bindingArg === 'all') {
            return bindingValue.every(p => userPerms.includes(p));
        }
        return bindingValue.some(p => userPerms.includes(p));
    }

    return userPerms.includes(bindingValue);
}

/**
 * Vue 3 Directive: v-can
 */
export const canDirective = {
    beforeMount(el, binding, vnode) {
        applyPermission(el, binding, vnode);
    },
    mounted(el, binding, vnode) {
        applyPermission(el, binding, vnode);
    },
    updated(el, binding, vnode) {
        applyPermission(el, binding, vnode);
    }
};

function applyPermission(el, binding, vnode) {
    const user = getAuthUser(vnode);
    const allowed = checkUserPermission(binding.value, binding.arg, user);

    if (!allowed) {
        el.setAttribute('data-v-can-denied', 'true');
        el.style.setProperty('display', 'none', 'important');
        el.setAttribute('aria-hidden', 'true');
        if (el.tagName === 'BUTTON' || el.tagName === 'INPUT' || el.tagName === 'SELECT') {
            el.disabled = true;
        }
    } else {
        if (el.getAttribute('data-v-can-denied') === 'true') {
            el.removeAttribute('data-v-can-denied');
            el.style.removeProperty('display');
            el.removeAttribute('aria-hidden');
            if (el.tagName === 'BUTTON' || el.tagName === 'INPUT' || el.tagName === 'SELECT') {
                el.disabled = false;
            }
        }
    }
}

export default canDirective;
