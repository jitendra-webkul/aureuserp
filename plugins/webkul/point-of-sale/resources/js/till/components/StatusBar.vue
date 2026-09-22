<script setup>
import { inject, computed, ref, onMounted } from 'vue'

const till = inject('till')

const queue = till.queue.state

const slot = ref(null)

const pending = computed(() => queue.pending.length)

const failures = computed(() => queue.pending.filter((entry) => entry.lastError))

const badge = 'flex size-9 flex-none items-center justify-center rounded-lg'

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
            <svg
                class="size-5"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.5"
                aria-hidden="true"
            >
                <path stroke-linecap="round" stroke-linejoin="round" d="m3 3 8.735 8.735m0 0a.374.374 0 1 1 .53.53m-.53-.53.53.53m0 0L21 21M14.652 9.348a3.75 3.75 0 0 1 0 5.304m2.121-7.425a6.75 6.75 0 0 1 0 9.546m2.121-11.667c3.808 3.807 3.808 9.98 0 13.788m-9.546-4.242a3.733 3.733 0 0 1-1.06-2.122m-1.061 4.243a6.75 6.75 0 0 1-1.625-6.929m-.496 9.05c-3.068-3.067-3.664-7.67-1.79-11.334M12 12h.008v.008H12V12Z" />
            </svg>
        </span>

        <span
            v-if="pending > 0"
            :class="`${badge} relative text-info-600 dark:text-info-400`"
            :title="till.t('offline.waiting', { count: pending })"
            :aria-label="till.t('offline.waiting', { count: pending })"
        >
            <svg
                class="size-5"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.5"
                aria-hidden="true"
            >
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
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
            <svg
                class="size-5"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.5"
                aria-hidden="true"
            >
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
            </svg>
        </span>

        <span
            v-if="till.state.databaseUnavailable"
            :class="`${badge} text-danger-600 dark:text-danger-400`"
            :title="till.t('offline.storage-unavailable')"
            :aria-label="till.t('offline.storage-unavailable')"
        >
            <svg
                class="size-5"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.5"
                aria-hidden="true"
            >
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75m16.5 0v3.75C20.25 16.153 16.556 18 12 18s-8.25-1.847-8.25-4.125v-3.75" />
            </svg>
        </span>
    </Teleport>
</template>
