<script setup lang="ts">
import { useColorUi } from '@/composables/useColorUi'
import { useStorage } from '@vueuse/core'

const { primaryColor } = useColorUi()
const toast = useToast()
const toaster = { expand: false, progress: false, color: primaryColor.value, max: 3 }

const cookie = useStorage('cookie-consent', 'pending')
if (cookie.value !== 'accepted') {
    toast.add({
        title: 'We use first-party cookies to enhance your experience on our website.',
        duration: 0,
        close: false,
        actions: [
            {
                label: 'Accept',
                color: 'neutral',
                variant: 'outline',
                onClick: () => {
                    cookie.value = 'accepted'
                },
            },
            {
                label: 'Remind me later',
                color: 'neutral',
                variant: 'ghost',
            },
        ],
    })
}
</script>

<template>
    <Suspense>
        <UApp :toaster="toaster">
            <UMain>
                <UDashboardGroup as="div" storage="local" storage-key="dashboard" :persistent="true">
                    <SidebarNavigation />
                    <slot />
                </UDashboardGroup>
            </UMain>
        </UApp>
    </Suspense>
</template>

<style scoped></style>
