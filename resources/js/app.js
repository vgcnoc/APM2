import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import '../css/app.css';

window.addEventListener('error', (e) => {
    alert('JS Error: ' + e.message);
});

createInertiaApp({
    title: (title) => `${title} - ISP Management`,
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.vue', { eager: true });
        return pages[`./Pages/${name}.vue`];
    },
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });
        app.config.errorHandler = (err, instance, info) => {
            alert('Vue Error: ' + err.message + '\nInfo: ' + info);
            console.error(err);
        };
        app.use(plugin)
            .mount(el);
    },
    progress: {
        color: '#3b82f6',
        showSpinner: true,
    },
});
