import { ITableHeaders } from "@/Components/DataTable.vue"
import axios from "axios"

export interface IStorage {
    id: number,
    name: string,
    limit: number
}

export const StoragesIndexHeaders: ITableHeaders[] = [
    {
        label: 'ID',
        name: 'id',
        type: 'number',
    },    
{
    label: 'Name',
    name: 'name',
    type: 'string',
},
{
    label: 'Limit',
    name: 'limit',
    type: 'string',
},]

export function fetchStorages() {
    return axios.get('/storages')
}

export function editStorage(id: number, name: string, limit: number) {
    return axios.put(`/storages/${id}`, {
        name,
        limit,
    })
}