<template>
  <div class="flex h-screen overflow-hidden" style="background:#f1f5f9">
    <!-- Sidebar -->
    <aside :class="sidebarHidden ? 'w-0 overflow-hidden' : collapsed ? 'w-16' : 'w-[232px]'" class="bg-gray-900 text-white flex flex-col shrink-0 transition-all duration-200">
      <!-- Logo -->
      <div class="flex items-center gap-3 px-3 py-5 border-b border-gray-800 min-h-[72px]">
        <img v-if="restaurant.logo_url" :src="restaurant.logo_url" alt="Restaurant logo" class="w-10 h-10 rounded-lg object-cover border border-gray-700 shrink-0" />
        <span v-else class="text-2xl shrink-0">🍻</span>
        <div v-if="!collapsed" class="overflow-hidden">
          <p class="font-bold text-gold-400 text-base leading-tight truncate">{{ restaurant.name }}</p>
          <p class="text-sm text-gray-400">POS & Inventory System</p>
        </div>
      </div>

      <!-- Nav -->
      <nav class="flex-1 py-4 overflow-y-auto overflow-x-hidden" style="scrollbar-width:thin; scrollbar-color:#f59e0b #1f2937">
        <div v-if="!collapsed" class="px-4 mb-2 text-sm font-semibold text-gray-500 uppercase tracking-wider">Main</div>
        <router-link v-for="item in navItems" :key="item.to" :to="item.to"
          :title="collapsed ? item.label : ''"
          :class="[
            'flex items-center py-2.5 mx-2 rounded-lg text-base transition-colors',
            collapsed ? 'justify-center px-0' : 'gap-3 px-4',
            isNavActive(item.to) ? 'text-white shadow-md' : 'text-gray-300 hover:bg-gray-800 hover:text-white'
          ]"
          :style="isNavActive(item.to) ? 'background: linear-gradient(135deg,#f59e0b,#ea580c)' : ''">
          <component :is="item.icon" class="w-5 h-5 shrink-0 opacity-90" />
          <span v-if="!collapsed">{{ item.label }}</span>
        </router-link>

        <!-- Hotel section -->
        <template v-if="hotelNavItems.length > 0">
          <div v-if="!collapsed" class="px-4 mt-4 mb-2 text-sm font-semibold text-gray-500 uppercase tracking-wider">Hotel</div>
          <div v-else class="my-3 mx-3 border-t border-gray-700"></div>
          <router-link v-for="item in hotelNavItems" :key="item.to" :to="item.to"
            :title="collapsed ? item.label : ''"
            :class="[
              'flex items-center py-2.5 mx-2 rounded-lg text-base transition-colors',
              collapsed ? 'justify-center px-0' : 'gap-3 px-4',
              isNavActive(item.to) ? 'text-white shadow-md' : 'text-gray-300 hover:bg-gray-800 hover:text-white'
            ]"
            :style="isNavActive(item.to) ? 'background: linear-gradient(135deg,#f59e0b,#ea580c)' : ''">
            <component :is="item.icon" class="w-5 h-5 shrink-0 opacity-90" />
            <span v-if="!collapsed">{{ item.label }}</span>
          </router-link>
        </template>

        <!-- Admin / feature section -->
        <template v-if="adminNavItems.length > 0">
          <div v-if="!collapsed" class="px-4 mt-4 mb-2 text-sm font-semibold text-gray-500 uppercase tracking-wider">Admin</div>
          <div v-else class="my-3 mx-3 border-t border-gray-700"></div>
          <router-link v-for="item in adminNavItems" :key="item.to" :to="item.to"
            :title="collapsed ? item.label : ''"
            :class="[
              'flex items-center py-2.5 mx-2 rounded-lg text-base transition-colors',
              collapsed ? 'justify-center px-0' : 'gap-3 px-4',
              isNavActive(item.to) ? 'text-white shadow-md' : 'text-gray-300 hover:bg-gray-800 hover:text-white'
            ]"
            :style="isNavActive(item.to) ? 'background: linear-gradient(135deg,#f59e0b,#ea580c)' : ''">
            <component :is="item.icon" class="w-5 h-5 shrink-0 opacity-90" />
            <span v-if="!collapsed">{{ item.label }}</span>
          </router-link>
        </template>
      </nav>

      <!-- Collapse toggle only -->
      <div class="px-2 py-3 border-t border-gray-800">
        <button @click="toggleCollapse"
          :title="collapsed ? 'Expand sidebar' : 'Collapse sidebar'"
          class="w-full flex items-center justify-center gap-2 py-1.5 rounded-lg text-gray-400 hover:text-white hover:bg-gray-700 transition-colors text-sm">
          <ChevronDoubleLeftIcon v-if="!collapsed" class="w-4 h-4" />
          <ChevronDoubleRightIcon v-else class="w-4 h-4" />
          <span v-if="!collapsed">Collapse</span>
        </button>
      </div>
    </aside>

    <!-- Main area -->
    <div class="flex-1 flex flex-col min-h-0 min-w-0">
      <!-- Top bar -->
      <header class="bg-white border-b border-gray-200 px-6 py-3 grid items-center" style="grid-template-columns:1fr auto 1fr">
        <!-- LEFT: title + full screen + date -->
        <div class="flex items-center gap-3">
          <router-link v-if="sidebarHidden" to="/" title="Dashboard"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-xs font-semibold transition-colors">
            <HomeIcon class="w-4 h-4" />
            Home
          </router-link>
          <h1 class="text-lg font-semibold text-gray-800">{{ pageTitle }}</h1>
          <button @click="toggleSidebarHidden"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-gray-200 text-xs font-medium text-gray-500 hover:text-gray-700 hover:bg-gray-50 transition-colors">
            <ArrowsPointingOutIcon v-if="!sidebarHidden" class="w-3.5 h-3.5" />
            <ArrowsPointingInIcon v-else class="w-3.5 h-3.5" />
            {{ sidebarHidden ? 'Exit Full Screen' : 'Full Screen' }}
          </button>
          <button @click="() => window.location.reload()"
            title="Refresh page"
            class="p-1.5 rounded-lg border border-gray-200 text-gray-400 hover:text-gray-700 hover:bg-gray-50 transition-colors">
            <ArrowPathIcon class="w-4 h-4" />
          </button>
          <span class="text-sm text-gray-500">{{ currentDate }}</span>
        </div>

        <!-- CENTER: page-specific content (e.g. order type toggle) -->
        <div id="navbar-center" class="flex items-center justify-center"></div>

        <!-- RIGHT: shift, user, logout -->
        <div class="flex items-center gap-3 text-sm text-gray-500 justify-end">
          <button v-if="auth.user?.role === 'cashier'" @click="openShiftModal"
            :class="currentShift
              ? 'bg-green-50 text-green-700 border-green-200 hover:bg-green-100'
              : 'bg-red-50 text-red-600 border-red-200 hover:bg-red-100'"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border text-xs font-semibold transition-colors">
            <span :class="currentShift ? 'bg-green-500 animate-pulse' : 'bg-red-500'" class="w-2 h-2 rounded-full"></span>
            <span v-if="currentShift">
              Shift · Since {{ new Date(currentShift.opened_at).toLocaleTimeString('en-LK', { hour: '2-digit', minute: '2-digit' }) }}
            </span>
            <span v-else>No Shift — Start Now</span>
          </button>
          <button v-if="currentShift && auth.user?.role === 'cashier'" @click="showCashOutModal = true"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-orange-50 text-orange-700 border border-orange-200 hover:bg-orange-100 text-xs font-semibold transition-colors">
            <BanknotesIcon class="w-3.5 h-3.5" />
            Cash Out
          </button>
          <button @click="showGuide = true"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-amber-50 text-amber-700 border border-amber-200 hover:bg-amber-100 text-xs font-semibold transition-colors">
            <span class="w-4 h-4 rounded-full bg-amber-500 text-white flex items-center justify-center text-[10px] font-bold leading-none">?</span>
            Getting Started
          </button>
          <!-- User info + logout -->
          <div class="flex items-center gap-2 pl-3 border-l border-gray-200">
            <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-sm font-bold shrink-0"
                 style="background: linear-gradient(135deg,#f59e0b,#ea580c)">
              {{ auth.user?.name?.charAt(0) }}
            </div>
            <div class="hidden xl:block leading-tight">
              <p class="text-xs font-semibold text-gray-800">{{ auth.user?.name }}</p>
              <p class="text-[10px] text-gray-400">{{ auth.user?.email }}</p>
            </div>
            <button @click="doLogout"
              class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-red-50 text-red-600 border border-red-200 hover:bg-red-100 text-xs font-semibold transition-colors ml-1">
              <ArrowRightOnRectangleIcon class="w-3.5 h-3.5" />
              Logout
            </button>
          </div>
        </div>
      </header>

  

      <!-- Server offline alert -->
      <div v-if="!serverOnline"
        class="no-print flex items-center justify-center gap-2 px-4 py-2 bg-orange-600 text-white text-xs font-semibold shrink-0 animate-pulse">
        <span class="w-2 h-2 rounded-full bg-white shrink-0"></span>
        <span>Server unreachable — check your connection. Data may not save correctly.</span>
      </div>

      <!-- Page -->
      <main :class="route.name === 'sales.new' ? 'flex-1 overflow-hidden' : 'flex-1 overflow-auto p-6'"
            class="text-[15px]">
        <router-view />
      </main>
    </div>
  </div>

  <GettingStarted v-model="showGuide" />
  <ShiftModal v-if="showShiftModal" :current-shift="currentShift" :required="shiftRequired || shiftStale" :stale="shiftStale" @close="ui.closeShiftModal()" @shifted="onShifted" />
  <CashOutModal v-if="showCashOutModal" @close="showCashOutModal = false" @saved="showCashOutModal = false" />
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useUiStore } from '@/stores/ui'
import axios from 'axios'
import GettingStarted from '@/components/GettingStarted.vue'
import ShiftModal from '@/components/ShiftModal.vue'
import CashOutModal from '@/components/CashOutModal.vue'
import {
  HomeIcon, CubeIcon, TagIcon, UsersIcon,
  TruckIcon, ShoppingCartIcon, ArchiveBoxIcon,
  ArrowRightOnRectangleIcon, SparklesIcon,
  UserGroupIcon, ClipboardDocumentCheckIcon,
  ClipboardDocumentListIcon, CurrencyDollarIcon, FireIcon,
  TableCellsIcon, ChartBarIcon, Cog6ToothIcon, BanknotesIcon,
  ChevronDoubleLeftIcon, ChevronDoubleRightIcon,
  ArrowsPointingOutIcon, ArrowsPointingInIcon, ArrowPathIcon,
  BuildingOfficeIcon, CalendarDaysIcon,
  ClockIcon, DocumentChartBarIcon, CalendarIcon,
  ReceiptPercentIcon, BeakerIcon, ScaleIcon,
  PresentationChartLineIcon, DocumentTextIcon,
  ArrowUturnLeftIcon, WalletIcon,
} from '@heroicons/vue/24/outline'

