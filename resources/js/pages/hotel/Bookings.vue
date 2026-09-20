<template>
  <div class="space-y-5">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center gap-3 justify-between">
      <div class="flex flex-wrap items-center gap-2">
        <input v-model="filters.date" type="date" class="form-input w-44" @change="fetchBookings" />
        <select v-model="filters.status" class="form-input w-36" @change="fetchBookings">
          <option value="">All Status</option>
          <option value="reserved">Reserved</option>
          <option value="checked_in">Checked In</option>
          <option value="checked_out">Checked Out</option>
          <option value="cancelled">Cancelled</option>
        </select>
        <button v-if="filters.date || filters.status" @click="clearFilters" class="text-xs text-gray-400 hover:text-gray-600 underline">Clear</button>
      </div>
      <router-link to="/hotel/bookings/new"
        class="inline-flex items-center gap-2 px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-lg font-semibold text-sm shadow-sm transition-colors shrink-0">
        <PlusIcon class="w-4 h-4" /> New Booking
      </router-link>
    </div>

    <!-- Stats row -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
      <div v-for="stat in stats" :key="stat.label" :class="stat.bg" class="rounded-xl p-4 shadow-sm border">
        <div :class="stat.color" class="text-2xl font-bold">{{ stat.value }}</div>
        <div class="text-xs text-gray-500 mt-0.5">{{ stat.label }}</div>
      </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl border border-gray-300 shadow-md overflow-hidden">
      <div v-if="loading" class="flex justify-center py-10">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-amber-500"></div>
      </div>
      <table v-else class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="table-th">Booking#</th>
            <th class="table-th">Room</th>
            <th class="table-th">Guest</th>
            <th class="table-th">Check In</th>
            <th class="table-th">Check Out</th>
            <th class="table-th text-right">Total</th>
            <th class="table-th text-right">Paid</th>
            <th class="table-th">Status</th>
            <th class="table-th"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="b in bookings" :key="b.id" class="hover:bg-gray-50">
            <td class="table-td font-mono text-xs">{{ b.booking_number }}</td>
            <td class="table-td">
              <span class="font-semibold">{{ b.room?.room_number }}</span>
              <span class="text-xs text-gray-400 ml-1">{{ b.room?.name }}</span>
            </td>
            <td class="table-td">
              <div class="font-medium text-sm">{{ b.guest_name }}</div>
              <div v-if="b.guest_phone" class="text-xs text-gray-400">{{ b.guest_phone }}</div>
            </td>
            <td class="table-td text-sm">{{ b.check_in_date }}</td>
            <td class="table-td text-sm">{{ b.check_out_date }} <span class="text-xs text-gray-400">({{ b.nights }}n)</span></td>
            <td class="table-td text-right font-semibold">{{ fmt(b.total) }}</td>
            <td class="table-td text-right text-sm">{{ fmt(b.amount_paid) }}</td>
            <td class="table-td">
              <span :class="statusBadge(b.status)" class="badge text-xs">{{ statusLabel(b.status) }}</span>
            </td>
            <td class="table-td">
              <router-link :to="`/hotel/bookings/${b.id}`"
                class="text-xs px-3 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 rounded-lg font-medium">
                View
              </router-link>
            </td>
          </tr>
          <tr v-if="!bookings.length">
            <td colspan="9" class="text-center text-gray-400 py-10">No bookings found.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="pagination && pagination.last_page > 1" class="flex justify-center gap-2">
      <button v-for="p in pagination.last_page" :key="p"
        @click="page = p; fetchBookings()"
        :class="p === page ? 'bg-amber-500 text-white' : 'bg-white text-gray-700 border border-gray-300'"
        class="w-8 h-8 rounded-lg text-sm font-medium">{{ p }}</button>
    </div>

  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { PlusIcon } from '@heroicons/vue/24/outline'
import axios from 'axios'

const bookings = ref([])
const pagination = ref(null)
const loading = ref(false)
const page = ref(1)
const filters = reactive({ date: '', status: '' })

async function fetchBookings() {
  loading.value = true
  try {
    const params = { page: page.value }
    if (filters.date) params.date = filters.date
    if (filters.status) params.status = filters.status
    const { data } = await axios.get('/api/hotel/bookings', { params })
    bookings.value = data.data
    pagination.value = data
  } finally {
    loading.value = false
  }
}

function clearFilters() {
  filters.date = ''
  filters.status = ''
  page.value = 1
  fetchBookings()
}

const stats = computed(() => {
  const all = bookings.value
  return [
    { label: 'Reserved', value: all.filter(b => b.status === 'reserved').length, bg: 'bg-blue-50 border-blue-200', color: 'text-blue-700' },
    { label: 'Checked In', value: all.filter(b => b.status === 'checked_in').length, bg: 'bg-amber-50 border-amber-200', color: 'text-amber-700' },
    { label: 'Checked Out', value: all.filter(b => b.status === 'checked_out').length, bg: 'bg-green-50 border-green-200', color: 'text-green-700' },
    { label: 'Cancelled', value: all.filter(b => b.status === 'cancelled').length, bg: 'bg-gray-50 border-gray-200', color: 'text-gray-500' },
  ]
})

function fmt(n) { return 'LKR ' + Number(n || 0).toLocaleString('en-LK', { minimumFractionDigits: 2 }) }

function statusBadge(s) {
  return {
    reserved: 'bg-blue-100 text-blue-700',
    checked_in: 'bg-amber-100 text-amber-700',
    checked_out: 'bg-green-100 text-green-700',
    cancelled: 'bg-gray-100 text-gray-500',
  }[s] ?? 'bg-gray-100 text-gray-600'
}
function statusLabel(s) {
  return { reserved: 'Reserved', checked_in: 'Checked In', checked_out: 'Checked Out', cancelled: 'Cancelled' }[s] ?? s
}

onMounted(fetchBookings)
</script>
