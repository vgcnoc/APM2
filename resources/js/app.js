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
        app.use(plugin)
            .mount(el);
    },
    progress: {
        color: '#3b82f6',
        showSpinner: true,
    },
});
