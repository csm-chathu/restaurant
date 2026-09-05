<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <div class="flex gap-3">
        <input v-model="search" type="search" placeholder="PO number…" class="form-input w-44" @input="debouncedFetch" />
        <select v-model="supplierFilter" class="form-input w-44" @change="fetch">
          <option value="">All suppliers</option>
          <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</option>
        </select>
      </div>
      <router-link to="/purchases/new" class="btn-primary flex items-center gap-2">
        <PlusIcon class="w-4 h-4" /> New Purchase
      </router-link>
    </div>

    <div class="card p-0 overflow-hidden">
      <table class="w-full">
        <thead class="bg-gray-50 border-b">
          <tr>
            <th class="table-th w-8"></th>
            <th class="table-th">PO Number</th>
            <th class="table-th">Supplier</th>
            <th class="table-th">Date</th>
            <th class="table-th text-right">Total</th>
            <th class="table-th">Status</th>
            <th class="table-th">Actions</th>
          </tr>
        </thead>
        <tbody>
          <template v-for="p in purchases.data" :key="p.id">
            <tr class="hover:bg-gray-50 border-b border-gray-100 cursor-pointer" @click="toggleExpand(p)">
              <td class="table-td w-8 text-center">
                <ChevronRightIcon class="w-4 h-4 text-gray-400 transition-transform inline-block"
                  :class="{ 'rotate-90': expandedId === p.id }" />
              </td>
              <td class="table-td font-mono text-xs font-semibold text-gray-700">{{ p.purchase_number }}</td>
              <td class="table-td">{{ p.supplier?.name }}</td>
              <td class="table-td text-gray-500 text-xs">{{ new Date(p.purchased_at).toLocaleDateString() }}</td>
              <td class="table-td text-right font-semibold">LKR {{ Number(p.total).toLocaleString() }}</td>
              <td class="table-td">
                <span :class="statusClass(p.status)" class="badge capitalize">{{ statusLabel(p.status) }}</span>
              </td>
              <td class="table-td" @click.stop>
                <div class="flex items-center gap-2">
                  <button
                    v-if="!['received','completed','cancelled'].includes(p.status)"
                    @click="openStatusModal(p)"
                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-amber-100 text-amber-700 hover:bg-amber-200 transition-colors"
                  >
                    <AdjustmentsHorizontalIcon class="w-3.5 h-3.5" /> Update Status
                  </button>
                  <button @click="del(p)" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-red-100 text-red-700 hover:bg-red-200">
                    <TrashIcon class="w-3.5 h-3.5" /> Delete
                  </button>
                </div>
              </td>
            </tr>

            <!-- Expanded items row -->
            <tr v-if="expandedId === p.id" class="bg-amber-50/50">
              <td colspan="7" class="px-6 py-3">
                <div v-if="loadingItems" class="flex items-center gap-2 text-sm text-gray-400 py-2">
                  <ArrowPathIcon class="w-4 h-4 animate-spin" /> Loading items…
                </div>
                <div v-else-if="(expandedItems[p.id] ?? []).length === 0" class="text-sm text-gray-400 py-2">
                  No items on this purchase order.
                </div>
                <table v-else class="w-full text-sm">
                  <thead>
                    <tr class="text-xs text-gray-500 uppercase tracking-wide">
                      <th class="pb-2 text-left font-semibold">Product</th>
                      <th class="pb-2 text-right font-semibold">Qty</th>
                      <th class="pb-2 text-right font-semibold">Unit Cost</th>
                      <th class="pb-2 text-right font-semibold">Line Total</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-amber-100">
                    <tr v-for="item in expandedItems[p.id]" :key="item.id">
                      <td class="py-1.5 text-gray-700 font-medium">{{ item.product?.name ?? '—' }}</td>
                      <td class="py-1.5 text-right text-gray-600">{{ item.quantity }}</td>
                      <td class="py-1.5 text-right text-gray-600">LKR {{ Number(item.unit_cost).toLocaleString() }}</td>
                      <td class="py-1.5 text-right font-semibold text-amber-700">LKR {{ Number(item.total).toLocaleString() }}</td>
                    </tr>
                  </tbody>
                  <tfoot>
                    <tr class="border-t border-amber-200">
                      <td colspan="3" class="pt-2 text-xs text-gray-500 font-semibold text-right">Order Total</td>
                      <td class="pt-2 text-right font-bold text-amber-700">LKR {{ Number(p.total).toLocaleString() }}</td>
                    </tr>
                  </tfoot>
                </table>
              </td>
            </tr>
          </template>

          <tr v-if="!purchases.data?.length">
            <td colspan="7" class="table-td text-center text-gray-400 py-8">No purchases</td>
          </tr>
        </tbody>
      </table>
      <div class="px-4 py-3 border-t flex justify-between text-sm text-gray-600">
        <span>{{ purchases.total ?? 0 }} records</span>
        <div class="flex gap-2">
          <button @click="page--; fetch()" :disabled="page<=1" class="btn-secondary py-1 px-3 text-xs disabled:opacity-40">Prev</button>
          <button @click="page++; fetch()" :disabled="page>=purchases.last_page" class="btn-secondary py-1 px-3 text-xs disabled:opacity-40">Next</button>
        </div>
      </div>
    </div>

    <!-- Status Modal -->
    <Teleport to="body">
      <Transition name="fade">
        <div v-if="statusModal.show" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" @click.self="statusModal.show = false">
          <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
            <!-- Header -->
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
              <div>
                <h3 class="text-base font-bold text-gray-900">Update Status</h3>
                <p class="text-xs text-gray-400 mt-0.5 font-mono">{{ statusModal.purchase?.purchase_number }}</p>
              </div>
              <button @click="statusModal.show = false" class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-gray-700 hover:bg-gray-100 rounded-lg text-xl leading-none">✕</button>
            </div>

            <!-- Status tiles -->
            <div class="p-5 grid grid-cols-2 gap-3">
              <button
                v-for="tile in statusTiles"
                :key="tile.value"
                @click="confirmStatusChange(tile)"
                :disabled="statusModal.saving || tile.value === statusModal.purchase?.status"
                class="flex flex-col items-start gap-2 p-4 rounded-xl border-2 text-left transition-all disabled:cursor-not-allowed"
                :class="tile.value === statusModal.purchase?.status
                  ? tile.activeClass + ' ring-2 ring-offset-1 ' + tile.ringClass
                  : tile.value === statusModal.saving
                    ? 'opacity-50'
                    : tile.hoverClass + ' border-gray-200'"
              >
                <div class="flex items-center gap-2 w-full">
                  <span class="text-xl">{{ tile.icon }}</span>
                  <span class="font-bold text-sm flex-1">{{ tile.label }}</span>
                  <CheckCircleIcon v-if="tile.value === statusModal.purchase?.status" class="w-4 h-4 shrink-0" :class="tile.checkClass" />
                </div>
                <p class="text-xs leading-tight" :class="tile.descClass">{{ tile.desc }}</p>
                <span v-if="tile.value === 'received'" class="text-[10px] font-semibold px-1.5 py-0.5 rounded bg-green-100 text-green-700">
                  ↑ Updates Stock
                </span>
              </button>
            </div>

            <div v-if="statusModal.error" class="px-5 pb-4 text-xs text-red-600 bg-red-50 mx-5 rounded-lg py-2">{{ statusModal.error }}</div>
            <div v-if="statusModal.saving" class="px-5 pb-4 flex items-center gap-2 text-xs text-gray-500">
              <ArrowPathIcon class="w-3.5 h-3.5 animate-spin" /> Updating…
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <ConfirmModal :show="!!confirmDelete" :message="confirmMessage" @confirm="doDelete" @cancel="confirmDelete = null" />
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import axios from 'axios'
import { PlusIcon, TrashIcon, ChevronRightIcon, ArrowPathIcon, AdjustmentsHorizontalIcon, CheckCircleIcon } from '@heroicons/vue/24/outline'
import ConfirmModal from '@/components/ConfirmModal.vue'

