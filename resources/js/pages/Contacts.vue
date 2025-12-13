<script setup lang="ts">
import AddContact from '@/components/AddContact.vue'
import DeleteModal from '@/components/DeleteModal.vue'
import Layout from '@/layouts/default.vue'
import emitter from '@/lib/emitter'
import { contactColumns } from '@/tables/columns/contacts'
import { IContact } from '@/types/contacts/contacts'
import { IModelResource } from '@/types/modelResource'
import { TEAMS_EVENTS } from '@/utils/constants'
import { Head, router, useForm } from '@inertiajs/vue3'
import { getPaginationRowModel } from '@tanstack/table-core'
import { watchDebounced } from '@vueuse/core'
import { upperCase } from 'text-case'
import { onMounted, onUnmounted, ref, resolveComponent, useTemplateRef, watch } from 'vue'
import { route } from 'ziggy-js'

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

interface IContactResource extends IModelResource {
    data: IContact[]
}

const props = defineProps<{
    contacts?: IContactResource
    contactsCount: number
    sourceTypes: string[]
    countryCodes: string[]
}>()

const overlay = useOverlay()
const isDataLoading = ref(true)
const contactResources = ref<undefined | IContactResource>(props.contacts)
const table = useTemplateRef('table')
const addContactActionModal = overlay.create(AddContact)
const deleteActionModal = overlay.create(DeleteModal)
const rowSelection = ref({})
const pagination = ref({
    pageIndex: 0,
    pageSize: 25,
})
const tableSearch = ref('')
const tableFilters = ref({
    countryCode: 'all',
    sourceType: 'all',
})

const columnVisibility = ref({
    name: true,
    mobile: true,
    id: false,
})

const UButton = resolveComponent('UButton')
const UDropdownMenu = resolveComponent('UDropdownMenu')

const InertiaBefore = router.on('before', () => (isDataLoading.value = true))
const InertiaFinish = router.on('finish', () => (isDataLoading.value = false))

const reloadInertiaPage = () => router.reload({ only: ['contacts', 'contactsCount', 'notification'] })

onMounted(() => {
    isDataLoading.value = false
})

onUnmounted(() => {
    InertiaBefore()
    InertiaFinish()
    emitter.all.clear()
})

emitter.on('*', (type) => {
    if (type.toString().includes('contacts:')) {
        reloadInertiaPage()
    }
})

watch(
    () => props.contacts,
    (newContacts) => {
        contactResources.value = newContacts
    },
    { deep: true },
)

watch(
    () => pagination,
    (newPagination) => {
        router.get(
            route('contacts.create'),
            {
                ...route().queryParams,
                per_page: newPagination.value.pageSize,
                page: newPagination.value.pageIndex + 1,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                only: ['contacts'],
            },
        )
    },
    { deep: true },
)

watch(
    () => tableFilters,
    (newTableFilters) => {
        router.get(
            route('contacts.create'),
            {
                per_page: pagination.value.pageSize,
                page: pagination.value.pageIndex + 1,
                source_type_filter: newTableFilters.value.sourceType,
                mobile_country_code_filter: newTableFilters.value.countryCode,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                only: ['contacts'],
            },
        )
    },
    { deep: true },
)

watchDebounced(
    () => tableSearch,
    (tableSearchValue) => {
        router.get(
            route('contacts.create'),
            {
                search: tableSearchValue.value,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                only: ['contacts'],
            },
        )
    },
    {
        deep: true,
        debounce: 1000,
        maxWait: 5000,
    },
)

// if the user changes the teams, we need to refresh the user context and its contacts list
emitter.on(TEAMS_EVENTS.SWITCH, () => reloadInertiaPage())
</script>

