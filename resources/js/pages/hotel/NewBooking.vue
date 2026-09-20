<template>
  <div class="flex flex-col h-full">

    <!-- Page header -->
    <div class="flex items-center gap-3 mb-4">
      <router-link to="/hotel/bookings" class="text-gray-400 hover:text-gray-600">
        <ArrowLeftIcon class="w-5 h-5" />
      </router-link>
      <h2 class="text-xl font-bold text-gray-900">New Booking</h2>
    </div>

    <!-- Two-column layout -->
    <div class="flex gap-5 flex-1 min-h-0">

      <!-- ── LEFT: Booking form ── -->
      <div class="flex-1 min-w-0 overflow-y-auto space-y-5 pb-6">

        <div class="card space-y-5">

          <!-- Room selection -->
          <div>
            <h3 class="font-semibold text-gray-700 mb-3">Room</h3>
            <div v-if="loadingRooms" class="text-sm text-gray-400">Loading rooms…</div>
            <div v-else class="grid grid-cols-2 sm:grid-cols-3 gap-2">
              <button v-for="room in availableRooms" :key="room.id"
                @click="selectRoom(room)"
                :class="form.room_id === room.id ? 'border-amber-500 bg-amber-50 shadow-md' : 'border-gray-200 hover:border-amber-300'"
                class="border-2 rounded-xl p-3 text-left transition-all">
                <div class="font-bold text-gray-900">Room {{ room.room_number }}</div>
                <div class="text-xs text-gray-500 capitalize">{{ room.type.replace('_','-') }} · {{ room.category }}</div>
                <div class="text-sm font-semibold text-amber-700 mt-1">LKR {{ Number(room.rate_per_night).toLocaleString() }}/night</div>
              </button>
              <div v-if="!availableRooms.length" class="col-span-3 text-center text-gray-400 py-4">No available rooms.</div>
            </div>
          </div>

          <!-- Guest details -->
          <div>
            <h3 class="font-semibold text-gray-700 mb-3">Guest Details</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">

              <!-- Phone with lookup — FIRST -->
              <div class="relative">
                <label class="form-label">Phone <span class="text-red-500">*</span></label>
                <input v-model="form.guest_phone" type="text" class="form-input" placeholder="Mobile number"
                  @input="debounceLookup('phone')" @focus="debounceLookup('phone')" autocomplete="off" />
                <GuestHistory v-if="guestHits.length && lookupField === 'phone'"
                  :hits="guestHits" @select="fillGuest" @close="guestHits = []" />
              </div>

              <div>
                <label class="form-label">Guest Name <span class="text-red-500">*</span></label>
                <input v-model="form.guest_name" type="text" class="form-input" placeholder="Full name" />
              </div>

              <!-- NIC with lookup -->
              <div class="relative">
                <label class="form-label">NIC / Passport</label>
                <input v-model="form.guest_nic" type="text" class="form-input" placeholder="ID number"
                  @input="debounceLookup('nic')" @focus="debounceLookup('nic')" autocomplete="off" />
                <GuestHistory v-if="guestHits.length && lookupField === 'nic'"
                  :hits="guestHits" @select="fillGuest" @close="guestHits = []" />
              </div>

              <div>
                <label class="form-label">No. of Guests</label>
                <input v-model.number="form.guest_count" type="number" min="1" class="form-input" />
              </div>
            </div>
          </div>

          <!-- Stay dates -->
          <div>
            <h3 class="font-semibold text-gray-700 mb-3">Stay</h3>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
              <div>
                <label class="form-label">Check-in Date *</label>
                <input v-model="form.check_in_date" type="date" class="form-input" />
              </div>
              <div>
                <label class="form-label">Check-in Time</label>
                <input v-model="form.check_in_time" type="time" class="form-input" />
              </div>
              <div>
                <label class="form-label">Check-out Date *</label>
                <input v-model="form.check_out_date" type="date" class="form-input" />
              </div>
              <div>
                <label class="form-label">Nights</label>
                <input :value="nights" type="number" class="form-input bg-gray-50" readonly />
              </div>
            </div>
          </div>

          <!-- Billing -->
          <div>
            <h3 class="font-semibold text-gray-700 mb-3">Billing</h3>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
              <div>
                <label class="form-label">Rate/Night (LKR) *</label>
                <input v-model.number="form.rate_per_night" type="number" min="0" class="form-input" />
              </div>
              <div>
                <label class="form-label">Deposit (LKR)</label>
                <input v-model.number="form.deposit" type="number" min="0" class="form-input" />
              </div>
              <div>
                <label class="form-label">Payment Method</label>
                <select v-model="form.payment_method" class="form-input">
                  <option value="cash">Cash</option>
                  <option value="card">Card</option>
                  <option value="bank_transfer">Bank Transfer</option>
                  <option value="other">Other</option>
                </select>
              </div>
            </div>

            <!-- Summary -->
            <div v-if="nights > 0 && form.rate_per_night" class="mt-3 bg-amber-50 border border-amber-200 rounded-xl p-4 space-y-1">
              <div class="flex justify-between text-sm text-gray-600">
                <span>Room ({{ nights }} night{{ nights > 1 ? 's' : '' }} × LKR {{ Number(form.rate_per_night).toLocaleString() }})</span>
                <span>LKR {{ Number(form.rate_per_night * nights).toLocaleString() }}</span>
              </div>
              <div class="flex justify-between text-sm text-gray-600">
                <span>Deposit collected</span>
                <span>LKR {{ Number(form.deposit || 0).toLocaleString() }}</span>
              </div>
              <div class="border-t border-amber-300 pt-1 flex justify-between font-bold text-gray-900">
                <span>Balance due at checkout</span>
                <span>LKR {{ Number(Math.max(0, form.rate_per_night * nights - (form.deposit || 0))).toLocaleString() }}</span>
              </div>
            </div>
          </div>

          <!-- Notes -->
          <div>
            <label class="form-label">Notes</label>
            <textarea v-model="form.notes" class="form-input" rows="2" placeholder="Any special requirements…"></textarea>
          </div>

          <!-- Error -->
          <p v-if="error" class="text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg px-3 py-2">{{ error }}</p>

          <!-- Submit -->
          <div class="flex gap-3">
            <button @click="submit" :disabled="saving || !canSubmit"
              class="flex-1 px-6 py-2.5 bg-amber-500 hover:bg-amber-600 disabled:opacity-50 text-white font-semibold rounded-lg transition-colors">
              {{ saving ? 'Creating…' : 'Create Booking' }}
            </button>
            <router-link to="/hotel/bookings"
              class="px-6 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-lg">
              Cancel
            </router-link>
          </div>
        </div>
      </div>

      <!-- ── RIGHT: Guest history panel ── -->
      <div class="w-96 shrink-0 flex flex-col gap-4 overflow-y-auto pb-6">

        <!-- Header -->
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden flex flex-col flex-1">
          <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between bg-gray-50 rounded-t-2xl">
            <div>
              <div class="font-semibold text-gray-800 text-sm">Guest History</div>
              <div v-if="guestHistory.length" class="text-xs text-gray-500 mt-0.5">
                {{ form.guest_name }} · {{ guestHistory.length }} booking{{ guestHistory.length !== 1 ? 's' : '' }}
              </div>
            </div>
            <button v-if="guestHistory.length" @click="guestHistory = []"
              class="text-gray-400 hover:text-gray-600 text-lg leading-none">&times;</button>
          </div>

          <!-- Empty state -->
          <div v-if="!guestHistory.length" class="flex-1 flex flex-col items-center justify-center text-center px-6 py-10 text-gray-400">
            <UserIcon class="w-10 h-10 mb-3 opacity-30" />
            <p class="text-sm font-medium text-gray-500">No guest selected</p>
            <p class="text-xs mt-1">Type a phone or NIC number and select a guest to view their stay history here.</p>
          </div>

          <!-- History list -->
          <div v-else class="divide-y divide-gray-100 overflow-y-auto">
            <div v-for="b in guestHistory" :key="b.id"
              class="px-4 py-3 hover:bg-gray-50 transition-colors">
              <div class="flex items-start justify-between gap-2 mb-1.5">
                <div class="flex items-center gap-2 min-w-0">
                  <span :class="dotColor(b.status)" class="w-2 h-2 rounded-full shrink-0 mt-0.5"></span>
                  <span class="font-semibold text-gray-900 text-sm truncate">
                    Room {{ b.room?.room_number }}<span v-if="b.room?.name" class="font-normal text-gray-400"> — {{ b.room.name }}</span>
                  </span>
                </div>
                <span :class="statusBadge(b.status)" class="badge text-xs shrink-0">{{ statusLabel(b.status) }}</span>
              </div>
              <div class="text-xs text-gray-500 ml-4 space-y-0.5">
                <div>{{ b.check_in_date }} → {{ b.check_out_date }} · {{ b.nights }} night{{ b.nights !== 1 ? 's' : '' }}</div>
                <div class="flex items-center justify-between">
                  <span class="font-semibold text-gray-700">LKR {{ Number(b.total).toLocaleString() }}</span>
                  <div class="flex items-center gap-2">
                    <span class="capitalize text-gray-400">{{ b.payment_status }}</span>
                    <button @click="viewBooking(b.id)"
                      class="text-xs px-2 py-0.5 bg-gray-100 hover:bg-amber-100 text-gray-600 hover:text-amber-700 rounded transition-colors">
                      View
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>

  </div>

  <!-- Booking detail modal -->
  <Teleport to="body">
    <div v-if="detailModal.open" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="detailModal.open = false">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">

        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 sticky top-0 bg-white rounded-t-2xl">
          <div>
            <div class="font-bold text-gray-900">{{ detailModal.booking?.booking_number }}</div>
            <div class="text-xs text-gray-500">Room {{ detailModal.booking?.room?.room_number }} — {{ detailModal.booking?.guest_name }}</div>
          </div>
          <div class="flex items-center gap-2">
            <span v-if="detailModal.booking" :class="statusBadge(detailModal.booking.status)" class="badge text-xs font-semibold px-2.5 py-1">
              {{ statusLabel(detailModal.booking.status) }}
            </span>
            <button @click="detailModal.open = false" class="ml-2 text-gray-400 hover:text-gray-700 text-xl leading-none">&times;</button>
          </div>
        </div>

        <div v-if="detailModal.loading" class="flex justify-center py-10">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-amber-500"></div>
        </div>

        <div v-else-if="detailModal.booking" class="px-6 py-4 space-y-5">
          <div class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
            <div>
              <div class="text-xs text-gray-400 uppercase tracking-wide mb-0.5">Room</div>
              <div class="font-semibold">{{ detailModal.booking.room?.room_number }} <span class="text-gray-400 font-normal capitalize">{{ detailModal.booking.room?.type?.replace('_','-') }} · {{ detailModal.booking.room?.category }}</span></div>
            </div>
            <div>
              <div class="text-xs text-gray-400 uppercase tracking-wide mb-0.5">Guests</div>
              <div class="font-semibold">{{ detailModal.booking.guest_count }} person{{ detailModal.booking.guest_count > 1 ? 's' : '' }}</div>
            </div>
            <div>
              <div class="text-xs text-gray-400 uppercase tracking-wide mb-0.5">Check-in</div>
              <div class="font-semibold">{{ detailModal.booking.check_in_date }} <span v-if="detailModal.booking.check_in_time" class="text-gray-400 font-normal">{{ detailModal.booking.check_in_time }}</span></div>
            </div>
            <div>
              <div class="text-xs text-gray-400 uppercase tracking-wide mb-0.5">Check-out</div>
              <div class="font-semibold">{{ detailModal.booking.check_out_date }} <span v-if="detailModal.booking.check_out_time" class="text-gray-400 font-normal">{{ detailModal.booking.check_out_time }}</span></div>
            </div>
            <div>
              <div class="text-xs text-gray-400 uppercase tracking-wide mb-0.5">Nights</div>
              <div class="font-semibold">{{ detailModal.booking.nights }}</div>
            </div>
            <div>
              <div class="text-xs text-gray-400 uppercase tracking-wide mb-0.5">Rate / Night</div>
              <div class="font-semibold">LKR {{ Number(detailModal.booking.rate_per_night).toLocaleString() }}</div>
            </div>
          </div>

          <div v-if="detailModal.booking.charges?.length">
            <div class="text-xs text-gray-400 uppercase tracking-wide mb-2">Food &amp; Extra Charges</div>
            <div class="divide-y divide-gray-100 border border-gray-100 rounded-xl overflow-hidden">
              <div v-for="c in detailModal.booking.charges" :key="c.id" class="flex items-center justify-between px-3 py-2 text-sm bg-white">
                <div>
                  <div class="font-medium text-gray-800">{{ c.description }}</div>
                  <div class="text-xs text-gray-400 capitalize">{{ c.charge_type.replace('_',' ') }} · {{ c.quantity }} × LKR {{ Number(c.unit_price).toLocaleString() }} <span v-if="c.created_at">· {{ fmtTime(c.created_at) }}</span></div>
                </div>
                <span class="font-semibold text-gray-900">LKR {{ Number(c.amount).toLocaleString() }}</span>
              </div>
            </div>
          </div>

          <div class="bg-gray-50 rounded-xl p-4 space-y-1.5 text-sm">
            <div class="flex justify-between text-gray-600">
              <span>Room ({{ detailModal.booking.nights }}n)</span>
              <span>LKR {{ Number(detailModal.booking.room_total).toLocaleString() }}</span>
            </div>
            <div class="flex justify-between text-gray-600">
              <span>Extra charges</span>
              <span>LKR {{ Number(detailModal.booking.charges_total).toLocaleString() }}</span>
            </div>
            <div v-if="detailModal.booking.service_charge > 0" class="flex justify-between text-gray-600">
              <span>Service charge ({{ detailModal.booking.service_charge_pct }}%)</span>
              <span>LKR {{ Number(detailModal.booking.service_charge).toLocaleString() }}</span>
            </div>
            <div v-if="detailModal.booking.discount > 0" class="flex justify-between text-green-600">
              <span>Discount</span>
              <span>−LKR {{ Number(detailModal.booking.discount).toLocaleString() }}</span>
            </div>
            <div class="flex justify-between font-bold text-gray-900 border-t border-gray-200 pt-2 mt-1">
              <span>Total</span>
              <span>LKR {{ Number(detailModal.booking.total).toLocaleString() }}</span>
            </div>
            <div class="flex justify-between text-gray-500 text-xs">
              <span>Deposit</span>
              <span>LKR {{ Number(detailModal.booking.deposit).toLocaleString() }}</span>
            </div>
            <div class="flex justify-between text-xs" :class="detailModal.booking.payment_status === 'paid' ? 'text-green-600 font-semibold' : 'text-amber-600 font-semibold'">
              <span>{{ detailModal.booking.payment_status === 'paid' ? 'Fully paid' : 'Balance due' }}</span>
              <span>LKR {{ Number(Math.max(0, detailModal.booking.total - detailModal.booking.amount_paid)).toLocaleString() }}</span>
            </div>
          </div>

          <div v-if="detailModal.booking.notes" class="text-sm text-gray-600 bg-amber-50 border border-amber-100 rounded-lg p-3">
            {{ detailModal.booking.notes }}
          </div>
        </div>

        <div class="px-6 py-4 border-t border-gray-100 flex justify-end gap-2">
          <router-link :to="`/hotel/bookings/${detailModal.booking?.id}/receipt`" target="_blank"
            class="text-sm px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium">
            Print Invoice
          </router-link>
          <button @click="detailModal.open = false" class="text-sm px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-lg font-semibold">
            Close
          </button>
        </div>
      </div>
    </div>
  </Teleport>

