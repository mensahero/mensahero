<script setup lang="ts">
import DeleteTeam from '@/components/DeleteTeam.vue'
import { usePermissions } from '@/composables/usePermissions'
import Layout from '@/layouts/default.vue'
import emitter from '@/lib/emitter'
import { columns } from '@/tables/columns/teams'
import { ITeam } from '@/types/teams'
import { User } from '@/types/user'
import { TEAMS_EVENTS } from '@/utils/constants'
import { Head, router, useForm } from '@inertiajs/vue3'
import type { RadioGroupItem } from '@nuxt/ui'
import { capitalCase } from 'text-case'
import { onMounted, ref, watch } from 'vue'

defineOptions({ layout: Layout })

const breadcrumbItems = ref([
    {
        label: 'Home',
    },
    {
        label: 'Manage Teams',
        to: route('teams.manage.index', {}, false),
        target: '_self',
    },
])

interface ITeams extends ITeam {
    id: string
    name: string
    default: boolean
    user_owner: User
}

interface IRolesPermission {
    uuid: string
    label: string
    description: string
}

export interface IMembers {
    invited: {
        data: {
            id: string
            email: string
            role_id: string
            created_at: string
        }[]
    }
    members: {
        data: {
            id: number
            name: string
            email: string
            email_verified_at: string
            created_at: string
            updated_at: string
            is_owner: boolean
            role_id: string
        }[]
    }
}

export type TMembersTable = {
    id: string | number
    name: string
    email: string
    created_at: string
    role: IRolesPermission['label'] | 'N/A'
    role_id: string
    status: 'Member' | 'Invited'
    is_owner: boolean
}

const props = defineProps<{
    team: ITeams
    roles_permissions: IRolesPermission[]
    members: IMembers
    deletePasswordRequired: boolean
}>()

const roleOptions = ref<RadioGroupItem[]>(props.roles_permissions)
const teamData = ref(props.team)
const membersProps = ref<IMembers>(props.members)
const membersData = ref<TMembersTable[]>()
const membersInvitedData = ref<TMembersTable[]>()
const mergeMembersTableData = ref<TMembersTable[]>()
const { can } = usePermissions()

const formTeamInfo = useForm({
    name: teamData.value.name,
    default: teamData.value.default,
})

const teamInfoSubmit = () => {
    formTeamInfo.clearErrors()
    formTeamInfo.put(route('teams.manage.update.team.name', props.team.id), {
        onSuccess: () => {
            reloadProps()
            emitter.emit(TEAMS_EVENTS.UPDATE)
        },
    })
}

const reloadProps = () => {
    router.reload({
        only: ['team', 'members', 'roles_permissions', 'deletePasswordRequired', 'auth'],
    })
}

const inviteMember = useForm({
    email: '',
    role: '',
})

const inviteMemberSubmit = () => {
    inviteMember.clearErrors()
    inviteMember.post(route('teams.manage.invite'), {
        onSuccess: () => {
            emitter.emit(TEAMS_EVENTS.MEMBER_INVITED)
            inviteMember.resetAndClearErrors()
            reloadProps()
        },
        preserveScroll: true,
        preserveState: true,
    })
}

const prepMembersTableData = () => {
    membersData.value = membersProps.value.members.data.map((member) => ({
        ...member,
        status: 'Member',
        role: props.roles_permissions.find((role) => role.uuid === member.role_id)?.label ?? 'N/A',
    }))

    membersInvitedData.value = membersProps.value.invited.data.map((inviteMember) => ({
        ...inviteMember,
        status: 'Invited',
        role: props.roles_permissions.find((role) => role.uuid === inviteMember.role_id)?.label ?? 'N/A',
        name: '',
        is_owner: false,
    }))

    mergeMembersTableData.value = membersData.value.concat(membersInvitedData.value)
}

onMounted(() => {
    // prep members table
    prepMembersTableData()
})

const scrollTo = (id: string) => {
    document.getElementById(id)?.scrollIntoView({ behavior: 'smooth' })
}

emitter.on('*', () => reloadProps())

watch(
    () => props.members,
    (newMembersData) => {
        membersProps.value = newMembersData
        prepMembersTableData()
    },
)
watch(
    () => props.team,
    (newValue) => {
        teamData.value = newValue
        formTeamInfo
            .defaults({
                name: newValue.name,
                default: newValue.default,
            })
            .resetAndClearErrors()
    },
)
</script>

