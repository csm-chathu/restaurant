<template>
  <div class="space-y-5">

    <!-- Header -->
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-3">
        <router-link to="/hotel/bookings" class="text-gray-400 hover:text-gray-600">
          <ArrowLeftIcon class="w-5 h-5" />
        </router-link>
        <div>
          <h2 class="text-xl font-bold text-gray-900">Booking {{ booking?.booking_number }}</h2>
          <p class="text-sm text-gray-500">Room {{ booking?.room?.room_number }} — {{ booking?.guest_name }}</p>
        </div>
      </div>
      <div class="flex items-center gap-2">
        <button v-if="booking" @click="receiptModal = true"
          class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 rounded-lg text-sm font-medium shadow-sm">
          <PrinterIcon class="w-4 h-4" /> Print Invoice
        </button>
        <span v-if="booking" :class="statusBadge(booking.status)" class="badge text-sm font-semibold px-3 py-1">
          {{ statusLabel(booking.status) }}
        </span>
      </div>
    </div>

    <div v-if="loading" class="flex justify-center py-12">
      <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-amber-500"></div>
    </div>

    <template v-else-if="booking">
      <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">

        <!-- Left: Info + Charges (takes 2 cols) -->
        <div class="xl:col-span-2 space-y-5">

          <!-- Booking info card -->
          <div class="card space-y-4">
            <h3 class="font-semibold text-gray-800">Booking Details</h3>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 text-sm">
              <div>
                <div class="text-gray-400 text-xs uppercase tracking-wide">Room</div>
                <div class="font-semibold text-gray-900">{{ booking.room?.room_number }} <span class="text-gray-400 font-normal">{{ booking.room?.name }}</span></div>
                <div class="text-xs text-gray-500 capitalize">{{ booking.room?.type?.replace('_','-') }} · {{ booking.room?.category }}</div>
              </div>
              <div>
                <div class="text-gray-400 text-xs uppercase tracking-wide">Check-in</div>
                <div class="font-semibold">{{ booking.check_in_date }}</div>
                <div v-if="booking.check_in_time" class="text-xs text-gray-500">{{ booking.check_in_time }}</div>
              </div>
              <div>
                <div class="text-gray-400 text-xs uppercase tracking-wide">Check-out</div>
                <div class="font-semibold">{{ booking.check_out_date }}</div>
                <div class="text-xs text-gray-500">{{ booking.nights }} night{{ booking.nights > 1 ? 's' : '' }}</div>
              </div>
              <div>
                <div class="text-gray-400 text-xs uppercase tracking-wide">Guest</div>
                <div class="font-semibold">{{ booking.guest_name }}</div>
                <div class="text-xs text-gray-500">{{ booking.guest_phone }} · {{ booking.guest_count }} person{{ booking.guest_count > 1 ? 's' : '' }}</div>
              </div>
              <div>
                <div class="text-gray-400 text-xs uppercase tracking-wide">NIC</div>
                <div class="font-semibold">{{ booking.guest_nic || '—' }}</div>
              </div>
              <div>
                <div class="text-gray-400 text-xs uppercase tracking-wide">Booked by</div>
                <div class="font-semibold">{{ booking.user?.name ?? '—' }}</div>
              </div>
            </div>
            <div v-if="booking.notes" class="text-sm text-gray-600 bg-gray-50 rounded-lg p-3">{{ booking.notes }}</div>
            <div v-if="booking.status === 'reserved'" class="pt-2 flex flex-wrap items-center gap-3">
              <button @click="doCheckIn" :disabled="actioning"
                class="px-7 py-3 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white font-bold rounded-xl text-base shadow-sm">
                {{ actioning ? 'Processing…' : 'Check In Guest' }}
              </button>
              <button @click="receiptModal = true"
                class="inline-flex items-center gap-2 px-5 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-xl text-base shadow-sm">
                <PrinterIcon class="w-5 h-5" /> Print Invoice
              </button>
              <button @click="cancelModal.open = true" :disabled="actioning"
                class="px-5 py-3 bg-red-50 hover:bg-red-100 text-red-600 border border-red-300 font-semibold rounded-xl text-base disabled:opacity-50">
                Cancel Booking
              </button>
            </div>
            <div v-if="booking.status === 'checked_in'" class="pt-2 flex flex-wrap items-center gap-3">
              <button @click="receiptModal = true"
                class="inline-flex items-center gap-2 px-5 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-xl text-base shadow-sm">
                <PrinterIcon class="w-5 h-5" /> Print Invoice
              </button>
              <button @click="cancelModal.open = true" :disabled="actioning"
                class="px-5 py-3 bg-red-50 hover:bg-red-100 text-red-600 border border-red-300 font-semibold rounded-xl text-base disabled:opacity-50">
                Cancel Booking
              </button>
            </div>
          </div>

          <!-- Charges -->
          <div class="card space-y-4">
            <div class="flex items-center justify-between">
              <h3 class="font-semibold text-gray-800">Charges</h3>
              <button v-if="canEdit" @click="showChargeForm = !showChargeForm"
                class="text-xs px-3 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 rounded-lg font-medium">
                + Add Charge
              </button>
            </div>

            <!-- Add charge form -->
            <div v-if="showChargeForm && canEdit" class="bg-blue-50 border border-blue-200 rounded-xl p-4 space-y-3">
              <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">

                <!-- Product search -->
                <div class="lg:col-span-2 relative">
                  <label class="form-label">Product Search <span class="text-gray-400 font-normal">(optional)</span></label>
                  <input
                    v-model="productSearch"
                    type="text"
                    class="form-input"
                    placeholder="Type product name to search…"
                    @input="onProductSearch"
                    @focus="onProductSearch"
                    autocomplete="off"
                  />
                  <!-- Product dropdown -->
                  <div v-if="productResults.length"
                    class="absolute z-40 top-full left-0 right-0 mt-1 bg-white border border-gray-300 rounded-xl shadow-xl overflow-hidden">
                    <div v-for="p in productResults" :key="p.id"
                      @click="selectProduct(p)"
                      class="flex items-center justify-between px-4 py-2.5 hover:bg-amber-50 cursor-pointer border-b border-gray-100 last:border-0">
                      <div>
                        <div class="text-sm font-medium text-gray-800">{{ p.name }}</div>
                        <div class="text-xs text-gray-400 capitalize">{{ p.category?.name ?? p.product_type }}</div>
                      </div>
                      <span class="text-sm font-semibold text-amber-700 shrink-0 ml-3">LKR {{ Number(p.selling_price).toLocaleString() }}</span>
                    </div>
                  </div>
                </div>

                <div>
                  <label class="form-label">Type</label>
                  <select v-model="chargeForm.charge_type" class="form-input">
                    <option value="food">Food</option>
                    <option value="beverage">Beverage</option>
                    <option value="laundry">Laundry</option>
                    <option value="room_service">Room Service</option>
                    <option value="other">Other</option>
                  </select>
                </div>

                <div>
                  <!-- spacer on large, used on small -->
                </div>

                <div class="lg:col-span-2">
                  <label class="form-label">Description *</label>
                  <input v-model="chargeForm.description" type="text" class="form-input" placeholder="e.g. Breakfast, Laundry" />
                </div>

                <div>
                  <label class="form-label">Unit Price (LKR) *</label>
                  <input v-model.number="chargeForm.unit_price" type="number" min="0" class="form-input" />
                </div>

                <div>
                  <label class="form-label">Qty</label>
                  <input v-model.number="chargeForm.quantity" type="number" min="0.5" step="0.5" class="form-input" />
                </div>

                <!-- Amount preview + actions -->
                <div class="lg:col-span-4 flex items-center justify-between gap-3 pt-1">
                  <div v-if="chargeForm.unit_price && chargeForm.quantity" class="text-sm text-gray-600 space-y-0.5">
                    <div>Amount: <span class="font-bold text-gray-900">LKR {{ Number(chargeForm.unit_price * chargeForm.quantity).toLocaleString() }}</span></div>
                    <div v-if="['food','beverage','room_service'].includes(chargeForm.charge_type)" class="text-xs text-amber-600">
                      + 10% service charge: LKR {{ Number(chargeForm.unit_price * chargeForm.quantity * 0.1).toLocaleString() }}
                    </div>
                  </div>
                  <div class="flex gap-2 ml-auto">
                    <button @click="addCharge" :disabled="savingCharge || !chargeForm.description || !chargeForm.unit_price"
                      class="px-5 py-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white rounded-lg font-semibold text-sm">
                      {{ savingCharge ? 'Adding…' : 'Add Charge' }}
                    </button>
                    <button @click="cancelChargeForm" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm">Cancel</button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Charges list -->
            <div v-if="booking.charges?.length">
              <div class="grid grid-cols-[1fr_auto_auto_auto] gap-x-4 text-xs text-gray-400 uppercase tracking-wide font-medium px-1 pb-1 border-b border-gray-100">
                <span>Item</span><span class="text-right">Qty</span><span class="text-right">Unit</span><span class="text-right">Amount</span>
              </div>
              <div v-for="c in booking.charges" :key="c.id"
                class="grid grid-cols-[1fr_auto_auto_auto] gap-x-4 items-center py-2.5 border-b border-gray-50 last:border-0">
                <div>
                  <div class="text-sm font-medium text-gray-800">{{ c.description }}</div>
                  <div class="text-xs text-gray-400 capitalize flex items-center gap-2">
                    <span>{{ c.charge_type.replace('_',' ') }}</span>
                    <span class="text-gray-300">·</span>
                    <span class="text-gray-400">{{ fmtTime(c.created_at) }}</span>
                  </div>
                </div>
                <div class="text-sm text-right text-gray-600">{{ c.quantity }}</div>
                <div class="text-sm text-right text-gray-600">{{ Number(c.unit_price).toLocaleString() }}</div>
                <div class="flex items-center gap-2 justify-end">
                  <span class="text-sm font-semibold text-gray-900">LKR {{ Number(c.amount).toLocaleString() }}</span>
                  <button v-if="canEdit" @click="removeCharge(c)" class="text-red-300 hover:text-red-600 text-xs leading-none">✕</button>
                </div>
              </div>
              <div class="flex justify-end pt-2 font-bold text-gray-800 text-sm">
                Charges total: LKR {{ Number(booking.charges_total).toLocaleString() }}
              </div>
            </div>
            <div v-else class="text-sm text-gray-400 text-center py-6">No extra charges yet.</div>
          </div>
        </div>

        <!-- Right: Bill summary + checkout -->
        <div class="space-y-5">

          <!-- Bill summary -->
          <div class="card space-y-3">
            <h3 class="font-semibold text-gray-800">Bill Summary</h3>
            <div class="space-y-2 text-sm">
              <div class="flex justify-between text-gray-600">
                <span>Room ({{ booking.nights }}n × {{ fmt(booking.rate_per_night) }})</span>
                <span>{{ fmt(booking.room_total) }}</span>
              </div>
              <div class="flex justify-between text-gray-600">
                <span>Extra charges</span>
                <span>{{ fmt(booking.charges_total) }}</span>
              </div>
              <div v-if="booking.service_charge > 0" class="flex justify-between text-gray-600">
                <span class="flex items-center gap-1">
                  Service charge
                  <span class="text-xs bg-gray-100 text-gray-500 rounded px-1">{{ booking.service_charge_pct }}%</span>
                </span>
                <span>{{ fmt(booking.service_charge) }}</span>
              </div>
              <div v-if="booking.discount > 0" class="flex justify-between text-green-600">
                <span>Discount</span>
                <span>−{{ fmt(booking.discount) }}</span>
              </div>
              <div class="border-t border-gray-200 pt-2 flex justify-between font-bold text-gray-900 text-base">
                <span>Total</span>
                <span>{{ fmt(booking.total) }}</span>
              </div>
              <div class="flex justify-between text-gray-500 text-xs">
                <span>Deposit paid</span>
                <span>{{ fmt(booking.deposit) }}</span>
              </div>
              <div class="flex justify-between font-semibold text-amber-700">
                <span>Balance due</span>
                <span>{{ fmt(Math.max(0, booking.total - booking.amount_paid)) }}</span>
              </div>
            </div>
          </div>

          <!-- Checkout form -->
          <div v-if="canEdit" class="card space-y-3 border-2 border-amber-300">
            <h3 class="font-semibold text-gray-800">Checkout</h3>
            <div>
              <label class="form-label">Discount (LKR)</label>
              <input v-model.number="checkoutForm.discount" type="number" min="0" class="form-input" />
            </div>
            <div>
              <label class="form-label">Amount Collected Now (LKR)</label>
              <input v-model.number="checkoutForm.amount_paid" type="number" min="0" class="form-input" />
            </div>
            <div>
              <label class="form-label">Payment Method</label>
              <select v-model="checkoutForm.payment_method" class="form-input">
                <option value="cash">Cash</option>
                <option value="card">Card</option>
                <option value="bank_transfer">Bank Transfer</option>
                <option value="other">Other</option>
              </select>
            </div>
            <p v-if="checkoutError" class="text-xs text-red-600">{{ checkoutError }}</p>
            <button @click="doCheckout" :disabled="actioning"
              class="w-full py-2.5 bg-green-600 hover:bg-green-700 disabled:opacity-50 text-white font-bold rounded-lg">
              {{ actioning ? 'Processing…' : 'Complete Checkout' }}
            </button>
          </div>

          <!-- Checked out summary -->
          <div v-if="booking.status === 'checked_out'" class="card bg-green-50 border-green-300 space-y-3">
            <div class="font-semibold text-green-700">Checked Out</div>
            <div class="text-sm text-green-600">Check-out at {{ booking.check_out_time ?? '—' }}</div>
            <div class="text-sm text-green-600">Total paid: {{ fmt(booking.amount_paid) }}</div>
            <button @click="receiptModal = true"
              class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-green-300 hover:bg-green-100 text-green-700 font-semibold rounded-lg text-sm shadow-sm">
              <PrinterIcon class="w-4 h-4" /> Print Invoice
            </button>
          </div>

        </div>
      </div>
    </template>

    <div v-else class="text-center text-gray-400 py-12">Booking not found.</div>

  </div>

  <!-- Invoice Modal -->
  <Teleport to="body">
    <div v-if="receiptModal" class="fixed inset-0 bg-black/60 flex items-center justify-center z-50 p-4" @click.self="receiptModal = false">
      <div class="bg-white rounded-2xl shadow-2xl flex flex-col" style="width:400px; max-height:90vh;">

        <!-- Modal toolbar -->
        <div class="flex items-center justify-between px-5 py-3 border-b border-gray-200 shrink-0">
          <span class="font-semibold text-gray-800">Invoice — {{ booking?.booking_number }}</span>
          <div class="flex items-center gap-2">
            <button @click="printInvoice"
              class="inline-flex items-center gap-1.5 px-4 py-1.5 bg-amber-500 hover:bg-amber-600 text-white rounded-lg text-sm font-semibold">
              <PrinterIcon class="w-4 h-4" /> Print
            </button>
            <button @click="receiptModal = false" class="text-gray-400 hover:text-gray-700 text-2xl leading-none ml-1">&times;</button>
          </div>
        </div>

        <!-- Receipt preview (scrollable) -->
        <div class="overflow-y-auto p-4 flex justify-center bg-gray-100" id="invoice-print-area">
          <div class="receipt" v-if="booking">

            <div class="r-center r-bold" style="font-size:15px;letter-spacing:1px;">{{ restaurantName }}</div>
            <div v-if="restaurantAddress" class="r-center r-small r-mt2">{{ restaurantAddress }}</div>
            <div class="r-center r-small r-mt2">HOTEL INVOICE</div>
            <div class="r-dashes r-mt4"></div>

            <div class="r-row r-mt2"><span class="r-label">Booking#</span><span class="r-value r-bold">{{ booking.booking_number }}</span></div>
            <div class="r-row"><span class="r-label">Printed</span><span class="r-value">{{ fmtNow() }}</span></div>
            <div class="r-dashes r-mt4"></div>

            <div class="r-sec r-mt2">GUEST</div>
            <div class="r-row r-mt1"><span class="r-label">Name</span><span class="r-value r-bold">{{ booking.guest_name }}</span></div>
            <div v-if="booking.guest_phone" class="r-row"><span class="r-label">Phone</span><span class="r-value">{{ booking.guest_phone }}</span></div>
            <div v-if="booking.guest_nic" class="r-row"><span class="r-label">NIC</span><span class="r-value">{{ booking.guest_nic }}</span></div>
            <div class="r-row"><span class="r-label">Persons</span><span class="r-value">{{ booking.guest_count }}</span></div>
            <div class="r-dashes r-mt4"></div>

            <div class="r-sec r-mt2">STAY</div>
            <div class="r-row r-mt1"><span class="r-label">Room</span><span class="r-value r-bold">{{ booking.room?.room_number }}{{ booking.room?.name ? ' — ' + booking.room.name : '' }}</span></div>
            <div class="r-row"><span class="r-label">Type</span><span class="r-value">{{ booking.room?.type?.replace('_','-').toUpperCase() }} / {{ cap(booking.room?.category) }}</span></div>
            <div class="r-row"><span class="r-label">Check-in</span><span class="r-value">{{ booking.check_in_date }}{{ booking.check_in_time ? ' ' + booking.check_in_time : '' }}</span></div>
            <div class="r-row"><span class="r-label">Check-out</span><span class="r-value">{{ booking.check_out_date }}{{ booking.check_out_time ? ' ' + booking.check_out_time : '' }}</span></div>
            <div class="r-row"><span class="r-label">Nights</span><span class="r-value">{{ booking.nights }}</span></div>
            <div class="r-dashes r-mt4"></div>

            <div class="r-sec r-mt2">CHARGES</div>
            <div class="r-mt1"></div>
            <div class="r-item-name r-mt1">Room Rental</div>
            <div class="r-item-row">
              <span class="r-small">{{ booking.nights }}n × LKR {{ n(booking.rate_per_night) }}</span>
              <span class="r-bold">{{ lkr(booking.room_total) }}</span>
            </div>

            <template v-if="booking.charges?.length">
              <div class="r-dashes-light r-mt3"></div>
              <div v-for="c in booking.charges" :key="c.id" class="r-mt2">
                <div class="r-item-name">{{ c.description }}</div>
                <div class="r-item-row">
                  <span class="r-small">{{ c.quantity }} × LKR {{ n(c.unit_price) }} <span class="r-muted">{{ fmtTimeShort(c.created_at) }}</span></span>
                  <span class="r-bold">{{ lkr(c.amount) }}</span>
                </div>
              </div>
            </template>

            <div class="r-dashes r-mt4"></div>
            <div class="r-row r-mt2"><span class="r-label">Room charges</span><span class="r-value">{{ lkr(booking.room_total) }}</span></div>
            <div class="r-row"><span class="r-label">Extra charges</span><span class="r-value">{{ lkr(booking.charges_total) }}</span></div>
            <div v-if="booking.service_charge > 0" class="r-row">
              <span class="r-label">Service chg ({{ booking.service_charge_pct }}%)</span>
              <span class="r-value">{{ lkr(booking.service_charge) }}</span>
            </div>
            <div v-if="booking.discount > 0" class="r-row">
              <span class="r-label">Discount</span><span class="r-value">-{{ lkr(booking.discount) }}</span>
            </div>

            <div class="r-dashes-heavy r-mt3"></div>
            <div class="r-total"><span>TOTAL</span><span>{{ lkr(booking.total) }}</span></div>
            <div class="r-dashes-heavy"></div>

            <div class="r-row r-mt2"><span class="r-label">Deposit paid</span><span class="r-value">{{ lkr(booking.deposit) }}</span></div>
            <div class="r-row"><span class="r-label">Paid at checkout</span><span class="r-value">{{ lkr(Math.max(0, booking.amount_paid - booking.deposit)) }}</span></div>
            <div class="r-row r-bold"><span class="r-label">Balance due</span><span class="r-value">{{ lkr(Math.max(0, booking.total - booking.amount_paid)) }}</span></div>
            <div class="r-row"><span class="r-label">Payment</span><span class="r-value" style="text-transform:capitalize">{{ booking.payment_method?.replace('_',' ') }}</span></div>

            <template v-if="booking.notes">
              <div class="r-dashes r-mt4"></div>
              <div class="r-small r-mt2 r-center" style="font-style:italic">{{ booking.notes }}</div>
            </template>

            <div class="r-dashes r-mt4"></div>
            <div class="r-center r-small r-mt2">Thank you for choosing</div>
            <div class="r-center r-bold r-mt1">{{ restaurantName }}</div>
            <div class="r-center r-small r-mt2">Please keep this for your records</div>
            <div style="margin-top:20px"></div>
          </div>
        </div>
      </div>
    </div>
  </Teleport>

  <!-- Cancel Booking Modal -->
  <div v-if="cancelModal.open" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md space-y-4 p-6">
      <div class="flex items-start gap-3">
        <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center shrink-0">
          <XCircleIcon class="w-5 h-5 text-red-600" />
        </div>
        <div>
          <h3 class="font-semibold text-gray-900">Cancel Booking</h3>
          <p class="text-sm text-gray-500 mt-0.5">
            Booking <span class="font-medium text-gray-700">{{ booking?.booking_number }}</span> will be cancelled and the room will be set to available.
          </p>
        </div>
      </div>
      <div>
        <label class="form-label">Reason for cancellation <span class="text-red-500">*</span></label>
        <textarea v-model="cancelModal.reason" class="form-input" rows="3"
          placeholder="e.g. Guest did not arrive, booking error…"></textarea>
      </div>
      <p v-if="cancelModal.error" class="text-xs text-red-600">{{ cancelModal.error }}</p>
      <div class="flex gap-3 pt-1">
        <button @click="confirmCancel" :disabled="cancelModal.saving || !cancelModal.reason.trim()"
          class="flex-1 py-2.5 bg-red-600 hover:bg-red-700 disabled:opacity-50 text-white font-semibold rounded-lg text-sm">
          {{ cancelModal.saving ? 'Cancelling…' : 'Confirm Cancellation' }}
        </button>
        <button @click="cancelModal.open = false; cancelModal.reason = ''"
          class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-lg text-sm">
          Keep Booking
        </button>
      </div>
    </div>
  </div>

  <!-- Remove Charge Modal -->
  <div v-if="removeModal.charge" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md space-y-4 p-6">
      <div class="flex items-start gap-3">
        <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center shrink-0">
          <TrashIcon class="w-5 h-5 text-red-600" />
        </div>
        <div>
          <h3 class="font-semibold text-gray-900">Remove Charge</h3>
          <p class="text-sm text-gray-500 mt-0.5">
            "<span class="font-medium text-gray-700">{{ removeModal.charge.description }}</span>"
            — LKR {{ Number(removeModal.charge.amount).toLocaleString() }}
          </p>
        </div>
      </div>

      <div>
        <label class="form-label">Reason for removal <span class="text-red-500">*</span></label>
        <textarea
          v-model="removeModal.reason"
          class="form-input"
          rows="3"
          placeholder="e.g. Entered by mistake, guest cancelled order…"
          ref="reasonRef"
        ></textarea>
      </div>

      <p v-if="removeModal.error" class="text-xs text-red-600">{{ removeModal.error }}</p>

      <div class="flex gap-3 pt-1">
        <button
          @click="confirmRemoveCharge"
          :disabled="removeModal.saving || !removeModal.reason.trim()"
          class="flex-1 py-2.5 bg-red-600 hover:bg-red-700 disabled:opacity-50 text-white font-semibold rounded-lg text-sm transition-colors">
          {{ removeModal.saving ? 'Removing…' : 'Remove Charge' }}
        </button>
        <button @click="removeModal.charge = null; removeModal.reason = ''"
          class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-lg text-sm">
          Cancel
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onBeforeUnmount } from 'vue'
import { useRoute } from 'vue-router'
import { ArrowLeftIcon, TrashIcon, PrinterIcon, XCircleIcon } from '@heroicons/vue/24/outline'
import axios from 'axios'

