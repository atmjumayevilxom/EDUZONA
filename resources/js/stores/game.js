import { defineStore } from 'pinia'
import { ref } from 'vue'
import axios from 'axios'

export const useGameStore = defineStore('game', () => {
    const games = ref([])
    const gamesMeta = ref(null)   // { current_page, last_page, total, per_page }
    const currentGame = ref(null)
    const categories = ref([])
    const templates = ref([])
    const loading = ref(false)
    const error = ref(null)

    async function fetchCategories() {
        const res = await axios.get('/api/categories')
        categories.value = res.data.data
    }

    async function fetchTemplates() {
        const res = await axios.get('/api/templates')
        templates.value = res.data.data
    }

    async function fetchGames(page = 1, filters = {}) {
        loading.value = true
        try {
            const params = { page, ...filters }
            Object.keys(params).forEach(k => { if (params[k] === '' || params[k] === null || params[k] === undefined) delete params[k] })
            const res = await axios.get('/api/games', { params })
            games.value = res.data.data
            gamesMeta.value = res.data.meta ?? {
                current_page: res.data.current_page ?? 1,
                last_page:    res.data.last_page    ?? 1,
                total:        res.data.total        ?? 0,
                per_page:     res.data.per_page     ?? 15,
            }
        } finally {
            loading.value = false
        }
    }

    async function fetchGame(id) {
        loading.value = true
        error.value = null
        currentGame.value = null
        try {
            const res = await axios.get(`/api/games/${id}`)
            currentGame.value = res.data.data
        } catch (e) {
            error.value = e.response?.status === 404
                ? "O'yin topilmadi."
                : e.response?.status === 403
                    ? "Sizda bu o'yinni ko'rish huquqi yo'q."
                    : "O'yinni yuklashda xato yuz berdi."
        } finally {
            loading.value = false
        }
    }

    async function generateGame(payload) {
        loading.value = true
        error.value = null
        try {
            const res = await axios.post('/api/games/generate', payload)
            currentGame.value = res.data.data
            return res.data.data
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Xato yuz berdi'
            throw e
        } finally {
            loading.value = false
        }
    }

    async function createSession(gameId, classroomId = null) {
        const payload = {}
        if (classroomId) payload.classroom_id = classroomId
        const res = await axios.post(`/api/games/${gameId}/session/create`, payload)
        return res.data.data
    }

    async function copyGame(gameId) {
        const res = await axios.post(`/api/games/${gameId}/copy`)
        return res.data.data
    }

    async function retryGame(gameId) {
        const res = await axios.post(`/api/games/${gameId}/retry`)
        const updated = res.data.data
        currentGame.value = updated
        const idx = games.value.findIndex(g => g.id === gameId)
        if (idx !== -1) games.value[idx] = updated
        return updated
    }

    async function deleteGame(gameId) {
        await axios.delete(`/api/games/${gameId}`)
        games.value = games.value.filter(g => g.id !== gameId)
        if (currentGame.value?.id === gameId) currentGame.value = null
    }

    return {
        games, gamesMeta, currentGame, categories, templates, loading, error,
        fetchCategories, fetchTemplates, fetchGames, fetchGame, generateGame, createSession, copyGame, retryGame, deleteGame
    }
})
