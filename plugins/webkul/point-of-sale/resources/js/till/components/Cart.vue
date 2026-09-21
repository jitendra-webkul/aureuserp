<script setup>
import { inject, computed } from 'vue'

const till = inject('till')

const state = till.state

const order = computed(() => till.activeOrder)

const totals = computed(() => till.orderTotals())

const paying = computed(() => state.screen === 'payment')

const methods = computed(() => till.master.payment_methods.all())

function productName(productId) {
    return till.master.products.get(productId)?.name ?? ''
}

function productImage(productId) {
    if (!till.config.show_product_images) {
        return null
    }

    return till.master.products.get(productId)?.image ?? null
}
</script>

<template>
    <div class="flex min-h-0 flex-col overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
        <div v-if="paying" class="flex min-h-[clamp(4rem,12vh,7rem)] flex-auto flex-col gap-2 overflow-y-auto p-3">
            <button
                v-for="method in methods"
                :key="method.id"
                type="button"
                class="flex min-h-14 flex-none items-center gap-3 rounded-lg border border-gray-200 bg-white px-4 text-start text-base font-medium text-gray-950 transition-colors hover:border-primary-400 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                @click="till.addPayment(method.id)"
            >
                <svg class="size-6 flex-none text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" />
                </svg>

                <span class="truncate">{{ method.name }}</span>
            </button>
        </div>

        <div v-else class="min-h-[clamp(4rem,12vh,7rem)] flex-auto overflow-y-auto">
            <p v-if="!order?.lines.length" class="p-6 text-center text-sm text-gray-500 dark:text-gray-400">
                {{ till.t('cart.empty.description') }}
            </p>

            <div
                v-for="line in order?.lines ?? []"
                :key="line.uuid"
                class="flex w-full items-start gap-2 border-b border-gray-100 px-3 py-2.5 text-start transition-colors dark:border-gray-800"
                :class="state.activeLineUuid === line.uuid ? 'bg-primary-50 dark:bg-primary-500/10' : 'hover:bg-gray-50 dark:hover:bg-gray-800'"
            >
                <button
                    type="button"
                    class="flex min-w-0 flex-1 items-start gap-3 text-start"
                    @click="till.selectLine(line.uuid)"
                >
                <span
                    v-if="till.config.show_product_images"
                    class="flex size-10 flex-none items-center justify-center overflow-hidden rounded-lg bg-gray-100 dark:bg-gray-800"
                >
                    <img
                        v-if="productImage(line.product_id)"
                        :src="productImage(line.product_id)"
                        :alt="productName(line.product_id)"
                        class="h-full w-full object-cover"
                    >

                    <svg v-else class="size-5 text-gray-300 dark:text-gray-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
                    </svg>
                </span>

                <span class="min-w-0 flex-1">
                    <span class="block truncate text-sm font-medium text-gray-950 dark:text-white">
                        {{ productName(line.product_id) }}
                    </span>

                    <span class="block font-mono text-xs tabular-nums text-gray-500 dark:text-gray-400">
                        {{ line.qty }} &times; {{ till.money(line.price_unit) }}
                        <template v-if="line.discount"> &middot; &minus;{{ till.t('cart.discount', { percentage: line.discount }) }}</template>
                    </span>

                    <span v-if="line.note" class="mt-0.5 flex flex-wrap items-center gap-1">
                        <span
                            v-for="(part, index) in till.noteParts(line.note)"
                            :key="index"
                            class="inline-flex max-w-full truncate rounded px-1.5 py-0.5 text-[0.6875rem] font-medium leading-tight"
                            :class="part.color ? '' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300'"
                            :style="part.color ? { backgroundColor: `${part.color}26`, color: part.color } : null"
                        >
                            {{ part.text }}
                        </span>
                    </span>
                </span>

                <span class="flex-none font-mono text-sm tabular-nums font-semibold text-gray-950 dark:text-white">
                    {{ till.money(till.displayLineTotal(line)) }}
                </span>
                </button>

                <button
                    type="button"
                    class="flex size-9 flex-none items-center justify-center rounded-lg text-gray-400 transition active:bg-danger-100 active:text-danger-700 hover:bg-danger-50 hover:text-danger-600 dark:hover:bg-danger-500/10 dark:hover:text-danger-400 dark:active:bg-danger-500/20"
                    :aria-label="till.t('cart.remove', { product: productName(line.product_id) })"
                    @click.stop="till.removeLine(line.uuid)"
                >
                    <svg class="size-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
                    </svg>
                </button>
            </div>
        </div>

        <div class="flex-none border-t border-gray-100 p-[clamp(0.5rem,1.2vh,0.75rem)] dark:border-gray-800">
            <div class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400">
                <span>{{ till.t('cart.subtotal') }}</span>
                <span class="font-mono tabular-nums">{{ till.money(totals.subtotal) }}</span>
            </div>

            <div class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400">
                <span>{{ till.t('cart.tax') }}</span>
                <span class="font-mono tabular-nums">{{ till.money(totals.tax) }}</span>
            </div>

            <div v-if="totals.rounding" class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400">
                <span>{{ till.t('cart.rounding') }}</span>
                <span class="font-mono tabular-nums">{{ till.money(totals.rounding) }}</span>
            </div>

            <div class="mt-1.5 flex items-center justify-between border-t border-gray-100 pt-1.5 text-lg font-semibold text-gray-950 dark:border-gray-800 dark:text-white">
                <span>{{ till.t('cart.total') }}</span>
                <span class="font-mono tabular-nums">{{ till.money(totals.total) }}</span>
            </div>
        </div>
    </div>
</template>
