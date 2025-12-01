import DeleteModal from '@/components/DeleteModal.vue'
import UpdateContact from '@/components/UpdateContact.vue'
import { IContact } from '@/types/contacts/contacts'
import { router, useForm } from '@inertiajs/vue3'
import { useToast } from '@nuxt/ui/runtime/composables/useToast.js'
import type { Row } from '@tanstack/table-core'
import { useClipboard } from '@vueuse/core'
import { route } from 'ziggy-js'

const { copy } = useClipboard()
const overlay = useOverlay()
const updateContactModal = overlay.create(UpdateContact)
const deleteActionModal = overlay.create(DeleteModal)

export const contactRows = (row: Row<IContact>) => {
    return [
        {
            type: 'label',
            label: 'Actions',
        },
        {
            label: 'Copy Mobile',
            onSelect: async () => {
                await copy(row.original.mobile)

                useToast().add({
                    title: 'Mobile copied to clipboard!',
                    color: 'success',
                    icon: 'i-lucide-circle-check',
                    duration: 2000,
                })
            },
        },
        {
            label: 'Edit Contact',
            onSelect: async () => {
                await updateContactModal.open({
                    record: row.original,
                }).result
            },
        },
        {
            type: 'separator',
        },
        {
            label: 'Delete Contact',
            color: 'error',
            onSelect: async () => {
                await deleteActionModal.open({
                    onSubmit: () => {
                        const form = useForm({
                            ids: [row.original.id],
                        })
                        form.post(route('contacts.destroy'), {
                            onSuccess: () => {
                                router.reload({
                                    only: ['contacts', 'contactsCount'],
                                })
                            },
                        })
                    },
                }).result
            },
        },
    ]
}
