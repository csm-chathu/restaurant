<template>
  <div class="space-y-5">

    <!-- Header -->
    <div class="flex flex-col gap-3">
      <div class="flex flex-col sm:flex-row sm:items-center gap-3 justify-between">
        <div class="flex flex-wrap items-center gap-2">
          <select v-model="filters.status" class="form-input w-36" @change="fetchRooms">
            <option value="">All Status</option>
            <option value="available">Available</option>
            <option value="occupied">Occupied</option>
            <option value="cleaning">Cleaning</option>
            <option value="maintenance">Maintenance</option>
          </select>
          <select v-model="filters.type" class="form-input w-32" @change="fetchRooms">
            <option value="">All Types</option>
            <option value="ac">AC</option>
            <option value="non_ac">Non-AC</option>
          </select>
          <select v-model="filters.category" class="form-input w-36" @change="fetchRooms">
            <option value="">All Categories</option>
            <option value="single">Single</option>
            <option value="double">Double</option>
            <option value="triple">Triple</option>
            <option value="suite">Suite</option>
            <option value="dormitory">Dormitory</option>
          </select>
        </div>
        <div class="flex items-center gap-3 shrink-0">
          <!-- Legend -->
          <div class="flex items-center gap-3 text-xs text-gray-600 bg-white border border-gray-200 rounded-lg px-3 py-2 shadow-sm">
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm bg-green-400 inline-block"></span>Available</span>
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm bg-amber-400 inline-block"></span>Occupied</span>
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm bg-blue-400 inline-block"></span>Cleaning</span>
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm bg-red-400 inline-block"></span>Maintenance</span>
          </div>
          <button v-if="canManage" @click="openModal()" type="button"
            class="inline-flex items-center gap-2 px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-lg font-semibold text-sm shadow-sm transition-colors">
            <PlusIcon class="w-4 h-4" /> Add Room
          </button>
        </div>
      </div>
    </div>

    <!-- Room Grid -->
    <div v-if="loading" class="flex justify-center py-12">
      <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-amber-500"></div>
    </div>

    <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
      <div v-for="room in rooms" :key="room.id"
        :class="cardBg(room.status)"
        class="rounded-xl shadow-md p-4 border flex flex-col">

        <!-- Room header -->
        <div class="flex items-start justify-between mb-3">
          <div>
            <span class="text-lg font-bold text-gray-900">Room {{ room.room_number }}</span>
            <span v-if="room.name" class="ml-1 text-sm text-gray-500">— {{ room.name }}</span>
          </div>
          <span :class="statusBadge(room.status)" class="badge text-xs font-semibold shrink-0">
            {{ statusLabel(room.status) }}
          </span>
        </div>

        <!-- Details -->
        <div class="text-sm text-gray-600 space-y-1.5 flex-1">
          <div class="flex gap-1.5 flex-wrap">
            <span :class="room.type === 'ac' ? 'bg-blue-100 text-blue-700' : 'bg-orange-100 text-orange-700'"
              class="px-2 py-0.5 rounded-full text-xs font-semibold uppercase tracking-wide">
              {{ room.type.replace('_', '-') }}
            </span>
            <span :class="categoryBadge(room.category)"
              class="px-2 py-0.5 rounded-full text-xs font-semibold capitalize">
              {{ room.category }}
            </span>
            <span v-if="room.floor" class="px-2 py-0.5 rounded-full bg-gray-100 text-gray-500 text-xs font-medium">
              Floor {{ room.floor }}
            </span>
          </div>
          <div class="flex justify-between items-center">
            <span class="text-gray-500">Max {{ room.max_guests }} guests</span>
            <span class="font-semibold text-gray-800">LKR {{ Number(room.rate_per_night).toLocaleString() }}/night</span>
          </div>
          <div v-if="room.amenities" class="text-xs text-gray-400 truncate">{{ room.amenities }}</div>

          <!-- Active booking info -->
          <div v-if="room.active_booking"
            :class="isToday(room.active_booking.check_out_date)
              ? 'bg-red-50 border-red-400 border-2'
              : 'bg-amber-50 border-amber-200'"
            class="text-xs border rounded-lg p-2 space-y-0.5 mt-1">
            <div :class="isToday(room.active_booking.check_out_date) ? 'text-red-800' : 'text-amber-800'" class="font-semibold">
              {{ room.active_booking.guest_name }}
            </div>
            <div :class="isToday(room.active_booking.check_out_date) ? 'text-red-700 font-bold' : 'text-amber-700'"
              class="flex items-center gap-1">
              <span v-if="isToday(room.active_booking.check_out_date)" class="inline-block w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse"></span>
              Check-out: {{ fmtDate(room.active_booking.check_out_date) }}
              <span v-if="isToday(room.active_booking.check_out_date)" class="ml-1 text-red-600 font-bold uppercase tracking-wide">TODAY</span>
            </div>
          </div>
        </div>

        <!-- Actions — always pinned to bottom -->
        <div class="flex gap-2 mt-4">
          <router-link v-if="room.status === 'occupied' && room.active_booking"
            :to="`/hotel/bookings/${room.active_booking.id}`"
            class="flex-1 text-center text-xs px-3 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-lg font-semibold transition-colors">
            View Booking
          </router-link>
          <router-link v-else-if="room.status === 'available'"
            :to="`/hotel/bookings/new?room=${room.id}`"
            class="flex-1 text-center text-xs px-3 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-semibold transition-colors">
            Book Room
          </router-link>
          <button v-else @click="openStatusModal(room)"
            class="flex-1 text-xs px-3 py-2 bg-white/60 hover:bg-white text-gray-700 border border-gray-300 rounded-lg font-medium transition-colors">
            Change Status
          </button>
          <button v-if="canManage" @click="openModal(room)"
            class="w-16 text-xs py-2 bg-white/60 hover:bg-blue-50 text-blue-700 border border-blue-200 rounded-lg font-medium transition-colors">
            Edit
          </button>
        </div>
      </div>

      <div v-if="!rooms.length" class="col-span-full text-center text-gray-400 py-12">No rooms found.</div>
    </div>

    <!-- ── Add / Edit Room Modal ── -->
    <Teleport to="body">
      <div v-if="modal.open" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="closeModal">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg">

          <!-- Modal header -->
          <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
            <h3 class="font-bold text-gray-900 text-lg">{{ modal.editing ? 'Edit Room' : 'Add New Room' }}</h3>
            <button @click="closeModal" class="text-gray-400 hover:text-gray-700 text-2xl leading-none">&times;</button>
          </div>

          <!-- Form body -->
          <div class="px-6 py-5 space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="form-label">Room Number <span class="text-red-500">*</span></label>
                <input v-model="form.room_number" type="text" class="form-input" placeholder="e.g. 101" />
              </div>
              <div>
                <label class="form-label">Name / Label</label>
                <input v-model="form.name" type="text" class="form-input" placeholder="e.g. Sea View" />
              </div>
              <div>
                <label class="form-label">Type <span class="text-red-500">*</span></label>
                <select v-model="form.type" class="form-input">
                  <option value="ac">AC</option>
                  <option value="non_ac">Non-AC</option>
                </select>
              </div>
              <div>
                <label class="form-label">Category <span class="text-red-500">*</span></label>
                <select v-model="form.category" class="form-input">
                  <option value="single">Single</option>
                  <option value="double">Double</option>
                  <option value="triple">Triple</option>
                  <option value="suite">Suite</option>
                  <option value="dormitory">Dormitory</option>
                </select>
              </div>
              <div>
                <label class="form-label">Floor</label>
                <input v-model="form.floor" type="text" class="form-input" placeholder="e.g. Ground, 1st" />
              </div>
              <div>
                <label class="form-label">Max Guests</label>
                <input v-model.number="form.max_guests" type="number" min="1" class="form-input" />
              </div>
              <div>
                <label class="form-label">Rate / Night (LKR) <span class="text-red-500">*</span></label>
                <input v-model.number="form.rate_per_night" type="number" min="0" class="form-input" />
              </div>
              <div>
                <label class="form-label">Status</label>
                <select v-model="form.status" class="form-input">
                  <option value="available">Available</option>
                  <option value="occupied">Occupied</option>
                  <option value="cleaning">Cleaning</option>
                  <option value="maintenance">Maintenance</option>
                </select>
              </div>
              <div class="col-span-2">
                <label class="form-label">Amenities</label>
                <input v-model="form.amenities" type="text" class="form-input" placeholder="e.g. WiFi, TV, Hot Water, A/C" />
              </div>
              <div class="col-span-2">
                <label class="form-label">Notes</label>
                <textarea v-model="form.notes" class="form-input" rows="2" placeholder="Internal notes…"></textarea>
              </div>
            </div>

            <p v-if="formError" class="text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg px-3 py-2">{{ formError }}</p>
          </div>

          <!-- Modal footer -->
          <div class="flex gap-3 px-6 py-4 border-t border-gray-100">
            <button @click="saveRoom" :disabled="saving || !form.room_number || !form.rate_per_night"
              class="flex-1 py-2.5 bg-amber-500 hover:bg-amber-600 disabled:opacity-50 text-white font-semibold rounded-xl transition-colors">
              {{ saving ? 'Saving…' : (modal.editing ? 'Update Room' : 'Add Room') }}
            </button>
            <button @click="closeModal"
              class="px-6 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-xl">
              Cancel
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Status Change Modal -->
    <Teleport to="body">
      <div v-if="statusModal.room" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="statusModal.room = null">
        <div class="bg-white rounded-2xl shadow-2xl w-80 space-y-4 p-6">
          <h3 class="font-semibold text-gray-900">Change Status — Room {{ statusModal.room.room_number }}</h3>
          <select v-model="statusModal.newStatus" class="form-input w-full">
            <option value="available">Available</option>
            <option value="cleaning">Cleaning</option>
            <option value="maintenance">Maintenance</option>
          </select>
          <div class="flex gap-2">
            <button @click="confirmStatusChange" :disabled="statusModal.saving"
              class="flex-1 px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-lg font-semibold text-sm disabled:opacity-50">
              {{ statusModal.saving ? 'Saving…' : 'Save' }}
            </button>
            <button @click="statusModal.room = null" class="px-4 py-2 bg-gray-100 rounded-lg text-sm">Cancel</button>
          </div>
        </div>
      </div>
    </Teleport>

  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { PlusIcon } from '@heroicons/vue/24/outline'
