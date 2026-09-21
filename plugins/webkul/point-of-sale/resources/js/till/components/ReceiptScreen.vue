<script setup>
import { inject, computed } from 'vue'

const till = inject('till')

const order = computed(() => till.activeOrder)

const totals = computed(() => till.orderTotals())

const company = computed(() => till.boot.company ?? {})

const payments = computed(() =>
    (order.value?.payments ?? [])
        .filter((payment) => ! payment.is_change)
        .map((payment) => ({
            uuid: payment.uuid,
            name: till.master.payment_methods.get(payment.payment_method_id)?.name ?? '',
            amount: Number(payment.amount ?? 0),
        })),
)

const orderedAt = computed(() => {
    const value = order.value?.created_at

    return value ? new Date(value).toLocaleString() : ''
})

function productName(productId) {
    return till.master.products.get(productId)?.name ?? ''
}

function uomName(line) {
    return till.master.uoms.get(line.uom_id)?.name ?? ''
}

function print() {
    window.pointOfSaleReceipt.print('.pos-receipt')
}
</script>

<template>
    <div class="flex min-h-0 flex-col gap-3">
        <div class="min-h-0 flex-auto overflow-y-auto rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
            <div class="pos-receipt mx-auto w-[80mm] max-w-full p-4 text-gray-950 dark:text-white">
                <div class="text-center">
                    <img
                        v-if="company.logo"
                        :src="company.logo"
                        :alt="company.name"
                        class="mx-auto mb-2 max-h-16 object-contain"
                    />

                    <p class="text-lg font-bold">{{ company.name }}</p>

                    <p v-if="company.phone" class="text-xs">{{ till.t('receipt.phone') }} {{ company.phone }}</p>

                    <p v-if="company.email" class="text-xs">{{ company.email }}</p>

                    <p v-if="company.website" class="text-xs underline">{{ company.website }}</p>
                </div>

                <p v-if="till.config.receipt_header" class="mt-2 whitespace-pre-line text-center text-xs">
                    {{ till.config.receipt_header }}
                </p>

                <p v-if="till.boot.session.user_name" class="mt-2 text-center text-xs font-semibold">
                    {{ till.t('receipt.served-by', { cashier: till.boot.session.user_name }) }}
                </p>

                <p v-if="order?.tracking_number" class="text-center text-3xl font-light leading-tight">
                    {{ order.tracking_number }}
                </p>

                <p v-if="order?.partner_id" class="mt-2 text-center text-xs">
                    {{ till.master.partners.get(order.partner_id)?.name }}
                </p>

                <div class="mt-3 space-y-1">
                    <div v-for="line in order?.lines ?? []" :key="line.uuid">
                        <div class="flex items-start justify-between gap-2 text-sm font-bold">
                            <span class="min-w-0 flex-1">{{ productName(line.product_id) }}</span>
                            <span class="flex-none tabular-nums">{{ till.money(till.displayLineTotal(line)) }}</span>
                        </div>

                        <div class="text-xs tabular-nums">
                            {{ line.qty }} &times; {{ till.money(line.price_unit) }}<template v-if="uomName(line)"> / {{ uomName(line) }}</template><template v-if="line.discount"> &middot; &minus;{{ till.t('cart.discount', { percentage: line.discount }) }}</template>
                        </div>

                        <div v-if="line.customer_note" class="text-xs italic">{{ line.customer_note }}</div>
                    </div>
                </div>

                <div class="my-2 border-t border-dashed border-gray-300 dark:border-gray-700"></div>

                <div class="flex items-center justify-between text-sm">
                    <span>{{ till.t('receipt.untaxed') }}</span>
                    <span class="tabular-nums">{{ till.money(totals.subtotal) }}</span>
                </div>

                <div
                    v-for="tax in totals.breakdown"
                    :key="tax.id"
                    class="flex items-center justify-between text-sm"
                >
                    <span>{{ tax.name }}</span>
                    <span class="tabular-nums">{{ till.money(tax.amount) }}</span>
                </div>

                <div v-if="totals.rounding" class="flex items-center justify-between text-sm">
                    <span>{{ till.t('receipt.rounding') }}</span>
                    <span class="tabular-nums">{{ till.money(totals.rounding) }}</span>
                </div>

                <div class="my-2 border-t border-dashed border-gray-300 dark:border-gray-700"></div>

                <div class="flex items-center justify-between text-lg font-bold">
                    <span>{{ till.t('receipt.total') }}</span>
                    <span class="tabular-nums">{{ till.money(totals.total) }}</span>
                </div>

                <div
                    v-for="payment in payments"
                    :key="payment.uuid"
                    class="flex items-center justify-between text-sm"
                >
                    <span>{{ payment.name }}</span>
                    <span class="tabular-nums">{{ till.money(payment.amount) }}</span>
                </div>

                <div v-if="totals.change > 0" class="flex items-center justify-between text-sm font-semibold">
                    <span>{{ till.t('receipt.change') }}</span>
                    <span class="tabular-nums">{{ till.money(totals.change) }}</span>
                </div>

                <p v-if="till.config.receipt_footer" class="mt-3 whitespace-pre-line text-center text-xs">
                    {{ till.config.receipt_footer }}
                </p>

                <div class="mt-3 text-center text-xs">
                    <p>{{ till.t('receipt.order', { order: order?.pos_reference }) }}</p>
                    <p>{{ orderedAt }}</p>
                </div>
            </div>
        </div>

        <div class="flex flex-none gap-2">
            <button
                type="button"
                class="flex min-h-12 flex-1 items-center justify-center rounded-lg border border-gray-200 bg-white text-sm font-medium text-gray-600 hover:bg-gray-50 dark:hover:bg-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                @click="print()"
            >
                {{ till.t('common.print') }}
            </button>

            <button
                type="button"
                class="flex min-h-12 flex-2 items-center justify-center rounded-lg bg-primary-600 text-sm font-semibold text-white hover:bg-primary-700"
                @click="till.newOrder()"
            >
                {{ till.t('receipt.new-order') }}
            </button>
        </div>
    </div>
</template>
