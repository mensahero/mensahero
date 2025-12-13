<script setup lang="ts">
import CreateTeam from '@/components/CreateTeamModal.vue'
import httpClient from '@/lib/axios'
import emitter from '@/lib/emitter'
import { TEAMS_EVENTS } from '@/utils/constants'
import { router } from '@inertiajs/vue3'
import { echo } from '@laravel/echo-vue'
import type { DropdownMenuItem } from '@nuxt/ui'
import { computed, onBeforeMount, ref, watch } from 'vue'

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

const toast = useToast()
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
                httpClient
                    .post(route('teams.switchTeam'), {
                        team: team.id,
                    })
                    .then(() => emitter.emit(TEAMS_EVENTS.SWITCH, team.id))
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

const retrieveTeams = async (override: boolean = true) => {
    const teamsResp = await httpClient<ITeams[]>(route('teams.getTeamMenu'))
    teams.value = teamsResp.data.map((team: ITeams) => {
        return {
            ...team,
            avatar: {
                src: undefined,
                alt: team.label,
            },
        }
    })
    if (override) {
        teams.value.forEach((team) => {
            if (team.current_team) {
                selectedTeam.value = team
            }
        })
    }
}

onBeforeMount(async () => {
    await retrieveTeams()
})

const reloadTeamsAndPermissions = () => {
    router.reload({
        only: ['auth'],
    })
}
const currentOwnerTeamDeleted = async () => {
    // refresh client information
    await retrieveTeams()
    const filteredTeams = teams.value.filter((team) => team.id !== selectedTeam.value?.id)
    selectedTeam.value = filteredTeams.filter((team) => team.default)[0]
    httpClient
        .post(route('teams.switchTeam'), {
            team: selectedTeam.value.id,
        })
        .then(() => {
            emitter.emit(TEAMS_EVENTS.SWITCH, selectedTeam.value?.id)
            router.visit(route('dashboard'))
        })
}

emitter.on(TEAMS_EVENTS.SWITCH, () => reloadTeamsAndPermissions())
emitter.on(TEAMS_EVENTS.DELETE, async () => await retrieveTeams())
emitter.on(TEAMS_EVENTS.UPDATE, async () => {
    await retrieveTeams()
})
emitter.on(TEAMS_EVENTS.CREATE, async () => {
    await retrieveTeams()
})

watch(
    () => selectedTeam,
    (currenTeam) => {
        if (currenTeam.value) {
            echo()
                .private(`Team.${currenTeam.value?.id}`)
                .listen('.deleted', async (event: ITeams) => {
                    // make sure that the current team that the user is not the deleted, if it is then change the team and redirect to
                    // dashboard, otherwise refresh the available team list only.
                    if (selectedTeam.value?.id === event.id) {
                        toast.add({
                            title: 'Team has been deleted',
                            description: 'The team that you are part of has been deleted by the owner.',
                            color: 'warning',
                            icon: 'i-lucide:triangle-alert',
                        })
                        await currentOwnerTeamDeleted()
                    } else {
                        reloadTeamsAndPermissions()
                        await retrieveTeams(false)
                    }
                })
        }
    },
    {
        immediate: true,
        deep: true,
    },
)
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
