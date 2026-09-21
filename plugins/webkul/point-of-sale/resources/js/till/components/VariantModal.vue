<script setup>
import { inject, computed } from 'vue'
import TillModal from './TillModal.vue'

const till = inject('till')

const state = till.state

const resolved = computed(() => till.resolveVariant())

function extraPrice(value) {
    return value.extra_price ? `+${till.money(value.extra_price)}` : ''
}
</script>

<template>
    <TillModal :open="state.variantProductId !== null" width="max-w-xl" @close="till.closeVariants()">
        <div class="flex flex-none items-center justify-between gap-2 border-b border-gray-100 p-4 dark:border-gray-800">
            <div class="min-w-0">
                <p class="text-base font-semibold text-gray-950 dark:text-white">{{ till.t('variants.heading') }}</p>

                <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ till.variantProduct?.name }}</p>
            </div>

            <button
                type="button"
                class="flex-none rounded-lg px-3 py-2 text-sm text-gray-500 hover:bg-gray-50 active:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800 dark:active:bg-gray-700"
                @click="till.closeVariants()"
            >
                {{ till.t('common.close') }}
            </button>
        </div>

        <div class="flex min-h-0 flex-auto flex-col gap-4 overflow-y-auto p-4">
            <div v-for="attribute in till.variantAttributes" :key="attribute.id" class="flex flex-col gap-2">
                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ attribute.name }}</p>

                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="value in attribute.values"
                        :key="value.id"
                        type="button"
                        class="flex min-h-11 items-center gap-1.5 rounded-lg border px-3 text-sm transition-colors"
                        :class="state.variantSelection[attribute.id] === value.id
                            ? 'border-primary-500 bg-primary-50 font-semibold text-primary-800 dark:border-primary-400 dark:bg-primary-500/15 dark:text-primary-200'
                            : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50 active:bg-gray-100 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800 dark:active:bg-gray-700'"
                        @click="till.selectVariantValue(attribute.id, value.id)"
                    >
                        <span>{{ value.name }}</span>

                        <span v-if="value.extra_price" class="font-mono text-xs tabular-nums opacity-70">
                            {{ extraPrice(value) }}
                        </span>
                    </button>
                </div>
            </div>

            <p v-if="!resolved" class="rounded-lg bg-danger-50 px-3 py-2 text-sm text-danger-700 dark:bg-danger-500/10 dark:text-danger-300">
                {{ till.t('variants.unavailable') }}
            </p>
        </div>

        <div class="flex flex-none gap-2 border-t border-gray-100 p-4 dark:border-gray-800">
            <button
                type="button"
                class="flex min-h-11 flex-1 items-center justify-center rounded-lg border border-gray-200 bg-white text-sm font-medium text-gray-600 hover:bg-gray-50 active:bg-gray-100 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:active:bg-gray-700"
                @click="till.closeVariants()"
            >
                {{ till.t('common.discard') }}
            </button>

            <button
                type="button"
                class="flex min-h-11 flex-2 items-center justify-center gap-2 rounded-lg bg-primary-600 text-sm font-semibold text-white transition-colors hover:bg-primary-700 disabled:bg-gray-200 disabled:text-gray-400 dark:disabled:bg-gray-800"
                :disabled="!resolved"
                @click="till.confirmVariant()"
            >
                <span>{{ till.t('variants.confirm') }}</span>

                <span v-if="resolved" class="font-mono tabular-nums">{{ till.money(till.priceFor(resolved.id)) }}</span>
            </button>
        </div>
    </TillModal>
</template>
