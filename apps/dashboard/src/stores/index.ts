import { createPinia } from 'pinia';

export { useAuthStore } from './auth';
export { useProjectContextStore } from './projectContext';

export const pinia = createPinia();
