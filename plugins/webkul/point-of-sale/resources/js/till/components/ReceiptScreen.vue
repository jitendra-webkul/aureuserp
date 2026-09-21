<script setup>
import { inject, computed } from 'vue'

const till = inject('till')

const order = computed(() => till.activeOrder)

const totals = computed(() => till.orderTotals())

function productName(productId) {
    return till.master.products.get(productId)?.name ?? ''
}

function print() {
    window.print()
}
</script>

<template>
    <div class="flex min-h-0 flex-col gap-3">
        <div class="min-h-0 flex-auto overflow-y-auto rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-900">
            <p v-if="till.config.receipt_header" class="mb-4 whitespace-pre-line text-center text-sm text-gray-600 dark:text-gray-300">
                {{ till.config.receipt_header }}
            </p>

            <p class="text-center font-mono text-xs tabular-nums text-gray-400">
                {{ order?.pos_reference }}
            </p>

            <div class="my-4 border-t border-dashed border-gray-200 dark:border-gray-700"></div>

            <div
                v-for="line in order?.lines ?? []"
                :key="line.uuid"
                class="flex items-start justify-between gap-3 py-1 text-sm"
            >
                <span class="min-w-0 flex-1">
                    <span class="block truncate text-gray-950 dark:text-white">{{ productName(line.product_id) }}</span>
                    <span class="block font-mono text-xs tabular-nums text-gray-500">
                        {{ line.qty }} &times; {{ till.money(line.price_unit) }}<template v-if="line.discount"> &middot; &minus;{{ line.discount }}% discount</template>
                    </span>
                </span>

                <span class="flex-none font-mono tabular-nums text-gray-950 dark:text-white">
                    {{ till.money(till.displayLineTotal(line)) }}
                </span>
            </div>

            <div class="my-4 border-t border-dashed border-gray-200 dark:border-gray-700"></div>

            <div class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400">
                <span>Subtotal</span>
                <span class="font-mono tabular-nums">{{ till.money(totals.subtotal) }}</span>
            </div>

            <div
                v-for="tax in totals.breakdown"
                :key="tax.id"
                class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400"
            >
                <span>{{ tax.name }}</span>
                <span class="font-mono tabular-nums">{{ till.money(tax.amount) }}</span>
            </div>

            <div class="mt-2 flex items-center justify-between border-t border-gray-100 pt-2 text-lg font-semibold text-gray-950 dark:border-gray-800 dark:text-white">
                <span>Total</span>
                <span class="font-mono tabular-nums">{{ till.money(totals.total) }}</span>
            </div>

            <div v-if="totals.change > 0" class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400">
                <span>Change</span>
                <span class="font-mono tabular-nums">{{ till.money(totals.change) }}</span>
            </div>

            <p v-if="till.config.receipt_footer" class="mt-4 whitespace-pre-line text-center text-sm text-gray-600 dark:text-gray-300">
                {{ till.config.receipt_footer }}
            </p>
        </div>

        <div class="flex flex-none gap-2">
            <button
                type="button"
                class="flex min-h-12 flex-1 items-center justify-center rounded-lg border border-gray-200 bg-white text-sm font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                @click="print()"
            >
                Print
            </button>

            <button
                type="button"
                class="flex min-h-12 flex-2 items-center justify-center rounded-lg bg-primary-600 text-sm font-semibold text-white hover:bg-primary-700"
                @click="till.newOrder()"
            >
                New order
            </button>
        </div>
    </div>
</template>
