import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createPinia } from 'pinia';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { initializeTheme } from '@/composables/useAppearance';
import { initializeFlashToast } from '@/lib/flashToast';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} | ${appName}` : appName),

    // Fallback quand le layout de la page est un objet de props (ex: auth pages)
    layout: (name) => {
        if (name === 'Welcome') {
            return null;
        }

        if (name.startsWith('auth/')) {
            return AuthLayout;
        }

        if (name.startsWith('settings/')) {
            return [AppLayout, SettingsLayout];
        }

        return AppLayout;
    },

    resolve: async (name) => {
        const page = await resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./pages/**/*.vue'),
        );

        // Pour les pages sans layout défini (pas d'objet de props), on assigne directement
        if (page.default.layout === undefined) {
            if (name.startsWith('auth/')) {
                page.default.layout = AuthLayout;
            } else if (name.startsWith('settings/')) {
                page.default.layout = [AppLayout, SettingsLayout];
            } else if (name !== 'Welcome') {
                page.default.layout = AppLayout;
            }
        }

        return page;
    },

    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(createPinia())
            .mount(el);
    },

    progress: {
        color: '#4B5563',
    },
});

initializeTheme();
initializeFlashToast();