const route = useRoute()
const booking = ref(null)
const loading = ref(false)
const actioning = ref(false)
const showChargeForm = ref(false)
const savingCharge = ref(false)
const checkoutError = ref('')
const removeModal = reactive({ charge: null, reason: '', saving: false, error: '' })
const cancelModal = reactive({ open: false, reason: '', saving: false, error: '' })
const receiptModal = ref(false)
const restaurantName = ref('Hotel')
const restaurantAddress = ref('')

// Product search state
const productSearch = ref('')
const productResults = ref([])
const allProducts = ref([])
let searchTimer = null

const chargeForm = reactive({
  product_id: null, description: '', charge_type: 'food', unit_price: 0, quantity: 1,
})

const checkoutForm = reactive({
  discount: 0, amount_paid: 0, payment_method: 'cash',
})

const canEdit = computed(() => booking.value && ['reserved', 'checked_in'].includes(booking.value.status))

async function fetchBooking() {
  loading.value = true
  try {
    const [bookingRes, settingsRes] = await Promise.all([
      axios.get(`/api/hotel/bookings/${route.params.id}`),
      axios.get('/api/settings/restaurant').catch(() => ({ data: {} })),
    ])
    booking.value = bookingRes.data
    checkoutForm.discount = bookingRes.data.discount || 0
    checkoutForm.amount_paid = Math.max(0, bookingRes.data.total - bookingRes.data.amount_paid)
    restaurantName.value    = settingsRes.data?.name    || 'Hotel'
    restaurantAddress.value = settingsRes.data?.address || ''
  } finally {
    loading.value = false
  }
}

