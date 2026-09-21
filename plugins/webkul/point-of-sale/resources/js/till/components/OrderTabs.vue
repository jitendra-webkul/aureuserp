<script setup>
import { inject, computed } from 'vue'

const till = inject('till')

const state = till.state

const drafts = computed(() => till.drafts)

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
            v-for="order in visible"
            :key="order.uuid"
            type="button"
            class="flex min-w-0 flex-1 flex-col rounded-lg border px-2.5 py-1 text-start leading-tight transition-colors"
            :class="state.activeOrderUuid === order.uuid
                ? 'border-primary-600 bg-primary-50 dark:border-primary-500 dark:bg-primary-500/15'
                : 'border-gray-200 bg-white hover:border-gray-300 dark:hover:border-gray-600 dark:border-gray-700 dark:bg-gray-900'"
            @click="till.selectOrder(order.uuid)"
        >
            <span class="truncate text-xs font-semibold leading-tight text-gray-950 dark:text-white">
                {{ till.orderLabel(order) }}
            </span>

            <span class="truncate font-mono text-[0.625rem] leading-tight tabular-nums text-gray-500 dark:text-gray-400">
                {{ summary(order) }}
            </span>
        </button>

        <button
            v-if="overflow > 0"
            type="button"
            class="flex flex-none flex-col rounded-lg border border-gray-200 bg-white px-2.5 py-1 text-start leading-tight transition-colors hover:border-gray-300 dark:hover:border-gray-600 dark:border-gray-700 dark:bg-gray-900"
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
