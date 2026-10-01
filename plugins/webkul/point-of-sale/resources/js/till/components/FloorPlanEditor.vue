<script setup>
import { inject, computed } from 'vue'

const till = inject('till')

const state = till.state

const draft = computed(() => state.planDraft)

const selected = computed(() => till.planSelectedTable)

const palette = ['#ef4444', '#f97316', '#eab308', '#22c55e', '#14b8a6', '#3b82f6', '#8b5cf6', '#ec4899', '#78716c']

const backgrounds = ['#ffffff', '#f5f5f4', '#fef3c7', '#dcfce7', '#dbeafe', '#ede9fe', '#fce7f3', '#e7e5e4']

const canvas = computed(() => {
    const tables = till.planTables

    const width = Math.max(800, ...tables.map((table) => table.position_h + table.width + 200))
    const height = Math.max(500, ...tables.map((table) => table.position_v + table.height + 200))

    const style = { width: `${width}px`, height: `${height}px` }

    if (draft.value?.background_color) {
        style.backgroundColor = draft.value.background_color
    }

    return style
})

const button = 'flex min-h-10 flex-none items-center justify-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 disabled:opacity-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800'

let gesture = null

function placement(table) {
    const style = {
        left: `${table.position_h}px`,
        top: `${table.position_v}px`,
        width: `${table.width}px`,
        height: `${table.height}px`,
    }

    if (table.color) {
        style.borderColor = table.color
    }

    return style
}

function startMove(event, table) {
    if (event.button !== 0) {
        return
    }

    till.state.planSelectedKey = table.key

    gesture = {
        mode: 'move',
        key: table.key,
        x: event.clientX,
        y: event.clientY,
        left: table.position_h,
        top: table.position_v,
        width: table.width,
        height: table.height,
    }

    event.currentTarget.setPointerCapture(event.pointerId)
}

function startResize(event, table) {
    event.stopPropagation()

    gesture = {
        mode: 'resize',
        key: table.key,
        x: event.clientX,
        y: event.clientY,
        width: table.width,
        height: table.height,
    }

    event.currentTarget.setPointerCapture(event.pointerId)
}

function track(event) {
    if (!gesture) {
        return
    }

    const dx = event.clientX - gesture.x
    const dy = event.clientY - gesture.y

    if (gesture.mode === 'move') {
        till.movePlanTable(gesture.key, gesture.left + dx, gesture.top + dy)
    } else {
        till.resizePlanTable(gesture.key, gesture.width + dx, gesture.height + dy)
    }
}

function release() {
    gesture = null
}
</script>

