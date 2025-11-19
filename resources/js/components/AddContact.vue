<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import { ref } from 'vue'

defineProps<{
    show: boolean
    countryCodes?: string[]
    source?: string[]
}>()

const openModal = ref(false)
const emit = defineEmits(['close'])
const toast = useToast()
const form = useForm({
    name: '',
    mobile: '',
    country_code: '',
    source: '',
})

const onSubmit = () => {
    form.post(route('contacts.store'), {
        onSuccess: () => {
            toast.add({
                title: 'Contact added successfully',
                color: 'success',
                description: 'Your contact has been added successfully',
                icon: 'i-heroicons-check-circle',
            })
            form.resetAndClearErrors()
            // close the modal
            closeModal()
        },
        only: ['contacts', 'contactsCount'],
    })
}

const closeModal = () => {
    openModal.value = false
    emit('close')
}
</script>

<template>
    <UModal
        v-model:open="openModal"
        title="Add Contact"
        description="Add a new contact to your address book"
        :ui="{ footer: 'justify-end', body: 'w-full' }"
        @update:open="() => form.resetAndClearErrors()"
        v-if="show"
    >
        <UButton class="mr-auto" color="primary" icon="i-heroicons:user-plus" variant="subtle" label="Add Contact" />
        <template #body>
            <UForm class="flex w-full flex-row gap-3 space-y-2" @submit.prevent="onSubmit">
                <div class="w-6/12 space-y-2">
                    <UFormField label="Name" name="name" :error="form.errors.name" required>
                        <UInput v-model="form.name" placeholder="Enter your name" class="w-full" autofocus />
                    </UFormField>
                    <UFormField label="Code" name="country_code" :error="form.errors.country_code" required>
                        <USelect
                            v-model="form.country_code"
                            placeholder="Select your code"
                            :items="countryCodes || []"
                            class="w-full"
                        />
                    </UFormField>
                </div>
                <div class="w-6/12 space-y-2">
                    <UFormField label="Mobile Number" name="mobile" :error="form.errors.mobile" required>
                        <UInput v-model="form.mobile" placeholder="Enter your mobile number" class="w-full" />
                    </UFormField>
                    <UFormField label="Source" name="source" :error="form.errors.source">
                        <USelect
                            v-model="form.source"
                            placeholder="Select source"
                            :items="source || []"
                            class="w-full"
                        />
                    </UFormField>
                </div>
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
            <UButton label="Submit" @click.prevent="onSubmit" data-test="create-contact-button" />
        </template>
    </UModal>
</template>

<style scoped></style>
