import { usePage } from '@inertiajs/vue3'
import { onMounted, ref, watch } from 'vue'

export function usePermissions() {
    const permissionSets = ref()

    onMounted(() => {
        const page = usePage()
        permissionSets.value = page.props?.auth.permissions
    })

    watch(
        () => usePage().props?.auth.permissions,
        (permissions) => (permissionSets.value = permissions),
    )

    function can(permission: string) {
        if (!permissionSets.value) return false

        return !!permissionSets.value.includes(permission)
    }

    function cannot(permission: string) {
        return !can(permission)
    }

    return { can, cannot }
}