const auth      = useAuthStore()
const ui        = useUiStore()
const router    = useRouter()
const route     = useRoute()
const showGuide      = ref(false)
const showShiftModal  = computed({
  get: () => ui.shiftModalOpen,
  set: (v) => v ? ui.openShiftModal() : ui.closeShiftModal(),
})
const shiftRequired   = ref(false)
const shiftStale      = ref(false)
const showCashOutModal = ref(false)
const currentShift    = ref(null)
const restaurant = ref({ name: 'Liquor Shop + Bar', logo_url: '', address: '', enabled_product_types: ['food', 'other'] })

const collapsed = ref(localStorage.getItem('sidebar_collapsed') === 'true')
const sidebarHidden = ref(false)

const showMaintenanceAlert = new Date() <= new Date('2026-07-22')

const serverOnline = ref(true)
let healthInterval = null

async function checkHealth() {
  try {
    await axios.get('/api/health', { timeout: 60000 })
    serverOnline.value = true
  } catch {
    serverOnline.value = false
  }
}

watch(() => route.name, (name) => {
  if (name === 'sales.new') {
    collapsed.value = true
  } else {
    collapsed.value = false
    localStorage.setItem('sidebar_collapsed', 'false')
  }
}, { immediate: true })

function toggleCollapse() {
  collapsed.value = !collapsed.value
  localStorage.setItem('sidebar_collapsed', collapsed.value)
}

