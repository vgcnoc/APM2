import { usePage } from '@inertiajs/vue3';

/**
 * Composable RBAC terpusat untuk Vue 3 / Inertia.js.
 * Memeriksa izin (permission), peran (role), dan scope area user.
 */
export function usePermission() {
    const page = usePage();

    const getUser = () => page.props.auth?.user;

    const hasRole = (role) => {
        const u = getUser();
        if (!u) return false;

        // Superadmin bypass
        const primaryRole = String(u.role || '').toLowerCase();
        if (['admin', 'super admin', 'superadmin'].includes(primaryRole)) {
            return true;
        }

        let roles = [];
        if (Array.isArray(u.roles)) roles = [...u.roles];
        else if (u.roles) roles = Object.values(u.roles);
        if (u.role) roles.push(u.role);

        const normalizedRoles = roles.map(r => String(r).toLowerCase());

        if (Array.isArray(role)) {
            return role.some(r => normalizedRoles.includes(String(r).toLowerCase()));
        }
        return normalizedRoles.includes(String(role).toLowerCase());
    };

    const can = (permission) => {
        if (!permission) return true;
        const u = getUser();
        if (!u) return false;

        // Superadmin / Admin bypass
        if (hasRole('admin')) return true;

        let perms = [];
        if (Array.isArray(u.permissions)) perms = u.permissions;
        else if (u.permissions) perms = Object.values(u.permissions);

        if (Array.isArray(permission)) {
            return permission.some(p => perms.includes(p));
        }

        return perms.includes(permission);
    };

    const canAny = (permissions) => {
        return can(permissions);
    };

    const canAll = (permissions) => {
        if (!Array.isArray(permissions)) return can(permissions);
        return permissions.every(p => can(p));
    };

    const hasArea = (areaId) => {
        const u = getUser();
        if (!u) return false;
        if (hasRole('admin')) return true;

        const areas = u.accessible_areas || (u.area_id ? [u.area_id] : []);
        return areas.includes(Number(areaId)) || areas.includes(String(areaId));
    };

    return {
        user: getUser,
        hasRole,
        can,
        canAny,
        canAll,
        hasArea,
    };
}
