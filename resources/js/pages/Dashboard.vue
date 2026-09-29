<template>
  <div class="space-y-5">

    <!-- ── Cashier quick actions ── -->
    <div v-if="isCashier" class="flex gap-3 justify-end">
      <router-link to="/sales/new"
        class="flex items-center gap-2.5 text-white rounded-xl px-4 py-2.5 shadow-md transition-all hover:scale-105"
        style="background: linear-gradient(135deg,#f59e0b,#ea580c)">
        <ShoppingCartIcon class="w-5 h-5 shrink-0" />
        <div>
          <p class="text-sm font-semibold leading-tight">POS Billing</p>
          <p class="text-xs opacity-80">Start a new bill</p>
        </div>
      </router-link>
      <router-link v-if="showOpenBottles" to="/open-bottles"
        class="flex items-center gap-2.5 text-white rounded-xl px-4 py-2.5 shadow-md transition-all hover:scale-105"
        style="background: linear-gradient(135deg,#6366f1,#8b5cf6)">
        <SparklesIcon class="w-5 h-5 shrink-0" />
        <div>
          <p class="text-sm font-semibold leading-tight">Open Bottles</p>
          <p class="text-xs opacity-80">Track open bottle pours</p>
        </div>
      </router-link>
    </div>

    <!-- ── KPI cards ── -->
    <div v-if="!isCashier" class="flex items-center gap-3">
      <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-3 flex-1">

        <!-- Today Revenue -->
        <div class="rounded-2xl shadow-md px-4 py-3 flex flex-col gap-1 text-white relative overflow-hidden"
             style="background: linear-gradient(135deg,#fbbf24 0%,#f59e0b 40%,#b45309 100%)">
          <div class="absolute right-3 top-3 w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center">
            <!-- Cash / banknote icon -->
            <svg class="w-6 h-6 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
              <rect x="2" y="6" width="20" height="12" rx="2"/>
              <circle cx="12" cy="12" r="3"/>
              <path d="M6 9v.01M18 15v.01"/>
            </svg>
          </div>
          <span class="text-xs font-semibold uppercase tracking-wide opacity-80">Today's Revenue</span>
          <p class="text-xl font-black">{{ kpiCards[0]?.value }}</p>
          <p class="text-xs opacity-70">{{ kpiCards[0]?.sub }}</p>
        </div>

        <!-- Month Revenue -->
        <div class="rounded-2xl shadow-md px-4 py-3 flex flex-col gap-1 text-white relative overflow-hidden"
             style="background: linear-gradient(135deg,#34d399 0%,#10b981 40%,#065f46 100%)">
          <div class="absolute right-3 top-3 w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center">
            <!-- Trending up chart icon -->
            <svg class="w-6 h-6 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
              <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
              <polyline points="17 6 23 6 23 12"/>
            </svg>
          </div>
          <span class="text-xs font-semibold uppercase tracking-wide opacity-80">Month Revenue</span>
          <p class="text-xl font-black">{{ kpiCards[1]?.value }}</p>
        </div>

        <!-- Purchases -->
        <div class="rounded-2xl shadow-md px-4 py-3 flex flex-col gap-1 text-white relative overflow-hidden"
             style="background: linear-gradient(135deg,#60a5fa 0%,#3b82f6 40%,#1e3a8a 100%)">
          <div class="absolute right-3 top-3 w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center">
            <!-- Shopping bag icon -->
            <svg class="w-6 h-6 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
              <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
              <line x1="3" y1="6" x2="21" y2="6"/>
              <path d="M16 10a4 4 0 0 1-8 0"/>
            </svg>
          </div>
          <span class="text-xs font-semibold uppercase tracking-wide opacity-80">Purchases</span>
          <p class="text-xl font-black">{{ kpiCards[2]?.value }}</p>
        </div>

        <!-- Pending Bills -->
        <div class="rounded-2xl shadow-md px-4 py-3 flex flex-col gap-1 text-white relative overflow-hidden"
             style="background: linear-gradient(135deg,#fb923c 0%,#f97316 40%,#9a3412 100%)">
          <div class="absolute right-3 top-3 w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center">
            <!-- Receipt / bill icon -->
            <svg class="w-6 h-6 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
              <path d="M14 2H6a2 2 0 0 0-2 2v16l3-2 2 2 2-2 2 2 3 2V4a2 2 0 0 0-2-2z"/>
              <line x1="9" y1="9" x2="15" y2="9"/>
              <line x1="9" y1="13" x2="15" y2="13"/>
            </svg>
          </div>
          <span class="text-xs font-semibold uppercase tracking-wide opacity-80">Pending Bills</span>
          <p class="text-xl font-black">{{ kpiCards[3]?.value }}</p>
          <p class="text-xs opacity-70">{{ kpiCards[3]?.sub }}</p>
        </div>


        <!-- Customers -->
        <div class="rounded-2xl shadow-md px-4 py-3 flex flex-col gap-1 text-white relative overflow-hidden"
             style="background: linear-gradient(135deg,#a78bfa 0%,#8b5cf6 40%,#4c1d95 100%)">
          <div class="absolute right-3 top-3 w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center">
            <!-- Users icon -->
            <svg class="w-6 h-6 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
              <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
              <circle cx="9" cy="7" r="4"/>
              <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
              <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
          </div>
          <span class="text-xs font-semibold uppercase tracking-wide opacity-80">Customers</span>
          <p class="text-xl font-black">{{ kpiCards[5]?.value }}</p>
        </div>

      </div>

      <!-- New Bill CTA -->
      <router-link to="/sales/new"
        class="flex flex-col items-center justify-center gap-2 text-white rounded-2xl px-7 shadow-lg shrink-0 font-bold text-base whitespace-nowrap self-stretch new-bill-btn"
        style="background: linear-gradient(160deg,#fbbf24 0%,#f97316 50%,#c2410c 100%)">
        <ShoppingCartIcon class="w-8 h-8" />
        New Bill
      </router-link>
    </div>

    <!-- ── Row 2: Revenue trend + Fast moving items ── -->
    <div v-if="!isCashier" class="grid grid-cols-1 lg:grid-cols-3 gap-4">

      <!-- Revenue trend -->
      <div class="lg:col-span-2 bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
        <div class="px-4 pt-4 pb-3 flex items-center justify-between"
             style="background: linear-gradient(90deg,#fffbeb,#fff7ed)">
          <div>
            <h3 class="font-bold text-gray-800 text-sm">Revenue Trend</h3>
            <p class="text-xs text-gray-400">Last 30 days — revenue & bill count</p>
          </div>
          <span class="text-xs font-bold px-3 py-1 rounded-full text-amber-700"
                style="background: linear-gradient(90deg,#fde68a,#fcd34d)">30 days</span>
        </div>
        <div class="p-4 h-56">
          <Bar v-if="revenueTrendData" :data="revenueTrendData" :options="trendOptions" />
          <ChartEmpty v-else :loaded="loaded" />
        </div>
      </div>

      <!-- Fast moving items -->
      <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden flex flex-col">
        <div class="px-4 pt-4 pb-3" style="background: linear-gradient(90deg,#ecfdf5,#f0fdf4)">
          <h3 class="font-bold text-gray-800 text-sm">Fast Moving Items</h3>
          <p class="text-xs text-gray-400">This month · sorted by qty sold</p>
        </div>
        <div class="p-4 flex-1 flex flex-col">
          <div v-if="!loaded" class="flex-1 flex items-center justify-center">
            <div class="w-5 h-5 border-2 border-gray-200 border-t-amber-400 rounded-full animate-spin"></div>
          </div>
          <div v-else-if="!data.top_products?.length" class="flex-1 flex flex-col items-center justify-center text-gray-300 gap-2">
            <span class="text-3xl">📦</span>
            <span class="text-xs">No sales this month</span>
          </div>
          <div v-else class="space-y-3 overflow-y-auto flex-1" style="max-height: 220px">
            <div v-for="p in data.top_products" :key="p.id" class="flex items-center gap-2.5">
              <div class="w-10 h-10 rounded-xl overflow-hidden bg-amber-50 border border-amber-100 shrink-0">
                <img v-if="p.image" :src="p.image" :alt="p.name" class="w-full h-full object-cover" />
                <div v-else class="w-full h-full flex items-center justify-center text-amber-300 text-base">🍽️</div>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-xs font-semibold text-gray-800 truncate">{{ p.name }}</p>
                <div class="mt-1 flex items-center gap-1.5">
                  <div class="flex-1 h-1.5 rounded-full overflow-hidden" style="background:#f3f4f6">
                    <div class="h-full rounded-full transition-all"
                         style="background: linear-gradient(90deg,#f59e0b,#ea580c)"
                         :style="{ width: (Number(p.total_sold) / maxTopSold * 100) + '%' }"></div>
                  </div>
                  <span class="text-xs text-gray-400 shrink-0">×{{ p.total_sold }}</span>
                </div>
                <p class="text-xs font-bold text-orange-600 mt-0.5">LKR {{ shortNum(p.total_revenue) }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Row 3: Recent bills ── -->
    <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
      <div class="px-4 pt-4 pb-3 flex items-center justify-between"
           style="background: linear-gradient(90deg,#fafafa,#fff8f0)">
        <h3 class="font-bold text-gray-800 text-sm flex items-center gap-2">
          <span class="w-3 h-3 rounded-full" style="background:linear-gradient(135deg,#f59e0b,#ea580c)"></span>
          Recent Bills
        </h3>
      </div>
      <div class="px-4 pb-4 overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="text-xs text-gray-400 uppercase tracking-wide border-b-2 border-orange-100">
              <th class="text-left py-2 pr-3 font-semibold">#</th>
              <th class="text-left py-2 pr-3 font-semibold">Invoice</th>
              <th class="text-left py-2 pr-3 font-semibold">Customer</th>
              <th class="text-left py-2 pr-3 font-semibold">Table</th>
              <th class="text-left py-2 pr-3 font-semibold">Date & Time</th>
              <th class="text-left py-2 pr-3 font-semibold">Payment</th>
              <th class="text-left py-2 pr-3 font-semibold">Status</th>
              <th class="text-right py-2 pr-3 font-semibold">Total</th>
              <th class="text-center py-2 font-semibold">Bill</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-orange-50">
            <tr v-for="(sale, idx) in data.recent_sales" :key="sale.id"
                class="transition-colors hover:bg-orange-50 group">
              <!-- Row number -->
              <td class="py-2.5 pr-3 text-xs text-gray-300 font-medium">{{ idx + 1 }}</td>

              <!-- Invoice -->
              <td class="py-2.5 pr-3">
                <span class="font-mono text-xs font-bold text-gray-800">{{ sale.invoice_number }}</span>
              </td>

              <!-- Customer -->
              <td class="py-2.5 pr-3">
                <div class="flex items-center gap-1.5">
                  <div class="w-6 h-6 rounded-full flex items-center justify-center text-white text-[10px] font-bold shrink-0"
                       style="background:linear-gradient(135deg,#f59e0b,#ea580c)">
                    {{ (sale.customer?.name ?? 'W')[0].toUpperCase() }}
                  </div>
                  <span class="text-xs text-gray-600">{{ sale.customer?.name ?? 'Walk-in' }}</span>
                </div>
              </td>

              <!-- Table -->
              <td class="py-2.5 pr-3">
                <span v-if="sale.table" class="text-xs px-2 py-0.5 rounded-full font-semibold bg-amber-50 text-amber-700 border border-amber-100">
                  {{ sale.table_number }}
                </span>
                <span v-else class="text-xs text-gray-300">—</span>
              </td>

              <!-- Date & Time -->
              <td class="py-2.5 pr-3 text-xs text-gray-500 whitespace-nowrap">
                <div>{{ new Date(sale.sold_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) }}</div>
                <div class="text-gray-400">{{ new Date(sale.sold_at).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' }) }}</div>
              </td>

              <!-- Payment method -->
              <td class="py-2.5 pr-3">
                <span class="text-xs capitalize font-medium text-gray-600">
                  {{ sale.payment_method ? sale.payment_method.replace('_', ' ') : '—' }}
                </span>
              </td>

              <!-- Payment status -->
              <td class="py-2.5 pr-3">
                <span class="text-xs px-2 py-0.5 rounded-full font-semibold" :class="statusClass(sale.payment_status)">
                  {{ sale.payment_status }}
                </span>
              </td>

              <!-- Total -->
              <td class="py-2.5 pr-3 text-right">
                <span class="text-sm font-black text-orange-600">LKR {{ shortNum(sale.total) }}</span>
              </td>

              <!-- View receipt -->
              <td class="py-2.5 text-center">
                <router-link :to="`/sales/${sale.id}/receipt`"
                  class="inline-flex items-center justify-center w-7 h-7 rounded-lg opacity-0 group-hover:opacity-100 transition-all hover:scale-110"
                  style="background:linear-gradient(135deg,#f59e0b,#ea580c)" title="View receipt">
                  <svg class="w-3.5 h-3.5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16l3-2 2 2 2-2 2 2 3 2V4a2 2 0 0 0-2-2z"/>
                    <line x1="9" y1="9" x2="15" y2="9"/><line x1="9" y1="13" x2="15" y2="13"/>
                  </svg>
                </router-link>
              </td>
            </tr>
            <tr v-if="!data.recent_sales?.length">
              <td colspan="9" class="py-8 text-center text-sm text-gray-400">No sales yet</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ── Row 5: Low stock ── -->
    <div v-if="data.low_stock?.length" class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
      <div class="px-4 pt-4 pb-3" style="background: linear-gradient(90deg,#fff1f2,#fff)">
        <h3 class="font-bold text-red-700 text-sm flex items-center gap-2">
          <span class="w-2.5 h-2.5 rounded-full bg-red-500 animate-pulse shrink-0"></span>
          Low Stock Alerts ({{ data.low_stock.length }})
        </h3>
      </div>
      <div class="px-4 pb-4 overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="text-xs text-gray-400 uppercase tracking-wide border-b-2 border-red-100">
              <th class="text-left py-2 pr-4">SKU</th>
              <th class="text-left py-2 pr-4">Product</th>
              <th class="text-left py-2 pr-4">Category</th>
              <th class="text-right py-2 pr-4">Stock</th>
              <th class="text-right py-2">Min Level</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-red-50">
            <tr v-for="p in data.low_stock" :key="p.id" class="hover:bg-red-50 transition-colors">
              <td class="py-1.5 pr-4 font-mono text-xs text-gray-400">{{ p.sku }}</td>
              <td class="py-1.5 pr-4 font-semibold text-gray-800">{{ p.name }}</td>
              <td class="py-1.5 pr-4 text-xs text-gray-400">{{ p.category?.name ?? '—' }}</td>
              <td class="py-1.5 pr-4 text-right">
                <span class="px-2 py-0.5 rounded-full text-xs font-bold text-white"
                      style="background:linear-gradient(90deg,#ef4444,#dc2626)">{{ p.stock_quantity }}</span>
              </td>
              <td class="py-1.5 text-right text-xs text-gray-400">{{ p.min_stock_level }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, defineComponent, h } from 'vue'
import axios from 'axios'
import { onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { ShoppingCartIcon, SparklesIcon } from '@heroicons/vue/24/outline'
import { Line, Bar, Doughnut } from 'vue-chartjs'
import {
  Chart as ChartJS,
  CategoryScale, LinearScale,
  PointElement, LineElement,
  BarElement, ArcElement,
  Title, Tooltip, Legend, Filler,
} from 'chart.js'

ChartJS.register(
  CategoryScale, LinearScale,
  PointElement, LineElement,
  BarElement, ArcElement,
  Title, Tooltip, Legend, Filler,
)

// ── Inline empty-state component ───────────────────────
const ChartEmpty = defineComponent({
  props: { loaded: Boolean, message: { type: String, default: 'No data available' } },
  setup(props) {
    return () => h('div', { class: 'h-full flex flex-col items-center justify-center text-gray-300 gap-2' }, [
      props.loaded
        ? [h('svg', { class: 'w-8 h-8 opacity-30', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' },
            [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '1.5', d: 'M3 17l6-6 4 4 8-8' })]),
          h('span', { class: 'text-xs' }, props.message)]
        : [h('div', { class: 'w-5 h-5 border-2 border-gray-200 border-t-amber-400 rounded-full animate-spin' })],
    ])
  },
})

// ── Auth ──────────────────────────────────────────────
const auth      = useAuthStore()
const isCashier = computed(() => auth.user?.role === 'cashier')

// ── Data ──────────────────────────────────────────────
const data   = ref({})
const loaded = ref(false)
const enabledProductTypes = ref(['food', 'other'])
const showOpenBottles = computed(() => enabledProductTypes.value.includes('other'))

// ── Color palettes ────────────────────────────────────
const donutColors    = ['#10b981', '#3b82f6', '#8b5cf6', '#f59e0b', '#ef4444', '#06b6d4']
const categoryColors = ['#f59e0b', '#3b82f6', '#10b981', '#8b5cf6', '#ef4444', '#06b6d4', '#ec4899']

// ── KPI cards ─────────────────────────────────────────
const kpiCards = computed(() => {
  const t = data.value.totals ?? {}
  return [
    { label: "Today's Revenue",  value: 'LKR ' + shortNum(t.revenue_today   ?? 0), icon: '💰', color: 'text-amber-600', sub: `${t.sales_today ?? 0} bills` },
    { label: 'Month Revenue',    value: 'LKR ' + shortNum(t.revenue_month   ?? 0), icon: '📈', color: 'text-green-600'  },
    { label: 'Purchases (Mo.)',  value: 'LKR ' + shortNum(t.purchases_month ?? 0), icon: '🛒', color: 'text-blue-600'   },
    { label: 'Pending Bills',    value: 'LKR ' + shortNum(t.pending_amount  ?? 0), icon: '⏳', color: 'text-orange-500', sub: `${t.pending_count ?? 0} unpaid` },
    { label: 'Low Stock',        value: t.low_stock_count ?? '—',                  icon: '⚠️', color: 'text-red-500'    },
    { label: 'Customers',        value: t.customers       ?? '—',                  icon: '👥', color: 'text-purple-600' },
  ]
})

// ── Chart data ────────────────────────────────────────
const revenueTrendData = computed(() => {
  const sales = data.value.sales_chart
  if (!sales?.length) return null
  return {
    labels: sales.map(s => {
      const d = new Date(s.date)
      return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
    }),
    datasets: [
      {
        label: 'Revenue (LKR)',
        data: sales.map(s => Number(s.revenue)),
        backgroundColor: sales.map(() => 'rgba(217,119,6,0.75)'),
        hoverBackgroundColor: '#d97706',
        borderRadius: 6,
        borderSkipped: false,
        yAxisID: 'y',
      },
      {
        label: 'Bills',
        data: sales.map(s => Number(s.count)),
        backgroundColor: 'rgba(59,130,246,0.6)',
        hoverBackgroundColor: '#3b82f6',
        borderRadius: 6,
        borderSkipped: false,
        yAxisID: 'y1',
      },
    ],
  }
})

const maxTopSold = computed(() => {
  const products = data.value.top_products ?? []
  return Math.max(...products.map(p => Number(p.total_sold)), 1)
})

const paymentMethodData = computed(() => {
  const methods = data.value.payment_methods ?? []
  if (!methods.length) return null
  return {
    labels: methods.map(m => m.payment_method.replace('_', ' ')),
    datasets: [{
      data: methods.map(m => Number(m.revenue)),
      backgroundColor: donutColors.slice(0, methods.length),
      borderWidth: 2,
      borderColor: '#fff',
      hoverOffset: 6,
    }],
  }
})

const categorySalesData = computed(() => {
  const cats = data.value.category_sales ?? []
  if (!cats.length) return null
  return {
    labels: cats.map(c => c.category),
    datasets: [{
      data: cats.map(c => Number(c.revenue)),
      backgroundColor: categoryColors.slice(0, cats.length),
      borderWidth: 2,
      borderColor: '#fff',
      hoverOffset: 6,
    }],
  }
})

const hourlyPatternData = computed(() => {
  const pattern = data.value.hourly_pattern ?? []
  if (!pattern.length) return null
  const map = Object.fromEntries(pattern.map(h => [h.hour, Number(h.count)]))
  const maxCount = Math.max(...Object.values(map), 1)
  const counts = Array.from({ length: 24 }, (_, i) => map[i] ?? 0)
  return {
    labels: Array.from({ length: 24 }, (_, i) => {
      if (i === 0) return '12am'
      if (i === 12) return '12pm'
      return i < 12 ? `${i}am` : `${i - 12}pm`
    }),
    datasets: [{
      label: 'Bills',
      data: counts,
      backgroundColor: counts.map(v => {
        const opacity = v === 0 ? 0.08 : 0.25 + (v / maxCount) * 0.75
        return `rgba(217,119,6,${opacity.toFixed(2)})`
      }),
      borderRadius: 3,
      borderSkipped: false,
    }],
  }
})

// ── Peak hour helper ──────────────────────────────────
const peakHour = computed(() => {
  const pattern = data.value.hourly_pattern ?? []
  if (!pattern.length) return null
  return pattern.reduce((a, b) => Number(a.count) >= Number(b.count) ? a : b).hour
})

function formatHour(h) {
  if (h === 0) return '12:00 am'
  if (h === 12) return '12:00 pm'
  return h < 12 ? `${h}:00 am` : `${h - 12}:00 pm`
}

// ── Chart options ─────────────────────────────────────
const trendOptions = {
  responsive: true,
  maintainAspectRatio: false,
  interaction: { mode: 'index', intersect: false },
  plugins: {
    legend: { display: true, position: 'top', labels: { boxWidth: 10, font: { size: 11 }, usePointStyle: true } },
    tooltip: {
      callbacks: {
        label: ctx => ctx.datasetIndex === 0
          ? ` LKR ${Number(ctx.raw).toLocaleString('en-LK', { maximumFractionDigits: 0 })}`
          : ` ${ctx.raw} bills`,
      },
    },
  },
  scales: {
    y:  { beginAtZero: true, position: 'left',  grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { callback: v => 'LKR ' + (v >= 1000 ? (v / 1000).toFixed(0) + 'K' : v), font: { size: 10 } } },
    y1: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false },    ticks: { font: { size: 10 } } },
    x:  { grid: { display: false }, ticks: { font: { size: 10 }, maxRotation: 45, minRotation: 0 } },
  },
}

const doughnutOptions = {
  responsive: true,
  maintainAspectRatio: false,
  cutout: '65%',
  plugins: {
    legend: { display: false },
    tooltip: { callbacks: { label: ctx => ` LKR ${Number(ctx.raw).toLocaleString('en-LK', { maximumFractionDigits: 0 })}` } },
  },
}

const hourlyOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => ` ${ctx.raw} bills` } } },
  scales: {
    y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { font: { size: 9 }, stepSize: 1 } },
    x: { grid: { display: false }, ticks: { font: { size: 8 }, maxRotation: 0, callback: (_, i) => i % 3 === 0 ? hourlyPatternData.value?.labels[i] ?? '' : '' } },
  },
}

// ── Utilities ─────────────────────────────────────────
function shortNum(v) {
  const n = Number(v || 0)
  if (n >= 1_000_000) return (n / 1_000_000).toFixed(1) + 'M'
  if (n >= 1_000)     return (n / 1_000).toFixed(1) + 'K'
  return n.toLocaleString('en-LK', { maximumFractionDigits: 0 })
}

function statusClass(s) {
  return {
    paid:     'bg-green-100 text-green-700',
    pending:  'bg-yellow-100 text-yellow-700',
    partial:  'bg-blue-100 text-blue-700',
    refunded: 'bg-red-100 text-red-700',
    draft:    'bg-gray-100 text-gray-500',
  }[s] ?? 'bg-gray-100 text-gray-700'
}

onMounted(async () => {
  try {
    const [{ data: d }, { data: settings }] = await Promise.all([
      axios.get('/api/dashboard'),
      axios.get('/api/settings/restaurant').catch(() => ({ data: {} })),
    ])
    data.value = d
    if (settings.enabled_product_types) {
      enabledProductTypes.value = settings.enabled_product_types
    }
  } finally {
    loaded.value = true
  }
})
</script>
