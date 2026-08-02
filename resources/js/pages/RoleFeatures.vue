<template>
  <div class="space-y-6">
    <div>
      <h2 class="text-xl font-semibold text-gray-800">Role Feature Access</h2>
      <p class="text-sm text-gray-500 mt-0.5">Control which sidebar features each role can access</p>
    </div>

    <div v-if="loading" class="flex justify-center py-20">
      <svg class="animate-spin w-8 h-8 text-amber-500" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
      </svg>
    </div>

    <div v-else-if="error" class="card text-red-600 text-sm">{{ error }}</div>

    <div v-else class="card overflow-x-auto p-0">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-gray-200 bg-gray-50">
            <th class="table-th sticky left-0 bg-gray-50 w-48">Feature</th>
            <th v-for="role in roles" :key="role.role" class="table-th text-center capitalize min-w-[100px]">
              {{ roleLabel(role.role) }}
            </th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="feature in allFeatures" :key="feature"
            class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
            <td class="table-td sticky left-0 bg-white font-medium text-gray-700">
              {{ featureLabel(feature) }}
            </td>
            <td v-for="role in roles" :key="role.role" class="table-td text-center">
              <button
                type="button"
                @click="toggle(role, feature)"
                class="w-6 h-6 rounded-md flex items-center justify-center mx-auto transition-colors"
                :class="hasFeature(role, feature)
                  ? 'bg-amber-500 text-white hover:bg-amber-600'
                  : 'bg-gray-100 text-gray-300 hover:bg-gray-200'"
              >
                <svg v-if="hasFeature(role, feature)" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
                <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Save bar -->
    <div v-if="dirty.size > 0" class="fixed bottom-4 right-4 flex items-center gap-3 bg-white border border-gray-200 rounded-2xl shadow-xl px-5 py-3">
      <span class="text-sm text-gray-600">{{ dirty.size }} role(s) with unsaved changes</span>
      <button @click="saveAll" :disabled="saving" class="btn-primary text-sm px-4 py-2">
        {{ saving ? 'Saving…' : 'Save All' }}
      </button>
    </div>

    <div v-if="saved" class="fixed bottom-4 right-4 flex items-center gap-2 bg-green-600 text-white rounded-2xl shadow-xl px-5 py-3 text-sm font-semibold">
      ✓ Changes saved
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const loading  = ref(true)
const saving   = ref(false)
const saved    = ref(false)
const error    = ref('')
const roles    = ref([])       // [{ role, features: [...] }]
const allFeatures = ref([])
const dirty    = ref(new Set())

const FEATURE_LABELS = {
  dashboard:        'Dashboard',
  pos_billing:      'POS Billing',
  products:         'Products',
  menu_categories:  'Menu Categories',
  guests:           'Guests',
  tables:           'Tables',
  suppliers:        'Suppliers',
  open_bottles:     'Open Bottles',
  my_shift:         'My Shift',
  reports:          'Reports',
  purchases:        'Purchase Orders',
  price_matrix:     'Price Matrix',
  opening_balance:  'Opening Balance',
  grn:              'GRN',
  supplier_returns: 'Supplier Returns',
  bottle_deposits:  'Bottle Deposits',
  finance:          'Finance',
  shift_summary:    'Shift Summary',
  damages:          'Damages',
  stock_ledger:     'Stock Ledger',
  users_roles:      'Users & Roles',
  settings:         'Settings',
}

const ROLE_LABELS = {
  admin:        'Admin',
  owner:        'Owner',
  manager:      'Manager',
  cashier:      'Cashier',
  store_keeper: 'Store Keeper',
}

function featureLabel(key) { return FEATURE_LABELS[key] ?? key }
function roleLabel(key)    { return ROLE_LABELS[key] ?? key }

function hasFeature(role, feature) {
  return role.features.includes(feature)
}

function toggle(role, feature) {
  const idx = role.features.indexOf(feature)
  if (idx === -1) {
    role.features.push(feature)
  } else {
    role.features.splice(idx, 1)
  }
  dirty.value.add(role.role)
}

async function load() {
  loading.value = true
  error.value   = ''
  try {
    const { data } = await axios.get('/api/role-features')
    roles.value       = data.roles
    allFeatures.value = data.all_features
  } catch (e) {
    error.value = e.response?.data?.message ?? 'Failed to load role features'
  } finally {
    loading.value = false
  }
}

async function saveAll() {
  saving.value = true
  try {
    await Promise.all(
      roles.value
        .filter(r => dirty.value.has(r.role))
        .map(r => axios.put(`/api/role-features/${r.role}`, { features: r.features }))
    )
    dirty.value.clear()
    saved.value = true
    setTimeout(() => { saved.value = false }, 2500)
  } catch (e) {
    error.value = e.response?.data?.message ?? 'Failed to save'
  } finally {
    saving.value = false
  }
}

onMounted(load)
</script>
