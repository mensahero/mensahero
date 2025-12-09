<script setup lang="ts">
import httpClient from '@/lib/axios'
import emitter from '@/lib/emitter'
import { TEAMS_EVENTS } from '@/utils/constants'
import { useForm } from '@inertiajs/vue3'
import type { RadioGroupItem } from '@nuxt/ui'
import { onBeforeMount, ref } from 'vue'

const props = defineProps<{
    title?: string
    description?: string
    currentRole: string
    teamMemberId: number | string
    teamMemberFlag: 'invited' | 'member'
}>()

interface IRolesPermission {
    uuid: string
    label: string
    description: string
}

const emit = defineEmits<{ close: [] }>()
const availableTeamRoles = ref<RadioGroupItem[]>([])

const closeModal = () => {
    emit('close')
}

const form = useForm({
    role: props.currentRole,
    isMember: props.teamMemberFlag === 'member',
})

const onSubmit = () => {
    if (form.isDirty) {
        form.patch(route('teams.manage.update.team.member.role', props.teamMemberId), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: (params) => {
                emitter.emit(TEAMS_EVENTS.MEMBER_ROLE_CHANGED, params.props.teamMemberId)
                // close the modal
                closeModal()
            },
        })
    } else {
        closeModal()
    }
}

onBeforeMount(async () => {
    console.log(form.role)

    const rolesPermissions = (await httpClient<{ roles: IRolesPermission[] }>(route('teams.getTeamRoles'))).data?.roles
    availableTeamRoles.value = rolesPermissions.map((role) => ({
        label: role.label,
        id: role.uuid,
        description: role.description,
    }))
})
</script>

<template>
    <UModal
        :close="{ onClick: () => closeModal() }"
        :title="title ?? 'Update Team Role?'"
        :description="description ?? 'Are you sure you want to update the role of this team member?'"
        :ui="{ footer: 'justify-end', body: 'w-full' }"
    >
        <template #body>
            <UForm class="w-full space-y-6" @submit.prevent="onSubmit">
                <UFormField label="Role" name="role" :error="form.errors.role" required>
                    <URadioGroup required v-model="form.role" value-key="id" :items="availableTeamRoles" />
                </UFormField>
            </UForm>
        </template>

        <template #footer="{ close }">
            <UButton
                label="Cancel"
                color="neutral"
                variant="outline"
                @click="
                    () => {
                        close
                        closeModal()
                    }
                "
            />
            <UButton
                label="Update Role"
                color="error"
                @click="onSubmit"
                data-test="modal-update-team-member-role-button"
            />
        </template>
    </UModal>
</template>

<style scoped></style>