</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ArrowLeftIcon, UserIcon } from '@heroicons/vue/24/outline'
import axios from 'axios'
import GuestHistory from './GuestHistory.vue'

const route = useRoute()
const router = useRouter()

const availableRooms = ref([])
const loadingRooms = ref(false)
const saving = ref(false)
const error = ref('')
const guestHits = ref([])
const guestHistory = ref([])
const lookupField = ref(null)
let lookupTimer = null
const detailModal = reactive({ open: false, loading: false, booking: null })

const form = reactive({
  room_id: null,
  guest_name: '',
  guest_phone: '',
  guest_nic: '',
  guest_count: 1,
  check_in_date: new Date().toISOString().slice(0, 10),
  check_in_time: new Date().toTimeString().slice(0, 5),
  check_out_date: '',
  rate_per_night: 0,
  deposit: 0,
  payment_method: 'cash',
  notes: '',
})

const nights = computed(() => {
  if (!form.check_in_date || !form.check_out_date) return 0
  return Math.max(0, (new Date(form.check_out_date) - new Date(form.check_in_date)) / 86400000)
})

const canSubmit = computed(() =>
  form.room_id && form.guest_name && form.guest_phone && form.check_in_date && form.check_out_date && nights.value > 0 && form.rate_per_night > 0
)

