<template>
  <div class="absolute z-50 top-full left-0 right-0 mt-1 bg-white border border-gray-300 rounded-xl shadow-xl overflow-hidden">

    <div class="px-3 py-2 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
      <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Past Visits</span>
      <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 text-xs">✕</button>
    </div>

    <div v-for="booking in hits" :key="booking.id"
      @click="$emit('select', booking)"
      class="flex items-start gap-3 px-3 py-2.5 hover:bg-amber-50 cursor-pointer border-b border-gray-100 last:border-0 transition-colors">

      <!-- Status dot -->
      <span :class="dotColor(booking.status)" class="mt-1 w-2 h-2 rounded-full shrink-0"></span>

      <div class="flex-1 min-w-0">
        <div class="flex items-baseline justify-between gap-2">
          <span class="font-semibold text-sm text-gray-900 truncate">{{ booking.guest_name }}</span>
          <span class="text-xs text-gray-400 shrink-0">{{ booking.check_in_date }}</span>
        </div>
        <div class="text-xs text-gray-500 mt-0.5">
          Room {{ booking.room?.room_number }}
          <span class="text-gray-300 mx-1">·</span>
          {{ booking.nights }} night{{ booking.nights !== 1 ? 's' : '' }}
          <span class="text-gray-300 mx-1">·</span>
          <span :class="statusColor(booking.status)" class="font-medium capitalize">{{ booking.status.replace('_', ' ') }}</span>
        </div>
        <div class="text-xs text-gray-400 mt-0.5 truncate">
          <span v-if="booking.guest_phone">{{ booking.guest_phone }}</span>
          <span v-if="booking.guest_phone && booking.guest_nic" class="mx-1">·</span>
          <span v-if="booking.guest_nic">{{ booking.guest_nic }}</span>
        </div>
      </div>

      <div class="text-xs font-semibold text-gray-700 shrink-0 text-right">
        LKR {{ Number(booking.total).toLocaleString() }}
      </div>
    </div>

    <div class="px-3 py-1.5 bg-gray-50 text-xs text-gray-400 text-center">
      Click a row to fill guest details
    </div>
  </div>
</template>

<script setup>
defineProps({ hits: Array })
defineEmits(['select', 'close'])

function dotColor(s) {
  return {
    reserved:    'bg-blue-400',
    checked_in:  'bg-amber-400',
    checked_out: 'bg-green-400',
    cancelled:   'bg-gray-300',
  }[s] ?? 'bg-gray-300'
}

function statusColor(s) {
  return {
    reserved:    'text-blue-600',
    checked_in:  'text-amber-600',
    checked_out: 'text-green-600',
    cancelled:   'text-gray-400',
  }[s] ?? ''
}
</script>
