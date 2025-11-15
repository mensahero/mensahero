<script setup lang="ts">
import { useColorUi } from '@/composables/useColorUi'

const { primaryColor } = useColorUi()
const toaster = { expand: false, progress: false, color: primaryColor.value, max: 3 }
</script>

<template>
    <UApp :toaster="toaster">
        <UMain>
            <UDashboardGroup as="div" storage="local" storage-key="dashboard" :persistent="true">
                <SidebarNavigation />
                <slot />
            </UDashboardGroup>
        </UMain>
    </UApp>
</template>

<style scoped></style>
