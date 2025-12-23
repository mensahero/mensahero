<script setup lang="ts">
import Layout from '@/layouts/default.vue'
import { Head, router } from '@inertiajs/vue3'
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

const reloadProps = () => {
    router.reload({
        only: [''],
    })
}
</script>

<template>
    <AppLayout :breadcrumbItems="breadcrumbItems">
        <Head title="Devices" />
        <NoData
            title="No devices found."
            icon="heroicons:cpu-chip"
            description="It looks like you didn't have any devices yet. Download the device gateway to connect your mobile devices."
            :actions="[
                {
                    label: 'Download',
                    icon: 'lucide:cloud-download',
                },
                {
                    icon: 'i-lucide-refresh-cw',
                    label: 'Refresh',
                    color: 'neutral',
                    variant: 'subtle',
                    onClick: reloadProps,
                },
            ]"
        />
    </AppLayout>
</template>

<style scoped></style>