async function fetchProducts() {
  try {
    const { data } = await axios.get('/api/products', { params: { per_page: 500 } })
    allProducts.value = data.data ?? data
  } catch {}
}

function onProductSearch() {
  clearTimeout(searchTimer)
  const q = productSearch.value.trim().toLowerCase()
  if (!q) { productResults.value = []; return }
  searchTimer = setTimeout(() => {
    productResults.value = allProducts.value
      .filter(p => p.name.toLowerCase().includes(q))
      .slice(0, 8)
  }, 200)
}

function selectProduct(p) {
  chargeForm.product_id  = p.id
  chargeForm.description = p.name
  chargeForm.unit_price  = p.selling_price
  productSearch.value    = p.name
  productResults.value   = []
}

function cancelChargeForm() {
  showChargeForm.value = false
  resetChargeForm()
}

function resetChargeForm() {
  Object.assign(chargeForm, { product_id: null, description: '', charge_type: 'food', unit_price: 0, quantity: 1 })
  productSearch.value = ''
  productResults.value = []
}

async function doCheckIn() {
  actioning.value = true
  try {
    await axios.post(`/api/hotel/bookings/${booking.value.id}/check-in`)
    await fetchBooking()
  } finally {
    actioning.value = false
  }
}

async function addCharge() {
  savingCharge.value = true
  try {
    await axios.post(`/api/hotel/bookings/${booking.value.id}/charges`, chargeForm)
    showChargeForm.value = false
    resetChargeForm()
    await fetchBooking()
  } finally {
    savingCharge.value = false
  }
}

