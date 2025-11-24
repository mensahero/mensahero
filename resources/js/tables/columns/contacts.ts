import { contactRows } from '@/tables/rows/contacts'
import { IContact } from '@/types/contacts/contacts'
import type { TableColumn } from '@nuxt/ui'
import UAvatar from '@nuxt/ui/runtime/components/Avatar.vue'
import UBadge from '@nuxt/ui/runtime/components/Badge.vue'
import UButton from '@nuxt/ui/runtime/components/Button.vue'
import UCheckbox from '@nuxt/ui/runtime/components/Checkbox.vue'
import UDropdownMenu from '@nuxt/ui/runtime/components/DropdownMenu.vue'
import { h } from 'vue'

export const contactColumns: TableColumn<IContact>[] = [
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
                        items: contactRows(row),
                        'aria-label': 'Table rows actions dropdown menu',
                    },
                    () =>
                        h(UButton, {
                            icon: 'i-lucide-ellipsis-vertical',
                            color: 'neutral',
                            variant: 'ghost',
                            class: 'ml-auto',
                            'data-test': 'contact-actions-dropdown-trigger',
                            'aria-label': 'Actions dropdown',
                        }),
                ),
            )
        },
    },
]
