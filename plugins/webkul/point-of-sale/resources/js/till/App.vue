<script setup>
import { inject } from 'vue'
import OrderTabs from './components/OrderTabs.vue'
import ProductGrid from './components/ProductGrid.vue'
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
import StatusBar from './components/StatusBar.vue'

const till = inject('till')

const state = till.state
</script>

<template>
    <div class="pos-screen fixed inset-x-0 bottom-0 top-16 flex min-h-0 flex-col">
        <StatusBar />

        <div class="grid h-full min-h-0 flex-auto grid-cols-[1fr] grid-rows-[minmax(0,1fr)] gap-4 p-4 lg:grid-cols-[1fr_minmax(22rem,32%)]">
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
    </div>
</template>
