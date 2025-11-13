<script setup lang="ts">
import { useAppearance } from '@/composables/useAppearance'
import { useColorUi } from '@/composables/useColorUi'
import { router, usePage } from '@inertiajs/vue3'
import type { NavigationMenuItem } from '@nuxt/ui'
import { computed, ref, watch } from 'vue'

const page = usePage()
const user = ref(page.props.auth.user)
const { updateAppearance } = useAppearance()
const { updateUi } = useColorUi()

const settingNavOpen = ref(false)

const handleLogout = (event?: Event) => {
    // Remove focus/ring from the clicked element
    if (event?.target instanceof HTMLElement) {
        event.target.blur()
    }

    router.post(
        route('logout'),
        {},
        {
            onSuccess: () => {
                updateAppearance('system')
                // reset app ui colors
                updateUi('brand-red', 'slate')
                // flush
                router.flushAll()
            },
        },
    )
}

const sidebarNavigationItems = computed<NavigationMenuItem[][]>(() => [
    [
        {
            label: 'Home',
            icon: 'i-lucide-house',
            to: route('dashboard', {}, false),
            target: '_self',
        },
    ],
])

const profilePopoverNavigationItems = computed<NavigationMenuItem[][]>(() => [
    [
        {
            label: 'Account',
            icon: 'i-lucide-user-cog',
            description: 'Configuration for user profile',
            to: route('settings.account.edit', {}, false),
            target: '_self',
        },
        {
            label: 'Appearance',
            icon: 'i-lucide-swatch-book',
            description: 'Define preference for your application themes',
            to: route('settings.appearance.edit', {}, false),
            target: '_self',
        },
        {
            label: 'Authentication',
            icon: 'i-hugeicons-security',
            description: 'Secure your password',
            to: route('settings.password.edit', {}, false),
            target: '_self',
        },
        {
            label: 'Two Factor Auth (2FA)',
            icon: 'i-streamline-plump:padlock-key',
            description: 'Secure your account by adding 2FA',
            to: route('settings.two-factor.show', {}, false),
            target: '_self',
        },
        {
            label: 'Sessions',
            icon: 'i-heroicons-signal',
            description: 'Configuration for user profile',
            to: route('settings.sessions.edit', {}, false),
            target: '_self',
        },
    ],
    [
        {
            label: 'Logout',
            type: 'trigger',
            icon: 'i-uil:signout',
            description: 'Logout user',
            autofocus: false,
            class: 'focus:ring-0 focus-visible:ring-0 focus:outline-none cursor-pointer logout',
            onClick: () => handleLogout(),
        },
    ],
])

watch(
    () => page.props.auth.user,
    () => {
        user.value = page.props.auth.user
    },
    {
        immediate: true,
        deep: true,
    },
)

watch(
    () => page.url,
    () => {
        settingNavOpen.value = route().current('settings.*')
    },
    {
        immediate: true,
        deep: true,
    },
)
</script>

<template>
    <UDashboardSidebar
        collapsible
        :min-size="15"
        :default-size="15.5"
        :max-size="23"
        :ui="{ footer: 'border-t border-default' }"
    >
        <template #header="{ collapsed }">
            <div v-if="!collapsed" class="flex w-full flex-row items-center justify-center">
                <LogoWithName class="h-10 w-auto shrink-0" />
            </div>
            <Logo v-else class="h-8 w-auto shrink-0 justify-center" />
        </template>

        <template #default="{ collapsed }">
            <UNavigationMenu
                type="single"
                highlight
                highlight-color="primary"
                :collapsed="collapsed"
                :tooltip="!!collapsed"
                :popover="!!collapsed"
                :items="sidebarNavigationItems[0]"
                orientation="vertical"
            />
        </template>

        <template #footer="{ collapsed }">
            <UPopover :arrow="true">
                <UUser
                    class="profile-popover-user"
                    :name="collapsed ? undefined : user.name"
                    :description="collapsed ? undefined : user.email"
                    :avatar="{
                        src: undefined, // you can replace it with your user avatar
                        alt: user.name, // fallback image holder if the 'src' is undefined
                    }"
                />

                <template #content>
                    <UNavigationMenu
                        class="px-2 py-5 [&_button]:focus:before:ring-0 [&_button]:focus-visible:before:ring-0"
                        type="single"
                        highlight
                        highlight-color="primary"
                        :collapsed="collapsed"
                        :tooltip="!!collapsed"
                        :popover="!!collapsed"
                        :items="profilePopoverNavigationItems"
                        orientation="vertical"
                    />
                </template>
            </UPopover>
        </template>
    </UDashboardSidebar>
</template>
