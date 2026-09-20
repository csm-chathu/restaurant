<template>
  <div>
    <!-- Screen toolbar — hidden on print -->
    <div class="no-print flex items-center gap-3 mb-6">
      <router-link :to="`/hotel/bookings/${route.params.id}`"
        class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-800">
        <ArrowLeftIcon class="w-4 h-4" /> Back to Booking
      </router-link>
      <button @click="doPrint"
        class="inline-flex items-center gap-2 px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-lg font-medium text-sm shadow-sm ml-auto">
        <PrinterIcon class="w-4 h-4" /> Print Invoice
      </button>
    </div>

    <div v-if="loading" class="flex justify-center py-20 no-print">
      <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-amber-500"></div>
    </div>

    <!-- THERMAL RECEIPT — 80mm -->
    <div v-else-if="booking" class="receipt-wrap">
      <div class="receipt">

        <!-- Header -->
        <div class="center bold" style="font-size:15px; letter-spacing:1px;">{{ restaurantName }}</div>
        <div v-if="restaurantAddress" class="center small mt2">{{ restaurantAddress }}</div>
        <div class="center small mt2">HOTEL INVOICE</div>
        <div class="dashes mt4"></div>

        <!-- Booking info -->
        <div class="row mt2"><span class="label">Booking#</span><span class="value bold">{{ booking.booking_number }}</span></div>
        <div class="row"><span class="label">Printed</span><span class="value">{{ fmtNow() }}</span></div>
        <div class="dashes mt4"></div>

        <!-- Guest -->
        <div class="section-title mt2">GUEST</div>
        <div class="row mt1"><span class="label">Name</span><span class="value bold">{{ booking.guest_name }}</span></div>
        <div v-if="booking.guest_phone" class="row"><span class="label">Phone</span><span class="value">{{ booking.guest_phone }}</span></div>
        <div v-if="booking.guest_nic" class="row"><span class="label">NIC</span><span class="value">{{ booking.guest_nic }}</span></div>
        <div class="row"><span class="label">Persons</span><span class="value">{{ booking.guest_count }}</span></div>
        <div class="dashes mt4"></div>

        <!-- Stay -->
        <div class="section-title mt2">STAY</div>
        <div class="row mt1"><span class="label">Room</span><span class="value bold">{{ booking.room?.room_number }}{{ booking.room?.name ? ' — ' + booking.room.name : '' }}</span></div>
        <div class="row"><span class="label">Type</span><span class="value">{{ booking.room?.type?.replace('_','-').toUpperCase() }} / {{ cap(booking.room?.category) }}</span></div>
        <div class="row"><span class="label">Check-in</span><span class="value">{{ booking.check_in_date }}{{ booking.check_in_time ? ' ' + booking.check_in_time : '' }}</span></div>
        <div class="row"><span class="label">Check-out</span><span class="value">{{ booking.check_out_date }}{{ booking.check_out_time ? ' ' + booking.check_out_time : '' }}</span></div>
        <div class="row"><span class="label">Nights</span><span class="value">{{ booking.nights }}</span></div>
        <div class="dashes mt4"></div>

        <!-- Charges -->
        <div class="section-title mt2">CHARGES</div>
        <div class="mt1"></div>

        <!-- Room charge -->
        <div class="item-name mt1">Room Rental</div>
        <div class="item-row">
          <span class="small">{{ booking.nights }}n × LKR {{ n(booking.rate_per_night) }}</span>
          <span class="bold">{{ lkr(booking.room_total) }}</span>
        </div>

        <!-- Extra charges -->
        <template v-if="booking.charges?.length">
          <div class="dashes-light mt3"></div>
          <div v-for="c in booking.charges" :key="c.id" class="mt2">
            <div class="item-name">{{ c.description }}</div>
            <div class="item-row">
              <span class="small">{{ c.quantity }} × LKR {{ n(c.unit_price) }} <span class="muted">{{ fmtTime(c.created_at) }}</span></span>
              <span class="bold">{{ lkr(c.amount) }}</span>
            </div>
          </div>
        </template>

        <div class="dashes mt4"></div>

        <!-- Totals -->
        <div class="row mt2"><span class="label">Room charges</span><span class="value">{{ lkr(booking.room_total) }}</span></div>
        <div class="row"><span class="label">Extra charges</span><span class="value">{{ lkr(booking.charges_total) }}</span></div>
        <div v-if="booking.service_charge > 0" class="row">
          <span class="label">Service chg ({{ booking.service_charge_pct }}%)</span>
          <span class="value">{{ lkr(booking.service_charge) }}</span>
        </div>
        <div v-if="booking.discount > 0" class="row">
          <span class="label">Discount</span>
          <span class="value">-{{ lkr(booking.discount) }}</span>
        </div>

        <div class="dashes-heavy mt3"></div>
        <div class="total-row">
          <span>TOTAL</span>
          <span>{{ lkr(booking.total) }}</span>
        </div>
        <div class="dashes-heavy"></div>

        <div class="row mt2"><span class="label">Deposit paid</span><span class="value">{{ lkr(booking.deposit) }}</span></div>
        <div class="row"><span class="label">Paid at checkout</span><span class="value">{{ lkr(Math.max(0, booking.amount_paid - booking.deposit)) }}</span></div>
        <div class="row bold" :class="balanceDue > 0 ? '' : ''">
          <span class="label">Balance due</span>
          <span class="value">{{ lkr(balanceDue) }}</span>
        </div>
        <div class="row"><span class="label">Payment</span><span class="value capitalize">{{ booking.payment_method?.replace('_',' ') }}</span></div>
        <div class="row"><span class="label">Status</span><span class="value bold">{{ statusLabel(booking.status) }}</span></div>

        <!-- Notes -->
        <template v-if="booking.notes">
          <div class="dashes mt4"></div>
          <div class="small mt2 center" style="font-style:italic;">{{ booking.notes }}</div>
        </template>

        <!-- Footer -->
        <div class="dashes mt4"></div>
        <div class="center small mt2">Thank you for choosing</div>
        <div class="center bold mt1">{{ restaurantName }}</div>
        <div class="center small mt2">Please keep this for your records</div>
        <div class="mt6"></div>

      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { ArrowLeftIcon, PrinterIcon } from '@heroicons/vue/24/outline'
