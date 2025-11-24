<script setup lang="ts">
import { usePage } from '@inertiajs/vue3'
import type { BreadcrumbItem } from '@nuxt/ui'
import { onMounted, watch } from 'vue'

const props = defineProps<{
    breadcrumbItems: BreadcrumbItem[]
}>()

const toast = useToast()
const inertiaPage = usePage()

onMounted(() => {
    if (inertiaPage.props.notification) {
        toast.add({
            title: inertiaPage.props.notification?.title,
            description: inertiaPage.props.notification.message,
            color: inertiaPage.props.notification?.color,
            icon: inertiaPage.props.notification?.icon,
        })
    }
})

watch(
    () => inertiaPage.props.notification,
    (notification) => {
        if (notification) {
            toast.add({
                title: notification.title,
                description: notification.message,
                color: notification.color,
                icon: notification.icon,
            })
        }
    },
)
</script>

<template>
    <UDashboardPanel>
        <template #header>
            <UDashboardNavbar>
                <template #title>
                    <UDashboardSidebarCollapse as="button" :disabled="false" />
                    <UBreadcrumb :items="props.breadcrumbItems" />
                </template>
                <template #right>
                    <slot name="action" />
                </template>
            </UDashboardNavbar>
        </template>

        <template #body>
            <slot />
        </template>
    </UDashboardPanel>
</template>

<style scoped></style>