function toggleSidebarHidden() {
  sidebarHidden.value = !sidebarHidden.value
}


const allNavItems = [
  { to: '/',                 label: 'Dashboard',       icon: HomeIcon,                    feature: 'dashboard' },
  { to: '/products',         label: 'Products',        icon: CubeIcon,                    feature: 'products' },
  { to: '/categories',       label: 'Menu Categories', icon: TagIcon,                     feature: 'menu_categories' },
  { to: '/customers',        label: 'Guests',          icon: UsersIcon,                   feature: 'guests' },
  { to: '/tables',           label: 'Tables',          icon: TableCellsIcon,              feature: 'tables' },
  { to: '/suppliers',        label: 'Suppliers',       icon: TruckIcon,                   feature: 'suppliers' },
  { to: '/sales',            label: 'POS Billing',     icon: ShoppingCartIcon,            feature: 'pos_billing' },
  { to: '/open-bottles',     label: 'Open Bottles',    icon: BeakerIcon,                  feature: 'open_bottles' },
  { to: '/my-shift-summary', label: 'My Shift',        icon: ClockIcon,                   feature: 'my_shift' },
  { to: '/reports',          label: 'Reports',         icon: PresentationChartLineIcon,   feature: 'reports' },
  { to: '/daily-report',     label: 'Daily Report',    icon: CalendarIcon,                feature: 'daily_report' },
  { to: '/purchases',        label: 'Purchase Orders', icon: ClipboardDocumentCheckIcon,  feature: 'purchases' },
]