import axios from 'axios'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const canManage = computed(() => ['admin', 'owner', 'manager'].includes(auth.user?.role) || auth.user?.is_super_admin)

const rooms   = ref([])
const loading = ref(false)
const saving  = ref(false)
const formError = ref('')

const filters = reactive({ status: '', type: '', category: '' })

const form = reactive({
  room_number: '', name: '', type: 'non_ac', category: 'single',
  floor: '', max_guests: 2, rate_per_night: '', status: 'available',
  amenities: '', notes: '',
})

const modal = reactive({ open: false, editing: null })
const statusModal = reactive({ room: null, newStatus: 'available', saving: false })

function openModal(room = null) {
  formError.value = ''
  if (room) {
    modal.editing = room
    Object.assign(form, {
      room_number: room.room_number, name: room.name || '', type: room.type,
      category: room.category, floor: room.floor || '', max_guests: room.max_guests,
      rate_per_night: room.rate_per_night, status: room.status,
      amenities: room.amenities || '', notes: room.notes || '',
    })
  } else {
    modal.editing = null
    Object.assign(form, { room_number: '', name: '', type: 'non_ac', category: 'single', floor: '', max_guests: 2, rate_per_night: '', status: 'available', amenities: '', notes: '' })
  }
  modal.open = true
}

