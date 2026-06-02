import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

export function useAuth() {
    const page = usePage()

    const user = computed(() => page.props.auth?.user)
    const isAdmin = computed(() => user.value?.role === 'admin')
    const isEncargado = computed(() => user.value?.role === 'encargado')
    const system = computed(() => page.props.system)

    function can(permission) {
        if (isAdmin.value) return true

        const permissions = {
            'manage-branches': false,
            'manage-users': false,
            'manage-settings': false,
            'manage-transfers': false,
            'approve-presales': false,
            'view-profits': user.value?.can_view_profits ?? false,
        }

        return permissions[permission] ?? false
    }

    function hasRole(...roles) {
        return roles.includes(user.value?.role)
    }

    return {
        user,
        isAdmin,
        isEncargado,
        system,
        can,
        hasRole,
    }
}
