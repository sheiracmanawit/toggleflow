import { createRouter, createWebHistory, type NavigationGuard, type RouteRecordRaw } from 'vue-router';

import DashboardPage from '../pages/DashboardPage.vue';
import FoundationPage from '../pages/FoundationPage.vue';
import SignInPage from '../pages/SignInPage.vue';

export const routes: RouteRecordRaw[] = [
    { path: '/', component: FoundationPage },
    { path: '/sign-in', component: SignInPage },
    { path: '/dashboard', component: DashboardPage, meta: { requiresAuth: true } },
];

export const authenticationGuard: NavigationGuard = (to) => {
    if (to.meta.requiresAuth) {
        return { path: '/sign-in', query: { redirect: to.fullPath } };
    }
};

export const router = createRouter({ history: createWebHistory(), routes });

router.beforeEach(authenticationGuard);
