import './app.css';
import './bootstrap';

import { createApp } from 'vue';
import ui from '@nuxt/ui/vue-plugin';

import App from './App.vue';
import { router } from './router';
import { setSessionExpiredHandler } from '@shared/api/http';
import { useAuthStore } from '@features/authentication';
import { pinia } from './pinia';
import { themePreferenceController } from './theme/themePreference';

themePreferenceController.start();

const authStore = useAuthStore(pinia);

setSessionExpiredHandler(() => {
    const expiredPath = router.currentRoute.value.fullPath;

    authStore.expire();

    if (router.currentRoute.value.path !== '/sign-in') {
        void router.replace({ path: '/sign-in', query: { redirect: expiredPath } });
    }
});

createApp(App).use(pinia).use(router).use(ui).mount('#app');
