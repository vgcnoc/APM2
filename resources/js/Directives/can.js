/**
 * Vue 3 Directive: v-can
 * Penggunaan:
 *   <button v-can="'customers_booking_create'">Tambah Booking</button>
 *   <div v-can="['customers_survey_report', 'customers_survey_assign']">...</div>
 *   <div v-can:all="['perm_a', 'perm_b']">...</div>
 */
export function checkUserPermission(bindingValue, bindingArg, user) {
    if (!bindingValue) return true;
    if (!user) return false;

    // Super Admin / Admin bypass
    const role = String(user.role || '').toLowerCase();
    const roles = Array.isArray(user.roles) 
        ? user.roles.map(r => String(r).toLowerCase()) 
        : (user.roles ? Object.values(user.roles).map(r => String(r).toLowerCase()) : []);
    
    if (role === 'admin' || role === 'super admin' || role === 'superadmin' || roles.includes('admin') || roles.includes('super admin')) {
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

export const canDirective = {
    mounted(el, binding, vnode) {
        applyPermission(el, binding, vnode);
    },
    updated(el, binding, vnode) {
        applyPermission(el, binding, vnode);
    }
};

function applyPermission(el, binding, vnode) {
    const pageProps = vnode?.appContext?.config?.globalProperties?.$page?.props || window.__pageProps;
    const user = pageProps?.auth?.user;

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
