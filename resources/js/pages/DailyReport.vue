<template>
  <div>
    <!-- Toolbar -->
    <div class="no-print flex items-center justify-between mb-6 flex-wrap gap-3">
      <div class="flex items-center gap-3">
        <input type="date" v-model="selectedDate"
          class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400" />
        <button @click="fetchReport" :disabled="loading"
          class="px-4 py-2 bg-amber-500 hover:bg-amber-600 disabled:opacity-60 text-white rounded-lg text-sm font-medium">
          {{ loading ? 'Loading…' : 'Load' }}
        </button>
      </div>
      <button v-if="report" @click="printReport" :disabled="printing"
        class="inline-flex items-center gap-2 px-4 py-2 bg-amber-500 hover:bg-amber-600 disabled:opacity-60 text-white rounded-lg font-medium text-sm shadow-sm">
        <PrinterIcon class="w-4 h-4" />
        {{ printing ? 'Printing…' : 'Print Report' }}
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center py-20 text-gray-400">
      <ArrowPathIcon class="w-5 h-5 animate-spin mr-2" /> Loading…
    </div>

    <!-- No data -->
    <div v-else-if="report && report.totals.bill_count == 0" class="text-center py-20 text-gray-400">
      No completed sales on {{ formatDateLabel(selectedDate) }}.
    </div>

    <!-- Report -->
    <div v-else-if="report" id="daily-report-wrapper">
      <div id="daily-report" class="report-paper">

        <!-- Header -->
        <div style="text-align:center; margin-bottom:6px;">
          <div style="font-size:17px; font-weight:800; letter-spacing:1px; text-transform:uppercase;">
            {{ restaurantName }}
          </div>
          <div style="font-size:13px; font-weight:800; margin-top:2px;">DAILY POS REPORT</div>
          <div style="font-size:12px; font-weight:800; margin-top:1px;">{{ formatDateLabel(report.date) }}</div>
        </div>

        <hr class="rpt-divider-double" />

        <!-- Summary -->
        <div class="rpt-section">
          <div class="rpt-row"><span>Total Bills</span><span>{{ report.totals.bill_count }}</span></div>
          <div v-if="Number(report.totals.subtotal) !== Number(report.totals.total_revenue)" class="rpt-row">
            <span>Subtotal</span><span>{{ lkr(report.totals.subtotal) }}</span>
          </div>
          <div v-if="Number(report.totals.total_discount) > 0" class="rpt-row">
            <span>Discounts</span><span>-{{ lkr(report.totals.total_discount) }}</span>
          </div>
          <div v-if="Number(report.totals.total_tax) > 0" class="rpt-row">
            <span>Tax</span><span>+{{ lkr(report.totals.total_tax) }}</span>
          </div>
        </div>

        <hr class="rpt-divider-double" />

        <div class="rpt-row rpt-total">
          <span>TOTAL REVENUE</span><span>{{ lkr(report.totals.total_revenue) }}</span>
        </div>

        <hr class="rpt-divider" />

        <!-- Payment breakdown -->
        <div style="font-size:11px; font-weight:800; margin-bottom:3px;">PAYMENT BREAKDOWN</div>
        <div class="rpt-section">
          <div v-for="p in report.payments" :key="p.payment_method" class="rpt-row">
            <span style="text-transform:capitalize;">{{ p.payment_method?.replace(/_/g,' ') }}</span>
            <span>{{ lkr(p.amount) }}</span>
          </div>
          <div v-if="report.payments.length === 0" style="font-size:11px; color:#555;">—</div>
        </div>

        <hr class="rpt-divider" />

        <!-- Top items -->
        <div style="font-size:11px; font-weight:800; margin-bottom:3px;">TOP ITEMS</div>
        <div class="rpt-section">
          <div v-for="(item, idx) in report.top_items" :key="idx" class="rpt-row">
            <span style="flex:1; padding-right:4px; word-break:break-word;">{{ idx + 1 }}. {{ item.name }}</span>
            <span style="white-space:nowrap;">×{{ formatQty(item.qty) }}</span>
          </div>
          <div v-if="report.top_items.length === 0" style="font-size:11px; color:#555;">No items</div>
        </div>

        <hr class="rpt-divider" />

        <!-- Cashiers -->
        <div v-if="report.cashiers.length > 1">
          <div style="font-size:11px; font-weight:800; margin-bottom:3px;">BY CASHIER</div>
          <div class="rpt-section">
            <div v-for="c in report.cashiers" :key="c.name" class="rpt-row">
              <span style="flex:1;">{{ c.name }}</span>
              <span style="white-space:nowrap; margin-right:6px;">{{ c.bill_count }} bills</span>
              <span>{{ lkr(c.revenue) }}</span>
            </div>
          </div>
          <hr class="rpt-divider" />
        </div>

        <!-- Footer -->
        <div style="text-align:center; font-size:11px; font-weight:800; line-height:1.6; margin-top:4px;">
          <div>*** End of Day Report ***</div>
          <div>Printed: {{ printedAt }}</div>
          <div style="letter-spacing:0.5px;">www.lumac.lk</div>
        </div>

      </div>
    </div>

    <!-- Empty state before load -->
    <div v-else-if="!loading && !report" class="text-center py-20 text-gray-400">
      Select a date and click Load.
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { PrinterIcon, ArrowPathIcon } from '@heroicons/vue/24/outline'

