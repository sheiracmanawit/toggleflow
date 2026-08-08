import { flushPromises, mount } from '@vue/test-utils';
import { createMemoryHistory, createRouter } from 'vue-router';
import { afterEach, describe, expect, it, vi } from 'vitest';

import { FeatureFlagValidationError, featureFlagService } from '@features/feature-flags';
import type { FeatureFlag } from '@features/feature-flags';
import FeatureFlagDetailsPage from './FeatureFlagDetailsPage.vue';

const flag: FeatureFlag = {
    id: 2,
    project_id: 1,
    name: 'New checkout',
    key: 'new-checkout',
    description: 'Controls the new checkout.',
    status: 'active',
    updated_at: '2026-07-26T00:00:00Z',
    environment_states: [
        {
            environment: { id: 10, name: 'Development', key: 'development', color: '#2563eb' },
            enabled: false,
            updated_at: '2026-07-26T00:00:00Z',
        },
        {
            environment: { id: 11, name: 'Staging', key: 'staging', color: '#b45309' },
            enabled: false,
            updated_at: '2026-07-26T00:00:00Z',
        },
        {
            environment: { id: 12, name: 'Production', key: 'production', color: '#7c3aed' },
            enabled: false,
            updated_at: '2026-07-26T00:00:00Z',
        },
    ],
};

const secondFlag: FeatureFlag = {
    ...structuredClone(flag),
    id: 3,
    name: 'Search recommendations',
    key: 'search-recommendations',
    description: 'Controls recommendation ranking.',
};

const mountPage = async () => {
    const router = createRouter({
        history: createMemoryHistory(),
        routes: [
            { path: '/projects/:projectId/flags/:flagId', component: FeatureFlagDetailsPage },
            { path: '/projects/:projectId/flags', component: { template: '<div>Flags</div>' } },
        ],
    });
    await router.push('/projects/1/flags/2');
    await router.isReady();
    const wrapper = mount(FeatureFlagDetailsPage, {
        attachTo: document.body,
        global: { plugins: [router] },
    });
    await flushPromises();

    return { wrapper, router };
};

afterEach(() => {
    document.body.innerHTML = '';
    vi.restoreAllMocks();
});

