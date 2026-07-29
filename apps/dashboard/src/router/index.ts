import {
    createRouter,
    createWebHistory,
    type NavigationGuard,
    type RouteLocationRaw,
    type RouteRecordRaw,
} from 'vue-router';

import { pinia, useAuthStore } from '../stores';
import DashboardPage from '../pages/DashboardPage.vue';
import ApiKeysPage from '../pages/ApiKeysPage.vue';
import FeatureFlagDetailsPage from '../pages/FeatureFlagDetailsPage.vue';
import FeatureFlagsPage from '../pages/FeatureFlagsPage.vue';
import FoundationPage from '../pages/FoundationPage.vue';
import ProjectOverviewPage from '../pages/ProjectOverviewPage.vue';
import ProjectsPage from '../pages/ProjectsPage.vue';
import SignInPage from '../pages/SignInPage.vue';

export const routes: RouteRecordRaw[] = [
    { path: '/', component: FoundationPage },
    { path: '/sign-in', component: SignInPage, meta: { guestOnly: true } },
    { path: '/app', component: DashboardPage, meta: { requiresAuth: true } },
    { path: '/projects', component: ProjectsPage, meta: { requiresAuth: true } },
    {
        path: '/projects/:projectId(\\d+)',
        component: ProjectOverviewPage,
        meta: { requiresAuth: true },
    },
    {
        path: '/projects/:projectId(\\d+)/flags',
        component: FeatureFlagsPage,
        meta: { requiresAuth: true },
    },
    {
        path: '/projects/:projectId(\\d+)/flags/:flagId(\\d+)',
        component: FeatureFlagDetailsPage,
        meta: { requiresAuth: true },
    },
    {
        path: '/projects/:projectId(\\d+)/api-keys',
        component: ApiKeysPage,
        meta: { requiresAuth: true },
    },
];

export const authenticationGuard: NavigationGuard = async (to): Promise<RouteLocationRaw | undefined> => {
    const authStore = useAuthStore(pinia);

    if (to.meta.requiresAuth || (to.meta.guestOnly && authStore.status !== 'guest')) {
        await authStore.resolve(true);
    }

    if (to.meta.requiresAuth && !authStore.isAuthenticated) {
        authStore.setMessage('Your session has expired or authentication is required. Please sign in.');

        return { path: '/sign-in', query: { redirect: to.fullPath } };
    }

    if (to.meta.guestOnly && authStore.isAuthenticated) {
        return { path: '/app' };
    }
};

export const safeRedirect = (value: unknown): string => {
    if (typeof value !== 'string' || !value.startsWith('/') || value.startsWith('//')) {
        return '/app';
    }

    let decoded: string;

    try {
        decoded = decodeURIComponent(value);
    } catch {
        return '/app';
    }
    const reservedPrefixes = ['/dashboard', '/api', '/sanctum'];

    if (
        decoded.startsWith('//') ||
        reservedPrefixes.some((prefix) => decoded === prefix || decoded.startsWith(`${prefix}/`))
    ) {
        return '/app';
    }

    return decoded;
};

export const router = createRouter({ history: createWebHistory(), routes });

router.beforeEach(authenticationGuard);
