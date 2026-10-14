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



createInertiaApp({
    title: (title) => `${title} - ISP Management`,
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.vue', { eager: true });
        return pages[`./Pages/${name}.vue`];
    },
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });
        app.config.globalProperties.route = window.route;

        // RBAC Global Helpers
        app.config.globalProperties.$can = (permission) => {
            const user = props.initialPage.props.auth?.user;
            if (!user) return false;
            if (user.role === 'admin' || user.role === 'Super Admin' || (user.roles && (Array.isArray(user.roles) ? user.roles : Object.values(user.roles)).includes('admin'))) return true;
            if (!permission) return true;
            let perms = Array.isArray(user.permissions) ? user.permissions : (user.permissions ? Object.values(user.permissions) : []);
            if (Array.isArray(permission)) return permission.some(p => perms.includes(p));
            return perms.includes(permission);
        };

        app.config.globalProperties.$hasRole = (role) => {
            const user = props.initialPage.props.auth?.user;
            if (!user) return false;
            if (user.role === 'admin' || user.role === 'Super Admin') return true;
            let roles = Array.isArray(user.roles) ? user.roles : (user.roles ? Object.values(user.roles) : []);
            if (user.role) roles.push(user.role);
            if (Array.isArray(role)) return role.some(r => roles.includes(r));
            return roles.includes(role);
        };

        app.use(plugin)
            .mount(el);
    },
    progress: {
        color: '#3b82f6',
        showSpinner: true,
    },
});
