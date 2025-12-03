import DeleteModal from '@/components/DeleteModal.vue'
import UpdateTeamRoleModal from '@/components/UpdateTeamRoleModal.vue'
import emitter from '@/lib/emitter'
import { TMembersTable } from '@/pages/Teams.vue'
import { useForm } from '@inertiajs/vue3'
import type { Row } from '@tanstack/table-core'
import { lowerFirst } from 'scule'

const overlay = useOverlay()
const deleteActionModal = overlay.create(DeleteModal)
const updateTeamRoleActionModal = overlay.create(UpdateTeamRoleModal)

export const teamsRows = (row: Row<TMembersTable>) => {
    return [
        {
            type: 'label',
            label: 'Actions',
        },
        {
            label: 'Update Role',
            onSelect: async () => {
                await updateTeamRoleActionModal.open({
                    currentRole: row.original.role_id,
                    teamMemberFlag: lowerFirst(row.original.status),
                    teamMemberId: row.original.id,
                }).result
            },
        },
        {
            label: 'Resend Invite',
            disabled: row.original.status !== 'Invited',
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
                const form = useForm({
                    isMember: row.original.status === 'Member',
                })
                await deleteActionModal.open({
                    description: `Are you sure you want to ${row.original.status === 'Invited' ? 'revoke the invitation? This action cannot be undone.' : 'remove the user from the team? This action cannot be undone.'}`,
                    actionLabel: row.original.status === 'Invited' ? 'Revoke Invite' : 'Remove User',
                    onSubmit: () => {
                        form.delete(route('teams.manage.remove.team.member', row.original.id), {
                            onSuccess: () => {
                                emitter.emit('team:member:deleted')
                            },
                        })
                    },
                }).result
            },
        },
    ]
}
