<script setup>
import { inject, ref, watch } from 'vue'
import TillModal from './TillModal.vue'

const till = inject('till')

const state = till.state

const confirmingCancel = ref(false)

watch(() => state.actionsModalOpen, (open) => {
    if (!open) {
        confirmingCancel.value = false
    }
})

const tile = 'flex min-h-20 flex-col items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-4 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 active:bg-gray-100 disabled:text-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800 dark:active:bg-gray-700 dark:disabled:text-gray-600'

const danger = 'flex min-h-20 flex-col items-center justify-center gap-2 rounded-lg border border-danger-200 bg-danger-50 px-3 py-4 text-sm font-medium text-danger-700 transition-colors hover:bg-danger-100 active:bg-danger-200 dark:border-danger-500/30 dark:bg-danger-500/10 dark:text-danger-300 dark:hover:bg-danger-500/20 dark:active:bg-danger-500/30'
</script>

<template>
    <TillModal :open="state.actionsModalOpen" width="max-w-2xl" @close="till.closeActions()">
        <div class="flex flex-none items-center justify-between gap-2 border-b border-gray-100 p-4 dark:border-gray-800">
            <p class="text-base font-semibold text-gray-950 dark:text-white">{{ till.t('actions.heading') }}</p>

            <button
                type="button"
                class="flex-none rounded-lg px-3 py-2 text-sm text-gray-500 hover:bg-gray-50 active:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800 dark:active:bg-gray-700"
                @click="till.closeActions()"
            >
                {{ till.t('common.close') }}
            </button>
        </div>

        <div class="grid grid-cols-2 gap-3 p-4 sm:grid-cols-3">
            <button
                v-if="till.canSelectPriceList()"
                type="button"
                :class="tile"
                @click="till.openPriceLists()"
            >
                <svg class="size-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M2 4.75A.75.75 0 0 1 2.75 4h14.5a.75.75 0 0 1 0 1.5H2.75A.75.75 0 0 1 2 4.75Zm0 5A.75.75 0 0 1 2.75 9h14.5a.75.75 0 0 1 0 1.5H2.75A.75.75 0 0 1 2 9.75Zm0 5a.75.75 0 0 1 .75-.75h14.5a.75.75 0 0 1 0 1.5H2.75a.75.75 0 0 1-.75-.75Z" clip-rule="evenodd" />
                </svg>

                <span>{{ till.t('price-lists.label') }}</span>

                <span class="truncate text-xs font-normal text-gray-500 dark:text-gray-400">
                    {{ till.activePriceList?.name ?? till.t('price-lists.default') }}
                </span>
            </button>

            <button
                v-if="!confirmingCancel"
                type="button"
                :class="danger"
                @click="confirmingCancel = true"
            >
                <svg class="size-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 0 0 6 3.75v.443A24 24 0 0 0 3.32 4.5a.75.75 0 0 0 .06 1.499l.46-.02.613 8.58A3 3 0 0 0 7.445 17.5h5.11a3 3 0 0 0 2.992-2.94l.613-8.581.46.02a.75.75 0 1 0 .06-1.5 24 24 0 0 0-2.68-.306V3.75A2.75 2.75 0 0 0 11.25 1h-2.5ZM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325A42 42 0 0 1 10 4Z" clip-rule="evenodd" />
                </svg>

                <span>{{ till.t('actions.cancel-order.label') }}</span>
            </button>

            <button
                v-else
                type="button"
                class="flex min-h-20 flex-col items-center justify-center gap-1 rounded-lg bg-danger-600 px-3 py-4 text-sm font-semibold text-white transition-colors hover:bg-danger-700 active:bg-danger-800"
                @click="till.cancelActiveOrder()"
            >
                <span>{{ till.t('actions.cancel-order.confirm') }}</span>

                <span class="text-center text-xs font-normal text-danger-100">
                    {{ till.t('actions.cancel-order.hint') }}
                </span>
            </button>
        </div>
    </TillModal>
</template>