const allAdminNavItems = [
  { to: '/price-matrix',     label: 'Price Matrix',    icon: ScaleIcon,                   feature: 'price_matrix' },
  { to: '/opening-balance',  label: 'Opening Balance', icon: WalletIcon,                  feature: 'opening_balance' },
  { to: '/grn',              label: 'GRN',             icon: ArchiveBoxIcon,              feature: 'grn' },
  { to: '/supplier-returns', label: 'Supplier Returns',icon: ArrowUturnLeftIcon,          feature: 'supplier_returns' },
  { to: '/bottle-deposits',  label: 'Bottle Deposits', icon: CurrencyDollarIcon,          feature: 'bottle_deposits' },
  { to: '/finance',          label: 'Finance',         icon: BanknotesIcon,               feature: 'finance' },
  { to: '/shift-summary',    label: 'Shift Summary',   icon: DocumentChartBarIcon,        feature: 'shift_summary' },
  { to: '/damages',          label: 'Damages',         icon: FireIcon,                    feature: 'damages' },
  { to: '/audit-log',        label: 'Stock Ledger',    icon: ClipboardDocumentListIcon,   feature: 'stock_ledger' },
  { to: '/users',            label: 'Users & Roles',   icon: UserGroupIcon,               feature: 'users_roles' },
  { to: '/settings',         label: 'Settings',        icon: Cog6ToothIcon,               feature: 'settings' },
]

function hasFeature(feature) {
  const user = auth.user
  if (!user) return false
  if (feature === 'open_bottles' && !restaurant.value.enabled_product_types?.includes('other')) return false
  if (user.is_super_admin) return true
  const allowed = user.allowed_features ?? []
  return allowed.includes(feature)
}

const navItems = computed(() =>
  allNavItems.filter(item => hasFeature(item.feature))
)

const adminNavItems = computed(() => {
  const items = allAdminNavItems.filter(item => hasFeature(item.feature))
  if (auth.user?.is_super_admin) {
    items.push({ to: '/role-features', label: 'Role Features', icon: Cog6ToothIcon, feature: null })
  }
  return items
})

const allHotelNavItems = [
  { to: '/hotel/rooms',    label: 'Rooms',    icon: BuildingOfficeIcon, feature: 'hotel_rooms' },
  { to: '/hotel/bookings', label: 'Bookings', icon: CalendarDaysIcon,   feature: 'hotel_bookings' },
  { to: '/hotel/reports',  label: 'Reports',  icon: ChartBarIcon,       feature: 'hotel_reports' },
]

