import { router } from '@inertiajs/vue3'
import { echo } from '@laravel/echo-vue'
import axios from 'axios'

const httpClient = axios.create({})

httpClient.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'

const socketId = echo().socketId()

if (socketId) {
    httpClient.defaults.headers.common['X-Socket-ID'] = socketId
}

httpClient.interceptors.response.use(
    (response) => response,
    (error) => {
        // Ensure the error has a response (network errors may not)
        const status = error?.response?.status

        if ([401, 419].includes(status)) {
            useToast().add({
                title: 'Session Expired!',
                description: 'Your session has expired. Please login again.',
                color: 'error',
                icon: 'i-lucide-circle-xmark',
            })
            router.visit(route('login'))
        }

        return Promise.reject(error)
    },
)

export default httpClient
