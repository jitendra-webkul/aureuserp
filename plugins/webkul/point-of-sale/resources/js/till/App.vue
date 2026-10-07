<script setup>
import { inject } from 'vue'
import OrderTabs from './components/OrderTabs.vue'
import ProductGrid from './components/ProductGrid.vue'
import FloorScreen from './components/FloorScreen.vue'
import GuestsModal from './components/GuestsModal.vue'
import SplitScreen from './components/SplitScreen.vue'
import BillModal from './components/BillModal.vue'
import TipModal from './components/TipModal.vue'
import OrderNameModal from './components/OrderNameModal.vue'
import GlobalDiscountModal from './components/GlobalDiscountModal.vue'
import TableSelectorModal from './components/TableSelectorModal.vue'
import Cart from './components/Cart.vue'
import Numpad from './components/Numpad.vue'
import PaymentPad from './components/PaymentPad.vue'
import PaymentScreen from './components/PaymentScreen.vue'
import ReceiptScreen from './components/ReceiptScreen.vue'
import CustomerModal from './components/CustomerModal.vue'
import OrdersModal from './components/OrdersModal.vue'
import ProductInfoModal from './components/ProductInfoModal.vue'
import NoteModal from './components/NoteModal.vue'
import ProductFormModal from './components/ProductFormModal.vue'
import ActionsModal from './components/ActionsModal.vue'
import PriceListModal from './components/PriceListModal.vue'
import VariantModal from './components/VariantModal.vue'
import LotModal from './components/LotModal.vue'
import MissingLotsModal from './components/MissingLotsModal.vue'
import ShipLaterModal from './components/ShipLaterModal.vue'
import RejectedOrderModal from './components/RejectedOrderModal.vue'
import KitchenTicket from './components/KitchenTicket.vue'
import StatusBar from './components/StatusBar.vue'
import ScannerButton from './components/ScannerButton.vue'

const till = inject('till')

const state = till.state
</script>

<template>
    <div class="pos-screen fixed inset-x-0 bottom-0 top-16 flex min-h-0 flex-col">
        <ScannerButton />

        <StatusBar />

        <FloorScreen v-if="state.screen === 'floor'" class="min-h-0 flex-auto p-4" />

        <SplitScreen v-else-if="state.screen === 'split'" class="min-h-0 flex-auto p-4" />

        <div v-else class="grid h-full min-h-0 flex-auto grid-cols-[1fr] grid-rows-[minmax(0,1fr)] gap-4 p-4 lg:grid-cols-[1fr_minmax(22rem,32%)]">
            <section class="flex min-h-0 min-w-0 flex-col gap-3">
                <ProductGrid v-if="state.screen === 'products'" />

                <PaymentScreen v-else-if="state.screen === 'payment'" />

                <ReceiptScreen v-else-if="state.screen === 'receipt'" />
            </section>

            <aside class="flex min-h-0 min-w-0 flex-col gap-[clamp(0.375rem,1vh,0.75rem)] overflow-y-auto">
                <OrderTabs />

                <Cart class="flex-auto" />

                <Numpad v-if="state.screen === 'products'" class="flex-none" />

                <PaymentPad v-else-if="state.screen === 'payment'" class="flex-none" />
            </aside>
        </div>

        <CustomerModal />

        <OrdersModal />

        <ProductInfoModal />

        <NoteModal />

        <ProductFormModal />

        <ActionsModal />

        <PriceListModal />

        <VariantModal />

        <LotModal />

        <MissingLotsModal />

        <ShipLaterModal />

        <GuestsModal />

        <BillModal />

        <TipModal />

        <OrderNameModal />

        <GlobalDiscountModal />

        <TableSelectorModal />

        <RejectedOrderModal />

        <KitchenTicket />

        <div
            v-if="state.sessionClosed"
            class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/60 p-4"
            role="alertdialog"
            aria-modal="true"
        >
            <div class="w-full max-w-md rounded-xl bg-white p-6 text-center shadow-xl dark:bg-gray-900">
                <p class="text-lg font-semibold text-gray-950 dark:text-white">{{ till.t('session-closed.heading') }}</p>

                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">{{ till.t('session-closed.body') }}</p>

                <a
                    :href="till.boot.session.registers_url"
                    class="mt-5 inline-flex min-h-11 items-center justify-center rounded-lg bg-primary-600 px-6 text-sm font-semibold text-white hover:bg-primary-700"
                >
                    {{ till.t('session-closed.back') }}
                </a>
            </div>
        </div>
    </div>
</template>