<template>
    <AppLayout :breadcrumbItems="breadcrumbItems">
        <Head title="Contacts" />
        <template #action>
            <AddContact
                :show="props.contacts !== undefined && props.contactsCount > 0"
                :countryCodes="props.countryCodes"
                :source="props.sourceTypes"
            />
        </template>
        <div
            v-if="props.contacts && props.contactsCount === 0"
            class="flex h-full w-full flex-col items-center justify-center gap-4"
        >
            <UEmpty
                size="sm"
                variant="naked"
                icon="i-heroicons:user-group"
                title="No Contacts found"
                description="It looks like you haven't added any contact. Create one to get started."
                :actions="[
                    {
                        icon: 'i-heroicons:user-plus',
                        label: 'Add Contact',
                        onClick: async () => {
                            await addContactActionModal.open({
                                show: true,
                                countryCodes: countryCodes || [],
                                source: sourceTypes || [],
                            }).result
                        },
                    },
                    {
                        icon: 'i-lucide-refresh-cw',
                        label: 'Refresh',
                        color: 'neutral',
                        variant: 'subtle',
                        onClick: () =>
                            router.reload({
                                only: ['contacts', 'contactsCount'],
                            }),
                    },
                ]"
            />
        </div>
        <div v-else>
            <div class="flex w-full flex-1 flex-col">
                <div class="flex flex-wrap items-center justify-between gap-1.5 px-1 py-2">
                    <UInput
                        id="search-contacts"
                        v-model="tableSearch"
                        class="max-w-sm"
                        :ui="{ trailing: 'pe-1' }"
                        icon="i-lucide-search"
                        placeholder="Search..."
                    >
                        <template v-if="tableSearch?.length" #trailing>
                            <UButton
                                color="neutral"
                                variant="link"
                                size="sm"
                                icon="i-lucide-circle-x"
                                aria-label="Clear input"
                                @click="tableSearch = ''"
                            />
                        </template>
                    </UInput>

                    <div class="flex flex-wrap items-center gap-1.5">
                        <UButton
                            v-if="table?.tableApi?.getFilteredSelectedRowModel().rows.length"
                            label="Delete"
                            color="error"
                            variant="subtle"
                            icon="i-lucide-trash"
                            @click.prevent="
                                async () => {
                                    const selectedIds = table?.tableApi
                                        .getFilteredSelectedRowModel()
                                        .rows.map((rows) => rows.original.id)
                                    const selectedRowsCount = table?.tableApi.getFilteredSelectedRowModel().rows.length

                                    await deleteActionModal.open({
                                        description: `Are you sure you want to delete this ${selectedRowsCount! > 1 ? selectedRowsCount : ''} record? This action cannot be undone.`,
                                        onSubmit: () => {
                                            const form = useForm({
                                                ids: selectedIds,
                                            })
                                            form.post(route('contacts.destroy'), {
                                                onSuccess: () => {
                                                    emitter.emit('contacts:deleted')
                                                    rowSelection = {}
                                                },
                                            })
                                        },
                                    }).result
                                }
                            "
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
                                        <UFormField label="Code" name="countryCode" class="w-full">
                                            <USelect
                                                v-model="tableFilters.countryCode"
                                                :items="[
                                                    { label: 'All', value: 'all' },
                                                    ...props.countryCodes.map((code) => ({
                                                        label: upperCase(code),
                                                        value: code,
                                                    })),
                                                ]"
                                                :ui="{
                                                    trailingIcon:
                                                        'group-data-[state=open]:rotate-180 transition-transform duration-200',
                                                }"
                                                placeholder="Filter status"
                                                class="min-w-28"
                                            />
                                        </UFormField>
                                        <UFormField label="Source" name="sourceType" class="w-full">
                                            <USelect
                                                v-model="tableFilters.sourceType"
                                                :items="[
                                                    { label: 'All', value: 'all' },
                                                    ...props.sourceTypes.map((source) => ({
                                                        label: upperCase(source),
                                                        value: source,
                                                    })),
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
                                        label: upperCase(column.columnDef.header as string),
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
                    :loading="isDataLoading"
                    v-model:column-visibility="columnVisibility"
                    v-model:row-selection="rowSelection"
                    v-model:pagination="pagination"
                    :pagination-options="{
                        getPaginationRowModel: getPaginationRowModel(),
                    }"
                    :data="contactResources?.data"
                    :columns="contactColumns"
                    :ui="{
                        base: 'table-fixed border-separate border-spacing-0',
                        thead: '[&>tr]:bg-elevated/50 [&>tr]:after:content-none',
                        tbody: '[&>tr]:last:[&>td]:border-b-0',
                        th: 'py-2 first:rounded-l-lg last:rounded-r-lg border-y border-default first:border-l last:border-r',
                        td: 'border-b border-default',
                    }"
                >
                    <template #empty>
                        <div class="flex h-full w-full flex-col items-center justify-center gap-4">
                            No available contacts.
                        </div>
                    </template>
                </UTable>

                <div
                    v-if="contactResources?.meta?.total && contactResources?.meta?.total > 0"
                    class="mt-auto flex items-center justify-between gap-3 border-t border-default pt-4"
                >
                    <div class="text-sm text-muted">
                        {{ table?.tableApi?.getFilteredSelectedRowModel().rows.length || 0 }} of
                        {{ table?.tableApi?.getFilteredRowModel().rows.length || 0 }} row(s) selected.
                    </div>

                    <div class="flex items-center gap-1.5">
                        <span class="text-sm text-muted">Record per page:</span>
                        <USelect v-model="pagination.pageSize" :items="[5, 10, 25, 50, 100]" />
                        <UPagination
                            :default-page="(table?.tableApi?.getState().pagination.pageIndex || 0) + 1"
                            :items-per-page="pagination.pageSize || 25"
                            :total="contactResources?.meta.total || 0"
                            @update:page="(p: number) => table?.tableApi?.setPageIndex(p - 1)"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped></style>
