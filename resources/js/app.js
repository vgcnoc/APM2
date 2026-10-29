import { createApp, h } from 'vue';
import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import '../css/app.css';
import '../css/dark.css';
import '../css/workspace.css';
import { applyTheme } from './Composables/useTheme';
import { applyDeviceScale } from './Composables/deviceScale';

// Optimasi Partial Reload otomatis saat filter/pencarian pada halaman yang sama
const SHARED_STATIC_PROPS = [
    'officers',
    'billingSettings',
    'app_logo',
    'app_favicon',
    'company_logo',
    'app_name',
    'company_name',
    'company_address',
    'company_phone',
    'company_email',
    'company_website',
];

const PIPELINE_STATIC_PROPS = [
    'packages',
    'odps',
    'availableOdps',
    'availableOnts',
    'technicians',
    'lead_technicians',
    'cs_officers',
    'materials',
    'materialTransactions',
    'taxSettings',
    'masterFees',
    'masterFeePasang',
    'statusOptions',
    'payment_banks',
    'invoice_issue_date',
    'company',
];

router.on('before', (event) => {
    const visit = event.detail?.visit;
    if (!visit || typeof window === 'undefined') return;
    if (
        String(visit.method || 'get').toLowerCase() === 'get' &&
        visit.preserveState === true &&
        Array.isArray(visit.only) && visit.only.length === 0 &&
        Array.isArray(visit.except) && visit.except.length === 0 &&
        visit.url?.pathname === window.location.pathname
    ) {
        const pathname = visit.url.pathname || '';
        const toSkip = [...SHARED_STATIC_PROPS];

        if (!pathname.includes('/areas')) {
            toSkip.push('areas');
        }

        if (
            pathname.startsWith('/customers') ||
            pathname.startsWith('/invoices') ||
            pathname.startsWith('/pgk') ||
            pathname.startsWith('/pgs') ||
            pathname.startsWith('/customer-reports') ||
            pathname.startsWith('/financial-reports')
        ) {
            toSkip.push(...PIPELINE_STATIC_PROPS);
            if (pathname.startsWith('/pgk') || pathname.startsWith('/pgs')) {
                toSkip.push('customers', 'leadTechnicians');
            }
        } else if (pathname.startsWith('/tickets')) {
            toSkip.push('customers', 'technicians');
        }

        visit.except.push(...toSkip);
    }
});

// Cegah modal popup 419 | PAGE EXPIRED dan refresh otomatis saat session/CSRF habis
router.on('invalid', (event) => {
    if (event.detail.response?.status === 419) {
        event.preventDefault();
        window.location.reload();
    }
});

// Workspace 90%; portal sales dan auth tetap mengikuti skala native perangkat.
applyTheme();

router.on('navigate', (event) => {
    if (event.detail?.page?.props) {
        window.__pageProps = event.detail.page.props;
    }
    applyDeviceScale(event.detail?.page?.component, event.detail?.page?.props);
    applyTheme();
});

router.on('success', (event) => {
    if (event.detail?.page?.props) {
        window.__pageProps = event.detail.page.props;
    }
    applyDeviceScale(event.detail?.page?.component, event.detail?.page?.props);
    applyTheme();
});

import { canDirective, checkUserPermission } from './Directives/can';
import Can from './Components/Can.vue';

createInertiaApp({
    title: (title) => `${title} - ISP Management`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        window.__pageProps = props.initialPage.props;
        applyDeviceScale(props.initialPage.component, props.initialPage.props);
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
