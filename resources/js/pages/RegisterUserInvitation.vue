<script setup lang="ts">
import UPasswordInput from '@/components/ui/UPasswordInput.vue'
import Layout from '@/layouts/auth.vue'
import { Head, useForm } from '@inertiajs/vue3'
import { capitalCase } from 'text-case'

defineOptions({ layout: Layout })

const props = defineProps<{
    email: string
    invitationId: string
}>()

const form = useForm({
    name: '',
    email: props.email,
    team: '',
    password: '',
    password_confirmation: '',
})

const onSubmit = () => {
    form.clearErrors()
    form.defaults('email', props.email)

    form.post(route('teams.invitations.store.user', props.invitationId), {
        onSuccess: () => {
            form.reset()
        },
        onError: () => {
            form.reset('password_confirmation')
        },
    })
}
</script>

<template>
    <div>
        <Head title="Register" />

        <!-- START: Register Form Root Layout -->
        <div class="w-full max-w-sm space-y-6">
            <!--  START:  Header Section        -->
            <div class="flex flex-col text-center">
                <div class="mb-4 flex justify-center">
                    <LogoWithName />
                </div>

                <div class="text-xl font-semibold text-pretty text-highlighted">Create an account</div>
                <div class="mt-1 text-base text-pretty text-muted">
                    Enter your details below to create your account and be able to accept the invitation.
                </div>
            </div>
            <!--   END: Header Section        -->

            <!--  START:  Form Section       -->
            <div class="flex flex-col gap-y-6">
                <UForm class="space-y-5" @submit="onSubmit">
                    <UFormField label="Name" name="name" :error="form.errors.name" required>
                        <UInput
                            class="w-full"
                            v-model="form.name"
                            placeholder="Enter your name"
                            @blur="(e: any) => (e.target.value = capitalCase(e.target.value))"
                            autofocus
                        />
                    </UFormField>

                    <UFormField label="Email" name="email" :error="form.errors.email" required>
                        <UInput class="w-full" v-model="form.email" placeholder="Enter your email" disabled />
                    </UFormField>

                    <UFormField label="Team Name" name="team" :error="form.errors.team" required>
                        <UInput
                            class="w-full"
                            v-model="form.team"
                            placeholder="Create your own personal team"
                            @blur="(e: any) => (e.target.value = capitalCase(e.target.value))"
                        />
                    </UFormField>

                    <UPasswordInput v-model="form.password" label="Password" :error="form.errors.password" required />

                    <USimplePasswordInput
                        v-model="form.password_confirmation"
                        label="Confirm Password"
                        name="password_confirmation"
                        :error="form.errors.password_confirmation"
                        placeholder="Confirm your password"
                    />

                    <UButton
                        label="Register & Accept Invitation"
                        type="submit"
                        block
                        :disabled="form.processing"
                        :loading="form.processing"
                        class="w-full"
                    />
                </UForm>
            </div>
        </div>
    </div>
</template>

<style scoped>
::-ms-reveal {
    display: none;
}
</style>
