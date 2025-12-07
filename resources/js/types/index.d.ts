import { Notification } from '@/types/notification'
import { User } from '@/types/user'

export interface Auth {
    user: User
    role: string
    permissions: string[]
}

export interface UiColors {
    primary: string
    neutral: string
}

export interface Theme {
    mode: 'light' | 'dark' | 'system'
}

export type AppPageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
    name: string
    auth: Auth
    csrf_token: string
    theme: Theme & UiColors
    notification?: Notification
}
