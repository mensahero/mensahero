import { IModelResourceData } from '@/types/modelResource'

export interface IContact extends IModelResourceData {
    id: string
    name: string
    mobile: string
    country_code: string
    source: string
    created: string
    updated: string
}
