import { defineStore } from 'pinia'
import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

export const useAuthStore = defineStore('auth', () => {
    const page = usePage()
    const user = computed(() => page.props.auth?.user ?? null)
    const isAdmin = computed(() => user.value?.role === 'admin')
    const isAuthenticated = computed(() => !!user.value)

    return { user, isAdmin, isAuthenticated }
})
