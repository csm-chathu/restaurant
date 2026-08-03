<template>
  <router-view />
</template>

<script setup>
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { useAuthStore } from '@/stores/auth'

const auth   = useAuthStore()
const router = useRouter()

axios.interceptors.response.use(
  res => res,
  async err => {
    if (err.response?.status === 401 && !err.config?.url?.includes('/login')) {
      await auth.logout()
      router.push('/login')
    }
    return Promise.reject(err)
  }
)

onMounted(() => {
  auth.refreshUser()
})
</script>