function removeCharge(charge) {
  removeModal.charge = charge
  removeModal.reason = ''
  removeModal.error  = ''
}

async function confirmRemoveCharge() {
  if (!removeModal.reason.trim()) return
  removeModal.saving = true
  removeModal.error  = ''
  try {
    await axios.delete(`/api/hotel/bookings/${booking.value.id}/charges/${removeModal.charge.id}`, {
      data: { reason: removeModal.reason.trim() }
    })
    removeModal.charge = null
    removeModal.reason = ''
    await fetchBooking()
  } catch (e) {
    removeModal.error = e.response?.data?.message || 'Failed to remove charge.'
  } finally {
    removeModal.saving = false
  }
}

async function confirmCancel() {
  if (!cancelModal.reason.trim()) return
  cancelModal.saving = true
  cancelModal.error  = ''
  try {
    await axios.post(`/api/hotel/bookings/${booking.value.id}/cancel`, { reason: cancelModal.reason.trim() })
    cancelModal.open   = false
    cancelModal.reason = ''
    await fetchBooking()
  } catch (e) {
    cancelModal.error = e.response?.data?.message || 'Failed to cancel booking.'
  } finally {
    cancelModal.saving = false
  }
}

async function doCheckout() {
  checkoutError.value = ''
  actioning.value = true
  try {
    await axios.post(`/api/hotel/bookings/${booking.value.id}/checkout`, checkoutForm)
    await fetchBooking()
    receiptModal.value = true
  } catch (e) {
    checkoutError.value = e.response?.data?.message || 'Checkout failed.'
  } finally {
    actioning.value = false
  }
}

