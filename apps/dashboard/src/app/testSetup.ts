import { config } from '@vue/test-utils';

// Icons are bundled and verified by the production build; component tests avoid runtime provider fetches.
config.global.stubs = { ...config.global.stubs, UIcon: true };
