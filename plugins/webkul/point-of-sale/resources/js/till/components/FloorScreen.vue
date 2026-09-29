<script setup>
import { inject, computed } from 'vue'

const till = inject('till')

const floor = computed(() => till.activeFloor)

const tables = computed(() => floor.value?.tables ?? [])

const isMapped = computed(() => tables.value.some((table) => table.position_h > 0 || table.position_v > 0))

const canvas = computed(() => {
    const width = Math.max(0, ...tables.value.map((table) => table.position_h + table.width))
    const height = Math.max(0, ...tables.value.map((table) => table.position_v + table.height))

    const style = {
        width: `${width + 24}px`,
        height: `${height + 24}px`,
    }

    if (floor.value?.background_color) {
        style.backgroundColor = floor.value.background_color
    }

    if (floor.value?.background_image) {
        style.backgroundImage = `url("${floor.value.background_image}")`
    }

    return style
})

const summaries = computed(() => new Map(tables.value.map((table) => [table.id, till.tableSummary(table.id)])))

function summary(table) {
    return summaries.value.get(table.id) ?? { count: 0, total: 0, guests: 0 }
}

function placement(table) {
    const style = {
        left: `${table.position_h}px`,
        top: `${table.position_v}px`,
        width: `${table.width}px`,
        height: `${table.height}px`,
    }

    if (table.color) {
        style.backgroundColor = table.color
    }

    return style
}

function tileClass(table) {
    const shape = table.shape === 'round' ? 'rounded-full' : 'rounded-xl'

    const occupied = summary(table).count > 0

    if (table.color) {
        return `${shape} border-2 text-white ${occupied ? 'border-primary-500 ring-4 ring-primary-500/40' : 'border-transparent'}`
    }

    return occupied
        ? `${shape} border-2 border-primary-500 bg-primary-50 text-primary-900 ring-4 ring-primary-500/20 dark:bg-primary-500/15 dark:text-primary-100`
        : `${shape} border-2 border-gray-300 bg-white text-gray-900 hover:border-gray-400 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 dark:hover:border-gray-500`
}
</script>

<template>
    <div class="flex min-h-0 flex-col gap-3">
        <div class="flex flex-none items-center gap-2">
            <div class="flex min-w-0 flex-1 items-center gap-2 overflow-x-auto">
                <button
                    v-for="entry in till.floors"
                    :key="entry.id"
                    type="button"
                    class="flex-none rounded-lg border px-4 py-2 text-sm font-semibold transition-colors"
                    :class="floor?.id === entry.id
                        ? 'border-primary-600 bg-primary-600 text-white dark:border-primary-500'
                        : 'border-gray-200 bg-white text-gray-700 hover:border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:border-gray-600'"
                    @click="till.selectFloor(entry.id)"
                >
                    {{ entry.name }}
                </button>
            </div>

            <button
                v-if="till.parkedOrders.length"
                type="button"
                class="flex flex-none items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 transition-colors hover:border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:border-gray-600"
                @click="till.openOrders()"
            >
                {{ till.t('tabs.all') }}

                <span class="rounded-full bg-gray-100 px-2 font-mono text-xs tabular-nums dark:bg-gray-800">{{ till.parkedOrders.length }}</span>
            </button>
        </div>

        <div class="min-h-0 flex-auto overflow-auto rounded-xl border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-950">
            <p v-if="!tables.length" class="p-10 text-center text-sm text-gray-500 dark:text-gray-400">
                {{ till.t('floor.empty') }}
            </p>

            <div v-else-if="isMapped" class="relative m-3 bg-cover bg-center bg-no-repeat" :style="canvas">
                <button
                    v-for="table in tables"
                    :key="table.id"
                    type="button"
                    class="absolute flex flex-col items-center justify-center gap-0.5 p-1 text-center shadow-sm transition-colors"
                    :class="tileClass(table)"
                    :style="placement(table)"
                    @click="till.openTable(table.id)"
                >
                    <span class="text-base font-bold leading-none">{{ table.table_number }}</span>

                    <span v-if="summary(table).count" class="font-mono text-[0.625rem] leading-none tabular-nums">
                        {{ till.money(summary(table).total) }}
                    </span>

                    <span v-else class="text-[0.625rem] leading-none opacity-70">
                        {{ till.choice('floor.seats', table.seats) }}
                    </span>
                </button>
            </div>

            <div v-else class="grid grid-cols-[repeat(auto-fill,minmax(8rem,1fr))] gap-3 p-3">
                <button
                    v-for="table in tables"
                    :key="table.id"
                    type="button"
                    class="flex aspect-square flex-col items-center justify-center gap-1 p-2 text-center shadow-sm transition-colors"
                    :class="tileClass(table)"
                    :style="table.color ? { backgroundColor: table.color } : null"
                    @click="till.openTable(table.id)"
                >
                    <span class="text-2xl font-bold leading-none">{{ table.table_number }}</span>

                    <span class="text-xs opacity-70">{{ till.choice('floor.seats', table.seats) }}</span>

                    <span v-if="summary(table).count" class="font-mono text-sm font-semibold tabular-nums">
                        {{ till.money(summary(table).total) }}
                    </span>

                    <span v-if="summary(table).count > 1" class="text-xs">
                        {{ till.choice('floor.orders', summary(table).count) }}
                    </span>

                    <span v-if="summary(table).guests" class="text-xs">
                        {{ till.choice('floor.guests', summary(table).guests) }}
                    </span>
                </button>
            </div>
        </div>
    </div>
</template>
