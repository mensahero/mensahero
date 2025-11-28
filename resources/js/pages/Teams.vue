<script setup lang="ts">
import Layout from '@/layouts/default.vue'
import { ITeam } from '@/types/teams'
import { User } from '@/types/user'
import { Head, router, useForm } from '@inertiajs/vue3'
import type { RadioGroupItem } from '@nuxt/ui'
import { ref } from 'vue'

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
    user_owner: User
}

interface IRolesPermission {
    name: string
    value: string
    description: string
}

const props = defineProps<{
    team: ITeams
    roles_permissions: IRolesPermission[]
}>()

console.log(props.roles_permissions)

const formTeamInfo = useForm({
    name: props.team.name,
})

const teamInfoSubmit = () => {
    formTeamInfo.clearErrors()
    formTeamInfo.put(route('teams.manage.update.team.name', props.team.id), {
        onSuccess: () => {
            reloadProps()
        },
    })
}

const reloadProps = () => {
    router.reload({
        only: ['teams', 'roles_permissions'],
    })
}

const roleOptions = ref<RadioGroupItem[]>(props.roles_permissions)

const inviteMember = useForm({
    email: '',
    role: '',
})

const inviteMemberSubmit = () => {
    inviteMember.clearErrors()
    inviteMember.post(route('teams.manage.invite'), {
        onSuccess: () => {
            reloadProps()
        },
        preserveScroll: true,
        preserveState: true,
    })
}
</script>

<template>
    <AppLayout :breadcrumbItems="breadcrumbItems">
        <Head title="Manage Teams" />

        <div class="flex w-full flex-col space-y-6">
            <!--  START:  Header Section        -->
            <HeadingSmall
                title="Team Name"
                description="The team's name and owner information."
                id="team-info-section"
            />
            <USeparator class="w-10/12" />
            <!--   END: Header Section        -->

            <UForm class="w-5/12 space-y-6 pb-15" @submit.prevent="teamInfoSubmit">
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
                <UFormField label="Team Name" name="name" :error="formTeamInfo.errors.name" required>
                    <UInput v-model="formTeamInfo.name" class="w-full" />
                </UFormField>

                <UButton
                    class="flex place-self-end"
                    :label="formTeamInfo.recentlySuccessful ? 'Saved.' : 'Save'"
                    type="submit"
                    :disabled="formTeamInfo.processing"
                    :loading="formTeamInfo.processing"
                />
            </UForm>

            <!--  Add Team Member Section     -->

            <!--  START:  Header Section        -->
            <HeadingSmall
                title="Add Team Member"
                description="Add a new team member to your team, allowing them to collaborate with you."
                id="add-team-member-section"
            />
            <USeparator class="w-10/12" />
            <!--   END: Header Section        -->

            <UForm class="w-5/12 space-y-6 pb-15" @submit.prevent="inviteMemberSubmit">
                <UFormField label="Email" name="email" :error="inviteMember.errors.email" required>
                    <UInput v-model="inviteMember.email" class="w-full" />
                </UFormField>
                <UFormField label="Role" name="role" :error="inviteMember.errors.role" required>
                    <URadioGroup
                        v-model="inviteMember.role"
                        class="w-full"
                        color="primary"
                        variant="table"
                        value-key="id"
                        :items="roleOptions"
                    />
                </UFormField>
                <UButton
                    class="flex place-self-end"
                    :label="inviteMember.recentlySuccessful ? 'Invitation Sent' : 'Invite'"
                    type="submit"
                />
            </UForm>

            <!--  Team Member Section     -->

            <!--  START:  Header Section        -->
            <HeadingSmall
                title="Team Member"
                description="All of the users that are part of this team."
                id="team-member-section"
            />
            <USeparator class="w-10/12" />
            <!--   END: Header Section        -->
        </div>
    </AppLayout>
</template>

<style scoped></style>