// Close product dropdown on outside click
function onClickOutside(e) {
  if (!e.target.closest('.relative')) productResults.value = []
}

function fmt(n) { return 'LKR ' + Number(n || 0).toLocaleString('en-LK', { minimumFractionDigits: 2 }) }

function fmtTime(ts) {
  if (!ts) return ''
  const d = new Date(ts)
  return d.toLocaleString('en-GB', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit', hour12: true })
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
  return { reserved: 'Reserved', checked_in: 'Checked In', checked_out: 'Checked Out', cancelled: 'Cancelled' }[s] ?? s
}

// Receipt helpers
function n(v)   { return Number(v || 0).toLocaleString('en-LK', { minimumFractionDigits: 2 }) }
function lkr(v) { return 'LKR ' + n(v) }
function cap(s) { return s ? s.charAt(0).toUpperCase() + s.slice(1) : '' }
function fmtNow() {
  return new Date().toLocaleString('en-GB', { day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit', hour12:true })
}
function fmtTimeShort(ts) {
  if (!ts) return ''
  return new Date(ts).toLocaleString('en-GB', { day:'2-digit', month:'short', hour:'2-digit', minute:'2-digit', hour12:true })
}

function printInvoice() {
  const el = document.getElementById('invoice-print-area')
  if (!el) return
  const win = window.open('', '_blank', 'width=420,height=700')
  win.document.write(`
    <html><head><title>Invoice</title>
    <style>
      @page { size: 80mm auto; margin: 4mm 3mm; }
      body { margin:0; padding:14px 10px; font-family:'Courier New',monospace; font-size:11px; line-height:1.45; color:#000; }
      .r-center { text-align:center; }
      .r-bold { font-weight:700; }
      .r-small { font-size:10px; }
      .r-muted { color:#666; }
      .r-mt1 { margin-top:3px; } .r-mt2 { margin-top:6px; } .r-mt3 { margin-top:9px; } .r-mt4 { margin-top:12px; }
      .r-dashes { border-top:1px dashed #999; } .r-dashes-light { border-top:1px dashed #ccc; } .r-dashes-heavy { border-top:2px solid #000; }
      .r-sec { font-size:10px; font-weight:700; letter-spacing:1.5px; color:#333; }
      .r-row { display:flex; justify-content:space-between; gap:4px; font-size:11px; line-height:1.5; }
      .r-label { color:#444; flex-shrink:0; } .r-value { text-align:right; }
      .r-item-name { font-weight:700; font-size:11px; }
      .r-item-row { display:flex; justify-content:space-between; font-size:10px; color:#333; }
      .r-total { display:flex; justify-content:space-between; font-size:14px; font-weight:700; padding:4px 0; }
    </style></head><body>
    ${el.querySelector('.receipt').outerHTML}
    </body></html>
  `)
  win.document.close()
  win.focus()
  setTimeout(() => { win.print(); win.close() }, 300)
}

onMounted(() => {
  fetchBooking()
  fetchProducts()
  document.addEventListener('click', onClickOutside)
})
onBeforeUnmount(() => document.removeEventListener('click', onClickOutside))
</script>

<style scoped>
.receipt {
  width: 302px;
  background: #fff;
  padding: 14px 10px;
  font-family: 'Courier New', Courier, monospace;
  font-size: 11px;
  line-height: 1.45;
  color: #000;
}
.r-center   { text-align: center; }
.r-bold     { font-weight: 700; }
.r-small    { font-size: 10px; }
.r-muted    { color: #666; }
.r-mt1 { margin-top: 3px; }
.r-mt2 { margin-top: 6px; }
.r-mt3 { margin-top: 9px; }
.r-mt4 { margin-top: 12px; }
.r-dashes        { border-top: 1px dashed #999; }
.r-dashes-light  { border-top: 1px dashed #ccc; }
.r-dashes-heavy  { border-top: 2px solid #000; }
.r-sec { font-size: 10px; font-weight: 700; letter-spacing: 1.5px; color: #333; }
.r-row { display: flex; justify-content: space-between; gap: 4px; font-size: 11px; line-height: 1.5; }
.r-label { color: #444; flex-shrink: 0; }
.r-value { text-align: right; }
.r-item-name { font-weight: 700; font-size: 11px; }
.r-item-row  { display: flex; justify-content: space-between; font-size: 10px; color: #333; }
.r-total { display: flex; justify-content: space-between; font-size: 14px; font-weight: 700; padding: 4px 0; }
</style>
