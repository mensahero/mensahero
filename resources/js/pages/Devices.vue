<script setup lang="ts">
import Layout from '@/layouts/default.vue'
import { Head } from '@inertiajs/vue3'
import { ref } from 'vue'

defineOptions({ layout: Layout })

const breadcrumbItems = ref([
    {
        label: 'Home',
    },
    {
        label: 'Devices',
        to: route('devices.index', {}, false),
        target: '_self',
    },
])
</script>

<template>
    <AppLayout :breadcrumbItems="breadcrumbItems">
        <Head title="Devices" />
    </AppLayout>
</template>

<style scoped></style>
