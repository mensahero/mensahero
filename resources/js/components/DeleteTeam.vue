<script setup lang="ts">
import emitter from '@/lib/emitter'
import { TEAMS_EVENTS } from '@/utils/constants'
import { useForm } from '@inertiajs/vue3'
import { ref } from 'vue'

interface Props {
    deletePasswordRequired: boolean
    isDefaultTeam: boolean
}

const props = defineProps<Props>()

const show = ref(false)

const form = useForm({
    current_password: '',
})

const onSubmit = () => {
    form.delete(route('teams.manage.destroy.team'), {
        onSuccess: () => {
            emitter.emit(TEAMS_EVENTS.DELETE)
            form.reset('current_password')
        },
    })
}
</script>

<template>
    <div class="space-y-6">
        <HeadingSmall title="Delete Team" description="Delete your team and all of its resources" />
        <div
            class="w-5/12 space-y-4 rounded-lg border border-red-100 bg-red-50 p-4 dark:border-red-200/10 dark:bg-red-700/10"
        >
            <div class="relative space-y-0.5 text-red-600 dark:text-red-100">
                <p class="font-medium">Warning</p>
                <p class="text-sm">Please proceed with caution, this cannot be undone.</p>
            </div>
            <UModal :close="false" :ui="{ footer: 'justify-end' }" @update:open="() => form.resetAndClearErrors()">
                <UButton color="error" variant="solid" data-test="delete-account">Delete Team</UButton>

                <template #title> Are you sure you want to delete your team? </template>

                <template #description>
                    Once the team is deleted, all of its resources and data will also be permanently deleted. Please
                    enter your password to confirm you would like to permanently delete the team.
                </template>

                <template #body>
                    <UForm class="w-full space-y-6" @submit.prevent="onSubmit">
                        <UFormField
                            v-if="props.deletePasswordRequired"
                            label="Confirm Password"
                            name="password"
                            :error="form.errors.current_password"
                            required
                        >
                            <UInput
                                v-model="form.current_password"
                                placeholder="Confirm your password"
                                :type="show ? 'text' : 'password'"
                                :ui="{ trailing: 'pe-1' }"
                                class="w-full"
                            >
                                <template #trailing>
                                    <UButton
                                        color="neutral"
                                        variant="link"
                                        size="sm"
                                        :icon="show ? 'i-lucide-eye-off' : 'i-lucide-eye'"
                                        :aria-label="show ? 'Hide password' : 'Show password'"
                                        :aria-pressed="show"
                                        aria-controls="password"
                                        @click="show = !show"
                                    />
                                </template>
                            </UInput>
                        </UFormField>
                    </UForm>
                </template>

                <template #footer="{ close }">
                    <UButton label="Cancel" color="neutral" variant="outline" @click="close" />
                    <UButton
                        label="Proceed"
                        color="error"
                        variant="solid"
                        @click.prevent="onSubmit"
                        data-test="confirm-delete-team-button"
                    />
                </template>
            </UModal>
        </div>
    </div>
</template>

<style scoped></style>
