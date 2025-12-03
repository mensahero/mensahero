<script setup lang="ts">
import emitter from '@/lib/emitter'
import { TEAMS_EVENTS } from '@/utils/constants'
import { useForm } from '@inertiajs/vue3'

defineProps<{
    title?: string
    description?: string
}>()

const emit = defineEmits<{ close: [] }>()
const form = useForm({
    name: '',
    default: false,
})

const closeModal = () => {
    emit('close')
}

const onSubmit = () => {
    form.clearErrors()
    form.post(route('teams.manage.create.team'), {
        onSuccess: () => {
            emitter.emit(TEAMS_EVENTS.SWITCH)
            emitter.emit(TEAMS_EVENTS.CREATE)
            form.resetAndClearErrors()
            closeModal()
        },
    })
}
</script>

<template>
    <UModal
        :close="{ onClick: () => closeModal() }"
        :title="title ?? 'Create New Team'"
        :description="description ?? 'Create a new team to collaborate with others and you can also set it as the default team for future logins. You will be redirected to the team dashboard after creating a new team.'"
        :ui="{ footer: 'justify-end', body: 'w-full' }"
    >
        <template #body>
            <UForm class="w-full space-y-6" @submit.prevent="onSubmit">
                <UFormField name="name" label="Team Name" :error="form.errors.name" required>
                    <UInput v-model="form.name" placeholder="Team Name" class="w-full" autofocus />
                </UFormField>
                <UFormField name="default" label="Mark as Default?">
                    <UCheckbox v-model="form.default" />
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
            <UButton label="Create Team" @click.prevent="onSubmit" data-test="modal-create-new-team-button" />
        </template>
    </UModal>
</template>

<style scoped></style>