const selectedDate = ref(new Date().toLocaleDateString('en-CA'))
const loading      = ref(false)
const printing     = ref(false)
const report       = ref(null)
const restaurantName = ref('Restaurant')
const printedAt    = ref('')

function lkr(val) {
  return 'LKR ' + Number(val || 0).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function formatDateLabel(d) {
  return new Date(d + 'T00:00:00').toLocaleDateString('en-LK', { day: '2-digit', month: 'long', year: 'numeric', weekday: 'long' })
}

function formatQty(qty) {
  const n = Number(qty)
  return n % 1 === 0 ? n.toFixed(0) : n.toFixed(2)
}

async function fetchReport() {
  loading.value = true
  report.value = null
  try {
    const { data } = await axios.get('/api/reports/daily-pos', { params: { date: selectedDate.value } })
    report.value = data
    printedAt.value = new Date().toLocaleString('en-LK', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
  } finally {
    loading.value = false
  }
}

async function printReport() {
  printing.value = true
  try {
    if (window.electronAPI?.printReceipt) {
      await window.electronAPI.printReceipt()
    } else {
      window.print()
      await new Promise(r => setTimeout(r, 600))
    }
  } finally {
    printing.value = false
  }
}

onMounted(async () => {
  try {
    const { data } = await axios.get('/api/settings/restaurant').catch(() => ({ data: {} }))
    restaurantName.value = data?.name || 'Restaurant'
  } catch {}
  await fetchReport()
})
</script>

<style>
.report-paper {
  width: 287px;
  padding: 16px 22px 16px 14px;
  margin: 0 auto 32px;
  background: #fff;
  box-shadow: 0 0 0 1px #e5e7eb, 0 4px 24px rgba(0,0,0,0.08);
  border-radius: 4px;
  font-family: 'Courier New', Courier, monospace;
  font-size: 12px;
  line-height: 1.5;
  font-weight: 800;
  color: #111;
}

.rpt-section { margin-bottom: 4px; }

.rpt-row {
  display: flex;
  justify-content: space-between;
  gap: 6px;
  font-size: 11px;
  font-weight: 800;
  margin-bottom: 2px;
}

.rpt-total {
  font-size: 14px !important;
  font-weight: 800;
  margin: 4px 0;
}

.rpt-divider        { border: none; border-top: 1px dashed #666; margin: 5px 0; }
.rpt-divider-double { border: none; border-top: 3px double #333; margin: 5px 0; }

@media print {
  html, body {
    margin: 0 !important;
    padding: 0 !important;
    background: #fff !important;
  }

  #app, #app > div, #app > div > div, #app > div > div > div, #app main {
    display: block !important;
    width: auto !important;
    min-width: 0 !important;
    height: auto !important;
    min-height: 0 !important;
    max-height: none !important;
    overflow: visible !important;
    padding: 0 !important;
    margin: 0 !important;
    background: #fff !important;
    flex: none !important;
  }

  aside, header, .no-print { display: none !important; }

  #daily-report-wrapper {
    position: static !important;
    width: 75mm !important;
    padding: 0 !important;
    margin: 0 !important;
    overflow: visible !important;
  }

  .report-paper {
    width: 75mm !important;
    max-width: 75mm !important;
    margin: 0 !important;
    padding: 4mm 7mm 4mm 3mm !important;
    box-shadow: none !important;
    border-radius: 0 !important;
    font-size: 9pt !important;
    font-weight: 800 !important;
    font-family: 'Courier New', Courier, monospace !important;
    color: #000 !important;
    background: #fff !important;
  }

  .rpt-total { font-size: 11pt !important; }

  #daily-report-wrapper * {
    color: #000 !important;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
    background: transparent !important;
  }

  @page {
    size: 76mm auto;
    margin: 0;
  }
}
</style>
