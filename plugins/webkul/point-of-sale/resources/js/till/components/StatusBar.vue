<script setup>
import { inject, computed } from 'vue'

const till = inject('till')

const queue = till.queue.state

const pending = computed(() => queue.pending.length)

const failures = computed(() => queue.pending.filter((entry) => entry.lastError))
</script>

<template>
    <div
        v-if="queue.offline || pending > 0 || till.state.databaseUnavailable"
        class="flex flex-none flex-wrap items-center gap-2 px-4 pt-3"
    >
        <span
            v-if="queue.offline"
            class="inline-flex items-center gap-1.5 rounded-md bg-warning-50 px-2 py-1 text-xs font-medium text-warning-700 dark:bg-warning-500/15 dark:text-warning-300"
        >
            Offline — sales continue and sync when the connection returns
        </span>

        <span
            v-if="pending > 0"
            class="inline-flex items-center gap-1.5 rounded-md bg-info-50 px-2 py-1 text-xs font-medium text-info-700 dark:bg-info-500/15 dark:text-info-300"
        >
            {{ pending }} order(s) waiting to sync
        </span>

        <span
            v-if="failures.length"
            class="inline-flex items-center gap-1.5 rounded-md bg-danger-50 px-2 py-1 text-xs font-medium text-danger-700 dark:bg-danger-500/15 dark:text-danger-300"
        >
            {{ failures.length }} order(s) rejected by the server
        </span>

        <span
            v-if="till.state.databaseUnavailable"
            class="inline-flex items-center gap-1.5 rounded-md bg-danger-50 px-2 py-1 text-xs font-medium text-danger-700 dark:bg-danger-500/15 dark:text-danger-300"
        >
            Local storage unavailable — reload before taking more orders
        </span>
    </div>
</template>
