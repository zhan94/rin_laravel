import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createPinia } from 'pinia';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import '../css/app.css';
import { initSentry } from './observability/sentry';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

if (typeof document !== 'undefined') {
    document.documentElement.classList.remove('dark');
}

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const pinia = createPinia();
        const app = createApp({ render: () => h(App, props) });

        app.use(plugin).use(pinia);
        initSentry(app);
        app.mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
