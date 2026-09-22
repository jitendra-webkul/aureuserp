<script setup>
import { inject, computed, ref, onMounted } from 'vue'

const till = inject('till')

const queue = till.queue.state

const slot = ref(null)

const pending = computed(() => queue.pending.length)

const failures = computed(() => queue.pending.filter((entry) => entry.lastError))

const badge = 'flex size-8 flex-none items-center justify-center rounded-lg'

onMounted(() => {
    slot.value = document.getElementById('pos-status-slot')
})
</script>

<template>
    <Teleport v-if="slot" :to="slot">
        <span
            v-if="queue.offline"
            :class="`${badge} text-danger-600 dark:text-danger-400`"
            :title="till.t('offline.banner')"
            :aria-label="till.t('offline.banner')"
        >
            <svg class="size-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path d="M3.28 2.22a.75.75 0 1 0-1.06 1.06l14.5 14.5a.75.75 0 1 0 1.06-1.06l-2.3-2.3a.75.75 0 0 0-.08-.1L4.4 3.22a.75.75 0 0 0-.1-.08l-1.02-.92ZM10 15.5a1.25 1.25 0 1 0 0 2.5 1.25 1.25 0 0 0 0-2.5ZM1.2 6.79a.75.75 0 0 0 .04 1.06l1.1 1.02a.75.75 0 0 0 1.02-.04 9.2 9.2 0 0 1 2.1-1.55L4.3 6.1A10.8 10.8 0 0 0 2.26 7.5a.75.75 0 0 0-1.06-.71ZM10 4.5c2.2 0 4.24.76 5.85 2.04l1.1-1.02a.75.75 0 0 0-.04-1.06A11.2 11.2 0 0 0 10 3c-.98 0-1.94.12-2.85.36l1.3 1.3c.51-.1 1.03-.16 1.55-.16ZM6.9 10.7l1.28 1.28a3.2 3.2 0 0 1 3.64 0l1.28-1.28a5 5 0 0 0-6.2 0Z" />
            </svg>
        </span>

        <span
            v-if="pending > 0"
            :class="`${badge} relative text-info-600 dark:text-info-400`"
            :title="till.t('offline.waiting', { count: pending })"
            :aria-label="till.t('offline.waiting', { count: pending })"
        >
            <svg class="size-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M15.31 5.3a.75.75 0 0 1 .06 1.06l-1.02 1.15a5.5 5.5 0 1 0 1.13 2.1.75.75 0 1 1 1.44-.42A7 7 0 1 1 15.1 5.24a.75.75 0 0 1 .2.06Zm-1.6-1.55a.75.75 0 0 1 .75.75v2.5a.75.75 0 0 1-.75.75h-2.5a.75.75 0 0 1 0-1.5h1.75V4.5a.75.75 0 0 1 .75-.75Z" clip-rule="evenodd" />
            </svg>

            <span class="absolute -end-0.5 -top-0.5 flex min-w-4 justify-center rounded-full bg-info-600 px-1 text-[0.625rem] font-semibold leading-4 text-white">
                {{ pending }}
            </span>
        </span>

        <span
            v-if="failures.length"
            :class="`${badge} text-danger-600 dark:text-danger-400`"
            :title="till.t('offline.rejected', { count: failures.length })"
            :aria-label="till.t('offline.rejected', { count: failures.length })"
        >
            <svg class="size-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495ZM10 5a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5A.75.75 0 0 1 10 5Zm0 9a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" />
            </svg>
        </span>

        <span
            v-if="till.state.databaseUnavailable"
            :class="`${badge} text-danger-600 dark:text-danger-400`"
            :title="till.t('offline.storage-unavailable')"
            :aria-label="till.t('offline.storage-unavailable')"
        >
            <svg class="size-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path d="M10 1c-3.87 0-7 1.12-7 2.5S6.13 6 10 6s7-1.12 7-2.5S13.87 1 10 1ZM3 6.4v2.1C3 9.88 6.13 11 10 11s7-1.12 7-2.5V6.4C15.6 7.4 13 8 10 8S4.4 7.4 3 6.4ZM3 11.4v2.1C3 14.88 6.13 16 10 16s7-1.12 7-2.5v-2.1C15.6 12.4 13 13 10 13s-5.6-.6-7-1.6Z" />
            </svg>
        </span>
    </Teleport>
</template>
