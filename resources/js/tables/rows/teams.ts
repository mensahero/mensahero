import DeleteModal from '@/components/DeleteModal.vue'
import { TMembersTable } from '@/pages/Teams.vue'
import type { Row } from '@tanstack/table-core'

const overlay = useOverlay()
const deleteActionModal = overlay.create(DeleteModal)

export const teamsRows = (row: Row<TMembersTable>) => {
    return [
        {
            type: 'label',
            label: 'Actions',
        },
        {
            label: 'Update Role',
            onSelect: async () => {
                console.log('update role')
            },
        },
        {
            label: 'Resend Invite',
            onSelect: async () => {
                console.log('resend invite')
            },
        },
        {
            type: 'separator',
        },
        {
            label: row.original.status === 'Invited' ? 'Revoke Invite' : 'Remove User',
            color: 'error',
            onSelect: async () => {
                await deleteActionModal.open({
                    description: `Are you sure you want to ${row.original.status === 'Invited' ? 'revoke the invitation? This action cannot be undone.' : 'remove the user from the team? This action cannot be undone.'}`,
                    actionLabel: row.original.status === 'Invited' ? 'Revoke Invite' : 'Remove User',
                    onSubmit: () => {
                        console.log('delete')
                    },
                }).result
            },
        },
    ]
}
