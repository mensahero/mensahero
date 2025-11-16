<script setup lang="ts">
import Layout from '@/layouts/default.vue'
import { IModelResource, IModelResourceData } from '@/types/modelResource'
import { Head, router } from '@inertiajs/vue3'
import type { TableColumn } from '@nuxt/ui'
import { getPaginationRowModel, type Row } from '@tanstack/table-core'
import { useClipboard } from '@vueuse/core'
import { upperFirst } from 'scule'
import { h, onMounted, ref, resolveComponent, useTemplateRef, watch } from 'vue'

defineOptions({ layout: Layout })

const breadcrumbItems = ref([
    {
        label: 'Home',
    },
    {
        label: 'Contacts',
        to: route('contacts.create', {}, false),
        target: '_self',
    },
])

interface IContact extends IModelResourceData {
    id: string
    name: string
    mobile: string
    country_code: string
    source: string
    created: string
    updated: string
}

interface IContactResource extends IModelResource {
    data: IContact[]
}

const props = defineProps<{
    contacts?: IContactResource
    sourceTypes?: string[]
    countryCodes?: string[]
}>()

const toast = useToast()
const pageLoading = ref(true)
const contactResources = ref<undefined | IContactResource>(props.contacts)
const { copy } = useClipboard()
const table = useTemplateRef('table')
const rowSelection = ref({ 1: true })
const pagination = ref({
    pageIndex: 0,
    pageSize: 10,
})

const UButton = resolveComponent('UButton')
const UCheckbox = resolveComponent('UCheckbox')
const UBadge = resolveComponent('UBadge')
const UDropdownMenu = resolveComponent('UDropdownMenu')
const UAvatar = resolveComponent('UAvatar')

onMounted(() => {
    router.reload({
        only: ['contacts'],
        onFinish: () => (pageLoading.value = false),
    })
})

watch(
    () => props.contacts,
    (newContacts) => (contactResources.value = newContacts),
    { immediate: true, deep: true },
)

const getRowItems = (row: Row<IContact>) => {
    return [
        {
            type: 'label',
            label: 'Actions',
        },
        {
            label: 'Copy Mobile',
            onSelect() {
                copy(row.original.mobile)

                toast.add({
                    title: 'Mobile copied to clipboard!',
                    color: 'success',
                    icon: 'i-lucide-circle-check',
                })
            },
        },
        {
            label: 'Send SMS',
        },
        {
            type: 'separator',
        },
        {
            label: 'Delete Contact',
        },
    ]
}

const columnVisibility = ref({
    name: true,
    mobile: true,
    id: false,
})

const columns: TableColumn<IContact>[] = [
    {
        id: 'select',
        enableHiding: false,
        header: ({ table }) =>
            h(UCheckbox, {
                modelValue: table.getIsSomePageRowsSelected() ? 'indeterminate' : table.getIsAllPageRowsSelected(),
                'onUpdate:modelValue': (value: boolean | 'indeterminate') => table.toggleAllPageRowsSelected(!!value),
                'aria-label': 'Select all',
            }),
        cell: ({ row }) =>
            h(UCheckbox, {
                modelValue: row.getIsSelected(),
                'onUpdate:modelValue': (value: boolean | 'indeterminate') => row.toggleSelected(!!value),
                'aria-label': 'Select row',
            }),
    },
    {
        accessorKey: 'id',
        header: 'ID',
    },
    {
        accessorKey: 'name',
        header: 'Name',
        enableHiding: false,
        cell: ({ row }) => {
            return h('div', { class: 'flex items-center gap-3' }, [
                h(UAvatar, {
                    alt: row.original.name,
                    size: 'lg',
                }),
                h('div', undefined, [h('p', { class: 'font-medium text-highlighted' }, row.original.name)]),
            ])
        },
    },
    {
        accessorKey: 'mobile',
        header: 'Mobile',
        enableHiding: false,
    },
    {
        accessorKey: 'country_code',
        header: 'Code',
        cell: ({ row }) =>
            h(UBadge, { class: 'capitalize', color: 'primary', variant: 'subtle' }, () => row.getValue('country_code')),
    },
    {
        accessorKey: 'source',
        header: 'Source',
        cell: ({ row }) =>
            h(UBadge, { class: 'capitalize', color: 'info', variant: 'subtle' }, () => row.getValue('source') ?? 'N/A'),
    },
    {
        id: 'actions',
        enableHiding: false,
        cell: ({ row }) => {
            return h(
                'div',
                { class: 'text-right' },
                h(
                    UDropdownMenu,
                    {
                        content: {
                            align: 'end',
                        },
                        items: getRowItems(row),
                        'aria-label': 'Actions dropdown',
                    },
                    () =>
                        h(UButton, {
                            icon: 'i-lucide-ellipsis-vertical',
                            color: 'neutral',
                            variant: 'ghost',
                            class: 'ml-auto',
                            'aria-label': 'Actions dropdown',
                        }),
                ),
            )
        },
    },
]
</script>

