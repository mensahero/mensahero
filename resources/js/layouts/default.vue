<script setup lang="ts">
const toaster = { expand: false, progress: false, max: 4 }
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