const purchases      = ref({ data: [] })
const suppliers      = ref([])
const search         = ref(''); const page = ref(1)
const supplierFilter = ref('')
const expandedId     = ref(null)
const expandedItems  = ref({})
const loadingItems   = ref(false)
const confirmDelete  = ref(null)
const confirmMessage = ref('')

const statusModal = reactive({
  show: false,
  purchase: null,
  saving: false,
  error: '',
})

const statusTiles = [
  {
    value: 'draft',
    label: 'Draft',
    icon: '📝',
    desc: 'Order created, not yet approved.',
    activeClass: 'border-gray-400 bg-gray-50 text-gray-700',
    hoverClass: 'hover:border-gray-400 hover:bg-gray-50',
    ringClass: 'ring-gray-300',
    checkClass: 'text-gray-500',
    descClass: 'text-gray-400',
  },
  {
    value: 'approved',
    label: 'Approved',
    icon: '✅',
    desc: 'Order approved internally.',
    activeClass: 'border-blue-400 bg-blue-50 text-blue-700',
    hoverClass: 'hover:border-blue-300 hover:bg-blue-50',
    ringClass: 'ring-blue-300',
    checkClass: 'text-blue-500',
    descClass: 'text-blue-400',
  },
  {
    value: 'sent',
    label: 'Sent to Supplier',
    icon: '🚚',
    desc: 'Order sent, awaiting delivery.',
    activeClass: 'border-purple-400 bg-purple-50 text-purple-700',
    hoverClass: 'hover:border-purple-300 hover:bg-purple-50',
    ringClass: 'ring-purple-300',
    checkClass: 'text-purple-500',
    descClass: 'text-purple-400',
  },
  {
    value: 'partial_received',
    label: 'Partial Received',
    icon: '📦',
    desc: 'Some items received.',
    activeClass: 'border-orange-400 bg-orange-50 text-orange-700',
    hoverClass: 'hover:border-orange-300 hover:bg-orange-50',
    ringClass: 'ring-orange-300',
    checkClass: 'text-orange-500',
    descClass: 'text-orange-400',
  },
  {
    value: 'received',
    label: 'Received',
    icon: '🏭',
    desc: 'All items received. Stock will be updated.',
    activeClass: 'border-green-400 bg-green-50 text-green-700',
    hoverClass: 'hover:border-green-300 hover:bg-green-50',
    ringClass: 'ring-green-300',
    checkClass: 'text-green-500',
    descClass: 'text-green-400',
  },
  {
    value: 'cancelled',
    label: 'Cancelled',
    icon: '❌',
    desc: 'Order cancelled.',
    activeClass: 'border-red-400 bg-red-50 text-red-700',
    hoverClass: 'hover:border-red-300 hover:bg-red-50',
    ringClass: 'ring-red-300',
    checkClass: 'text-red-500',
    descClass: 'text-red-400',
  },
]

