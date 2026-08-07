import {
    createRouter,
    createWebHistory,
    type NavigationGuard,
    type RouteLocationRaw,
    type RouteRecordRaw,
} from 'vue-router';

import { useAuthStore } from '@features/authentication';
import { pinia } from '@app/pinia';
import { DashboardPage } from '@features/dashboard';
import { AuditLogPage } from '@features/audit-history';
import { ApiKeysPage } from '@features/credentials';
import { FeatureFlagDetailsPage, FeatureFlagsPage } from '@features/feature-flags';
import FoundationPage from '../pages/FoundationPage.vue';
import { ProjectsPage } from '@features/projects';
import { SignInPage } from '@features/authentication';
import ProjectOverviewPage from '../pages/ProjectOverviewPage.vue';

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
    {
        path: '/projects/:projectId(\\d+)/audit-log',
        component: AuditLogPage,
        meta: { requiresAuth: true },
    },
];

if (import.meta.env.DEV) {
    routes.push({
        path: '/__ui-foundation',
        name: 'ui-foundation',
        component: () => import('../pages/UiFoundationPage.vue'),
    });
}

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

export const router = createRouter({ history: createWebHistory(), routes });

router.beforeEach(authenticationGuard);
