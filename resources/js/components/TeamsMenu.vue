<script setup lang="ts">
import CreateTeam from '@/components/CreateTeamModal.vue'
import emitter from '@/lib/emitter'
import apiFetch from '@/lib/ofetch'
import { TEAMS_EVENTS } from '@/utils/constants'
import { router } from '@inertiajs/vue3'
import type { DropdownMenuItem } from '@nuxt/ui'
import { computed, onBeforeMount, ref } from 'vue'

defineProps<{
    collapsed?: boolean
}>()

interface ITeams {
    id: string
    label: string
    current_team: boolean
    default: boolean
}

interface ITeamsMenu {
    id: string
    label: string
    avatar: {
        src: string | undefined
        alt: string
    }
    default: boolean
    current_team: boolean
    onSelect?: () => void
}

const teams = ref<ITeamsMenu[]>([])
const overlay = useOverlay()
const createTeamModalAction = overlay.create(CreateTeam)

const selectedTeam = ref<ITeamsMenu>()

const items = computed<DropdownMenuItem[][]>(() => {
    return [
        teams.value.map((team) => ({
            ...team,
            onSelect() {
                selectedTeam.value = team
                apiFetch(route('teams.switchTeam'), {
                    method: 'POST',
                    body: {
                        team: team.id,
                    },
                }).then(() => emitter.emit(TEAMS_EVENTS.SWITCH, team.id))
            },
        })),
        [
            {
                label: 'Create team',
                icon: 'i-lucide-circle-plus',
                onSelect: async () => {
                    await createTeamModalAction.open().result
                },
            },
            {
                label: 'Manage teams',
                icon: 'i-lucide-cog',
                onSelect() {
                    router.visit(route('teams.manage.index', {}, false))
                },
            },
        ],
    ]
})

const retrieveTeams = async () => {
    const teamsResp = await apiFetch<ITeams[]>(route('teams.getTeamMenu'))
    teams.value = teamsResp.map((team: ITeams) => {
        return {
            ...team,
            avatar: {
                src: undefined,
                alt: team.label,
            },
        }
    })
    teams.value.forEach((team) => {
        if (team.current_team) {
            selectedTeam.value = team
        }
    })
}

onBeforeMount(async () => {
    await retrieveTeams()
})

const reloadTeamsAndPermissions = () => {
    router.reload({
        only: ['auth'],
    })
}

emitter.on(TEAMS_EVENTS.SWITCH, () => reloadTeamsAndPermissions())
emitter.on(TEAMS_EVENTS.UPDATE, async () => {
    await retrieveTeams()
})
emitter.on(TEAMS_EVENTS.CREATE, async () => {
    await retrieveTeams()
})
</script>

<template>
    <UDropdownMenu
        :items="items"
        :content="{ align: 'center', collisionPadding: 12 }"
        :ui="{ content: collapsed ? 'w-40' : 'w-(--reka-dropdown-menu-trigger-width)' }"
    >
        <UButton
            v-bind="{
                ...selectedTeam,
                label: collapsed ? undefined : selectedTeam?.label,
                trailingIcon: collapsed ? undefined : 'i-lucide-chevrons-up-down',
            }"
            color="neutral"
            variant="ghost"
            block
            :square="collapsed"
            class="data-[state=open]:bg-elevated"
            :class="[!collapsed && 'py-2']"
            :ui="{
                trailingIcon: 'text-dimmed',
            }"
        />
    </UDropdownMenu>
</template>

<style scoped></style>
