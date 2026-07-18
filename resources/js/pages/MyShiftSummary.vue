<template>
  <div class="max-w-2xl mx-auto space-y-6">

    <!-- Header -->
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-3">
        <button @click="router.back()"
          class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-gray-200 text-xs font-medium text-gray-500 hover:text-gray-700 hover:bg-gray-50 transition-colors no-print">
          <ArrowLeftIcon class="w-3.5 h-3.5" />
          Back
        </button>
        <div>
          <h2 class="text-xl font-bold text-gray-800">My Shift Summary</h2>
          <p class="text-sm text-gray-500 mt-0.5">View and reprint your shift closing slip</p>
        </div>
      </div>
      <button v-if="summary" @click="printSlip"
        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold transition-colors no-print">
        <PrinterIcon class="w-4 h-4" />
        Reprint Slip
      </button>
    </div>

    <!-- Shift selector -->
    <div class="bg-white rounded-xl border border-gray-200 p-4 no-print">
      <div class="flex items-center justify-between mb-3">
        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Select Shift</label>
        <button @click="showDatePicker = !showDatePicker"
          class="inline-flex items-center gap-1.5 text-xs font-medium text-amber-600 hover:text-amber-800 transition-colors">
          <CalendarDaysIcon class="w-3.5 h-3.5" />
          {{ showDatePicker ? 'Hide date filter' : 'Pick date' }}
        </button>
      </div>

      <!-- Date picker (shown on demand) -->
      <div v-if="showDatePicker" class="flex flex-wrap items-end gap-3 mb-4 pb-4 border-b border-dashed border-gray-200">
        <div>
          <label class="block text-[11px] font-medium text-gray-400 mb-1">From</label>
          <input type="date" v-model="dateFrom" class="form-input text-sm py-1.5 px-2" />
        </div>
        <div>
          <label class="block text-[11px] font-medium text-gray-400 mb-1">To</label>
          <input type="date" v-model="dateTo" class="form-input text-sm py-1.5 px-2" />
        </div>
        <button @click="fetchShifts" :disabled="loadingShifts"
          class="px-4 py-1.5 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold transition-colors disabled:opacity-50">
          {{ loadingShifts ? 'Loading…' : 'Search' }}
        </button>
      </div>

      <div v-if="loadingShifts" class="text-sm text-gray-400">Loading shifts…</div>
      <div v-else-if="!shifts.length" class="text-sm text-gray-500">No shifts found for this period.</div>
      <div v-else class="flex flex-wrap gap-2">
        <button
          v-for="s in shifts" :key="s.id"
          @click="selectShift(s.id)"
          :class="[
            'px-3 py-2 rounded-lg border text-xs font-medium transition-colors',
            selectedId === s.id
              ? 'bg-amber-500 border-amber-500 text-white'
              : 'border-gray-200 text-gray-600 hover:border-amber-400 hover:text-amber-700'
          ]">
          <div>{{ formatDate(s.opened_at) }}</div>
          <div class="text-[10px] mt-0.5 opacity-80">{{ formatTime(s.opened_at) }} – {{ s.closed_at ? formatTime(s.closed_at) : 'open' }}</div>
        </button>
      </div>
    </div>

    <!-- Loading summary -->
    <div v-if="loadingSummary" class="text-center py-10 text-gray-400">Loading summary…</div>

    <!-- No selection -->
    <div v-else-if="!summary && !loadingSummary && shifts.length" class="text-center py-10 text-gray-400 text-sm">
      Select a shift above to view its summary.
    </div>

    <!-- Summary card -->
    <div v-else-if="summary" class="bg-white rounded-xl border border-gray-200 divide-y divide-gray-100">
      <!-- Shift info -->
      <div class="px-6 py-4 flex flex-wrap gap-x-8 gap-y-2 text-sm">
        <div><span class="text-gray-500">Cashier</span><span class="font-semibold text-gray-800 ml-2">{{ summary.shift?.user?.name }}</span></div>
        <div><span class="text-gray-500">Opened</span><span class="font-medium text-gray-700 ml-2">{{ formatDateTime(summary.shift?.opened_at) }}</span></div>
        <div><span class="text-gray-500">Closed</span><span class="font-medium text-gray-700 ml-2">{{ formatDateTime(summary.shift?.closed_at) }}</span></div>
      </div>

      <!-- Sales summary -->
      <div class="px-6 py-4">
        <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Sales Summary</div>
        <div class="grid grid-cols-3 gap-4">
          <div class="bg-gray-50 rounded-lg p-3 text-center">
            <div class="text-2xl font-bold text-gray-800">{{ summary.total_sales_count }}</div>
            <div class="text-xs text-gray-500 mt-1">Total Bills</div>
          </div>
          <div class="bg-gray-50 rounded-lg p-3 text-center">
            <div class="text-2xl font-bold text-gray-800">{{ summary.total_items }}</div>
            <div class="text-xs text-gray-500 mt-1">Total Items</div>
          </div>
          <div class="bg-amber-50 rounded-lg p-3 text-center">
            <div class="text-lg font-bold text-amber-700">LKR {{ lkr(summary.total_revenue) }}</div>
            <div class="text-xs text-amber-600 mt-1">Total Revenue</div>
          </div>
        </div>
      </div>

      <!-- Payment breakdown -->
      <div class="px-6 py-4">
        <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Payment Methods</div>
        <div class="space-y-1.5 text-sm">
          <div class="flex justify-between">
            <span class="text-gray-500">Cash Sales</span>
            <span class="font-medium">LKR {{ lkr(summary.cash_sales) }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-500">Card Sales</span>
            <span class="font-medium">LKR {{ lkr(summary.card_sales) }}</span>
          </div>
          <div v-if="summary.other_sales > 0" class="flex justify-between">
            <span class="text-gray-500">Other</span>
            <span class="font-medium">LKR {{ lkr(summary.other_sales) }}</span>
          </div>
        </div>
      </div>

      <!-- Category breakdown -->
      <div v-if="summary.category_breakdown?.length" class="px-6 py-4">
        <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">By Category</div>
        <div class="space-y-1.5 text-sm">
          <div v-for="cat in summary.category_breakdown" :key="cat.name" class="flex justify-between">
            <span class="text-gray-500">{{ cat.name }} <span class="text-gray-400">(×{{ cat.qty }})</span></span>
            <span>LKR {{ lkr(cat.total) }}</span>
          </div>
        </div>
      </div>

      <!-- Cash outs -->
      <div v-if="summary.cash_outs?.length" class="px-6 py-4">
        <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Cash Outs</div>
        <div class="space-y-1.5 text-sm">
          <div v-for="co in summary.cash_outs" :key="co.id" class="flex justify-between">
            <span class="text-gray-500 truncate max-w-[60%]">{{ co.reason }}</span>
            <span class="text-red-600 font-medium">− LKR {{ lkr(co.amount) }}</span>
          </div>
          <div class="flex justify-between font-semibold pt-1 border-t border-dashed">
            <span class="text-gray-600">Total Cash Outs</span>
            <span class="text-red-600">− LKR {{ lkr(summary.total_cash_outs) }}</span>
          </div>
        </div>
      </div>

      <!-- Cash drawer reconciliation -->
      <div class="px-6 py-4">
        <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Cash Drawer</div>
        <div class="space-y-1.5 text-sm">
          <div class="flex justify-between"><span class="text-gray-500">Opening Cash</span><span>LKR {{ lkr(summary.shift?.opening_cash) }}</span></div>
          <div class="flex justify-between"><span class="text-gray-500">+ Cash Sales</span><span>LKR {{ lkr(summary.cash_sales) }}</span></div>
          <div v-if="summary.total_cash_outs > 0" class="flex justify-between"><span class="text-gray-500">− Cash Outs</span><span class="text-red-600">LKR {{ lkr(summary.total_cash_outs) }}</span></div>
          <div class="flex justify-between"><span class="text-gray-500">Expected Cash</span><span>LKR {{ lkr(summary.expected_cash) }}</span></div>
          <div class="flex justify-between"><span class="text-gray-500">Closing Cash</span><span>LKR {{ lkr(summary.shift?.closing_cash) }}</span></div>
          <div class="flex justify-between font-bold text-base pt-1 border-t border-dashed"
               :class="(summary.variance ?? 0) < 0 ? 'text-red-600' : 'text-green-700'">
            <span>Variance</span>
            <span>{{ (summary.variance ?? 0) >= 0 ? '+' : '' }}LKR {{ lkr(summary.variance) }}</span>
          </div>
          <div class="flex justify-between pt-1">
            <span class="text-gray-500">Handover to Boss</span>
            <span class="text-red-600 font-semibold">LKR {{ lkr(summary.handover_amount) }}</span>
          </div>
          <div class="flex justify-between font-semibold"
               :class="summary.leftover_amount > 0 ? 'text-amber-700' : 'text-gray-700'">
            <span>Leftover / Next Opening</span>
            <span>LKR {{ lkr(summary.leftover_amount) }}</span>
          </div>
        </div>
      </div>

      <!-- Notes -->
      <div v-if="summary.shift?.notes" class="px-6 py-3 text-sm text-gray-500 italic">
        Notes: {{ summary.shift.notes }}
      </div>
    </div>

  </div>

  <!-- Thermal slip (teleported to body, same mechanism as ShiftModal) -->
  <Teleport to="body">
    <div id="my-shift-slip-wrapper" style="display:none;">
      <div id="my-shift-slip" class="my-shift-slip-paper">
        <div style="text-align:center; margin-bottom:4px;" v-if="summary">
          <div style="font-size:15px; font-weight:bold; letter-spacing:1px; text-transform:uppercase;">{{ restaurant.name || 'POS System' }}</div>
          <div v-if="restaurant.address" style="font-size:10px; margin-top:2px; white-space:pre-line; font-weight:600;">{{ restaurant.address }}</div>
          <div style="font-size:12px; margin-top:5px; font-weight:bold; letter-spacing:0.5px;">====  SHIFT CLOSED  ====</div>
        </div>
        <hr style="border:none; border-top:1px dashed #666; margin:5px 0;" />

        <div style="font-size:11px; line-height:1.8; font-weight:600;" v-if="summary">
          <div style="display:flex; justify-content:space-between;"><span>Cashier</span><span>{{ summary.shift?.user?.name }}</span></div>
          <div style="display:flex; justify-content:space-between;"><span>Date</span><span>{{ slipDate }}</span></div>
          <div style="display:flex; justify-content:space-between;"><span>Opened</span><span>{{ slipOpenedTime }}</span></div>
          <div style="display:flex; justify-content:space-between;"><span>Closed</span><span>{{ slipClosedTime }}</span></div>
        </div>

        <template v-if="summary">
          <hr style="border:none; border-top:1px dashed #666; margin:5px 0;" />
          <div style="font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:3px;">Sales Summary</div>
          <div style="font-size:11px; line-height:1.8; font-weight:600;">
            <div style="display:flex; justify-content:space-between;"><span>Total Bills</span><span>{{ summary.total_sales_count }}</span></div>
            <div style="display:flex; justify-content:space-between;"><span>Total Items</span><span>{{ summary.total_items }}</span></div>
            <div style="display:flex; justify-content:space-between; font-weight:800;"><span>Total Revenue</span><span>LKR {{ lkr(summary.total_revenue) }}</span></div>
          </div>

          <template v-if="summary.category_breakdown?.length">
            <hr style="border:none; border-top:1px dashed #666; margin:5px 0;" />
            <div style="font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:3px;">By Category</div>
            <div style="font-size:11px; line-height:1.8; font-weight:600;">
              <div v-for="cat in summary.category_breakdown" :key="cat.name" style="display:flex; justify-content:space-between;">
                <span>{{ cat.name }} <span style="font-weight:500;">(×{{ cat.qty }})</span></span>
                <span>{{ lkr(cat.total) }}</span>
              </div>
            </div>
          </template>

          <hr style="border:none; border-top:1px dashed #666; margin:5px 0;" />
          <div style="font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:3px;">Payment Methods</div>
          <div style="font-size:11px; line-height:1.8; font-weight:600;">
            <div style="display:flex; justify-content:space-between;"><span>Cash</span><span>LKR {{ lkr(summary.cash_sales) }}</span></div>
            <div style="display:flex; justify-content:space-between;"><span>Card</span><span>LKR {{ lkr(summary.card_sales) }}</span></div>
            <div v-if="summary.other_sales > 0" style="display:flex; justify-content:space-between;"><span>Other</span><span>LKR {{ lkr(summary.other_sales) }}</span></div>
          </div>

          <template v-if="summary.cash_outs?.length">
            <hr style="border:none; border-top:1px dashed #666; margin:5px 0;" />
            <div style="font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:3px;">Cash Outs</div>
            <div style="font-size:11px; line-height:1.8; font-weight:600;">
              <div v-for="co in summary.cash_outs" :key="co.id" style="display:flex; justify-content:space-between;">
                <span>{{ co.reason }}</span><span>- {{ lkr(co.amount) }}</span>
              </div>
              <div style="display:flex; justify-content:space-between; font-weight:800;">
                <span>Total Cash Outs</span><span>- LKR {{ lkr(summary.total_cash_outs) }}</span>
              </div>
            </div>
          </template>

          <hr style="border:none; border-top:1px dashed #666; margin:5px 0;" />
          <div style="font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:3px;">Cash Drawer</div>
          <div style="font-size:11px; line-height:1.8; font-weight:600;">
            <div style="display:flex; justify-content:space-between;"><span>Opening Cash</span><span>LKR {{ lkr(summary.shift?.opening_cash) }}</span></div>
            <div style="display:flex; justify-content:space-between;"><span>+ Cash Sales</span><span>LKR {{ lkr(summary.cash_sales) }}</span></div>
            <div v-if="summary.total_cash_outs > 0" style="display:flex; justify-content:space-between;"><span>- Cash Outs</span><span>LKR {{ lkr(summary.total_cash_outs) }}</span></div>
            <div style="display:flex; justify-content:space-between;"><span>Expected Cash</span><span>LKR {{ lkr(summary.expected_cash) }}</span></div>
            <div style="display:flex; justify-content:space-between;"><span>Closing Cash</span><span>LKR {{ lkr(summary.shift?.closing_cash) }}</span></div>
            <div style="display:flex; justify-content:space-between; font-weight:800; font-size:12px; margin-top:2px;"
                 :style="(summary.variance ?? 0) < 0 ? 'color:#c00;' : 'color:#080;'">
              <span>Variance</span>
              <span>{{ (summary.variance ?? 0) >= 0 ? '+' : '' }}LKR {{ lkr(summary.variance) }}</span>
            </div>
            <div style="display:flex; justify-content:space-between; margin-top:4px; border-top:1px dashed #999; padding-top:3px;">
              <span>Handover to Boss</span><span style="color:#c00; font-weight:700;">LKR {{ lkr(summary.handover_amount) }}</span>
            </div>
            <div style="display:flex; justify-content:space-between; font-weight:800; font-size:12px; color:#b45309;">
              <span>Leftover / Next Opening</span><span>LKR {{ lkr(summary.leftover_amount) }}</span>
            </div>
          </div>

          <template v-if="summary.shift?.notes">
            <hr style="border:none; border-top:1px dashed #666; margin:5px 0;" />
            <div style="font-size:10px; font-weight:600; color:#555;">Notes: {{ summary.shift.notes }}</div>
          </template>
        </template>

        <hr style="border:none; border-top:1px dashed #666; margin:5px 0;" />
        <div style="text-align:center; font-size:10px; font-weight:600;">*** Thank You ***</div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import axios from 'axios'
import { PrinterIcon, CalendarDaysIcon, ArrowLeftIcon } from '@heroicons/vue/24/outline'
import { useRouter } from 'vue-router'

const router = useRouter()

const shifts         = ref([])
const selectedId     = ref(null)
const summary        = ref(null)
const loadingShifts  = ref(false)
const loadingSummary = ref(false)
const restaurant     = ref({ name: '', address: '' })
const showDatePicker = ref(false)

// Default: yesterday → today (matches backend default)
const toISO = (d) => d.toISOString().slice(0, 10)
const dateFrom = ref(toISO(new Date(Date.now() - 86400000)))
const dateTo   = ref(toISO(new Date()))

onMounted(async () => {
  try {
    const { data } = await axios.get('/api/settings/restaurant').catch(() => ({ data: {} }))
    restaurant.value = data || {}
  } catch {}
  await fetchShifts()
})

async function fetchShifts() {
  loadingShifts.value = true
  summary.value = null
  selectedId.value = null
  try {
    const { data } = await axios.get('/api/cashier-shifts/my-shifts', {
      params: { date_from: dateFrom.value, date_to: dateTo.value },
    })
    shifts.value = data
    // Auto-select the most recent closed shift
    const lastClosed = data.find(s => s.status === 'closed')
    if (lastClosed) selectShift(lastClosed.id)
  } finally {
    loadingShifts.value = false
  }
}

async function selectShift(id) {
  if (selectedId.value === id) return
  selectedId.value = id
  summary.value = null
  loadingSummary.value = true
  try {
    const { data } = await axios.get(`/api/cashier-shifts/my-summary/${id}`)
    summary.value = data
  } catch {
    summary.value = null
  } finally {
    loadingSummary.value = false
  }
}

const slipDate = computed(() => {
  const dt = summary.value?.shift?.closed_at ?? summary.value?.shift?.opened_at
  if (!dt) return ''
  return new Date(dt).toLocaleDateString('en-LK', { day: '2-digit', month: 'short', year: 'numeric' })
})

const slipOpenedTime = computed(() => {
  const dt = summary.value?.shift?.opened_at
  if (!dt) return ''
  return new Date(dt).toLocaleTimeString('en-LK', { hour: '2-digit', minute: '2-digit' })
})

const slipClosedTime = computed(() => {
  const dt = summary.value?.shift?.closed_at
  if (!dt) return ''
  return new Date(dt).toLocaleTimeString('en-LK', { hour: '2-digit', minute: '2-digit' })
})

function lkr(val) {
  return Number(val || 0).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function formatDate(dt) {
  if (!dt) return ''
  return new Date(dt).toLocaleDateString('en-LK', { day: '2-digit', month: 'short', year: 'numeric' })
}

function formatTime(dt) {
  if (!dt) return ''
  return new Date(dt).toLocaleTimeString('en-LK', { hour: '2-digit', minute: '2-digit' })
}

function formatDateTime(dt) {
  if (!dt) return '—'
  const d = new Date(dt)
  return d.toLocaleDateString('en-LK', { day: '2-digit', month: 'short', year: 'numeric' }) + ' ' +
         d.toLocaleTimeString('en-LK', { hour: '2-digit', minute: '2-digit' })
}

async function printSlip() {
  await nextTick()
  const app     = document.getElementById('app')
  const wrapper = document.getElementById('my-shift-slip-wrapper')
  if (!wrapper) return

  if (app) app.style.setProperty('display', 'none', 'important')
  wrapper.style.display = 'block'
  void document.body.offsetHeight

  window.print()
  await new Promise(r => setTimeout(r, 600))

  wrapper.style.display = 'none'
  if (app) app.style.removeProperty('display')
}
</script>

<style>
@media print {
  html, body {
    height: auto !important;
    margin: 0 !important;
    padding: 0 !important;
  }
  #app { display: none !important; }
  .no-print { display: none !important; }
  #my-shift-slip-wrapper {
    display: block !important;
    padding: 0 5mm !important;
    background: #fff !important;
  }
  .my-shift-slip-paper {
    font-family: 'Courier New', Courier, monospace !important;
    font-size: 11pt !important;
    font-weight: 700 !important;
    line-height: 1.7 !important;
    color: #000 !important;
    width: 66mm !important;
    max-width: 66mm !important;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }
  @page { size: 76mm auto; margin: 0; }
}
</style>