let timer = null
function debouncedFetch() { clearTimeout(timer); timer = setTimeout(() => { page.value=1; fetch() }, 400) }

async function fetch() {
  const { data } = await axios.get('/api/purchases', { params: { page: page.value, search: search.value, supplier_id: supplierFilter.value } })
  purchases.value = data
}

async function toggleExpand(p) {
  if (expandedId.value === p.id) { expandedId.value = null; return }
  expandedId.value = p.id
  if (!expandedItems.value[p.id]) {
    loadingItems.value = true
    try {
      const { data } = await axios.get(`/api/purchases/${p.id}`)
      expandedItems.value[p.id] = data.items ?? []
    } finally { loadingItems.value = false }
  }
}

function statusClass(s) {
  return {
    received:         'bg-green-100 text-green-700',
    completed:        'bg-green-100 text-green-700',
    approved:         'bg-blue-100 text-blue-700',
    sent:             'bg-purple-100 text-purple-700',
    partial_received: 'bg-orange-100 text-orange-700',
    cancelled:        'bg-red-100 text-red-700',
  }[s] ?? 'bg-gray-100 text-gray-700'
}

function statusLabel(s) {
  return { partial_received: 'Partial Received', sent: 'Sent' }[s] ?? s
}

function openStatusModal(p) {
  statusModal.purchase = p
  statusModal.saving   = false
  statusModal.error    = ''
  statusModal.show     = true
}

async function confirmStatusChange(tile) {
  if (tile.value === statusModal.purchase?.status) return
  statusModal.saving = true
  statusModal.error  = ''
  try {
    await axios.patch(`/api/purchases/${statusModal.purchase.id}/status`, { status: tile.value })
    statusModal.show = false
    fetch()
  } catch (e) {
    statusModal.error = e.response?.data?.message ?? 'Failed to update status.'
  } finally {
    statusModal.saving = false
  }
}

function del(p) {
  confirmDelete.value  = p
  confirmMessage.value = `Delete purchase order ${p.purchase_number}? This cannot be undone.`
}

async function doDelete() {
  const p = confirmDelete.value
  confirmDelete.value = null
  await axios.delete(`/api/purchases/${p.id}`)
  delete expandedItems.value[p.id]
  if (expandedId.value === p.id) expandedId.value = null
  fetch()
}

onMounted(async () => {
  const { data } = await axios.get('/api/suppliers/all')
  suppliers.value = data
  fetch()
})
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.15s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
