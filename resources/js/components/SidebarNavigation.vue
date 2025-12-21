<script setup lang="ts">
import type { NavigationMenuItem } from '@nuxt/ui'
import { computed } from 'vue'

const sidebarNavigationItems = computed<NavigationMenuItem[][]>(() => [
    [
        {
            label: 'MAIN MENU',
            type: 'label',
        },
        {
            label: 'Home',
            icon: 'i-lucide-house',
            to: route('dashboard', {}, false),
            target: '_self',
        },
        {
            label: 'Devices',
            icon: 'heroicons:cpu-chip',
            to: route('devices.index', {}, false),
            target: '_self',
        },
        {
            label: 'Gateway',
            icon: 'carbon:gateway-api',
            to: route('dashboard', {}, false),
            target: '_self',
        },
        {
            label: 'Contacts',
            icon: 'i-heroicons:user-group',
            to: route('contacts.create', {}, false),
            target: '_self',
        },
    ],
])
</script>

<template>
    <UDashboardSidebar
        collapsible
        :min-size="15"
        :default-size="15.5"
        :max-size="23"
        resizable
        class="bg-elevated/25"
        :ui="{ footer: 'lg:border-t lg:border-default' }"
    >
        <template #header="{ collapsed }">
            <TeamsMenu :collapsed="collapsed" />
        </template>

        <template #default="{ collapsed }">
            <UNavigationMenu
                type="single"
                :collapsed="collapsed"
                :tooltip="!!collapsed"
                :popover="!!collapsed"
                :items="sidebarNavigationItems[0]"
                orientation="vertical"
            />
        </template>

        <template #footer="{ collapsed }">
            <UserMenu :collapsed="collapsed" />
        </template>
    </UDashboardSidebar>
</template>