<template>
    <AppLayout :breadcrumbItems="breadcrumbItems">
        <Head title="Manage Teams" />

        <div class="flex w-full flex-col space-y-6">
            <div id="team-info-section">
                <!--  START:  Header Section        -->
                <HeadingSmall title="Team Name" description="The team's name and owner information." />
                <!--   END: Header Section        -->

                <UForm class="w-5/12 space-y-6 pt-5 pb-15" @submit.prevent="teamInfoSubmit">
                    <UFormField label="Team Owner" name="owner">
                        <UUser
                            :name="props.team.user_owner.name"
                            :description="props.team.user_owner.email"
                            :avatar="{
                                src: undefined,
                                alt: `${props.team.user_owner.name}`,
                            }"
                            class="w-full"
                        />
                    </UFormField>
                    <UFormField
                        v-if="can('team:update')"
                        label="Team Name"
                        name="name"
                        :error="formTeamInfo.errors.name"
                        required
                    >
                        <UInput
                            v-model="formTeamInfo.name"
                            @blur="(e: any) => (e.target.value = capitalCase(e.target.value))"
                            class="w-full"
                        />
                    </UFormField>
                    <UFormField v-if="can('team:update')" label="Mark as Default" name="default">
                        <UCheckbox icon="i-heroicons:star" v-model="formTeamInfo.default" class="w-full" />
                    </UFormField>

                    <UButton
                        v-if="can('team:update')"
                        :label="formTeamInfo.recentlySuccessful ? 'Saved.' : 'Save'"
                        type="submit"
                        :disabled="formTeamInfo.processing"
                        :loading="formTeamInfo.processing"
                    />
                </UForm>

                <USeparator class="w-10/12" />
            </div>

            <div id="add-team-member-section" v-if="can('team:invite')">
                <!--  Add Team Member Section     -->

                <!--  START:  Header Section        -->
                <HeadingSmall
                    title="Add Team Member"
                    description="Add a new team member to your team, allowing them to collaborate with you."
                />
                <!--   END: Header Section        -->

                <UForm class="w-5/12 space-y-6 pt-5 pb-15" @submit.prevent="inviteMemberSubmit">
                    <UFormField label="Email" name="email" :error="inviteMember.errors.email" required>
                        <UInput v-model="inviteMember.email" class="w-full" />
                    </UFormField>
                    <UFormField label="Role" name="role" :error="inviteMember.errors.role" required>
                        <URadioGroup
                            v-model="inviteMember.role"
                            class="w-full"
                            color="primary"
                            variant="table"
                            value-key="uuid"
                            :items="roleOptions"
                        />
                    </UFormField>
                    <UButton :label="inviteMember.recentlySuccessful ? 'Invitation Sent' : 'Invite'" type="submit" />
                </UForm>

                <USeparator class="w-10/12" />
                <!--  Team Member Section     -->
            </div>

            <div id="team-member-section">
                <!--  START:  Header Section        -->
                <HeadingSmall title="Team Member" description="All of the users that are part of this team." />
                <!--   END: Header Section        -->
                <UTable
                    class="w-10/12 shrink-0 pt-5"
                    :ui="{
                        base: 'table-fixed border-separate border-spacing-0',
                        thead: '[&>tr]:bg-elevated/50 [&>tr]:after:content-none',
                        tbody: '[&>tr]:last:[&>td]:border-b-0',
                        th: 'py-2 first:rounded-l-lg last:rounded-r-lg border-y border-default first:border-l last:border-r',
                        td: 'border-b border-default',
                    }"
                    :data="mergeMembersTableData"
                    :columns="columns"
                >
                    <template #empty>
                        <UEmpty
                            size="sm"
                            variant="naked"
                            icon="i-heroicons:user-group"
                            title="No Team Members found"
                            description="It looks like you haven't added any members."
                            :actions="[
                                {
                                    icon: 'i-heroicons:envelope',
                                    label: 'Invite Member',
                                    onClick: async () => {
                                        scrollTo('add-team-member-section')
                                    },
                                },
                                {
                                    icon: 'i-lucide-refresh-cw',
                                    label: 'Refresh',
                                    color: 'neutral',
                                    variant: 'subtle',
                                    onClick: () => reloadProps(),
                                },
                            ]"
                        />
                    </template>
                </UTable>
                <USeparator class="w-10/12" />
            </div>

            <!--  Delete Team Section     -->
            <DeleteTeam
                v-if="can('team:delete')"
                :deletePasswordRequired="props.deletePasswordRequired"
                :isDefaultTeam="props.team.default"
            />
        </div>
    </AppLayout>
</template>

<style scoped></style>
