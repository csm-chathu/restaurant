<template>
  <div class="max-w-lg mx-auto">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-100">
        <h2 class="text-base font-semibold text-gray-800">My Profile</h2>
        <p class="text-sm text-gray-500 mt-0.5">Update your name, email, or password</p>
      </div>

      <form @submit.prevent="save" class="p-6 space-y-5">
        <!-- Avatar -->
        <div class="flex items-center gap-4">
          <div class="w-14 h-14 rounded-full bg-amber-500 flex items-center justify-center text-2xl font-bold text-white shrink-0">
            {{ form.name?.charAt(0)?.toUpperCase() }}
          </div>
          <div>
            <p class="text-sm font-medium text-gray-700">{{ auth.user?.role }}</p>
            <p class="text-xs text-gray-400">{{ auth.user?.branch?.name ?? 'No branch' }}</p>
          </div>
        </div>

        <div>
          <label class="form-label">Full Name *</label>
          <input v-model="form.name" required class="form-input" placeholder="Your name" />
        </div>

        <div>
          <label class="form-label">Email *</label>
          <input v-model="form.email" type="email" required class="form-input" placeholder="your@email.com" />
        </div>

        <div>
          <label class="form-label">New Password <span class="text-gray-400 font-normal">(leave blank to keep current)</span></label>
          <input v-model="form.password" type="password" minlength="6" class="form-input" placeholder="Min 6 characters" />
        </div>

        <p v-if="error" class="text-sm text-red-600 bg-red-50 px-3 py-2 rounded-lg">{{ error }}</p>
        <p v-if="success" class="text-sm text-green-700 bg-green-50 px-3 py-2 rounded-lg">{{ success }}</p>

        <div class="flex gap-3 pt-1">
          <router-link to="/" class="btn-secondary flex-1 text-center">Cancel</router-link>
          <button type="submit" :disabled="saving" class="btn-primary flex-1">
            {{ saving ? 'Saving…' : 'Save Changes' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { useAuthStore } from '@/stores/auth'

const auth    = useAuthStore()
const saving  = ref(false)
const error   = ref('')
const success = ref('')

const form = ref({ name: '', email: '', password: '' })

onMounted(() => {
  form.value.name  = auth.user?.name  ?? ''
  form.value.email = auth.user?.email ?? ''
})

async function save() {
  saving.value = true
  error.value  = ''
  success.value = ''
  try {
    const { data } = await axios.put('/api/profile', form.value)
    auth.user = data
    success.value = 'Profile updated successfully.'
    form.value.password = ''
  } catch (e) {
    error.value = e.response?.data?.message
      ?? Object.values(e.response?.data?.errors ?? {}).flat().join(', ')
      ?? 'Failed to save.'
  } finally {
    saving.value = false
  }
}
</script>