<template>
    <AppLayout :breadcrumbItems="breadcrumbItems">
        <Head title="Contacts" />
        <template #action>
            <UButton
                v-if="props.contacts && props.contacts.data.length > 0"
                class="mr-auto"
                color="primary"
                icon="i-lucide-plus"
                variant="subtle"
                label="Add Contact"
                @click="router.visit(route('contacts.create'))"
            />
        </template>
        <div v-if="pageLoading">
            {{ pageLoading }}
        </div>
        <div v-else>
            <div
                v-if="props.contacts && props.contacts.meta.total === 0"
                class="flex h-full flex-col items-center justify-center gap-4"
            >
                <UEmpty
                    icon="i-lucide-file"
                    title="No Contacts found"
                    description="It looks like you haven't added any contact. Create one to get started."
                    :actions="[
                        {
                            icon: 'i-lucide-plus',
                            label: 'Create new',
                        },
                        {
                            icon: 'i-lucide-refresh-cw',
                            label: 'Refresh',
                            color: 'neutral',
                            variant: 'subtle',
                        },
                    ]"
                />
            </div>
            <div v-else>
                <div class="flex w-full flex-1 flex-col">
                    <div class="flex flex-wrap items-center justify-between gap-1.5 px-1 py-2">
                        <UInput class="max-w-sm" icon="i-lucide-search" placeholder="Filter emails or mobile..." />

                        <div class="flex flex-wrap items-center gap-1.5">
                            <UButton
                                v-if="table?.tableApi?.getFilteredSelectedRowModel().rows.length"
                                label="Delete"
                                color="error"
                                variant="subtle"
                                icon="i-lucide-trash"
                            >
                                <template #trailing>
                                    <UKbd>
                                        {{ table?.tableApi?.getFilteredSelectedRowModel().rows.length }}
                                    </UKbd>
                                </template>
                            </UButton>

                            <!--            Filters                -->
                            <UPopover arrow>
                                <UButton color="neutral" variant="outline" icon="i-lucide-filter" label="Filters" />

                                <template #content>
                                    <div class="h-auto w-full p-5">
                                        <UForm class="space-y-3">
                                            <UFormField label="Code" name="code" class="w-full">
                                                <USelect
                                                    :items="[
                                                        { label: 'All', value: 'all' },
                                                        { label: 'Subscribed', value: 'subscribed' },
                                                        { label: 'Unsubscribed', value: 'unsubscribed' },
                                                        { label: 'Bounced', value: 'bounced' },
                                                    ]"
                                                    :ui="{
                                                        trailingIcon:
                                                            'group-data-[state=open]:rotate-180 transition-transform duration-200',
                                                    }"
                                                    placeholder="Filter status"
                                                    class="min-w-28"
                                                />
                                            </UFormField>
                                            <UFormField label="Code" name="code" class="w-full">
                                                <USelect
                                                    :items="[
                                                        { label: 'All', value: 'all' },
                                                        { label: 'Subscribed', value: 'subscribed' },
                                                        { label: 'Unsubscribed', value: 'unsubscribed' },
                                                        { label: 'Bounced', value: 'bounced' },
                                                    ]"
                                                    :ui="{
                                                        trailingIcon:
                                                            'group-data-[state=open]:rotate-180 transition-transform duration-200',
                                                    }"
                                                    placeholder="Filter status"
                                                    class="min-w-28"
                                                />
                                            </UFormField>
                                        </UForm>
                                    </div>
                                </template>
                            </UPopover>

                            <UDropdownMenu
                                :items="
                                    table?.tableApi
                                        ?.getAllColumns()
                                        .filter((column) => column.getCanHide())
                                        .map((column) => ({
                                            label: upperFirst(column.columnDef.header as string),
                                            type: 'checkbox' as const,
                                            checked: column.getIsVisible(),
                                            onUpdateChecked(checked: boolean) {
                                                table?.tableApi?.getColumn(column.id)?.toggleVisibility(!!checked)
                                            },
                                            onSelect(e: Event) {
                                                e.preventDefault()
                                            },
                                        }))
                                "
                                :content="{ align: 'end' }"
                            >
                                <UButton
                                    label="Display"
                                    color="neutral"
                                    variant="outline"
                                    trailing-icon="i-lucide-settings-2"
                                />
                            </UDropdownMenu>
                        </div>
                    </div>
                    <UTable
                        ref="table"
                        class="shrink-0"
                        :loading="pageLoading"
                        v-model:column-visibility="columnVisibility"
                        v-model:row-selection="rowSelection"
                        v-model:pagination="pagination"
                        :pagination-options="{
                            getPaginationRowModel: getPaginationRowModel(),
                        }"
                        :data="contactResources?.data"
                        :columns="columns"
                        :ui="{
                            base: 'table-fixed border-separate border-spacing-0',
                            thead: '[&>tr]:bg-elevated/50 [&>tr]:after:content-none',
                            tbody: '[&>tr]:last:[&>td]:border-b-0',
                            th: 'py-2 first:rounded-l-lg last:rounded-r-lg border-y border-default first:border-l last:border-r',
                            td: 'border-b border-default',
                        }"
                    />

                    <div class="mt-auto flex items-center justify-between gap-3 border-t border-default pt-4">
                        <div class="text-sm text-muted">
                            {{ table?.tableApi?.getFilteredSelectedRowModel().rows.length || 0 }} of
                            {{ table?.tableApi?.getFilteredRowModel().rows.length || 0 }} row(s) selected.
                        </div>

                        <div class="flex items-center gap-1.5">
                            <UPagination
                                :default-page="(table?.tableApi?.getState().pagination.pageIndex || 0) + 1"
                                :items-per-page="contactResources?.meta.per_page || 10"
                                :total="contactResources?.meta.total || 0"
                                @update:page="(p: number) => table?.tableApi?.setPageIndex(p - 1)"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped></style>
