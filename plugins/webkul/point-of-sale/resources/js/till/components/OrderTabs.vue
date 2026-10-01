<script setup>
import { inject, computed } from 'vue'
import OrderTableBadge from './OrderTableBadge.vue'

const till = inject('till')

const state = till.state

const drafts = computed(() => till.parkedOrders)

const table = computed(() => till.activeTable)

const visible = computed(() => {
    const all = drafts.value

    if (all.length <= 4) {
        return all
    }

    const active = all.find((order) => order.uuid === state.activeOrderUuid)

    const others = all.filter((order) => order.uuid !== active?.uuid)

    return active ? [active, ...others].slice(0, 3) : others.slice(0, 3)
})

const overflow = computed(() => drafts.value.length - visible.value.length)

function summary(order) {
    return `${till.orderQuantity(order)} · ${till.money(till.orderTotals(order).total)}`
}
</script>

<template>
    <div class="flex flex-none items-stretch gap-2">
        <button
            v-if="till.isRestaurant"
            type="button"
            class="flex flex-none items-center gap-1.5 rounded-lg border border-primary-600 bg-primary-600 px-2.5 text-xs font-semibold text-white transition-colors hover:bg-primary-700 dark:border-primary-500"
            :title="till.t('floor.back')"
            @click="till.goToFloor()"
        >
            <svg class="size-4 flex-none" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M4.25 2A2.25 2.25 0 0 0 2 4.25v2.5A2.25 2.25 0 0 0 4.25 9h2.5A2.25 2.25 0 0 0 9 6.75v-2.5A2.25 2.25 0 0 0 6.75 2h-2.5Zm0 9A2.25 2.25 0 0 0 2 13.25v2.5A2.25 2.25 0 0 0 4.25 18h2.5A2.25 2.25 0 0 0 9 15.75v-2.5A2.25 2.25 0 0 0 6.75 11h-2.5Zm9-9A2.25 2.25 0 0 0 11 4.25v2.5A2.25 2.25 0 0 0 13.25 9h2.5A2.25 2.25 0 0 0 18 6.75v-2.5A2.25 2.25 0 0 0 15.75 2h-2.5Zm0 9A2.25 2.25 0 0 0 11 13.25v2.5A2.25 2.25 0 0 0 13.25 18h2.5A2.25 2.25 0 0 0 18 15.75v-2.5A2.25 2.25 0 0 0 15.75 11h-2.5Z" clip-rule="evenodd" />
            </svg>

            <span class="truncate">{{ table ? till.tableLabel(table) : till.t('floor.back') }}</span>
        </button>

        <button
            v-if="till.isRestaurant"
            type="button"
            class="flex flex-none items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-2.5 text-xs font-semibold text-gray-700 transition-colors hover:border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:border-gray-600"
            :title="till.t('table-selector.label')"
            @click="till.openTableSelector()"
        >
            <svg class="size-4 flex-none" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11ZM2 9a7 7 0 1 1 12.452 4.391l3.328 3.329a.75.75 0 1 1-1.06 1.06l-3.329-3.328A7 7 0 0 1 2 9Z" clip-rule="evenodd" />
            </svg>

            <span class="truncate">{{ till.t('table-selector.label') }}</span>
        </button>

        <button
            v-for="order in visible"
            :key="order.uuid"
            type="button"
            class="flex min-w-0 flex-1 flex-col gap-0.5 rounded-lg border px-2.5 py-1 text-start leading-tight transition-colors"
            :class="state.activeOrderUuid === order.uuid
                ? 'border-primary-600 bg-primary-50 dark:border-primary-500 dark:bg-primary-500/15'
                : 'border-gray-200 bg-white hover:border-gray-300 dark:hover:border-gray-600 dark:border-gray-700 dark:bg-gray-900'"
            @click="till.selectOrder(order.uuid)"
        >
            <span class="flex min-w-0 items-center gap-1 text-xs font-semibold leading-tight text-gray-950 dark:text-white">
                <OrderTableBadge :order="order" />

                <span class="truncate">{{ till.orderLabel(order) }}</span>
            </span>

            <span class="truncate font-mono text-[0.625rem] leading-tight tabular-nums text-gray-500 dark:text-gray-400">
                {{ summary(order) }}
            </span>
        </button>

        <button
            v-if="overflow > 0"
            type="button"
            class="flex flex-none flex-col gap-0.5 rounded-lg border border-gray-200 bg-white px-2.5 py-1 text-start leading-tight transition-colors hover:border-gray-300 dark:hover:border-gray-600 dark:border-gray-700 dark:bg-gray-900"
            @click="till.openOrders()"
        >
            <span class="truncate text-xs font-semibold leading-tight text-gray-950 dark:text-white">
                {{ till.t('common.more', { count: overflow }) }}
            </span>

            <span class="truncate text-[0.625rem] leading-tight text-gray-500 dark:text-gray-400">
                {{ till.t('tabs.view-all') }}
            </span>
        </button>

        <button
            v-else-if="drafts.length > 0"
            type="button"
            class="flex w-11 flex-none items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition-colors hover:border-gray-300 dark:hover:border-gray-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
            :title="till.t('tabs.all')"
            :aria-label="till.t('tabs.all')"
            @click="till.openOrders()"
        >
            <svg class="size-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M2 4.75A.75.75 0 0 1 2.75 4h14.5a.75.75 0 0 1 0 1.5H2.75A.75.75 0 0 1 2 4.75Zm0 5A.75.75 0 0 1 2.75 9h14.5a.75.75 0 0 1 0 1.5H2.75A.75.75 0 0 1 2 9.75Zm0 5a.75.75 0 0 1 .75-.75h14.5a.75.75 0 0 1 0 1.5H2.75a.75.75 0 0 1-.75-.75Z" clip-rule="evenodd" />
            </svg>
        </button>

        <button
            type="button"
            class="flex w-11 flex-none items-center justify-center rounded-lg border border-dashed border-gray-200 bg-transparent text-lg text-gray-600 transition-colors hover:border-gray-300 dark:hover:border-gray-600 dark:border-gray-700 dark:text-gray-300"
            :title="till.t('tabs.new')"
            :aria-label="till.t('tabs.new')"
            @click="till.newOrder()"
        >
            +
        </button>
    </div>
</template>
