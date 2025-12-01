<script setup lang="ts">
defineProps<{
    title?: string
    description?: string
    closeLabel?: string
    actionLabel?: string
    onSubmit: () => void
}>()

const emit = defineEmits<{ close: [] }>()

const closeModal = () => {
    emit('close')
}
</script>

<template>
    <UModal
        :close="{ onClick: () => closeModal() }"
        :title="title ?? 'Are you sure?'"
        :description="description ?? 'Are you sure you want to delete this record? This action cannot be undone.'"
        :ui="{ footer: 'justify-end', body: 'w-full' }"
    >
        <template #footer="{ close }">
            <UButton
                :label="closeLabel ?? 'Cancel'"
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
                :label="actionLabel ?? 'Delete'"
                color="error"
                @click="
                    () => {
                        onSubmit()
                        closeModal()
                    }
                "
                data-test="modal-delete-record-button"
            />
        </template>
    </UModal>
</template>

<style scoped></style>
