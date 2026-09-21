<script setup>
import { watch, onBeforeUnmount } from 'vue'

const props = defineProps({
    open: { type: Boolean, default: false },
    width: { type: String, default: 'max-w-xl' },
})

const emit = defineEmits(['close'])

function onKeydown(event) {
    if (event.key === 'Escape') {
        emit('close')
    }
}

watch(() => props.open, (open) => {
    if (open) {
        window.addEventListener('keydown', onKeydown)
    } else {
        window.removeEventListener('keydown', onKeydown)
    }
})

onBeforeUnmount(() => window.removeEventListener('keydown', onKeydown))
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="open"
                class="fixed inset-0 z-50 flex items-start justify-center bg-gray-950/50 px-4 pb-4 pt-24 backdrop-blur-[2px]"
                @click.self="emit('close')"
            >
                <Transition
                    appear
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="translate-y-2 scale-95 opacity-0"
                    enter-to-class="translate-y-0 scale-100 opacity-100"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="translate-y-0 scale-100 opacity-100"
                    leave-to-class="translate-y-2 scale-95 opacity-0"
                >
                    <div
                        v-if="open"
                        class="flex max-h-[calc(100vh-8rem)] w-full flex-col overflow-hidden rounded-xl bg-white shadow-2xl ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"
                        :class="width"
                    >
                        <slot />
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
