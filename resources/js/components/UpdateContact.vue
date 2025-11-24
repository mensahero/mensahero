<script setup lang="ts">
import emitter from '@/lib/emitter'
import { IContact } from '@/types/contacts/contacts'
import { router, useForm } from '@inertiajs/vue3'
import { onMounted, reactive, ref } from 'vue'

const props = defineProps<{
    record: IContact
}>()

const openModal = ref(false)
const emit = defineEmits(['close'])
const form = useForm({
    name: props.record.name,
    mobile: props.record.mobile,
    country_code: props.record.country_code,
    source: props.record.source,
})

const selectFields = reactive<{
    source: string[]
    countryCodes: string[]
}>({
    source: [],
    countryCodes: [],
})

onMounted(() => {
    router.get(
        route('contacts.enums'),
        {},
        {
            onSuccess: (resp) => {
                selectFields.source = resp.props.sourceTypes as string[]
                selectFields.countryCodes = resp.props.countryCodes as string[]
            },
        },
    )
})

const onSubmit = () => {
    form.put(route('contacts.update', props.record.id), {
        onSuccess: () => {
            emitter.emit('contacts:updated')
            form.resetAndClearErrors()
            // close the modal
            closeModal()
        },
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
        title="Edit Contact"
        description="Edit an existing contact in your address book"
        :ui="{ footer: 'justify-end', body: 'w-full' }"
        @update:open="() => form.resetAndClearErrors()"
    >
        <template #body>
            <UForm class="flex w-full flex-row gap-3 space-y-2" @submit.prevent="onSubmit">
                <div class="w-6/12 space-y-2">
                    <UFormField label="Name" name="name" :error="form.errors.name" required>
                        <UInput
                            tabindex="1"
                            v-model="form.name"
                            placeholder="Enter your name"
                            class="w-full"
                            autofocus
                        />
                    </UFormField>
                    <UFormField label="Code" name="country_code" :error="form.errors.country_code" required>
                        <USelectMenu
                            tabindex="3"
                            v-model="form.country_code"
                            placeholder="Select your code"
                            :items="selectFields.countryCodes"
                            class="w-full"
                        />
                    </UFormField>
                </div>
                <div class="w-6/12 space-y-2">
                    <UFormField label="Mobile Number" name="mobile" :error="form.errors.mobile" required>
                        <UInput
                            tabindex="2"
                            v-model="form.mobile"
                            placeholder="Enter your mobile number"
                            class="w-full"
                        />
                    </UFormField>
                    <UFormField label="Source" name="source" :error="form.errors.source">
                        <USelectMenu
                            v-model="form.source"
                            tabindex="4"
                            placeholder="Select source"
                            :items="selectFields.source"
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
            <UButton label="Submit" @click.prevent="onSubmit" data-test="update-contact-button" />
        </template>
    </UModal>
</template>

<style scoped></style>
