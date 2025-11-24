export interface IModelResource {
    data: IModelResourceData[]
    links: IModelResourceLinks
    meta: IModelResourceMeta
}

export interface IModelResourceData {
    id: number | string
    [key: string]: unknown
}

export interface IModelResourceLinks {
    first: string
    last: string
    prev: string | null
    next: string | null
}

export interface IModelResourceMeta {
    current_page: number
    from: number
    last_page: number
    path: string
    per_page: number
    to: number
    total: number
    links: IModelResourceMetaLinks[]
}

interface IModelResourceMetaLinks {
    url: string
    label: string
    page: number
    active: boolean
}