function closeModal() {
  modal.open    = false
  modal.editing = null
  formError.value = ''
}

async function fetchRooms() {
  loading.value = true
  try {
    const params = {}
    if (filters.status)   params.status   = filters.status
    if (filters.type)     params.type     = filters.type
    if (filters.category) params.category = filters.category
    const { data } = await axios.get('/api/hotel/rooms', { params })
    rooms.value = data
  } finally {
    loading.value = false
  }
}

async function saveRoom() {
  formError.value = ''
  saving.value = true
  try {
    if (modal.editing) {
      await axios.put(`/api/hotel/rooms/${modal.editing.id}`, form)
    } else {
      await axios.post('/api/hotel/rooms', form)
    }
    closeModal()
    await fetchRooms()
  } catch (e) {
    formError.value = e.response?.data?.message || 'Failed to save room.'
  } finally {
    saving.value = false
  }
}

function openStatusModal(room) {
  statusModal.room      = room
  statusModal.newStatus = room.status === 'occupied' ? 'cleaning' : 'available'
}

async function confirmStatusChange() {
  statusModal.saving = true
  try {
    await axios.put(`/api/hotel/rooms/${statusModal.room.id}`, { status: statusModal.newStatus })
    statusModal.room = null
    await fetchRooms()
  } finally {
    statusModal.saving = false
  }
}

function isToday(d) {
  if (!d) return false
  return d === new Date().toISOString().slice(0, 10)
}

function fmtDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
}

function cardBg(s) {
  return {
    available:   'bg-green-50 border-green-200',
    occupied:    'bg-amber-50 border-amber-300',
    cleaning:    'bg-blue-50 border-blue-200',
    maintenance: 'bg-red-50 border-red-200',
  }[s] ?? 'bg-white border-gray-200'
}

function categoryBadge(c) {
  return {
    single:    'bg-purple-100 text-purple-700',
    double:    'bg-teal-100 text-teal-700',
    triple:    'bg-indigo-100 text-indigo-700',
    suite:     'bg-amber-100 text-amber-700',
    dormitory: 'bg-pink-100 text-pink-700',
  }[c] ?? 'bg-gray-100 text-gray-600'
}

function statusBadge(s) {
  return {
    available:   'bg-green-100 text-green-700',
    occupied:    'bg-amber-100 text-amber-700',
    cleaning:    'bg-blue-100 text-blue-700',
    maintenance: 'bg-red-100 text-red-700',
  }[s] ?? 'bg-gray-100 text-gray-600'
}

function statusLabel(s) {
  return { available: 'Available', occupied: 'Occupied', cleaning: 'Cleaning', maintenance: 'Maintenance' }[s] ?? s
}

onMounted(fetchRooms)
</script>