function debounceLookup(field) {
  lookupField.value = field
  clearTimeout(lookupTimer)
  const q = field === 'phone' ? form.guest_phone : form.guest_nic
  if (!q || q.length < 3) { guestHits.value = []; return }
  lookupTimer = setTimeout(() => lookup(q), 350)
}

async function lookup(q) {
  try {
    const { data } = await axios.get('/api/hotel/bookings/guest-lookup', { params: { q } })
    const seen = new Set()
    guestHits.value = data.filter(b => {
      const key = b.guest_phone || b.guest_nic || b.guest_name
      if (seen.has(key)) return false
      seen.add(key)
      return true
    })
  } catch {}
}

async function fillGuest(booking) {
  form.guest_name  = booking.guest_name
  form.guest_phone = booking.guest_phone ?? ''
  form.guest_nic   = booking.guest_nic ?? ''
  form.guest_count = booking.guest_count ?? 1
  guestHits.value  = []
  const q = form.guest_phone || form.guest_nic
  if (q) {
    try {
      const { data } = await axios.get('/api/hotel/bookings/guest-lookup', { params: { q } })
      guestHistory.value = data
    } catch {}
  }
}

async function viewBooking(id) {
  detailModal.open = true
  detailModal.loading = true
  detailModal.booking = null
  try {
    const { data } = await axios.get(`/api/hotel/bookings/${id}`)
    detailModal.booking = data
  } finally {
    detailModal.loading = false
  }
}

