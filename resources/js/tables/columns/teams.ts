import { TMembersTable } from '@/pages/Teams.vue'
import { teamsRows } from '@/tables/rows/teams'
import type { TableColumn } from '@nuxt/ui'
import UAvatar from '@nuxt/ui/runtime/components/Avatar.vue'
import UBadge from '@nuxt/ui/runtime/components/Badge.vue'
import UButton from '@nuxt/ui/runtime/components/Button.vue'
import UDropdownMenu from '@nuxt/ui/runtime/components/DropdownMenu.vue'
import dayjs from 'dayjs'
import advancedFormat from 'dayjs/plugin/advancedFormat'
import timezone from 'dayjs/plugin/timezone'
import utc from 'dayjs/plugin/utc'
import { h } from 'vue'

dayjs.extend(utc)
dayjs.extend(timezone)
dayjs.extend(advancedFormat)

export const columns: TableColumn<TMembersTable>[] = [
    {
        accessorKey: 'created_at',
        header: 'Date Joined / Invited',
        cell: ({ row }) => {
            const timestamp = row.getValue('created_at') as string
            const userTimezone = dayjs.tz.guess()
            const createdAt = dayjs(timestamp).tz(userTimezone).format('MMM DD, YYYY | hh:mm A')

            return createdAt
        },
    },
    {
        accessorKey: 'status',
        header: 'Status',
        cell: ({ row }) => {
            const color = {
                Member: 'success' as const,
                Invited: 'info' as const,
            }[row.getValue('status') as string]

            return h(UBadge, { class: 'capitalize', variant: 'subtle', color }, () => row.getValue('status'))
        },
    },
    {
        accessorKey: 'name',
        header: 'Name',
        cell: ({ row }) => {
            if (!row.original.name) return null
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
        accessorKey: 'email',
        header: 'Email',
    },
    {
        accessorKey: 'role',
        header: 'Role',
    },
    {
        accessorKey: 'actions',
        header: 'Actions',
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
                        items: teamsRows(row),
                        'aria-label': 'Table rows actions dropdown menu',
                    },
                    () =>
                        h(UButton, {
                            icon: 'i-lucide-ellipsis-vertical',
                            color: 'neutral',
                            variant: 'ghost',
                            class: 'ml-auto',
                            'data-test': 'member-actions-dropdown-trigger',
                            'aria-label': 'Actions dropdown',
                        }),
                ),
            )
        },
    },
]
