import { defineStore } from 'pinia'
import { ref } from 'vue'
import axios from 'axios'

export const useSessionStore = defineStore('session', () => {
    const session = ref(null)
    const loading = ref(false)

    async function fetchSession(code) {
        loading.value = true
        try {
            const res = await axios.get(`/api/sessions/${code}`)
            session.value = res.data.data
        } finally {
            loading.value = false
        }
    }

    return { session, loading, fetchSession }
})