import axios from 'axios'

const route   = useRoute()
const booking = ref(null)
const loading = ref(true)
const restaurantName    = ref('Hotel')
const restaurantAddress = ref('')

const balanceDue = computed(() => Math.max(0, (booking.value?.total ?? 0) - (booking.value?.amount_paid ?? 0)))

async function fetchBooking() {
  try {
    const [bookingRes, settingsRes] = await Promise.all([
      axios.get(`/api/hotel/bookings/${route.params.id}`),
      axios.get('/api/settings/restaurant').catch(() => ({ data: {} })),
    ])
    booking.value           = bookingRes.data
    restaurantName.value    = settingsRes.data?.name    || 'Hotel'
    restaurantAddress.value = settingsRes.data?.address || ''
  } finally {
    loading.value = false
  }
}

function doPrint() { window.print() }

function n(v)   { return Number(v || 0).toLocaleString('en-LK', { minimumFractionDigits: 2 }) }
function lkr(v) { return 'LKR ' + n(v) }

function fmtNow() {
  return new Date().toLocaleString('en-GB', { day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit', hour12:true })
}
function fmtTime(ts) {
  if (!ts) return ''
  return new Date(ts).toLocaleString('en-GB', { day:'2-digit', month:'short', hour:'2-digit', minute:'2-digit', hour12:true })
}
function cap(s) { return s ? s.charAt(0).toUpperCase() + s.slice(1) : '' }
function statusLabel(s) {
  return { reserved:'Reserved', checked_in:'Checked In', checked_out:'Checked Out', cancelled:'Cancelled' }[s] ?? s
}

onMounted(fetchBooking)
</script>

<style scoped>
/* ── Screen preview ── */
.receipt-wrap {
  display: flex;
  justify-content: center;
  padding: 20px 0 40px;
}
.receipt {
  width: 302px; /* 80mm at 96dpi ≈ 302px */
  background: #fff;
  padding: 14px 10px;
  font-family: 'Courier New', Courier, monospace;
  font-size: 11px;
  line-height: 1.45;
  color: #000;
  border: 1px dashed #ccc;
  box-shadow: 0 2px 12px rgba(0,0,0,.08);
}

/* ── Utility classes ── */
.center   { text-align: center; }
.bold     { font-weight: 700; }
.small    { font-size: 10px; }
.muted    { color: #666; }
.mt1 { margin-top: 3px; }
.mt2 { margin-top: 6px; }
.mt3 { margin-top: 9px; }
.mt4 { margin-top: 12px; }
.mt6 { margin-top: 20px; }
.capitalize { text-transform: capitalize; }

.dashes        { border-top: 1px dashed #999; }
.dashes-light  { border-top: 1px dashed #ccc; }
.dashes-heavy  { border-top: 2px solid #000; }

.section-title {
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 1.5px;
  color: #333;
}

/* key-value rows */
.row {
  display: flex;
  justify-content: space-between;
  gap: 4px;
  font-size: 11px;
  line-height: 1.5;
}
.label { color: #444; flex-shrink: 0; }
.value { text-align: right; }

/* charge item */
.item-name { font-weight: 700; font-size: 11px; }
.item-row {
  display: flex;
  justify-content: space-between;
  font-size: 10px;
  color: #333;
}

/* grand total row */
.total-row {
  display: flex;
  justify-content: space-between;
  font-size: 14px;
  font-weight: 700;
  padding: 4px 0;
}
</style>

<style>
/* ── Print styles (not scoped) ── */
@media print {
  @page {
    size: 80mm auto;   /* 80mm wide, auto height */
    margin: 4mm 3mm;
  }

  .no-print { display: none !important; }

  body, html {
    margin: 0 !important;
    padding: 0 !important;
    background: #fff !important;
  }

  /* hide app shell */
  nav, aside, header, footer,
  [class*="sidebar"], [class*="topbar"] {
    display: none !important;
  }

  .receipt-wrap {
    padding: 0 !important;
    display: block !important;
  }

  .receipt {
    width: 100% !important;
    border: none !important;
    box-shadow: none !important;
    padding: 0 !important;
    font-size: 11px !important;
  }
}
</style>