<template>
    <div class="flex min-h-0 flex-col gap-3">
        <div class="flex flex-none flex-wrap items-center gap-2 rounded-xl border border-gray-200 bg-white p-2 dark:border-gray-700 dark:bg-gray-900">
            <input
                v-model="draft.name"
                type="text"
                maxlength="255"
                :aria-label="till.t('floor-plan.floor-name')"
                :placeholder="till.t('floor-plan.floor-name')"
                class="min-h-10 w-44 rounded-lg border border-gray-200 bg-white px-3 text-sm text-gray-950 focus:border-primary-500 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-white"
            >

            <div class="flex items-center gap-1" :title="till.t('floor-plan.background')">
                <button
                    v-for="colour in backgrounds"
                    :key="colour"
                    type="button"
                    class="size-7 rounded-md border-2 transition-transform hover:scale-110"
                    :class="draft.background_color === colour ? 'border-primary-600' : 'border-gray-200 dark:border-gray-700'"
                    :style="{ backgroundColor: colour }"
                    :aria-label="colour"
                    @click="draft.background_color = colour"
                />

                <button
                    type="button"
                    class="flex size-7 items-center justify-center rounded-md border-2 border-dashed border-gray-300 text-xs text-gray-500 dark:border-gray-600 dark:text-gray-400"
                    :aria-label="till.t('floor-plan.no-colour')"
                    @click="draft.background_color = ''"
                >
                    &times;
                </button>
            </div>

            <span class="mx-1 h-6 w-px bg-gray-200 dark:bg-gray-700"></span>

            <button type="button" :class="button" @click="till.addPlanTable()">
                {{ till.t('floor-plan.add-table') }}
            </button>

            <button
                v-if="till.config.floor_plan?.can_create"
                type="button"
                :class="button"
                :disabled="state.planSaving"
                @click="till.addFloor()"
            >
                {{ till.t('floor-plan.add-floor') }}
            </button>

            <button
                v-if="till.config.floor_plan?.can_delete"
                type="button"
                class="flex min-h-10 flex-none items-center justify-center rounded-lg border px-3 text-sm font-medium transition-colors disabled:opacity-50"
                :class="state.planConfirmDelete
                    ? 'border-danger-600 bg-danger-600 text-white hover:bg-danger-700'
                    : 'border-danger-200 bg-white text-danger-700 hover:bg-danger-50 dark:border-danger-500/40 dark:bg-gray-900 dark:text-danger-300 dark:hover:bg-danger-500/10'"
                :disabled="state.planSaving"
                @click="till.deleteFloor()"
            >
                {{ state.planConfirmDelete ? till.t('floor-plan.confirm-delete-floor') : till.t('floor-plan.delete-floor') }}
            </button>

            <span class="flex-1"></span>

            <button type="button" :class="button" :disabled="state.planSaving" @click="till.cancelPlanEdit()">
                {{ till.t('common.cancel') }}
            </button>

            <button
                type="button"
                class="flex min-h-10 flex-none items-center justify-center rounded-lg bg-primary-600 px-5 text-sm font-semibold text-white transition-colors hover:bg-primary-700 disabled:opacity-60"
                :disabled="state.planSaving"
                @click="till.savePlan()"
            >
                {{ state.planSaving ? till.t('common.saving') : till.t('common.save') }}
            </button>

            <p v-if="state.planError" class="w-full text-sm text-danger-600 dark:text-danger-400">
                {{ state.planError }}
            </p>
        </div>

        <div v-if="selected" class="flex flex-none flex-wrap items-center gap-2 rounded-xl border border-primary-200 bg-primary-50 p-2 dark:border-primary-500/30 dark:bg-primary-500/10">
            <label class="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-200">
                {{ till.t('floor-plan.table-number') }}

                <input
                    :value="selected.table_number"
                    type="text"
                    maxlength="16"
                    class="min-h-10 w-20 rounded-lg border border-gray-200 bg-white px-2 text-center text-sm font-semibold text-gray-950 focus:border-primary-500 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                    @input="till.updatePlanTable({ table_number: $event.target.value })"
                >
            </label>

            <div class="flex items-center gap-1">
                <button type="button" :class="button" :aria-label="till.t('floor-plan.fewer-seats')" @click="till.updatePlanTable({ seats: selected.seats - 1 })">&minus;</button>

                <span class="min-w-16 text-center text-sm text-gray-700 dark:text-gray-200">{{ till.choice('floor.seats', selected.seats) }}</span>

                <button type="button" :class="button" :aria-label="till.t('floor-plan.more-seats')" @click="till.updatePlanTable({ seats: selected.seats + 1 })">+</button>
            </div>

            <button type="button" :class="button" @click="till.updatePlanTable({ shape: selected.shape === 'round' ? 'square' : 'round' })">
                {{ selected.shape === 'round' ? till.t('floor-plan.make-square') : till.t('floor-plan.make-round') }}
            </button>

            <div class="flex items-center gap-1">
                <button
                    v-for="colour in palette"
                    :key="colour"
                    type="button"
                    class="size-7 rounded-full border-2 transition-transform hover:scale-110"
                    :class="selected.color === colour ? 'border-gray-950 dark:border-white' : 'border-transparent'"
                    :style="{ backgroundColor: colour }"
                    :aria-label="colour"
                    @click="till.updatePlanTable({ color: colour })"
                />

                <button
                    type="button"
                    class="flex size-7 items-center justify-center rounded-full border-2 border-dashed border-gray-300 text-xs text-gray-500 dark:border-gray-600 dark:text-gray-400"
                    :aria-label="till.t('floor-plan.no-colour')"
                    @click="till.updatePlanTable({ color: null })"
                >
                    &times;
                </button>
            </div>

            <span class="flex-1"></span>

            <button type="button" :class="button" @click="till.duplicatePlanTable()">
                {{ till.t('floor-plan.duplicate') }}
            </button>

            <button
                type="button"
                class="flex min-h-10 flex-none items-center justify-center rounded-lg border border-danger-200 bg-white px-3 text-sm font-medium text-danger-700 transition-colors hover:bg-danger-50 dark:border-danger-500/40 dark:bg-gray-900 dark:text-danger-300 dark:hover:bg-danger-500/10"
                @click="till.removePlanTable()"
            >
                {{ till.t('floor-plan.delete-table') }}
            </button>
        </div>

        <p v-else class="flex-none text-xs text-gray-500 dark:text-gray-400">
            {{ till.t('floor-plan.hint') }}
        </p>

        <div class="min-h-0 flex-auto overflow-auto rounded-xl border-2 border-dashed border-primary-300 bg-gray-50 dark:border-primary-500/40 dark:bg-gray-950">
            <div
                class="relative bg-[radial-gradient(circle,rgb(0_0_0/0.08)_1px,transparent_1px)] bg-size-[20px_20px] dark:bg-[radial-gradient(circle,rgb(255_255_255/0.08)_1px,transparent_1px)]"
                :style="canvas"
                @pointerdown.self="state.planSelectedKey = null"
            >
                <div
                    v-for="table in till.planTables"
                    :key="table.key"
                    class="absolute flex touch-none cursor-move select-none flex-col items-center justify-center border-4 bg-white/80 text-gray-900 shadow-sm dark:bg-gray-900/80 dark:text-gray-100"
                    :class="[
                        table.shape === 'round' ? 'rounded-full' : 'rounded-xl',
                        table.color ? '' : 'border-gray-300 dark:border-gray-600',
                        state.planSelectedKey === table.key ? 'ring-4 ring-primary-500/50' : '',
                    ]"
                    :style="placement(table)"
                    @pointerdown="startMove($event, table)"
                    @pointermove="track"
                    @pointerup="release"
                    @pointercancel="release"
                >
                    <span class="text-base font-bold leading-none">{{ table.table_number }}</span>

                    <span class="text-[0.625rem] leading-none opacity-70">{{ till.choice('floor.seats', table.seats) }}</span>

                    <span
                        v-if="state.planSelectedKey === table.key"
                        class="absolute -bottom-2 -right-2 size-5 cursor-se-resize rounded-full border-2 border-white bg-primary-600 dark:border-gray-900"
                        @pointerdown="startResize($event, table)"
                        @pointermove="track"
                        @pointerup="release"
                        @pointercancel="release"
                    ></span>
                </div>
            </div>
        </div>
    </div>
</template>
