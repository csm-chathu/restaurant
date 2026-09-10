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

let handlingAuth = false
axios.interceptors.response.use(
  res => res,
  async err => {
    if (err.response?.status === 401 && !handlingAuth && !err.config?.url?.includes('/login') && !err.config?.url?.includes('/logout')) {
      handlingAuth = true
      try {
        await auth.logout()
        router.push('/login')
      } finally {
        handlingAuth = false
      }
    }
    return Promise.reject(err)
  }
)

onMounted(() => {
  auth.refreshUser()
})
</script>