function fmtTime(ts) {
  if (!ts) return ''
  return new Date(ts).toLocaleString('en-GB', { day:'2-digit', month:'short', hour:'2-digit', minute:'2-digit', hour12:true })
}

function dotColor(s) {
  return { reserved:'bg-blue-400', checked_in:'bg-amber-400', checked_out:'bg-green-400', cancelled:'bg-gray-300' }[s] ?? 'bg-gray-300'
}
function statusBadge(s) {
  return { reserved:'bg-blue-100 text-blue-700', checked_in:'bg-amber-100 text-amber-700', checked_out:'bg-green-100 text-green-700', cancelled:'bg-gray-100 text-gray-500' }[s] ?? 'bg-gray-100 text-gray-600'
}
function statusLabel(s) {
  return { reserved:'Reserved', checked_in:'Checked In', checked_out:'Checked Out', cancelled:'Cancelled' }[s] ?? s
}

function selectRoom(room) {
  form.room_id = room.id
  form.rate_per_night = room.rate_per_night
}

async function fetchAvailableRooms() {
  loadingRooms.value = true
  try {
    const { data } = await axios.get('/api/hotel/rooms', { params: { status: 'available' } })
    availableRooms.value = data
    if (route.query.room) {
      const preselected = data.find(r => r.id == route.query.room)
      if (preselected) selectRoom(preselected)
    }
  } finally {
    loadingRooms.value = false
  }
}

async function submit() {
  error.value = ''
  saving.value = true
  try {
    const { data } = await axios.post('/api/hotel/bookings', {
      room_id: form.room_id,
      guest_name: form.guest_name,
      guest_phone: form.guest_phone || null,
      guest_nic: form.guest_nic || null,
      guest_count: form.guest_count,
      check_in_date: form.check_in_date,
      check_in_time: form.check_in_time || null,
      check_out_date: form.check_out_date,
      rate_per_night: form.rate_per_night,
      deposit: form.deposit || 0,
      payment_method: form.payment_method,
      notes: form.notes || null,
    })
    router.push(`/hotel/bookings/${data.id}`)
  } catch (e) {
    error.value = e.response?.data?.message || 'Failed to create booking.'
  } finally {
    saving.value = false
  }
}

onMounted(fetchAvailableRooms)
</script>
