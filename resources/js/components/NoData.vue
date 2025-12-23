<script setup lang="ts">
export type variantType = 'solid' | 'outline' | 'soft' | 'subtle' | 'naked'
defineProps<{
    variant?: variantType
    icon?: string
    title?: string
    description?: string
    actions?: ButtonProps[]
}>()
</script>

<template>
    <UEmpty
        :variant="variant ?? 'naked'"
        :icon="icon ?? 'i-lucide-file'"
        :title="title ?? 'No Module Data'"
        :description="description ?? 'It looks like you haven\'t added any projects. Create one to get started.'"
        :actions="actions"
    />
</template>

<style scoped></style>