describe('FeatureFlagDetailsPage', () => {
    it('shows the active lifecycle explicitly', async () => {
        vi.spyOn(featureFlagService, 'get').mockResolvedValue(structuredClone(flag));
        const { wrapper } = await mountPage();

        expect(wrapper.get('[aria-label="Flag lifecycle: Active"]').text()).toBe('Active');
    });

    it('requires explicit impact confirmation before changing Production', async () => {
        vi.spyOn(featureFlagService, 'get').mockResolvedValue(structuredClone(flag));
        vi.spyOn(featureFlagService, 'setState').mockResolvedValue({
            ...structuredClone(flag),
            environment_states: flag.environment_states.map((state) =>
                state.environment.key === 'production' ? { ...state, enabled: true } : state,
            ),
        });
        const { wrapper } = await mountPage();

        const productionSwitch = wrapper.get('[aria-label="Enable New checkout in Production"]');
        await productionSwitch.trigger('click');
        await flushPromises();

        const dialog = document.body.querySelector<HTMLElement>('[role="dialog"]');
        expect(dialog?.textContent).toContain('will begin receiving true');
        expect(dialog?.textContent).toContain('does not deploy application code');
        expect(featureFlagService.setState).not.toHaveBeenCalled();

        dialog?.querySelector<HTMLButtonElement>('button:last-child')?.click();
        await flushPromises();

        expect(featureFlagService.setState).toHaveBeenCalledWith(1, 2, 12, true);
        expect(wrapper.text()).toContain('Production is now enabled');
    });

    it('confirms Production disable and cancels by keyboard without making a request', async () => {
        const enabledFlag = structuredClone(flag);
        enabledFlag.environment_states[2]!.enabled = true;
        vi.spyOn(featureFlagService, 'get').mockResolvedValue(enabledFlag);
        vi.spyOn(featureFlagService, 'setState');
        const { wrapper } = await mountPage();
        const productionSwitch = wrapper.get('[aria-label="Disable New checkout in Production"]');
        (productionSwitch.element as HTMLElement).focus();

        await productionSwitch.trigger('click');
        await flushPromises();
        const dialog = document.body.querySelector<HTMLElement>('[role="dialog"]');
        expect(dialog?.textContent).toContain('will begin receiving false');
        expect(document.activeElement?.textContent).toContain('Cancel');

        document.activeElement?.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }));
        await flushPromises();

        expect(document.body.querySelector('[role="dialog"]')).toBeNull();
        expect(featureFlagService.setState).not.toHaveBeenCalled();
        expect(document.activeElement).toBe(productionSwitch.element);
    });

    it('preserves the confirmed state and shows a persistent error when a mutation fails', async () => {
        vi.spyOn(featureFlagService, 'get').mockResolvedValue(structuredClone(flag));
        vi.spyOn(featureFlagService, 'setState').mockRejectedValue(new Error('Unavailable'));
        const { wrapper } = await mountPage();

        await wrapper.get('[aria-label="Enable New checkout in Development"]').trigger('click');
        await flushPromises();

        expect(wrapper.get('[role="alert"]').text()).toContain('last confirmed state is still shown');
        expect(wrapper.get('[aria-label="Enable New checkout in Development"]').attributes('aria-checked')).toBe(
            'false',
        );
    });

    it('prevents duplicate environment commands while a mutation is pending', async () => {
        vi.spyOn(featureFlagService, 'get').mockResolvedValue(structuredClone(flag));
        let resolveState: ((value: FeatureFlag) => void) | undefined;
        vi.spyOn(featureFlagService, 'setState').mockImplementation(
            () =>
                new Promise((resolve) => {
                    resolveState = resolve;
                }),
        );
        const { wrapper } = await mountPage();

        await wrapper.get('[aria-label="Enable New checkout in Development"]').trigger('click');
        await wrapper.get('[aria-label="Enable New checkout in Staging"]').trigger('click');

        expect(featureFlagService.setState).toHaveBeenCalledTimes(1);
        expect(wrapper.get('[aria-label="Enable New checkout in Staging"]').attributes('disabled')).toBeDefined();

        resolveState?.(structuredClone(flag));
        await flushPromises();
    });

    it('associates edit validation errors and keeps the confirmed details visible', async () => {
        vi.spyOn(featureFlagService, 'get').mockResolvedValue(structuredClone(flag));
        vi.spyOn(featureFlagService, 'update').mockRejectedValue(
            new FeatureFlagValidationError({
                name: ['The name field is required.'],
                description: ['The description is too long.'],
            }),
        );
        const { wrapper } = await mountPage();

        await wrapper.get('button:nth-of-type(1)').trigger('click');
        const name = document.querySelector<HTMLInputElement>('#edit-flag-name')!;
        name.value = '';
        name.dispatchEvent(new Event('input'));
        document.querySelector<HTMLFormElement>('[role="dialog"] form')?.requestSubmit();
        await flushPromises();

        expect(document.querySelector('#edit-flag-name')?.getAttribute('aria-describedby')).toBe(
            'edit-flag-name-error',
        );
        expect(document.querySelector('#edit-flag-description')?.getAttribute('aria-describedby')).toBe(
            'edit-flag-description-error',
        );
        expect(wrapper.get('h1').text()).toBe('New checkout');
    });

    it('keeps an archive failure visible and navigates only after a successful retry', async () => {
        vi.spyOn(featureFlagService, 'get').mockResolvedValue(structuredClone(flag));
        vi.spyOn(featureFlagService, 'archive')
            .mockRejectedValueOnce(new Error('Unavailable'))
            .mockResolvedValueOnce({ ...structuredClone(flag), status: 'archived' });
        const { wrapper, router } = await mountPage();

        await wrapper
            .findAll('button')
            .find((button) => button.text() === 'Archive flag')
            ?.trigger('click');
        const archiveButton = (): HTMLButtonElement | undefined =>
            Array.from(document.body.querySelectorAll<HTMLButtonElement>('[role="dialog"] button')).find(
                (button) => button.textContent === 'Archive flag',
            );
        archiveButton()?.click();
        await flushPromises();

        expect(document.body.textContent).toContain('The flag was not archived');
        expect(router.currentRoute.value.path).toBe('/projects/1/flags/2');

        archiveButton()?.click();
        await flushPromises();
        expect(router.currentRoute.value.path).toBe('/projects/1/flags');
    });

    it('shows a safe unavailable state without stale flag details', async () => {
        vi.spyOn(featureFlagService, 'get').mockRejectedValue(new Error('Forbidden'));
        const { wrapper } = await mountPage();

        expect(wrapper.get('[role="alert"]').text()).toContain('could not be found or you do not have access');
        expect(wrapper.text()).not.toContain('New checkout');
    });

    it('resets open workflows when the route changes to another flag', async () => {
        vi.spyOn(featureFlagService, 'get').mockImplementation((_projectId, flagId) =>
            Promise.resolve(structuredClone(flagId === 2 ? flag : secondFlag)),
        );
        const { wrapper, router } = await mountPage();

        await wrapper
            .findAll('button')
            .find((button) => button.text() === 'Edit details')
            ?.trigger('click');
        await wrapper.get('[aria-label="Enable New checkout in Production"]').trigger('click');
        await flushPromises();
        expect(document.body.querySelector('[role="dialog"]')).not.toBeNull();
        expect(document.body.querySelector('#edit-flag-name')).not.toBeNull();

        await router.push('/projects/1/flags/3');
        await flushPromises();

        expect(wrapper.get('h1').text()).toBe('Search recommendations');
        expect(document.body.querySelector('#edit-flag-name')).toBeNull();
        expect(document.body.querySelector('[role="dialog"]')).toBeNull();
    });

    it('discards a late mutation response after the route changes', async () => {
        vi.spyOn(featureFlagService, 'get').mockImplementation((_projectId, flagId) =>
            Promise.resolve(structuredClone(flagId === 2 ? flag : secondFlag)),
        );
        let resolveState: ((value: FeatureFlag) => void) | undefined;
        vi.spyOn(featureFlagService, 'setState').mockImplementation(
            () =>
                new Promise((resolve) => {
                    resolveState = resolve;
                }),
        );
        const { wrapper, router } = await mountPage();

        await wrapper.get('[aria-label="Enable New checkout in Development"]').trigger('click');
        await router.push('/projects/1/flags/3');
        await flushPromises();
        expect(wrapper.get('h1').text()).toBe('Search recommendations');

        resolveState?.({
            ...structuredClone(flag),
            environment_states: flag.environment_states.map((state) =>
                state.environment.key === 'development' ? { ...state, enabled: true } : state,
            ),
        });
        await flushPromises();

        expect(wrapper.get('h1').text()).toBe('Search recommendations');
        expect(wrapper.text()).not.toContain('Development is now enabled');
    });
});
