import { createApp, h } from 'vue';
import { createInertiaApp, router } from '@inertiajs/vue3';
import '../css/app.css';

// Cegah modal popup 419 | PAGE EXPIRED dan refresh otomatis saat session/CSRF habis
router.on('invalid', (event) => {
    if (event.detail.response?.status === 419) {
        event.preventDefault();
        window.location.reload();
    }
});

// Atur skala tampilan: default aplikasi 80%, halaman login & auth mengikuti layar perangkat (100% responsive)
const applyDeviceScale = (componentName) => {
    if (typeof document === 'undefined') return;
    const isAuth = (componentName || '').startsWith('Auth/');
    if (isAuth) {
        document.body.classList.add('auth-device-scale');
        document.body.style.zoom = '100%';
    } else {
        document.body.classList.remove('auth-device-scale');
        document.body.style.zoom = '80%';
    }
};

const applyTheme = () => {
    if (typeof document === 'undefined') return;
    try {
        const theme = localStorage.getItem('apm_theme');
        if (theme === 'dark' || (!theme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    } catch (e) {}
};
applyTheme();

router.on('navigate', (event) => {
    if (event.detail?.page?.props) {
        window.__pageProps = event.detail.page.props;
    }
    applyDeviceScale(event.detail?.page?.component);
    applyTheme();
});

router.on('success', (event) => {
    if (event.detail?.page?.props) {
        window.__pageProps = event.detail.page.props;
    }
    applyDeviceScale(event.detail?.page?.component);
    applyTheme();
});

import { canDirective, checkUserPermission } from './Directives/can';
import Can from './Components/Can.vue';

createInertiaApp({
    title: (title) => `${title} - ISP Management`,
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.vue', { eager: true });
        return pages[`./Pages/${name}.vue`];
    },
    setup({ el, App, props, plugin }) {
        window.__pageProps = props.initialPage.props;
        applyDeviceScale(props.initialPage.component);
        const app = createApp({ render: () => h(App, props) });
        app.config.globalProperties.route = window.route;

        // Register Global Directive & Component
        app.directive('can', canDirective);
        app.component('Can', Can);

        // RBAC Global Helpers
        app.config.globalProperties.$can = (permission, arg) => {
            const user = app.config.globalProperties.$page?.props?.auth?.user || props.initialPage.props.auth?.user;
            return checkUserPermission(permission, arg, user);
        };

        app.config.globalProperties.$hasRole = (role) => {
            const user = app.config.globalProperties.$page?.props?.auth?.user || props.initialPage.props.auth?.user;
            if (!user) return false;
            const primaryRole = String(user.role || '').toLowerCase();
            if (['admin', 'super admin', 'superadmin'].includes(primaryRole)) return true;
            let roles = Array.isArray(user.roles) ? user.roles : (user.roles ? Object.values(user.roles) : []);
            if (user.role) roles.push(user.role);
            const normalized = roles.map(r => String(r).toLowerCase());
            if (Array.isArray(role)) return role.some(r => normalized.includes(String(r).toLowerCase()));
            return normalized.includes(String(role).toLowerCase());
        };

        app.use(plugin)
            .mount(el);
    },
    progress: {
        color: '#3b82f6',
        showSpinner: true,
    },
});
