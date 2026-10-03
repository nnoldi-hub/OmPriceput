import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export function usePermissions() {
    const auth = computed(() => usePage().props.auth ?? {});
    const roles = computed(() => auth.value.roles ?? []);
    const permissions = computed(() => auth.value.permissions ?? []);

    const hasRole = (...names) => names.some((name) => roles.value.includes(name));
    const can = (...names) => names.some((name) => permissions.value.includes(name));
    const canManage = (resource) => can(`${resource}.manage`);

    return { roles, permissions, hasRole, can, canManage };
}