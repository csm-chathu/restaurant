<template>
  <div class="relative" ref="container">
    <div
      class="form-input flex items-center justify-between cursor-pointer gap-2 pr-2"
      :class="open ? 'ring-2 ring-amber-400 border-amber-400' : ''"
      @click="toggle"
    >
      <span :class="selectedLabel ? 'text-gray-800' : 'text-gray-400'" class="truncate text-sm">
        {{ selectedLabel || placeholder }}
      </span>
      <ChevronDownIcon class="w-4 h-4 text-gray-400 shrink-0 transition-transform" :class="open ? 'rotate-180' : ''" />
    </div>

    <div
      v-if="open"
      class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg overflow-hidden"
    >
      <div class="p-2 border-b border-gray-100">
        <input
          ref="searchInput"
          v-model="query"
          type="text"
          placeholder="Search…"
          class="w-full text-sm px-2 py-1.5 rounded-md border border-gray-200 outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400"
          @keydown.escape="close"
          @keydown.enter.prevent="selectHighlighted"
          @keydown.arrow-down.prevent="moveDown"
          @keydown.arrow-up.prevent="moveUp"
        />
      </div>
      <ul class="max-h-52 overflow-y-auto py-1" ref="listEl">
        <li
          v-if="filtered.length === 0"
          class="px-3 py-2 text-sm text-gray-400 text-center"
        >No results</li>
        <li
          v-for="(opt, i) in filtered"
          :key="opt.value"
          @click="select(opt)"
          class="px-3 py-2 text-sm cursor-pointer truncate"
          :class="[
            opt.value === modelValue ? 'bg-amber-50 text-amber-700 font-medium' : 'text-gray-700 hover:bg-gray-50',
            i === highlighted ? 'bg-amber-50' : ''
          ]"
        >{{ opt.label }}</li>
      </ul>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, nextTick, onMounted, onBeforeUnmount } from 'vue'
import { ChevronDownIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  modelValue: { default: '' },
  options: { type: Array, default: () => [] }, // [{ value, label }]
  placeholder: { type: String, default: '— Select —' },
})
const emit = defineEmits(['update:modelValue'])

const open        = ref(false)
const query       = ref('')
const highlighted = ref(-1)
const container   = ref(null)
const searchInput = ref(null)
const listEl      = ref(null)

const filtered = computed(() => {
  const q = query.value.toLowerCase()
  return props.options.filter(o => o.label.toLowerCase().includes(q))
})

const selectedLabel = computed(() =>
  props.options.find(o => o.value === props.modelValue)?.label ?? ''
)

function toggle() {
  open.value ? close() : openDropdown()
}

function openDropdown() {
  open.value = true
  query.value = ''
  highlighted.value = -1
  nextTick(() => searchInput.value?.focus())
}

function close() {
  open.value = false
  query.value = ''
}

function select(opt) {
  emit('update:modelValue', opt.value)
  close()
}

function selectHighlighted() {
  if (highlighted.value >= 0 && filtered.value[highlighted.value]) {
    select(filtered.value[highlighted.value])
  }
}

function moveDown() {
  highlighted.value = Math.min(highlighted.value + 1, filtered.value.length - 1)
  scrollToHighlighted()
}

function moveUp() {
  highlighted.value = Math.max(highlighted.value - 1, 0)
  scrollToHighlighted()
}

function scrollToHighlighted() {
  nextTick(() => {
    const items = listEl.value?.querySelectorAll('li')
    items?.[highlighted.value]?.scrollIntoView({ block: 'nearest' })
  })
}

function onClickOutside(e) {
  if (container.value && !container.value.contains(e.target)) close()
}

onMounted(() => document.addEventListener('mousedown', onClickOutside))
onBeforeUnmount(() => document.removeEventListener('mousedown', onClickOutside))
</script>
