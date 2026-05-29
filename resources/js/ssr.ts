import { createInertiaApp } from '@inertiajs/vue3';
import createServer from '@inertiajs/vue3/server';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createPinia } from 'pinia';
import { createSSRApp, h  } from 'vue';
import type {DefineComponent} from 'vue';
import { renderToString } from 'vue/server-renderer';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createServer((page) =>
    createInertiaApp({
        page,
        render: renderToString,
        title: (title) => (title ? `${title} | ${appName}` : appName),
        resolve: async (name) => {
            const component = await resolvePageComponent(
                `./pages/${name}.vue`,
                import.meta.glob<DefineComponent>('./pages/**/*.vue'),
            );

            if (component.default.layout === undefined) {
                if (name.startsWith('auth/')) {
                    component.default.layout = AuthLayout;
                } else if (name.startsWith('settings/')) {
                    component.default.layout = [AppLayout, SettingsLayout];
                } else if (name !== 'Welcome') {
                    component.default.layout = AppLayout;
                }
            }

            return component;
        },
        setup({ App, props, plugin }) {
            return createSSRApp({ render: () => h(App, props) })
                .use(plugin)
                .use(createPinia());
        },
    }),
);