const hotelNavItems = computed(() =>
  allHotelNavItems.filter(item => hasFeature(item.feature))
)

const pageTitles = {
  dashboard:     'Dashboard',
  products:      'Products',
  categories:    'Menu Categories',
  customers:     'Guests',
  tables:        'Restaurant Tables',
  suppliers:     'Suppliers',
  sales:         'POS Billing',
  'sales.new':   'New Bill',
  'sales.edit':  'Edit Draft Bill',
  'sales.receipt': 'Bill Receipt',
  purchases:     'Purchase Orders',
  'purchases.new': 'New Purchase Order',
  'price-matrix':    'Price Matrix',
  'grn':           'Goods Received Notes',
  'supplier-returns': 'Supplier Returns',
  'open-bottles':  'Open Bottle Tracking',
  'users':         'Users & Roles',
  'settings':      'Restaurant Settings',
  'reports':        'Reports & Analytics',
  'daily-report':   'Daily POS Report',
  'shift-summary':    'Shift Summary Report',
  'my-shift-summary': 'My Shift Summary',
  'finance':        'Finance Management',
  'day-end':       'Shift Close',
  'audit-log':     'Stock Ledger',
  'bottle-deposits':      'Bottle Deposits',
  'damages':         'Damages & Waste',
  'opening-balance': 'Opening Balances',
  'hotel.rooms':     'Hotel — Rooms',
  'hotel.bookings':  'Hotel — Bookings',
  'hotel.reports':   'Hotel — Reports',
  'hotel.bookings.new': 'Hotel — New Booking',
  'hotel.booking':         'Hotel — Booking Detail',
  'hotel.booking.receipt': 'Hotel — Invoice',
}

const pageTitle  = computed(() => pageTitles[route.name] ?? 'Liquor Shop POS')
const currentDate = computed(() => new Date().toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }))

async function doLogout() {
  await auth.logout()
  window.location.href = '/login'
}

async function loadRestaurant() {
  try {
    const { data } = await axios.get('/api/settings/restaurant')
    restaurant.value = {
      name: data.name || 'Liquor Shop + Bar',
      logo_url: data.logo_url || '',
      address: data.address || '',
      enabled_product_types: data.enabled_product_types ?? ['food', 'other'],
    }
  } catch {
    // Keep fallbacks if settings are unavailable.
  }
}

async function openShiftModal() {
  await loadCurrentShift()
  ui.openShiftModal()
}

function isNavActive(targetPath) {
  if (targetPath === '/') {
    return route.path === '/'
  }
  return route.path === targetPath || route.path.startsWith(`${targetPath}/`)
}

async function loadCurrentShift() {
  try {
    const { data } = await axios.get('/api/cashier-shifts/current')
    currentShift.value = (data && data.id) ? data : null
  } catch {
    currentShift.value = null
  }
}

function onShifted(shift) {
  const wasStale = shiftStale.value
  currentShift.value = shift
  shiftRequired.value = false
  shiftStale.value = false
  // After closing a stale shift, immediately prompt to open today's shift
  if (!shift && wasStale && auth.user?.role === 'cashier') {
    shiftRequired.value = true
    ui.openShiftModal()
  }
}

onUnmounted(() => {
  clearInterval(healthInterval)
})

onMounted(async () => {
  // Apply saved UI scale on every page load
  const savedScale = localStorage.getItem('pos_ui_scale')
  if (savedScale && savedScale !== '100') {
    document.documentElement.style.zoom = `${savedScale}%`
  }

  checkHealth()
  healthInterval = setInterval(checkHealth, 60000)
  loadRestaurant()
  await loadCurrentShift()
  if (auth.user?.role === 'cashier') {
    if (!currentShift.value) {
      shiftRequired.value = true
      ui.openShiftModal()
    } else {
      const shiftDay = new Date(currentShift.value.opened_at).toDateString()
      if (shiftDay !== new Date().toDateString()) {
        shiftStale.value = true
        ui.openShiftModal()
      }
    }
  }
})
</script>
