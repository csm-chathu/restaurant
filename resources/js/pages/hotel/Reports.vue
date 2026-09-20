<template>
  <div class="space-y-5">

    <!-- Header + filters -->
    <div class="flex flex-col sm:flex-row sm:items-center gap-3 justify-between">
      <h2 class="text-xl font-bold text-gray-900">Hotel Reports</h2>
      <div class="flex flex-wrap items-center gap-2">
        <input v-model="filters.from" type="date" class="form-input w-40" @change="fetchReport" />
        <span class="text-gray-400 text-sm">to</span>
        <input v-model="filters.to" type="date" class="form-input w-40" @change="fetchReport" />
        <button @click="fetchReport" class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-lg font-semibold text-sm">
          Apply
        </button>
      </div>
    </div>

    <div v-if="loading" class="flex justify-center py-16">
      <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-amber-500"></div>
    </div>

    <template v-else-if="report">

      <!-- KPI cards -->
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
        <div class="card text-center space-y-1">
          <div class="text-2xl font-bold text-gray-900">{{ report.summary.total_bookings }}</div>
          <div class="text-xs text-gray-500">Total Bookings</div>
        </div>
        <div class="card text-center space-y-1">
          <div class="text-2xl font-bold text-green-600">{{ report.summary.checked_out }}</div>
          <div class="text-xs text-gray-500">Checked Out</div>
        </div>
        <div class="card text-center space-y-1">
          <div class="text-2xl font-bold text-amber-600">{{ report.summary.active }}</div>
          <div class="text-xs text-gray-500">Active</div>
        </div>
        <div class="card text-center space-y-1">
          <div class="text-2xl font-bold text-red-500">{{ report.summary.cancelled }}</div>
          <div class="text-xs text-gray-500">Cancelled</div>
        </div>
        <div class="card text-center space-y-1">
          <div class="text-2xl font-bold text-gray-900">{{ report.summary.total_nights }}</div>
          <div class="text-xs text-gray-500">Total Nights</div>
        </div>
        <div class="card text-center space-y-1">
          <div class="text-lg font-bold text-teal-700">{{ fmt(report.summary.grand_total) }}</div>
          <div class="text-xs text-gray-500">Total Revenue</div>
        </div>
      </div>

      <!-- Revenue breakdown + charges by type -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

        <!-- Revenue breakdown -->
        <div class="card space-y-3">
          <h3 class="font-semibold text-gray-800">Revenue Breakdown</h3>
          <div class="space-y-2 text-sm">
            <div class="flex justify-between text-gray-600 pb-2 border-b border-gray-100">
              <span>Room Revenue</span>
              <span class="font-semibold">{{ fmt(report.summary.room_revenue) }}</span>
            </div>
            <div class="flex justify-between text-gray-600">
              <span>Extra Charges</span>
              <span class="font-semibold">{{ fmt(report.summary.charges_total) }}</span>
            </div>
            <div class="flex justify-between text-gray-600">
              <span>Service Charge (10%)</span>
              <span class="font-semibold">{{ fmt(report.summary.service_charge) }}</span>
            </div>
            <div class="flex justify-between text-green-600">
              <span>Discount</span>
              <span class="font-semibold">−{{ fmt(report.summary.discount) }}</span>
            </div>
            <div class="flex justify-between font-bold text-gray-900 border-t border-gray-200 pt-2">
              <span>Grand Total</span>
              <span>{{ fmt(report.summary.grand_total) }}</span>
            </div>
            <div class="flex justify-between text-teal-600">
              <span>Amount Collected</span>
              <span class="font-semibold">{{ fmt(report.summary.amount_collected) }}</span>
            </div>
          </div>
        </div>

        <!-- Charges by type -->
        <div class="card space-y-3">
          <h3 class="font-semibold text-gray-800">Charges by Category</h3>
          <div v-if="report.charges_by_type.length" class="space-y-2">
            <div v-for="c in report.charges_by_type" :key="c.charge_type"
              class="flex items-center gap-3">
              <span :class="typeBadge(c.charge_type)" class="badge text-xs w-24 text-center shrink-0 capitalize">
                {{ c.charge_type.replace('_',' ') }}
              </span>
              <div class="flex-1 bg-gray-100 rounded-full h-2 overflow-hidden">
                <div class="h-full bg-teal-500 rounded-full"
                  :style="`width:${Math.min(100, c.total / maxChargeType * 100)}%`"></div>
              </div>
              <div class="text-right shrink-0">
                <div class="text-sm font-semibold text-gray-800">{{ fmt(c.total) }}</div>
                <div class="text-xs text-gray-400">{{ c.count }} items</div>
              </div>
            </div>
          </div>
          <div v-else class="text-sm text-gray-400 text-center py-4">No charges in this period.</div>
        </div>
      </div>

      <!-- Top food items -->
      <div class="card space-y-3">
        <h3 class="font-semibold text-gray-800">Top Charged Items</h3>
        <div v-if="report.top_items.length" class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="text-xs text-gray-400 uppercase tracking-wide border-b border-gray-100">
                <th class="text-left pb-2 font-medium">Item</th>
                <th class="text-right pb-2 font-medium">Orders</th>
                <th class="text-right pb-2 font-medium">Qty</th>
                <th class="text-right pb-2 font-medium">Revenue</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
              <tr v-for="(item, i) in report.top_items" :key="i" class="hover:bg-gray-50">
                <td class="py-2.5">
                  <div class="font-medium text-gray-800">{{ item.description }}</div>
                </td>
                <td class="py-2.5 text-right text-gray-600">{{ item.orders }}</td>
                <td class="py-2.5 text-right text-gray-600">{{ item.total_qty }}</td>
                <td class="py-2.5 text-right font-semibold text-gray-900">{{ fmt(item.total_amount) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-else class="text-sm text-gray-400 text-center py-4">No items in this period.</div>
      </div>

      <!-- Bookings table -->
      <div class="card space-y-3">
        <h3 class="font-semibold text-gray-800">Bookings ({{ report.bookings.length }})</h3>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="text-xs text-gray-400 uppercase tracking-wide border-b border-gray-100">
                <th class="text-left pb-2 font-medium">Booking</th>
                <th class="text-left pb-2 font-medium">Guest</th>
                <th class="text-left pb-2 font-medium">Room</th>
                <th class="text-left pb-2 font-medium">Check-in</th>
                <th class="text-left pb-2 font-medium">Check-out</th>
                <th class="text-right pb-2 font-medium">Nights</th>
                <th class="text-right pb-2 font-medium">Room</th>
                <th class="text-right pb-2 font-medium">Extras</th>
                <th class="text-right pb-2 font-medium">Total</th>
                <th class="text-center pb-2 font-medium">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
              <tr v-for="b in report.bookings" :key="b.id" class="hover:bg-gray-50">
                <td class="py-2.5">
                  <router-link :to="`/hotel/bookings/${b.id}`" class="font-mono text-xs text-amber-700 hover:underline">
                    {{ b.booking_number }}
                  </router-link>
                </td>
                <td class="py-2.5 text-gray-800">{{ b.guest_name }}</td>
                <td class="py-2.5 text-gray-600">
                  {{ b.room?.room_number }}<span v-if="b.room?.name" class="text-gray-400"> – {{ b.room.name }}</span>
                </td>
                <td class="py-2.5 text-gray-600">{{ b.check_in_date }}</td>
                <td class="py-2.5 text-gray-600">{{ b.check_out_date }}</td>
                <td class="py-2.5 text-right text-gray-600">{{ b.nights }}</td>
                <td class="py-2.5 text-right text-gray-700">{{ fmtN(b.room_total) }}</td>
                <td class="py-2.5 text-right text-gray-700">{{ fmtN(b.charges_total) }}</td>
                <td class="py-2.5 text-right font-semibold text-gray-900">{{ fmtN(b.total) }}</td>
                <td class="py-2.5 text-center">
                  <span :class="statusBadge(b.status)" class="badge text-xs">{{ statusLabel(b.status) }}</span>
                </td>
              </tr>
            </tbody>
            <tfoot v-if="report.bookings.length" class="border-t-2 border-gray-200">
              <tr class="font-bold text-gray-900">
                <td colspan="6" class="py-2.5 text-right text-sm text-gray-500">Checked-out totals:</td>
                <td class="py-2.5 text-right">{{ fmtN(report.summary.room_revenue) }}</td>
                <td class="py-2.5 text-right">{{ fmtN(report.summary.charges_total) }}</td>
                <td class="py-2.5 text-right">{{ fmtN(report.summary.grand_total) }}</td>
                <td></td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>

    </template>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import axios from 'axios'

const loading = ref(false)
const report  = ref(null)

const today = new Date().toISOString().slice(0, 10)
const monthStart = new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().slice(0, 10)

const filters = reactive({ from: monthStart, to: today })

const maxChargeType = computed(() =>
  report.value ? Math.max(...report.value.charges_by_type.map(c => c.total), 1) : 1
)

async function fetchReport() {
  loading.value = true
  try {
    const { data } = await axios.get('/api/hotel/reports', { params: filters })
    report.value = data
  } finally {
    loading.value = false
  }
}

function fmt(v)  { return 'LKR ' + Number(v || 0).toLocaleString('en-LK', { minimumFractionDigits: 2 }) }
function fmtN(v) { return Number(v || 0).toLocaleString('en-LK', { minimumFractionDigits: 2 }) }

function typeBadge(t) {
  return {
    food:         'bg-orange-100 text-orange-700',
    beverage:     'bg-blue-100 text-blue-700',
    laundry:      'bg-purple-100 text-purple-700',
    room_service: 'bg-teal-100 text-teal-700',
    other:        'bg-gray-100 text-gray-600',
  }[t] ?? 'bg-gray-100 text-gray-600'
}

function statusBadge(s) {
  return {
    reserved:    'bg-blue-100 text-blue-700',
    checked_in:  'bg-amber-100 text-amber-700',
    checked_out: 'bg-green-100 text-green-700',
    cancelled:   'bg-gray-100 text-gray-500',
  }[s] ?? 'bg-gray-100 text-gray-600'
}
function statusLabel(s) {
  return { reserved:'Reserved', checked_in:'Checked In', checked_out:'Checked Out', cancelled:'Cancelled' }[s] ?? s
}

onMounted(fetchReport)
</script>
