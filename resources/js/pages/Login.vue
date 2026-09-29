<template>
  <div class="min-h-screen relative overflow-hidden flex items-center justify-center"
       style="background-color: #fff5f0;">

    <!-- ── Orange corner blobs ── -->
    <div class="absolute top-0 left-0 w-52 h-40 rounded-br-full pointer-events-none"
         style="background: linear-gradient(135deg, #f97316, #ea580c);"></div>
    <div class="absolute bottom-0 right-0 w-64 h-48 rounded-tl-full pointer-events-none"
         style="background: linear-gradient(315deg, #f97316, #ea580c);"></div>

    <!-- ── Animated floating food icons ── -->
    <span class="login-float absolute text-5xl pointer-events-none select-none opacity-30" style="left:6%;top:20%;animation-delay:0s">🍕</span>
    <span class="login-float absolute text-4xl pointer-events-none select-none opacity-25" style="left:14%;top:65%;animation-delay:1.2s">☕</span>
    <span class="login-float absolute text-5xl pointer-events-none select-none opacity-20" style="left:3%;top:50%;animation-delay:2.5s">🍔</span>
    <span class="login-float absolute text-3xl pointer-events-none select-none opacity-25" style="left:22%;top:10%;animation-delay:0.8s">🍰</span>
    <span class="login-float absolute text-4xl pointer-events-none select-none opacity-20" style="left:35%;top:80%;animation-delay:3s">🍜</span>
    <span class="login-float absolute text-3xl pointer-events-none select-none opacity-25" style="left:50%;top:8%;animation-delay:1.5s">🥗</span>
    <span class="login-float absolute text-5xl pointer-events-none select-none opacity-20" style="right:5%;top:30%;animation-delay:0.4s">🍣</span>
    <span class="login-float absolute text-3xl pointer-events-none select-none opacity-25" style="right:18%;top:70%;animation-delay:2s">🧁</span>
    <span class="login-float absolute text-4xl pointer-events-none select-none opacity-20" style="right:30%;top:15%;animation-delay:3.5s">🍷</span>
    <span class="login-float absolute text-3xl pointer-events-none select-none opacity-25" style="right:8%;bottom:12%;animation-delay:1s">🌮</span>
    <span class="login-spin  absolute text-2xl pointer-events-none select-none opacity-20" style="left:45%;top:40%;animation-delay:0s">⭐</span>
    <span class="login-spin  absolute text-xl  pointer-events-none select-none opacity-15" style="right:40%;bottom:30%;animation-delay:2s">✨</span>

    <!-- ── Real food photos (scattered, decorative) ── -->
    <!-- Pasta top-right -->
    <img src="https://images.unsplash.com/photo-1621996346565-e3dbc646d9a9?w=480&h=340&fit=crop&auto=format"
         alt="" aria-hidden="true"
         class="absolute top-0 right-16 w-72 h-52 object-cover rounded-bl-3xl shadow-xl pointer-events-none opacity-90"/>
    <!-- Pizza bottom-left -->
    <img src="https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?w=560&h=420&fit=crop&auto=format"
         alt="" aria-hidden="true"
         class="absolute bottom-0 left-0 w-96 h-72 object-cover rounded-tr-3xl shadow-xl pointer-events-none opacity-90"/>
    <!-- Burger + fries center-left (main hero) -->
    <img src="https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=640&h=520&fit=crop&auto=format"
         alt="" aria-hidden="true"
         class="absolute bottom-4 left-64 w-[30rem] h-80 object-cover rounded-2xl shadow-2xl pointer-events-none opacity-95"/>

    <!-- ── Main content wrapper ── -->
    <div class="relative z-10 w-full max-w-5xl mx-auto px-6 flex items-center justify-between gap-8 min-h-screen py-12">

      <!-- ── Left: Branding ── -->
      <div class="flex-1 flex flex-col items-start justify-center pb-64">
        <!-- Logo / shop icon -->
        <div class="mb-4 flex flex-col items-center">
          <div v-if="logoUrl" class="mb-3">
            <img :src="logoUrl" alt="Logo" class="h-20 w-auto object-contain drop-shadow-lg"/>
          </div>
          <div v-else class="mb-3 flex flex-col items-center">
            <!-- Chef hat icon -->
            <svg class="w-16 h-16 text-gray-800" viewBox="0 0 80 80" fill="none">
              <ellipse cx="40" cy="52" rx="28" ry="10" fill="currentColor" opacity="0.15"/>
              <rect x="16" y="42" width="48" height="18" rx="4" fill="currentColor"/>
              <circle cx="40" cy="28" r="18" fill="currentColor" opacity="0.9"/>
              <circle cx="22" cy="32" r="10" fill="currentColor" opacity="0.9"/>
              <circle cx="58" cy="32" r="10" fill="currentColor" opacity="0.9"/>
              <!-- shine lines -->
              <line x1="36" y1="10" x2="34" y2="4" stroke="#f97316" stroke-width="2" stroke-linecap="round"/>
              <line x1="40" y1="9" x2="40" y2="3" stroke="#f97316" stroke-width="2" stroke-linecap="round"/>
              <line x1="44" y1="10" x2="46" y2="4" stroke="#f97316" stroke-width="2" stroke-linecap="round"/>
            </svg>
          </div>

          <!-- Shop name -->
          <h1 class="text-5xl font-black tracking-tight leading-none">
            <span class="text-gray-900">{{ firstName }}</span>
            <span class="text-orange-500 italic ml-2">{{ lastName }}</span>
          </h1>
          <!-- Underline -->
          <div class="mt-1 w-full h-0.5 bg-gray-900 rounded-full"></div>
        </div>

        <!-- Tagline with typewriter -->
        <p class="text-lg font-semibold text-gray-700 mt-3">
          <span class="text-orange-500">{{ typedText }}<span class="inline-block w-0.5 h-5 bg-orange-500 align-middle ml-0.5 animate-pulse" v-if="!typingDone"></span></span>
        </p>
      </div>

      <!-- ── Right: Login card ── -->
      <div class="w-full max-w-lg shrink-0">
        <div class="bg-white rounded-3xl shadow-2xl px-16 py-16 relative overflow-hidden">
          <!-- Orange top-right accent blob inside card -->
          <div class="absolute -top-6 -right-6 w-20 h-20 rounded-full pointer-events-none"
               style="background: linear-gradient(135deg,#fed7aa,#f97316); opacity:0.35;"></div>

          <!-- Card heading -->
          <div class="flex items-center justify-center gap-2 mb-1">
            <svg class="w-4 h-4 text-orange-500" viewBox="0 0 16 16" fill="currentColor"><path d="M8 1l1.5 3 3.5.5-2.5 2.5.5 3.5L8 9l-3 1.5.5-3.5L3 4.5 6.5 4z"/></svg>
            <h2 class="text-3xl font-extrabold text-gray-900 tracking-wide">Welcome Back!</h2>
            <svg class="w-4 h-4 text-orange-500 scale-x-[-1]" viewBox="0 0 16 16" fill="currentColor"><path d="M8 1l1.5 3 3.5.5-2.5 2.5.5 3.5L8 9l-3 1.5.5-3.5L3 4.5 6.5 4z"/></svg>
          </div>
          <p class="text-center text-gray-400 text-sm mb-8 leading-relaxed">
            Sign in to manage your restaurant<br/>and keep things running smoothly.
          </p>

          <form @submit.prevent="submit" class="space-y-4">

            <!-- Email input -->
            <div class="flex items-center gap-3 bg-blue-50 rounded-full px-4 py-3 focus-within:ring-2 focus-within:ring-orange-200 transition">
              <div class="w-8 h-8 rounded-full bg-orange-500 flex items-center justify-center shrink-0">
                <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                  <polyline points="22,6 12,13 2,6"/>
                </svg>
              </div>
              <input
                v-model="form.email"
                type="text"
                required
                autocomplete="username"
                placeholder="Email"
                class="flex-1 bg-transparent text-sm text-gray-700 placeholder-gray-400 outline-none border-0"
              />
            </div>

            <!-- Password input -->
            <div class="flex items-center gap-3 bg-blue-50 rounded-full px-4 py-3 focus-within:ring-2 focus-within:ring-orange-200 transition">
              <div class="w-8 h-8 rounded-full bg-orange-500 flex items-center justify-center shrink-0">
                <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                  <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
              </div>
              <input
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                required
                autocomplete="current-password"
                placeholder="Password"
                class="flex-1 bg-transparent text-sm text-gray-700 placeholder-gray-400 outline-none border-0"
              />
              <button type="button" @click="showPassword = !showPassword"
                      class="text-gray-400 hover:text-orange-500 transition shrink-0">
                <svg v-if="!showPassword" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                </svg>
                <svg v-else class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                  <line x1="1" y1="1" x2="23" y2="23"/>
                </svg>
              </button>
            </div>

            <!-- Forgot password -->
            <div class="text-right -mt-1">
              <a href="#" class="text-sm text-orange-500 hover:text-orange-600 font-semibold">Forgot password?</a>
            </div>

            <!-- Error -->
            <p v-if="error" class="text-xs text-red-500 bg-red-50 px-4 py-2 rounded-full text-center">{{ error }}</p>

            <!-- Submit -->
            <button
              type="submit"
              :disabled="loading"
              class="w-full flex items-center justify-center gap-2 py-4 rounded-full font-bold text-base tracking-widest text-white transition active:scale-95 disabled:opacity-60 shadow-lg"
              style="background: linear-gradient(90deg, #f97316, #ea580c);"
            >
              {{ loading ? 'SIGNING IN…' : 'SIGN IN' }}
              <svg v-if="!loading" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
              </svg>
            </button>
          </form>

        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { reactive, ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import axios from 'axios'

const auth         = useAuthStore()
const router       = useRouter()
const form         = reactive({ email: '', password: '' })
const error        = ref('')
const loading      = ref(false)
const logoUrl      = ref(null)

// Typewriter
const typingPhrases = [
  'Fresh Flavors, Every Day! 🍽️',
  'Good Food, Great Vibes! ✨',
  'Taste the Difference! 🌟',
  'Your Kitchen, Our Pride! 👨‍🍳',
  'Served with Love! ❤️',
]
const typedText  = ref('')
const typingDone = ref(false)
let phraseIdx = 0

function runTypewriter() {
  typingDone.value = false
  const phrase = typingPhrases[phraseIdx % typingPhrases.length]
  phraseIdx++
  let i = 0
  typedText.value = ''

  const type = setInterval(() => {
    typedText.value += phrase[i++]
    if (i >= phrase.length) {
      clearInterval(type)
      typingDone.value = true
      setTimeout(erase, 2000)
    }
  }, 70)
}

function erase() {
  const erase = setInterval(() => {
    typedText.value = typedText.value.slice(0, -1)
    if (!typedText.value.length) {
      clearInterval(erase)
      setTimeout(runTypewriter, 400)
    }
  }, 40)
}
const shopName     = ref('Food Hub')
const showPassword = ref(false)

// Split shop name into two words for the styled heading
const firstName = computed(() => shopName.value.split(' ')[0] ?? shopName.value)
const lastName  = computed(() => shopName.value.split(' ').slice(1).join(' ') || '')

onMounted(async () => {
  runTypewriter()
  try {
    const { data } = await axios.get('/api/public/settings')
    shopName.value = data.name ?? 'Food Hub'
    logoUrl.value  = data.logo_url ?? null

    if (data.logo_url) {
      const link = document.querySelector("link[rel='icon']") ?? document.createElement('link')
      link.rel  = 'icon'
      link.href = data.logo_url
      document.head.appendChild(link)
    }
  } catch {}
})

async function submit() {
  error.value = ''
  loading.value = true
  try {
    await auth.login(form.email, form.password)
    router.push('/')
  } catch (e) {
    error.value = e.response?.data?.message ?? 'Login failed'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
@keyframes loginFloat {
  0%, 100% { transform: translateY(0px) rotate(0deg); }
  33%       { transform: translateY(-18px) rotate(4deg); }
  66%       { transform: translateY(-8px) rotate(-3deg); }
}
@keyframes loginSpin {
  0%   { transform: rotate(0deg) scale(1); }
  50%  { transform: rotate(180deg) scale(1.3); }
  100% { transform: rotate(360deg) scale(1); }
}
.login-float { animation: loginFloat 6s ease-in-out infinite; }
.login-spin  { animation: loginSpin 8s linear infinite; }
</style>
