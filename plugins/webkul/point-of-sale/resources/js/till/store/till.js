import { reactive, computed, watch } from 'vue'
import { computeAll } from '../tax/computer.js'
import { floatRound, floatCompare, floatIsZero } from '../tax/float.js'
import { TillDatabase } from './indexed-db.js'
import { createRecordSets, MASTER_INDEXES, TRANSACTIONAL_MODELS } from './records.js'
import { SyncQueue } from './sync-queue.js'

const PERSIST_DEBOUNCE = 200

const DATABASE_VERSION = 1

const BUFFER_MAX_LENGTH = 12

const KEYBOARD_BURST_WINDOW = 100

const KEYBOARD_BURST_LIMIT = 2

const FLOOR_IDLE_TIMEOUT = 180000

const DRAFT_REFRESH_INTERVAL = 20000

const TABLE_REFRESH_TIMEOUT = 1500

const BUFFER_KEYS = new Set(['Backspace', 'Delete', '+', '-', '.', ',', ...'0123456789'.split('')])

const MODIFIER_KEYS = new Set(['Shift', 'Control', 'Alt', 'Meta', 'AltGraph', 'CapsLock'])

function isBufferEmpty(buffer) {
    return buffer === '' || buffer === null
}

function nextBuffer(buffer, key, fresh = false) {
    if ((key === 'Backspace' || key === 'Delete') && fresh) {
        return ''
    }

    const current = buffer ?? ''

    let next = current

    if (key === '.' || key === ',') {
        if (isBufferEmpty(buffer)) {
            next = '0.'
        } else if (current === '-') {
            next = '-0.'
        } else if (!current.includes('.')) {
            next = `${current}.`
        }
    } else if (key === 'Delete') {
        next = isBufferEmpty(buffer) ? null : ''
    } else if (key === 'Backspace') {
        next = isBufferEmpty(buffer)
            ? null
            : current.slice(0, current.endsWith('.') ? -2 : -1)
    } else if (key === '+') {
        next = current.startsWith('-') ? current.slice(1) : current
    } else if (key === '-') {
        if (isBufferEmpty(buffer)) {
            next = '-0'
        } else {
            next = current.startsWith('-') ? current.slice(1) : `-${current}`
        }
    } else if (/^\+\d+(\.\d+)?$/.test(key)) {
        const bumped = Number(key.slice(1)) + (Number(current) || 0)

        next = String(floatRound(bumped, { precisionDigits: 4 }))
    } else if (/^\d$/.test(key)) {
        if (isBufferEmpty(buffer)) {
            next = key
        } else if (current.length <= BUFFER_MAX_LENGTH) {
            next = `${current}${key}`
        }
    }

    return next === '-' ? '' : next
}

function uuidv4() {
    if (window.crypto?.randomUUID) {
        return window.crypto.randomUUID()
    }

    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (char) => {
        const random = (Math.random() * 16) | 0
        const value = char === 'x' ? random : (random & 0x3) | 0x8

        return value.toString(16)
    })
}

export class Till {
    constructor(boot, { syncEndpoint, accessToken }) {
        this.boot = boot
        this.syncEndpoint = syncEndpoint
        this.accessToken = accessToken
        this.deferredEvictions = []

        this.master = createRecordSets(
            Object.fromEntries(Object.entries(MASTER_INDEXES).map(([name, indexes]) => [name, { key: 'id', indexes }])),
        )

        this.state = reactive({
            ready: false,
            screen: 'products',
            search: '',
            categoryId: null,
            orders: [],
            activeOrderUuid: null,
            activeLineUuid: null,
            floorId: boot.floors?.[0]?.id ?? null,
            guestsModalOpen: false,
            guestsDraft: 0,
            splitQuantities: {},
            autoPrintOrderUuid: null,
            globalDiscountModalOpen: false,
            transferOrderUuid: null,
            sessionClosed: false,
            openingTable: false,
            transferError: null,
            tableSelectorOpen: false,
            tableSelectorDraft: '',
            globalDiscountDraft: '',
            billModalOpen: false,
            tipModalOpen: false,
            tipDraft: '',
            orderNameModalOpen: false,
            orderNameDraft: '',
            numpadMode: 'qty',
            numpadBuffer: '',
            numpadFresh: true,
            priceListId: boot.config.price_list_id ?? null,
            fiscalPositionId: boot.config.fiscal_position_id ?? null,
            isTakeaway: false,
            toInvoice: false,
            databaseUnavailable: false,
            sequenceNumber: boot.session.sequence_number ?? 0,
            customerModalOpen: false,
            customerSearch: '',
            productInfoId: null,
            ordersModalOpen: false,
            ordersSearch: '',
            noteModalOpen: false,
            noteDraft: '',
            activePaymentUuid: null,
            paymentBuffer: '',
            paymentFresh: true,
            productModalOpen: false,
            actionsModalOpen: false,
            cancellingOrder: false,
            cancelError: null,
            variantProductId: null,
            lotLineUuid: null,
            lotRows: [],
            lotError: null,
            lotWarningOpen: false,
            cameraScanning: false,
            cameraError: null,
            variantSelection: {},
            priceListModalOpen: false,
            shipLaterModalOpen: false,
            shipLaterDraft: '',
            productSaving: false,
            productError: null,
            rememberedOrderUnavailable: false,
        })

        this.database = new TillDatabase(
            `pos-config-${boot.config.id}-${accessToken}`,
            DATABASE_VERSION,
            Object.entries(TRANSACTIONAL_MODELS).map(([name, options]) => [options.key, name]),
            { onFatal: () => { this.state.databaseUnavailable = true } },
        )

        this.queue = new SyncQueue(this.database, { endpoint: syncEndpoint })

        this.loadMaster(boot)
    }

    loadMaster(boot) {
        this.master.products.putMany(boot.products ?? [])
        this.master.partners.putMany(boot.partners ?? [])
        this.master.taxes.putMany(boot.taxes ?? [])
        this.master.categories.putMany(boot.categories ?? [])
        this.master.payment_methods.putMany(boot.payment_methods ?? [])
        this.master.price_lists.putMany(boot.price_lists ?? [])
        this.master.fiscal_positions.putMany(boot.fiscal_positions ?? [])
        this.master.bills.putMany(boot.bills ?? [])
        this.master.notes.putMany(boot.notes ?? [])
        this.master.uoms.putMany(boot.uoms ?? [])
        this.master.currencies.putMany(boot.currencies ?? [])

        this.stock = { ...(boot.stock ?? {}) }
        this.prices = boot.prices ?? { 0: {} }

        this.floors = boot.floors ?? []

        this.tableById = new Map(this.floors.flatMap((floor) => floor.tables.map((table) => [table.id, table])))

        this.variantsByProduct = new Map((boot.variants ?? []).map((entry) => [entry.product_id, entry]))

        this.lotsByProduct = (boot.lots ?? []).reduce((carry, lot) => {
            carry.set(lot.product_id, [...(carry.get(lot.product_id) ?? []), lot])

            return carry
        }, new Map())

        this.taxById = new Map((boot.taxes ?? []).map((tax) => [tax.id, tax]))

        for (const tax of this.taxById.values()) {
            tax.children = (tax.children_tax_ids ?? []).map((id) => this.taxById.get(id)).filter(Boolean)
        }
    }

    async start() {
        await this.restore()

        this.mergeServerOrders()

        this.dropSettledOrders()

        const refund = this.openPendingRefund()

        this.activateDraft(this.rememberedOrderUuid())

        if (refund) {
            this.state.activeOrderUuid = refund.uuid
            this.state.screen = 'products'
        } else if (this.isRestaurant) {
            this.goToFloor()
        }

        this.watchActiveOrder()

        this.queue.watch()

        window.addEventListener('point-of-sale:queue-flushed', (event) => {
            this.evictSynced(event.detail.uuids ?? [])
        })

        this.watchPersistence()

        this.startWedge()

        this.startKeyboard()

        this.startIdleWatch()

        this.startDraftRefresh()

        this.state.ready = true
    }

    async restore() {
        const stored = await this.database.readAll(Object.keys(TRANSACTIONAL_MODELS))

        const orders = stored['pos.order'] ?? []
        const lines = stored['pos.order.line'] ?? []
        const payments = stored['pos.payment'] ?? []

        const byOrder = new Map(orders.map((order) => [order.uuid, { ...order, lines: [], payments: [] }]))

        for (const line of lines) {
            byOrder.get(line.order_uuid)?.lines.push(line)
        }

        for (const payment of payments) {
            byOrder.get(payment.order_uuid)?.payments.push(payment)
        }

        const restored = [...byOrder.values()]

        const prefix = this.nextReference(0).slice(0, -4)

        for (const order of restored) {
            if (order.state === 'draft' && !order.serverId && order.pos_reference && !order.pos_reference.startsWith(prefix)) {
                order.pos_reference = null
                order.tracking_number = null
                order.sequence_number = null
            }
        }

        const highest = [...restored, ...(this.boot.orders ?? [])]
            .filter((order) => order.pos_reference?.startsWith(prefix))
            .reduce((carry, order) => {
                const number = parseInt(order.pos_reference.slice(prefix.length), 10)

                return Number.isNaN(number) ? carry : Math.max(carry, number)
            }, this.state.sequenceNumber)

        this.state.sequenceNumber = highest

        const seen = new Set([
            ...(this.boot.orders ?? []).map((order) => order.pos_reference),
            ...restored.filter((order) => order.serverId).map((order) => order.pos_reference),
        ])

        for (const order of restored) {
            order.sequence_number ??= this.sequenceFromReference(order.pos_reference) ?? this.nextSequenceNumber()

            order.tracking_number ??= this.trackingNumberFor(order.sequence_number)

            order.pos_reference ||= this.nextReference(order.sequence_number)

            if (!order.serverId && seen.has(order.pos_reference)) {
                order.sequence_number = this.nextSequenceNumber()

                order.tracking_number = this.trackingNumberFor(order.sequence_number)

                order.pos_reference = this.nextReference(order.sequence_number)
            }

            seen.add(order.pos_reference)

            this.state.orders.push(reactive(order))
        }

        return restored
    }

    mergeServerOrders() {
        const removed = new Set(this.removedDrafts())

        const serverOrders = (this.boot.orders ?? []).filter((order) => !removed.has(order.uuid))

        const serverUuids = new Set(serverOrders.map((order) => order.uuid))

        const stale = this.state.orders.filter((order) => order.serverId && order.state === 'draft' && !serverUuids.has(order.uuid))

        for (const order of stale) {
            this.database.remove('pos.order.line', order.lines.map((line) => line.uuid))
            this.database.remove('pos.payment', order.payments.map((payment) => payment.uuid))
            this.database.remove('pos.order', [order.uuid])
        }

        const staleUuids = new Set(stale.map((order) => order.uuid))

        this.state.orders = this.state.orders.filter((order) => !staleUuids.has(order.uuid))

        const localUuids = new Set(this.state.orders.map((order) => order.uuid))

        for (const order of serverOrders) {
            if (!localUuids.has(order.uuid)) {
                this.state.orders.push(this.hydrateServerOrder(order))
            }
        }
    }

    openPendingRefund() {
        const pending = this.boot.pending_refund

        if (!pending?.lines?.length) {
            return null
        }

        for (const product of pending.products ?? []) {
            if (!this.master.products.get(product.id)) {
                this.master.products.put(product)
            }
        }

        const destination = this.emptyOrderFor(pending.partner_id) ?? this.newOrder()

        destination.partner_id = pending.partner_id ?? destination.partner_id
        destination.price_list_id = pending.price_list_id ?? destination.price_list_id
        destination.fiscal_position_id = pending.fiscal_position_id ?? destination.fiscal_position_id
        destination.refunded_order_id = pending.refunded_order_id
        destination.locked_partner_id = pending.partner_id ?? null

        for (const line of pending.lines) {
            destination.lines.push(this.hydrateLine({ ...line, price_overridden: true }, destination.uuid))
        }

        return destination
    }

    emptyOrderFor(partnerId) {
        let fallback = null

        for (const order of this.drafts) {
            if (order.lines.length || order.payments.length) {
                continue
            }

            if ((order.partner_id ?? null) === (partnerId ?? null)) {
                return order
            }

            if (!order.partner_id && fallback === null) {
                fallback = order
            }
        }

        return fallback
    }

    hydrateServerOrder(order) {
        const sequenceNumber = order.sequence_number || this.sequenceFromReference(order.pos_reference) || this.nextSequenceNumber()

        return reactive({
            uuid: order.uuid ?? uuidv4(),
            serverId: order.id ?? null,
            sequence_number: sequenceNumber,
            pos_reference: order.pos_reference || this.nextReference(sequenceNumber),
            tracking_number: order.tracking_number || this.trackingNumberFor(sequenceNumber),
            partner_id: order.partner_id ?? null,
            state: order.state ?? 'draft',
            note: order.note ?? '',
            is_takeaway: Boolean(order.is_takeaway),
            table_id: order.table_id ?? null,
            customer_count: Number(order.customer_count ?? 0),
            floating_name: order.floating_name ?? '',
            is_booked: Boolean(order.is_booked),
            last_screen: order.last_screen ?? 'products',
            draft_dirty: Boolean(order.draft_dirty),
            to_invoice: Boolean(order.to_invoice),
            shipped_at: order.shipped_at ?? null,
            price_list_id: order.price_list_id ?? this.state.priceListId,
            fiscal_position_id: order.fiscal_position_id ?? this.state.fiscalPositionId,
            refunded_order_id: order.refunded_order_id ?? null,
            locked_partner_id: order.locked_partner_id ?? null,
            created_at: order.created_at ?? new Date().toISOString(),
            lines: (order.lines ?? []).map((line) => this.hydrateLine(line, order.uuid)),
            payments: (order.payments ?? []).map((payment) => ({
                uuid: payment.uuid ?? uuidv4(),
                order_uuid: order.uuid,
                payment_method_id: payment.payment_method_id,
                amount: Number(payment.amount ?? 0),
                is_change: Boolean(payment.is_change),
            })),
        })
    }

    hydrateLine(line, orderUuid) {
        return {
            uuid: line.uuid ?? uuidv4(),
            order_uuid: orderUuid,
            product_id: line.product_id,
            qty: Number(line.qty ?? 1),
            price_unit: Number(line.price_unit ?? 0),
            price_overridden: Boolean(line.price_overridden),
            discount: Number(line.discount ?? 0),
            note: line.note ?? '',
            lots: line.lots ?? [],
            tax_ids: line.tax_ids ?? [],
            refunded_order_line_id: line.refunded_order_line_id ?? null,
        }
    }

    get activeOrderStorageKey() {
        return `pos.active-order.${this.config.id}`
    }

    rememberedOrderUuid() {
        try {
            return window.localStorage.getItem(this.activeOrderStorageKey)
        } catch {
            return null
        }
    }

    watchActiveOrder() {
        watch(() => this.state.activeOrderUuid, (uuid) => {
            try {
                if (uuid) {
                    window.localStorage.setItem(this.activeOrderStorageKey, uuid)
                } else {
                    window.localStorage.removeItem(this.activeOrderStorageKey)
                }
            } catch {
                this.state.rememberedOrderUnavailable = true
            }
        }, { immediate: true })
    }

    watchPersistence() {
        let timer = null

        watch(
            () => JSON.stringify(this.state.orders),
            () => {
                if (timer) {
                    window.clearTimeout(timer)
                }

                timer = window.setTimeout(() => this.persist(), PERSIST_DEBOUNCE)
            },
            { deep: true },
        )
    }

    async persist() {
        const orders = []
        const lines = []
        const payments = []

        for (const order of this.state.orders) {
            orders.push({
                uuid: order.uuid,
                serverId: order.serverId,
                pos_reference: order.pos_reference,
                tracking_number: order.tracking_number,
                sequence_number: order.sequence_number,
                partner_id: order.partner_id,
                state: order.state,
                note: order.note,
                is_takeaway: order.is_takeaway,
                table_id: order.table_id ?? null,
                customer_count: order.customer_count ?? 0,
                floating_name: order.floating_name ?? '',
                is_booked: Boolean(order.is_booked),
                last_screen: order.last_screen ?? 'products',
                draft_dirty: Boolean(order.draft_dirty),
                to_invoice: order.to_invoice,
                shipped_at: order.shipped_at,
                price_list_id: order.price_list_id,
                fiscal_position_id: order.fiscal_position_id,
                refunded_order_id: order.refunded_order_id ?? null,
                locked_partner_id: order.locked_partner_id ?? null,
                created_at: order.created_at,
            })

            for (const line of order.lines) {
                lines.push({ ...line, order_uuid: order.uuid })
            }

            for (const payment of order.payments) {
                payments.push({ ...payment, order_uuid: order.uuid })
            }
        }

        await this.database.write('pos.order', orders)
        await this.database.write('pos.order.line', lines)
        await this.database.write('pos.payment', payments)
    }

    flushDeferredEvictions() {
        const deferred = this.deferredEvictions

        if (!deferred.length) {
            return
        }

        this.deferredEvictions = []

        this.evictSynced(deferred)
    }

    evictSynced(uuids) {
        if (!uuids.length) {
            return
        }

        const showingReceipt = this.state.screen === 'receipt'

        const evictable = new Set(uuids.filter((uuid) => {
            if (showingReceipt && uuid === this.state.activeOrderUuid) {
                if (!this.deferredEvictions.includes(uuid)) {
                    this.deferredEvictions.push(uuid)
                }

                return false
            }

            return true
        }))

        if (!evictable.size) {
            return
        }

        const removedLines = []
        const removedPayments = []

        this.state.orders = this.state.orders.filter((order) => {
            if (!evictable.has(order.uuid)) {
                return true
            }

            if (order.state === 'draft') {
                return true
            }

            removedLines.push(...order.lines.map((line) => line.uuid))
            removedPayments.push(...order.payments.map((payment) => payment.uuid))

            return false
        })

        this.database.remove('pos.order', [...evictable])
        this.database.remove('pos.order.line', removedLines)
        this.database.remove('pos.payment', removedPayments)

        if (!showingReceipt && this.activeOrder?.state !== 'draft') {
            this.activateDraft()
        }
    }

    dropSettledOrders() {
        const settled = this.state.orders.filter((order) => order.state !== 'draft')

        if (!settled.length) {
            return
        }

        this.database.remove('pos.order.line', settled.flatMap((order) => order.lines.map((line) => line.uuid)))
        this.database.remove('pos.payment', settled.flatMap((order) => order.payments.map((payment) => payment.uuid)))
        this.database.remove('pos.order', settled.map((order) => order.uuid))

        this.state.orders = this.state.orders.filter((order) => order.state === 'draft')
    }

    activateDraft(preferredUuid = null) {
        const drafts = this.drafts

        const preferred = drafts.find((order) => order.uuid === preferredUuid)

        if (this.isRestaurant && !preferred) {
            this.goToFloor()

            return null
        }

        const draft = preferred ?? drafts[0]

        if (!draft) {
            return this.newOrder()
        }

        this.state.activeOrderUuid = draft.uuid

        return draft
    }

    get config() {
        return this.boot.config
    }

    get currency() {
        return this.master.currencies.get(this.config.currency_id)
            ?? this.master.currencies.get(this.boot.company.currency_id)
            ?? { rounding: 0.01, decimal_places: 2, symbol: '', name: '', position: 'before' }
    }

    get roundingMethod() {
        return this.boot.company.tax_calculation_rounding_method ?? 'round_per_line'
    }

    get locale() {
        return this.boot.locale ?? { code: 'en', direction: 'ltr' }
    }

    get isRtl() {
        return this.locale.direction === 'rtl'
    }

    t(key, replacements = {}) {
        let line = key.split('.').reduce(
            (branch, segment) => (branch && typeof branch === 'object' ? branch[segment] : undefined),
            this.boot.translations ?? {},
        )

        if (typeof line !== 'string') {
            return key
        }

        for (const [token, value] of Object.entries(replacements)) {
            line = line.replaceAll(`:${token}`, value)
        }

        return line
    }

    choice(key, count, replacements = {}) {
        const line = this.t(key, { ...replacements, count })

        if (!line.includes('|')) {
            return line
        }

        const segments = line.split('|').map((segment) => segment.trim())

        for (const segment of segments) {
            const exact = segment.match(/^\{(-?\d+)\}\s*(.*)$/s)

            if (exact && Number(exact[1]) === count) {
                return exact[2]
            }

            const range = segment.match(/^\[(-?\d+|\*),\s*(-?\d+|\*)\]\s*(.*)$/s)

            if (range) {
                const from = range[1] === '*' ? -Infinity : Number(range[1])
                const to = range[2] === '*' ? Infinity : Number(range[2])

                if (count >= from && count <= to) {
                    return range[3]
                }
            }
        }

        const plain = segments.map((segment) => segment.replace(/^(\{[^}]*\}|\[[^\]]*\])\s*/, ''))

        return count === 1 ? (plain[0] ?? line) : (plain[1] ?? plain[0] ?? line)
    }

    money(amount) {
        const currency = this.currency

        const decimalPlaces = currency.decimal_places ?? 2

        const value = Number(amount ?? 0)

        const formatted = new Intl.NumberFormat(this.locale.code || document.documentElement.lang || 'en', {
            minimumFractionDigits: decimalPlaces,
            maximumFractionDigits: decimalPlaces,
        }).format(Math.abs(value))

        const sign = Number(value.toFixed(decimalPlaces)) < 0 ? '-' : ''

        const symbol = currency.symbol ?? ''

        return currency.position === 'after'
            ? `${sign}${formatted} ${symbol}`.trim()
            : `${sign}${symbol}${formatted}`.trim()
    }

    openOrders() {
        this.state.ordersSearch = ''
        this.state.ordersModalOpen = true
    }

    closeOrders() {
        this.state.ordersModalOpen = false
    }

    orderLabel(order) {
        if (order.floating_name) {
            return order.floating_name
        }

        if (order.partner_id) {
            return this.master.partners.get(order.partner_id)?.name ?? order.tracking_number
        }

        return order.tracking_number ?? order.pos_reference
    }

    orderQuantity(order) {
        return order.lines.reduce((carry, line) => carry + line.qty, 0)
    }

    get parkedOrders() {
        if (!this.isRestaurant) {
            return this.drafts
        }

        const tableId = this.activeOrder?.table_id ?? null

        return this.drafts.filter((order) => !order.table_id || order.table_id === tableId)
    }

    searchOrders(term) {
        const needle = term.trim().toLowerCase()

        if (!needle) {
            return this.parkedOrders
        }

        return this.parkedOrders.filter((order) => {
            const haystack = [
                this.orderLabel(order),
                order.pos_reference,
                order.tracking_number,
                this.orderTable(order)?.table_number,
                ...order.lines.map((line) => this.master.products.get(line.product_id)?.name),
            ]

            return haystack.some((field) => field && String(field).toLowerCase().includes(needle))
        })
    }

    cartQuantityByProduct() {
        const quantities = new Map()

        for (const line of this.activeOrder?.lines ?? []) {
            quantities.set(line.product_id, (quantities.get(line.product_id) ?? 0) + line.qty)

            const parentId = this.master.products.get(line.product_id)?.parent_id

            if (parentId) {
                quantities.set(parentId, (quantities.get(parentId) ?? 0) + line.qty)
            }
        }

        return quantities
    }

    openProductInfo(productId) {
        this.state.productInfoId = productId
    }

    closeProductInfo() {
        this.state.productInfoId = null
    }

    productInfo() {
        const product = this.master.products.get(this.state.productInfoId)

        if (!product) {
            return null
        }

        const price = this.priceFor(product.id)

        const cost = Number(product.cost ?? 0)

        const quantity = this.cartQuantityByProduct().get(product.id) ?? 0

        return {
            id: product.id,
            name: product.name,
            reference: product.reference,
            barcode: product.barcode,
            image: product.image,
            is_storable: Boolean(product.is_storable),
            available: this.freeQty(product.id),
            price,
            cost,
            margin: price - cost,
            margin_ratio: price ? ((price - cost) / price) * 100 : 0,
            quantity,
            total_price: price * quantity,
            total_cost: cost * quantity,
            total_margin: (price - cost) * quantity,
        }
    }

    get noteLabel() {
        return this.t(this.config.is_restaurant ? 'notes.kitchen' : 'notes.internal')
    }

    openNotes() {
        const line = this.activeLine

        if (!line) {
            return
        }

        this.state.noteDraft = String(line.note ?? '')
        this.state.noteModalOpen = true
    }

    closeNotes() {
        this.state.noteModalOpen = false
        this.state.noteDraft = ''
    }

    noteLines() {
        return this.state.noteDraft.split('\n').filter((line) => line !== '')
    }

    noteParts(note) {
        return String(note ?? '')
            .split('\n')
            .filter((part) => part !== '')
            .map((text) => ({
                text,
                color: this.master.notes.find((preset) => preset.name === text)?.color ?? null,
            }))
    }

    isNoteSelected(name) {
        return this.noteLines().includes(name)
    }

    toggleNote(noteId) {
        const name = this.master.notes.get(noteId)?.name

        if (!name) {
            return
        }

        const lines = this.noteLines()

        this.state.noteDraft = lines.includes(name)
            ? lines.filter((line) => line !== name).join('\n')
            : [...lines, name].join('\n')
    }

    applyNote() {
        const line = this.activeLine

        if (!line) {
            return
        }

        line.note = this.state.noteDraft.trim() === '' ? '' : this.state.noteDraft

        this.closeNotes()
    }

    openProductForm() {
        this.state.productError = null
        this.state.productModalOpen = true
    }

    closeProductForm() {
        this.state.productModalOpen = false
        this.state.productError = null
    }

    get productEndpoint() {
        return this.boot.config.product_endpoint
    }

    async createProduct(draft) {
        this.state.productSaving = true
        this.state.productError = null

        try {
            const response = await fetch(this.productEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
                },
                body: JSON.stringify(draft),
            })

            const body = await response.json().catch(() => ({}))

            if (!response.ok) {
                this.state.productError = body.message ?? this.t('product-form.error.failed', { status: response.status })

                return null
            }

            const product = body.data

            this.master.products.put(product)

            const listKey = String(this.state.priceListId ?? 0)

            this.prices[listKey] ??= {}
            this.prices[listKey][String(product.id)] = product.resolved_price ?? product.price

            this.addProduct(product.id)

            this.closeProductForm()

            return product
        } catch (error) {
            this.state.productError = navigator.onLine
                ? error.message
                : this.t('product-form.error.offline')

            return null
        } finally {
            this.state.productSaving = false
        }
    }

    openCustomers() {
        if (this.isCustomerLocked) {
            return
        }

        this.state.customerSearch = ''
        this.state.customerModalOpen = true
    }

    closeCustomers() {
        this.state.customerModalOpen = false
    }

    goToPayment({ ignoreLots = false } = {}) {
        if (!this.activeOrder?.lines.length) {
            return
        }

        if (!ignoreLots && this.linesMissingLots().length) {
            this.state.lotWarningOpen = true

            return
        }

        this.state.lotWarningOpen = false

        this.activeOrder.last_screen = 'payment'

        this.state.screen = 'payment'
    }

    goToProducts() {
        if (this.activeOrder) {
            this.activeOrder.last_screen = 'products'
        }

        this.state.screen = 'products'
    }

    get activeOrder() {
        return this.state.orders.find((order) => order.uuid === this.state.activeOrderUuid)
    }

    get isCustomerLocked() {
        return Boolean(this.activeOrder?.refunded_order_id) && Boolean(this.activeOrder?.locked_partner_id)
    }

    get activeLine() {
        return this.activeOrder?.lines.find((line) => line.uuid === this.state.activeLineUuid)
    }

    get drafts() {
        return this.state.orders.filter((order) => order.state === 'draft')
    }

    nextReference(sequence) {
        const session = String(this.boot.session.id).padStart(5, '0')

        const login = String(this.boot.session.login_number ?? 0).padStart(3, '0')

        return `${session}-${login}-${String(sequence).padStart(4, '0')}`
    }

    nextSequenceNumber() {
        this.state.sequenceNumber += 1

        return this.state.sequenceNumber
    }

    sequenceFromReference(reference) {
        const number = parseInt(String(reference ?? '').split('-').at(-1), 10)

        return Number.isNaN(number) || number < 1 ? null : number
    }

    trackingNumberFor(sequence) {
        return String(((this.boot.session.id % 10) * 100) + (sequence % 100)).padStart(3, '0')
    }

    newOrder(tableId = null) {
        this.state.priceListId = this.config.price_list_id ?? null

        const order = this.hydrateServerOrder({
            uuid: uuidv4(),
            lines: [],
            payments: [],
            price_list_id: this.config.price_list_id ?? null,
            fiscal_position_id: this.state.fiscalPositionId,
            is_takeaway: this.state.isTakeaway,
            table_id: this.isRestaurant ? tableId : null,
        })

        this.state.orders.push(order)

        this.state.activeOrderUuid = order.uuid
        this.state.activeLineUuid = null
        this.state.screen = 'products'

        this.resetNumpadBuffer()

        this.flushDeferredEvictions()

        return order
    }

    get isRestaurant() {
        return Boolean(this.config.is_restaurant) && this.floors.length > 0
    }

    get activeFloor() {
        return this.floors.find((floor) => floor.id === this.state.floorId) ?? this.floors[0] ?? null
    }

    get activeTable() {
        return this.tableById.get(this.activeOrder?.table_id) ?? null
    }

    orderTable(order) {
        return this.tableById.get(order?.table_id) ?? null
    }

    tableLabel(table) {
        return this.t('floor.table', { table: table.table_number })
    }

    tableOrders(tableId) {
        return this.drafts.filter((order) => order.table_id === tableId && this.orderHasContent(order))
    }

    tableSummary(tableId) {
        const orders = this.tableOrders(tableId)

        return {
            count: orders.length,
            total: orders.reduce((carry, order) => carry + this.orderTotals(order).total, 0),
            guests: orders.reduce((carry, order) => carry + (order.customer_count ?? 0), 0),
        }
    }

    orderHasContent(order) {
        return order.lines.length > 0
            || (order.customer_count ?? 0) > 0
            || Boolean(order.is_booked)
    }

    resumeScreenFor(order) {
        return order?.state === 'draft' && order.last_screen === 'payment' && order.lines.length
            ? 'payment'
            : 'products'
    }

    selectFloor(floorId) {
        this.state.floorId = floorId
    }

    async tapTable(tableId) {
        if (this.state.transferOrderUuid) {
            this.transferTo(tableId)

            return
        }

        if (this.state.openingTable) {
            return
        }

        this.state.openingTable = true

        try {
            await this.refreshBeforeOpening()
        } finally {
            this.state.openingTable = false
        }

        if (this.state.sessionClosed) {
            return
        }

        this.openTable(tableId)
    }

    refreshBeforeOpening() {
        if (!this.sharesDrafts || !navigator.onLine) {
            return Promise.resolve()
        }

        return Promise.race([
            this.pullDrafts(),
            new Promise((resolve) => window.setTimeout(resolve, TABLE_REFRESH_TIMEOUT)),
        ])
    }

    get canBookTable() {
        const order = this.activeOrder

        return Boolean(this.isRestaurant && order?.table_id && order.state === 'draft' && !order.lines.length)
    }

    bookTable() {
        if (!this.canBookTable) {
            return
        }

        this.activeOrder.is_booked = true

        this.goToFloor()
    }

    releaseTable() {
        const order = this.activeOrder

        if (!this.canBookTable) {
            return
        }

        order.is_booked = false

        this.discardOrder(order.uuid)
    }

    startTransfer() {
        const order = this.activeOrder

        if (!this.isRestaurant || !order || order.state !== 'draft') {
            return
        }

        this.closeActions()

        order.is_booked = true

        this.state.transferError = null

        this.goToFloor()

        this.state.transferOrderUuid = order.uuid
    }

    cancelTransfer() {
        this.state.transferOrderUuid = null
        this.state.transferError = null
    }

    get transferOrder() {
        return this.state.orders.find((order) => order.uuid === this.state.transferOrderUuid) ?? null
    }

    canMergeLines(target, source) {
        return target.product_id === source.product_id
            && floatCompare(target.price_unit, source.price_unit, { precisionDigits: 4 }) === 0
            && floatCompare(target.discount, source.discount, { precisionDigits: 4 }) === 0
            && !target.note
            && !source.note
            && !target.lots?.length
            && !source.lots?.length
            && !this.isLockedLine(target)
            && !this.isLockedLine(source)
    }

    transferTo(tableId) {
        const source = this.transferOrder

        if (!source) {
            this.cancelTransfer()

            return
        }

        const target = this.drafts.find((order) => order.table_id === tableId && order.uuid !== source.uuid)

        if (!target && source.table_id === tableId) {
            this.cancelTransfer()

            this.selectOrder(source.uuid)

            return
        }

        if (!target) {
            source.table_id = tableId
            source.floating_name = ''

            this.cancelTransfer()

            this.selectOrder(source.uuid)

            this.shareDrafts(source)

            return
        }

        if (source.payments.length) {
            this.state.transferError = this.t('transfer.has-payments')

            return
        }

        for (const line of source.lines) {
            const adopting = target.lines.find((entry) => this.canMergeLines(entry, line))

            if (adopting) {
                adopting.qty = floatRound(adopting.qty + line.qty, { precisionDigits: 4 })

                continue
            }

            target.lines.push(reactive({
                ...line,
                lots: [...(line.lots ?? [])],
                uuid: uuidv4(),
                order_uuid: target.uuid,
            }))
        }

        target.customer_count = (target.customer_count ?? 0) + (source.customer_count ?? 0)

        this.dropOrder(source)

        this.cancelTransfer()

        this.selectOrder(target.uuid)

        this.shareDrafts(target)
    }

    openTableSelector() {
        if (!this.isRestaurant) {
            return
        }

        this.state.tableSelectorDraft = ''
        this.state.tableSelectorOpen = true
    }

    closeTableSelector() {
        this.state.tableSelectorOpen = false
    }

    findTableByNumber(number) {
        const needle = String(number).trim().toLowerCase()

        const matches = (table) => String(table.table_number).trim().toLowerCase() === needle

        return this.activeFloor?.tables.find(matches)
            ?? this.floors.flatMap((floor) => floor.tables).find(matches)
            ?? null
    }

    confirmTableSelector() {
        const input = String(this.state.tableSelectorDraft ?? '').trim()

        if (!input) {
            return
        }

        this.state.tableSelectorOpen = false

        const table = this.findTableByNumber(input)

        if (table) {
            this.state.floorId = table.floor_id

            this.openTable(table.id)

            return
        }

        const floating = this.drafts.find((order) => !order.table_id && order.floating_name === input)

        if (floating) {
            this.selectOrder(floating.uuid)

            return
        }

        const order = this.newOrder(null)

        order.floating_name = input.slice(0, 64)
        order.is_booked = true
    }

    openTable(tableId) {
        const existing = this.drafts.find((order) => order.table_id === tableId)

        if (existing) {
            this.selectOrder(existing.uuid)

            return existing
        }

        return this.newOrder(tableId)
    }

    goToFloor() {
        const order = this.activeOrder

        if (order?.table_id) {
            this.state.floorId = this.tableById.get(order.table_id)?.floor_id ?? this.state.floorId
        }

        this.state.activeOrderUuid = null
        this.state.activeLineUuid = null
        this.state.screen = 'floor'

        this.resetNumpadBuffer()

        if (order?.state === 'draft' && !this.orderHasContent(order)) {
            this.dropOrder(order)
        }

        this.shareDrafts(order?.state === 'draft' && this.orderHasContent(order) ? order : null)
    }

    finishOrder() {
        if (this.isRestaurant) {
            this.goToFloor()

            return
        }

        this.newOrder()
    }

    dropOrder(order) {
        this.removeDraftOnServer(order)

        this.database.remove('pos.order.line', order.lines.map((line) => line.uuid))
        this.database.remove('pos.payment', order.payments.map((payment) => payment.uuid))
        this.database.remove('pos.order', [order.uuid])

        this.state.orders = this.state.orders.filter((entry) => entry.uuid !== order.uuid)
    }

    get sharesDrafts() {
        return this.isRestaurant && Boolean(this.config.draft_endpoint) && Boolean(this.boot.session.drafts_endpoint)
    }

    async requestJson(url, { method = 'GET', body = null } = {}) {
        const response = await fetch(url, {
            method,
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
            },
            body: body ? JSON.stringify(body) : null,
        })

        if (!response.ok) {
            throw new Error(String(response.status))
        }

        return response.json()
    }

    draftPayload(order) {
        const payload = this.orderPayload(order)

        delete payload.payments
        delete payload.amount_total

        return payload
    }

    async pushDraft(order) {
        if (!this.sharesDrafts || !order || order.state !== 'draft' || !navigator.onLine) {
            return
        }

        try {
            const body = await this.requestJson(this.config.draft_endpoint, {
                method: 'POST',
                body: { orders: [this.draftPayload(order)] },
            })

            const saved = (body.data ?? []).find((entry) => entry.uuid === order.uuid)

            if (saved) {
                order.serverId = saved.id
                order.draft_dirty = false
            }

            if ((body.errors ?? []).some((entry) => entry.uuid === order.uuid)) {
                await this.pullDrafts()
            }
        } catch {
            return
        }
    }

    get removedDraftsKey() {
        return `pos.removed-drafts.${this.config.id}`
    }

    removedDrafts() {
        try {
            return JSON.parse(window.localStorage.getItem(this.removedDraftsKey) ?? '[]')
        } catch {
            return []
        }
    }

    rememberRemovedDrafts(uuids) {
        try {
            window.localStorage.setItem(this.removedDraftsKey, JSON.stringify([...new Set(uuids)]))
        } catch {
            return
        }
    }

    removeDraftOnServer(order) {
        if (!this.sharesDrafts || !order?.serverId || order.state !== 'draft') {
            return
        }

        this.rememberRemovedDrafts([...this.removedDrafts(), order.uuid])

        this.flushRemovedDrafts()
    }

    async flushRemovedDrafts() {
        if (!navigator.onLine) {
            return
        }

        for (const uuid of this.removedDrafts()) {
            try {
                await this.requestJson(this.boot.config.order_cancel_endpoint, {
                    method: 'POST',
                    body: { uuid },
                })

                this.rememberRemovedDrafts(this.removedDrafts().filter((entry) => entry !== uuid))
            } catch (error) {
                if (/^4\d\d$/.test(error.message)) {
                    this.rememberRemovedDrafts(this.removedDrafts().filter((entry) => entry !== uuid))
                }
            }
        }
    }

    pullDrafts() {
        if (!this.sharesDrafts || !navigator.onLine) {
            return Promise.resolve()
        }

        this.draftPull ??= this.requestJson(this.boot.session.drafts_endpoint)
            .then((body) => this.mergeDrafts(body.data ?? []))
            .catch((error) => {
                if (error.message === '409') {
                    this.state.sessionClosed = true
                }
            })
            .finally(() => {
                this.draftPull = null
            })

        return this.draftPull
    }

    mergeDrafts(orders) {
        const removed = new Set(this.removedDrafts())

        const serverOrders = orders.filter((order) => !removed.has(order.uuid))

        const serverUuids = new Set(serverOrders.map((order) => order.uuid))

        const busy = (order) => order.uuid === this.state.activeOrderUuid
            || order.uuid === this.state.transferOrderUuid
            || order.state !== 'draft'
            || order.draft_dirty

        for (const order of this.state.orders.filter((entry) => entry.serverId && !busy(entry) && !serverUuids.has(entry.uuid))) {
            this.database.remove('pos.order.line', order.lines.map((line) => line.uuid))
            this.database.remove('pos.payment', order.payments.map((payment) => payment.uuid))
            this.database.remove('pos.order', [order.uuid])

            this.state.orders = this.state.orders.filter((entry) => entry.uuid !== order.uuid)
        }

        for (const serverOrder of serverOrders) {
            const index = this.state.orders.findIndex((entry) => entry.uuid === serverOrder.uuid)

            if (index === -1) {
                this.state.orders.push(this.hydrateServerOrder(serverOrder))

                continue
            }

            const local = this.state.orders[index]

            if (busy(local)) {
                continue
            }

            const fresh = this.hydrateServerOrder({ ...serverOrder, payments: local.payments })

            fresh.last_screen = local.last_screen

            const kept = new Set(fresh.lines.map((line) => line.uuid))

            this.database.remove('pos.order.line', local.lines.map((line) => line.uuid).filter((uuid) => !kept.has(uuid)))

            this.state.orders.splice(index, 1, fresh)
        }
    }

    async shareDrafts(order = null) {
        if (!this.sharesDrafts || this.state.sessionClosed) {
            return
        }

        if (order) {
            order.draft_dirty = true
        }

        await this.flushRemovedDrafts()

        for (const pending of this.state.orders.filter((entry) => entry.draft_dirty && entry.state === 'draft')) {
            await this.pushDraft(pending)
        }

        await this.pullDrafts()
    }

    startDraftRefresh() {
        if (this.draftRefresh || !this.sharesDrafts) {
            return
        }

        this.draftRefresh = window.setInterval(() => {
            if (this.state.screen === 'floor' && !document.hidden) {
                this.shareDrafts()
            }
        }, DRAFT_REFRESH_INTERVAL)

        this.shareDrafts()
    }

    openGuests() {
        const order = this.activeOrder

        if (!order) {
            return
        }

        this.state.guestsDraft = order.customer_count ?? 0
        this.state.guestsModalOpen = true
    }

    closeGuests() {
        this.state.guestsModalOpen = false
    }

    adjustGuests(step) {
        this.state.guestsDraft = Math.max(0, Math.trunc(Number(this.state.guestsDraft) || 0) + step)
    }

    confirmGuests() {
        const order = this.activeOrder

        this.state.guestsModalOpen = false

        if (!order) {
            return
        }

        order.customer_count = Math.max(0, Math.trunc(Number(this.state.guestsDraft) || 0))

        if (this.isRestaurant && order.customer_count === 0 && !order.lines.length && !order.payments.length) {
            this.goToFloor()
        }
    }

    get canSplit() {
        const order = this.activeOrder

        return Boolean(this.isRestaurant && this.config.enable_split_bill && order)
            && order.lines.reduce((carry, line) => carry + Math.abs(line.qty), 0) >= 2
    }

    openSplit() {
        if (!this.canSplit) {
            return
        }

        this.closeActions()

        this.state.splitQuantities = {}
        this.state.screen = 'split'
    }

    closeSplit() {
        this.state.splitQuantities = {}
        this.state.screen = 'products'
    }

    splitsWhole(line) {
        return !Number.isInteger(line.qty) || line.lots?.length > 0 || this.isLockedLine(line)
    }

    tapSplitLine(line) {
        const current = this.state.splitQuantities[line.uuid] ?? 0

        let next = current + 1

        if (this.splitsWhole(line)) {
            next = current === line.qty ? 0 : line.qty
        } else if (current >= line.qty) {
            next = 0
        }

        this.state.splitQuantities = { ...this.state.splitQuantities, [line.uuid]: next }
    }

    get splitTotal() {
        const order = this.activeOrder

        if (!order) {
            return 0
        }

        return order.lines.reduce((carry, line) => {
            const quantity = this.state.splitQuantities[line.uuid] ?? 0

            if (!quantity || !line.qty) {
                return carry
            }

            return carry + (this.lineTotals(line).total / line.qty) * quantity
        }, 0)
    }

    orderBaseName(order) {
        return this.orderTable(order)?.table_number ?? order.floating_name ?? ''
    }

    nextSplitName(order) {
        const base = String(this.orderBaseName(order) || order.tracking_number || '')

        const taken = this.drafts
            .map((entry) => entry.floating_name)
            .filter((name) => name && name.length === base.length + 1 && name.startsWith(base))
            .map((name) => name.slice(-1))
            .sort()

        const last = taken.at(-1)

        if (!last) {
            return `${base}B`
        }

        if (last >= 'Z') {
            return null
        }

        return `${base}${String.fromCharCode(last.charCodeAt(0) + 1)}`
    }

    confirmSplit() {
        const original = this.activeOrder

        if (!original) {
            return
        }

        const moving = original.lines.filter((line) => (this.state.splitQuantities[line.uuid] ?? 0) > 0)

        if (!moving.length) {
            return
        }

        const name = this.nextSplitName(original)

        if (!name) {
            return
        }

        const target = this.newOrder(null)

        target.floating_name = name
        target.partner_id = original.partner_id
        target.price_list_id = original.price_list_id
        target.fiscal_position_id = original.fiscal_position_id
        target.is_takeaway = original.is_takeaway

        const emptied = []

        for (const line of moving) {
            const quantity = this.state.splitQuantities[line.uuid]

            target.lines.push(reactive({
                ...line,
                lots: [...(line.lots ?? [])],
                uuid: uuidv4(),
                order_uuid: target.uuid,
                qty: quantity,
            }))

            const remaining = floatRound(line.qty - quantity, { precisionDigits: 4 })

            if (floatIsZero(remaining, { precisionDigits: 4 })) {
                emptied.push(line.uuid)
            } else {
                line.qty = remaining
            }
        }

        if (emptied.length) {
            this.database.remove('pos.order.line', emptied)

            original.lines = original.lines.filter((line) => !emptied.includes(line.uuid))
        }

        original.customer_count = Math.max(0, (original.customer_count ?? 0) - 1)

        this.state.splitQuantities = {}

        this.selectOrder(target.uuid)
    }

    get printsReceipts() {
        return this.config.enable_receipt_print !== false
    }

    get printsReceiptAutomatically() {
        return this.printsReceipts && Boolean(this.config.enable_receipt_auto_print)
    }

    openBill() {
        if (!this.activeOrder?.lines.length) {
            return
        }

        this.closeActions()

        this.state.billModalOpen = true
    }

    closeBill() {
        this.state.billModalOpen = false
    }

    get canToggleTakeaway() {
        return Boolean(this.isRestaurant && this.config.enable_takeaway && this.activeOrder)
    }

    toggleTakeaway() {
        const order = this.activeOrder

        if (!order || !this.canToggleTakeaway) {
            return
        }

        order.is_takeaway = !order.is_takeaway

        order.fiscal_position_id = order.is_takeaway
            ? (this.config.takeaway_fiscal_position_id ?? this.config.fiscal_position_id ?? null)
            : (this.config.fiscal_position_id ?? null)

        this.closeActions()
    }

    get canRenameOrder() {
        return Boolean(this.isRestaurant && this.activeOrder && !this.activeOrder.table_id)
    }

    openOrderName() {
        if (!this.canRenameOrder) {
            return
        }

        this.closeActions()

        this.state.orderNameDraft = this.activeOrder.floating_name ?? ''
        this.state.orderNameModalOpen = true
    }

    closeOrderName() {
        this.state.orderNameModalOpen = false
    }

    confirmOrderName() {
        const order = this.activeOrder

        if (order) {
            order.floating_name = String(this.state.orderNameDraft ?? '').trim().slice(0, 64)
        }

        this.state.orderNameModalOpen = false
    }

    get globalDiscountProductId() {
        const productId = this.config.discount_product_id

        return this.config.enable_global_discount && productId && this.master.products.get(productId) ? productId : null
    }

    isDiscountLine(line) {
        return Boolean(this.globalDiscountProductId) && line.product_id === this.globalDiscountProductId
    }

    isLockedLine(line) {
        return this.isTipLine(line) || this.isDiscountLine(line)
    }

    get canApplyGlobalDiscount() {
        return Boolean(this.globalDiscountProductId)
            && Boolean(this.activeOrder?.lines.some((line) => !this.isLockedLine(line)))
    }

    openGlobalDiscount() {
        if (!this.canApplyGlobalDiscount) {
            return
        }

        this.closeActions()

        this.state.globalDiscountDraft = String(this.config.global_discount_percentage ?? 10)
        this.state.globalDiscountModalOpen = true
    }

    closeGlobalDiscount() {
        this.state.globalDiscountModalOpen = false
    }

    confirmGlobalDiscount() {
        this.applyGlobalDiscount(Number(this.state.globalDiscountDraft) || 0)

        this.state.globalDiscountModalOpen = false
    }

    discountableBase(line) {
        const totals = this.lineTotals(line)

        const excluded = totals.breakdown
            .filter((tax) => !tax.price_include)
            .reduce((carry, tax) => carry + tax.amount, 0)

        return totals.total - excluded
    }

    applyGlobalDiscount(percentage) {
        const order = this.activeOrder

        const productId = this.globalDiscountProductId

        if (!order || !productId) {
            return
        }

        const rate = Math.min(Math.max(percentage, 0), 100)

        for (const line of order.lines.filter((entry) => this.isDiscountLine(entry))) {
            this.removeLine(line.uuid)
        }

        const groups = new Map()

        for (const line of order.lines) {
            if (this.isLockedLine(line)) {
                continue
            }

            const taxIds = [...(line.tax_ids ?? [])].sort((a, b) => a - b)

            const key = taxIds.join(',')

            const group = groups.get(key) ?? { taxIds, base: 0 }

            group.base += this.discountableBase(line)

            groups.set(key, group)
        }

        for (const { taxIds, base } of groups.values()) {
            const amount = floatRound(-(rate / 100) * base, { precisionRounding: this.currency.rounding })

            if (amount >= 0) {
                continue
            }

            order.lines.push(reactive({
                uuid: uuidv4(),
                order_uuid: order.uuid,
                product_id: productId,
                qty: 1,
                price_unit: amount,
                price_overridden: true,
                discount: 0,
                note: '',
                lots: [],
                tax_ids: taxIds.filter((id) => this.taxById.get(id)?.amount_type !== 'fixed'),
            }))
        }
    }

    get tipProductId() {
        const productId = this.config.tip_product_id

        return this.config.enable_tip && productId && this.master.products.get(productId) ? productId : null
    }

    isTipLine(line) {
        return Boolean(this.tipProductId) && line.product_id === this.tipProductId
    }

    tipLine(order = this.activeOrder) {
        return order?.lines.find((line) => this.isTipLine(line)) ?? null
    }

    tipAmount(order = this.activeOrder) {
        return this.tipLine(order)?.price_unit ?? 0
    }

    openTip() {
        const order = this.activeOrder

        if (!order || !this.tipProductId) {
            return
        }

        const tip = this.tipAmount(order)

        const change = this.orderTotals(order).change

        const start = tip === 0 && change > 0 ? change : tip

        this.state.tipDraft = start ? String(floatRound(start, { precisionRounding: this.currency.rounding })) : ''
        this.state.tipModalOpen = true
    }

    closeTip() {
        this.state.tipModalOpen = false
    }

    confirmTip() {
        this.setTip(Number(this.state.tipDraft) || 0)

        this.state.tipModalOpen = false
    }

    setTip(amount) {
        const order = this.activeOrder

        if (!order || !this.tipProductId) {
            return
        }

        const tip = Math.max(0, floatRound(amount, { precisionRounding: this.currency.rounding }))

        const line = this.tipLine(order)

        if (floatIsZero(tip, { precisionRounding: this.currency.rounding })) {
            if (line) {
                this.removeLine(line.uuid)
            }

            return
        }

        if (line) {
            line.price_unit = tip
            line.qty = 1

            return
        }

        const product = this.master.products.get(this.tipProductId)

        order.lines.push(reactive({
            uuid: uuidv4(),
            order_uuid: order.uuid,
            product_id: this.tipProductId,
            qty: 1,
            price_unit: tip,
            price_overridden: true,
            discount: 0,
            note: '',
            lots: [],
            tax_ids: product?.tax_ids ?? [],
        }))
    }

    startIdleWatch() {
        if (this.idleWatch || !this.isRestaurant) {
            return
        }

        this.idleWatch = () => {
            window.clearTimeout(this.idleTimer)

            this.idleTimer = window.setTimeout(() => this.returnToFloorWhenIdle(), FLOOR_IDLE_TIMEOUT)
        }

        window.addEventListener('pointerdown', this.idleWatch)
        window.addEventListener('keydown', this.idleWatch)

        this.idleWatch()
    }

    returnToFloorWhenIdle() {
        if (this.state.screen !== 'products' || document.querySelector('.fi-modal-open')) {
            this.idleWatch?.()

            return
        }

        this.goToFloor()
    }

    selectOrder(uuid) {
        this.state.activeOrderUuid = uuid
        this.state.activeLineUuid = null
        this.state.screen = this.resumeScreenFor(this.activeOrder)

        this.resetNumpadBuffer()

        this.state.priceListId = this.activeOrder?.price_list_id ?? this.config.price_list_id ?? null

        this.flushDeferredEvictions()
    }

    get priceLists() {
        return this.master.price_lists.all()
    }

    get activePriceList() {
        return this.master.price_lists.get(this.state.priceListId) ?? null
    }

    canSelectPriceList() {
        return Boolean(this.config.enable_price_list) && this.priceLists.length > 0
    }

    openActions() {
        this.state.actionsModalOpen = true
    }

    closeActions() {
        this.state.actionsModalOpen = false
        this.state.cancelError = null
    }

    openPriceLists() {
        this.state.actionsModalOpen = false
        this.state.priceListModalOpen = true
    }

    closePriceLists() {
        this.state.priceListModalOpen = false
    }

    selectPriceList(priceListId) {
        this.state.priceListId = priceListId

        const order = this.activeOrder

        if (order) {
            order.price_list_id = priceListId

            for (const line of order.lines) {
                if (!line.price_overridden) {
                    line.price_unit = this.priceFor(line.product_id)
                }
            }
        }

        this.closePriceLists()
    }

    async cancelActiveOrder() {
        const order = this.activeOrder

        if (!order || this.state.cancellingOrder) {
            return
        }

        if (order.serverId && !(await this.cancelOnServer(order))) {
            return
        }

        this.closeActions()

        this.discardOrder(order.uuid)
    }

    async cancelOnServer(order) {
        this.state.cancellingOrder = true
        this.state.cancelError = null

        try {
            const response = await fetch(this.boot.config.order_cancel_endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
                },
                body: JSON.stringify({ uuid: order.uuid }),
            })

            if (!response.ok) {
                const body = await response.json().catch(() => ({}))

                this.state.cancelError = body.message ?? this.t('actions.cancel-order.failed', { status: response.status })

                return false
            }

            return true
        } catch (error) {
            this.state.cancelError = navigator.onLine
                ? error.message
                : this.t('actions.cancel-order.offline')

            return false
        } finally {
            this.state.cancellingOrder = false
        }
    }

    discardOrder(uuid) {
        const order = this.state.orders.find((entry) => entry.uuid === uuid)

        if (!order) {
            return
        }

        this.removeDraftOnServer(order)

        this.database.remove('pos.order.line', order.lines.map((line) => line.uuid))
        this.database.remove('pos.payment', order.payments.map((payment) => payment.uuid))
        this.database.remove('pos.order', [uuid])

        this.state.orders = this.state.orders.filter((entry) => entry.uuid !== uuid)

        if (this.state.activeOrderUuid === uuid || !this.drafts.length) {
            this.activateDraft()
        }
    }

    priceFor(productId) {
        const listKey = String(this.state.priceListId ?? 0)

        const list = this.prices[listKey] ?? this.prices['0'] ?? {}

        const price = list[String(productId)]

        if (price !== undefined) {
            return Number(price)
        }

        return Number(this.master.products.get(productId)?.price ?? 0)
    }

    taxesFor(line) {
        const order = this.state.orders.find((entry) => entry.uuid === line.order_uuid)

        const mapped = this.mapTaxIds(line.tax_ids ?? [], order ? order.fiscal_position_id : this.state.fiscalPositionId)

        return mapped.map((id) => this.taxById.get(id)).filter(Boolean)
    }

    mapTaxIds(taxIds, fiscalPositionId = this.state.fiscalPositionId) {
        if (!fiscalPositionId || !taxIds.length) {
            return taxIds
        }

        const position = this.master.fiscal_positions.get(fiscalPositionId)

        if (!position || !position.tax_map.length) {
            return taxIds
        }

        const mapped = []

        for (const taxId of taxIds) {
            const matches = position.tax_map.filter((entry) => entry.source_id === taxId)

            if (!matches.length) {
                mapped.push(taxId)

                continue
            }

            for (const match of matches) {
                if (match.destination_id && !mapped.includes(match.destination_id)) {
                    mapped.push(match.destination_id)
                }
            }
        }

        return mapped
    }

    lineTotals(line) {
        const taxes = this.taxesFor(line)

        if (!taxes.length) {
            const subtotal = floatRound(line.price_unit * (1 - line.discount / 100) * line.qty, { precisionDigits: 4 })

            return { subtotal, tax: 0, total: subtotal, breakdown: [] }
        }

        const outcome = computeAll({
            taxes,
            priceUnit: line.price_unit * (1 - line.discount / 100),
            quantity: line.qty,
            currencyRounding: this.currency.rounding,
            roundingMethod: this.roundingMethod,
        })

        return {
            subtotal: floatRound(outcome.total_excluded, { precisionDigits: 4 }),
            tax: floatRound(outcome.total_tax, { precisionDigits: 4 }),
            total: floatRound(outcome.total_included, { precisionDigits: 4 }),
            breakdown: outcome.taxes,
        }
    }

    displayLineTotal(line) {
        const totals = this.lineTotals(line)

        return this.config.tax_display === 'total' ? totals.total : totals.subtotal
    }

    orderTotals(order = this.activeOrder) {
        if (!order) {
            return { subtotal: 0, tax: 0, total: 0, rounding: 0, paid: 0, due: 0, change: 0, breakdown: [] }
        }

        let subtotal = 0
        let tax = 0

        const breakdown = new Map()

        for (const line of order.lines) {
            const totals = this.lineTotals(line)

            subtotal += totals.subtotal
            tax += totals.tax

            for (const entry of totals.breakdown) {
                const current = breakdown.get(entry.id) ?? { id: entry.id, name: entry.name, amount: 0, base: 0 }

                current.amount += entry.amount
                current.base += entry.base

                breakdown.set(entry.id, current)
            }
        }

        subtotal = floatRound(subtotal, { precisionDigits: 4 })
        tax = floatRound(tax, { precisionDigits: 4 })

        let total = floatRound(subtotal + tax, { precisionDigits: 4 })

        const rounding = this.cashRoundingFor(order, total)

        total = floatRound(total + rounding, { precisionDigits: 4 })

        const settled = order.payments
            .filter((payment) => !payment.is_change)
            .reduce((carry, payment) => carry + payment.amount, 0)

        const refund = floatCompare(total, 0, { precisionRounding: this.currency.rounding }) < 0

        const balance = floatCompare(settled, total, { precisionRounding: this.currency.rounding })

        const change = !refund && balance > 0
            ? floatRound(settled - total, { precisionRounding: this.currency.rounding })
            : 0

        return {
            subtotal,
            tax,
            total,
            rounding,
            paid: settled,
            due: floatRound(total - settled, { precisionRounding: this.currency.rounding }),
            change,
            refund,
            covered: refund ? balance <= 0 : balance >= 0,
            breakdown: [...breakdown.values()],
        }
    }

    cashRoundingFor(order, total) {
        const rounding = this.config.cash_rounding

        if (!rounding) {
            return 0
        }

        if (this.config.enable_only_round_cash_method && !this.hasCashPayment(order)) {
            return 0
        }

        const rounded = floatRound(total, {
            precisionRounding: rounding.rounding,
            roundingMethod: rounding.rounding_method,
        })

        return floatRound(rounded - total, { precisionRounding: this.currency.rounding })
    }

    hasCashPayment(order) {
        return order.payments.some((payment) => {
            if (payment.is_change) {
                return false
            }

            return Boolean(this.master.payment_methods.get(payment.payment_method_id)?.is_cash_count)
        })
    }

    trackingFor(productId) {
        return this.master.products.get(productId)?.tracking ?? 'qty'
    }

    isTracked(productId) {
        return ['lot', 'serial'].includes(this.trackingFor(productId))
    }

    get lotsEnabled() {
        return Boolean(this.config.use_create_lots || this.config.use_existing_lots)
    }

    get lotLine() {
        return this.activeOrder?.lines.find((line) => line.uuid === this.state.lotLineUuid) ?? null
    }

    get lotProduct() {
        const line = this.lotLine

        return line ? this.master.products.get(line.product_id) ?? null : null
    }

    allowsOnlyOneLot(productId) {
        return this.trackingFor(productId) === 'lot'
    }

    get canCreateLots() {
        return Boolean(this.config.use_create_lots || !this.config.use_existing_lots)
    }

    existingLotsFor(productId) {
        return this.lotsByProduct.get(productId) ?? []
    }

    openLots(lineUuid) {
        const line = this.activeOrder?.lines.find((entry) => entry.uuid === lineUuid)

        if (!line) {
            return
        }

        const existing = this.existingLotsFor(line.product_id)

        if (!this.canCreateLots && !existing.length) {
            this.state.lotError = this.t('lots.none-available')
            this.state.lotLineUuid = lineUuid
            this.state.lotRows = []

            return
        }

        const captured = (line.lots ?? []).map((lot) => lot.lot_name)

        if (!captured.length && existing.length === 1) {
            this.applyLots(line, [existing[0].name])

            return
        }

        const blanks = Math.max(Math.ceil(Math.abs(line.qty) - captured.length), 1)

        const rows = this.allowsOnlyOneLot(line.product_id)
            ? [captured[0] ?? '']
            : [...captured, ...Array.from({ length: blanks }, () => '')]

        this.state.lotError = null
        this.state.lotRows = rows
        this.state.lotLineUuid = lineUuid
    }

    closeLots() {
        this.state.lotLineUuid = null
        this.state.lotRows = []
        this.state.lotError = null
    }

    setLotRow(index, value) {
        this.state.lotRows[index] = value
    }

    addLotRow() {
        this.state.lotRows.push('')
    }

    removeLotRow(index) {
        this.state.lotRows.splice(index, 1)
    }

    applyLots(line, names) {
        const serial = this.trackingFor(line.product_id) === 'serial'

        const unique = serial ? [...new Set(names)] : names

        const existing = this.existingLotsFor(line.product_id)

        if (serial && unique.length) {
            line.qty = line.qty < 0 ? -unique.length : unique.length
        }

        const share = unique.length ? Math.abs(line.qty) / unique.length : 0

        line.lots = unique.map((name) => ({
            lot_name: name,
            lot_id: existing.find((lot) => lot.name === name)?.id ?? null,
            qty: serial ? 1 : share,
        }))

        this.closeLots()
    }

    confirmLots() {
        const line = this.lotLine

        if (!line) {
            return
        }

        const names = this.state.lotRows.map((row) => String(row).trim()).filter(Boolean)

        if (!this.canCreateLots) {
            const available = this.existingLotsFor(line.product_id).map((lot) => lot.name)

            const unknown = names.filter((name) => !available.includes(name))

            if (unknown.length) {
                this.state.lotError = this.t('lots.none-available')

                return
            }
        }

        this.applyLots(line, names)
    }

    linesMissingLots(order = this.activeOrder) {
        if (!order || !this.lotsEnabled) {
            return []
        }

        return this.sellableLines(order).filter((line) => {
            if (!this.isTracked(line.product_id)) {
                return false
            }

            const captured = line.lots?.length ?? 0

            return this.trackingFor(line.product_id) === 'serial'
                ? captured !== Math.abs(line.qty)
                : captured === 0
        })
    }

    dismissLotWarning() {
        this.state.lotWarningOpen = false
    }

    bufferAmount(buffer) {
        const value = Number(buffer)

        return Number.isFinite(value) ? value : 0
    }

    pickProduct(productId) {
        const product = this.master.products.get(productId)

        if (product?.is_configurable && this.openVariants(productId)) {
            return
        }

        this.addLine(productId)
    }

    addLine(productId) {
        const line = this.addProduct(productId)

        if (line && this.lotsEnabled && this.isTracked(productId) && !line.lots?.length) {
            this.openLots(line.uuid)
        }

        return line
    }

    variantsFor(productId) {
        return this.variantsByProduct.get(productId) ?? null
    }

    get variantProduct() {
        return this.state.variantProductId ? this.master.products.get(this.state.variantProductId) : null
    }

    get variantAttributes() {
        return this.variantsFor(this.state.variantProductId)?.attributes ?? []
    }

    openVariants(productId) {
        const entry = this.variantsFor(productId)

        if (!entry) {
            return false
        }

        this.state.variantProductId = productId

        this.state.variantSelection = Object.fromEntries(
            entry.attributes.map((attribute) => [attribute.id, attribute.values[0]?.id ?? null]),
        )

        return true
    }

    closeVariants() {
        this.state.variantProductId = null
        this.state.variantSelection = {}
    }

    selectVariantValue(attributeId, valueId) {
        this.state.variantSelection[attributeId] = valueId
    }

    resolveVariant() {
        const entry = this.variantsFor(this.state.variantProductId)

        if (!entry) {
            return null
        }

        const chosen = Object.values(this.state.variantSelection).filter((id) => id !== null)

        if (chosen.length !== entry.attributes.length) {
            return null
        }

        const signature = [...chosen].sort((a, b) => a - b).join(',')

        const match = entry.variants.find((variant) => variant.value_ids.join(',') === signature)

        return match ? this.master.products.get(match.id) ?? null : null
    }

    confirmVariant() {
        const variant = this.resolveVariant()

        if (!variant) {
            return
        }

        const productId = variant.id

        this.closeVariants()

        this.addLine(productId)
    }

    addProduct(productId, { qty = 1 } = {}) {
        const order = this.activeOrder?.state === 'draft' ? this.activeOrder : this.newOrder()

        const product = this.master.products.get(productId)

        if (!product) {
            return null
        }

        const existing = order.lines.find((line) => (
            line.product_id === productId
            && line.discount === 0
            && !line.price_overridden
            && !line.note
        ))

        this.resetNumpadBuffer()

        if (existing) {
            existing.qty = floatRound(existing.qty + qty, { precisionDigits: 4 })

            this.state.activeLineUuid = existing.uuid

            return existing
        }

        const line = reactive({
            uuid: uuidv4(),
            order_uuid: order.uuid,
            product_id: productId,
            uom_id: product.uom_id,
            qty,
            price_unit: this.priceFor(productId),
            price_overridden: false,
            discount: 0,
            note: '',
            lots: [],
            tax_ids: product.tax_ids ?? [],
        })

        order.lines.push(line)

        this.state.activeLineUuid = line.uuid

        return line
    }

    selectLine(uuid) {
        this.state.activeLineUuid = this.state.activeLineUuid === uuid ? null : uuid

        this.resetNumpadBuffer()
    }

    removeLine(uuid) {
        const order = this.activeOrder

        if (!order) {
            return
        }

        this.database.remove('pos.order.line', [uuid])

        order.lines = order.lines.filter((line) => line.uuid !== uuid)

        if (this.state.activeLineUuid === uuid) {
            this.state.activeLineUuid = null
        }
    }

    numpadModeAllowed(mode) {
        if (mode === 'discount') {
            return Boolean(this.config.enable_line_discount)
        }

        if (mode === 'price') {
            return this.config.can_edit_price !== false
        }

        return mode === 'qty'
    }

    setNumpadMode(mode) {
        if (!this.numpadModeAllowed(mode)) {
            return
        }

        this.state.numpadMode = mode

        this.resetNumpadBuffer()
    }

    resetNumpadBuffer() {
        this.state.numpadBuffer = ''
        this.state.numpadFresh = true
    }

    pressNumpad(key) {
        const line = this.activeLine

        if (!line) {
            return
        }

        if (this.isLockedLine(line) && this.state.numpadMode !== 'price') {
            if (key === 'Backspace' || key === 'Delete') {
                this.removeLine(line.uuid)

                this.resetNumpadBuffer()
            }

            return
        }

        let buffer = nextBuffer(this.state.numpadBuffer, key, this.state.numpadFresh)

        if (key === '-' && buffer === '-0') {
            buffer = this.negatedNumpadValue(line) ?? buffer
        }

        this.state.numpadBuffer = buffer ?? ''
        this.state.numpadFresh = false

        if (buffer === null) {
            if (this.state.numpadMode === 'qty') {
                this.removeLine(line.uuid)
            }

            this.state.numpadMode = 'qty'

            this.resetNumpadBuffer()

            return
        }

        this.applyNumpad(line, this.bufferAmount(buffer))
    }

    negatedNumpadValue(line) {
        if (this.state.numpadMode === 'qty') {
            return line.refunded_order_line_id ? null : String(-line.qty)
        }

        if (this.state.numpadMode === 'discount') {
            return String(-line.discount)
        }

        if (this.state.numpadMode === 'price') {
            return String(-line.price_unit)
        }

        return null
    }

    applyNumpad(line, value) {
        if (this.state.numpadMode === 'qty') {
            line.qty = value
        } else if (this.state.numpadMode === 'discount') {
            line.discount = Math.min(Math.max(value, 0), 100)
        } else if (this.state.numpadMode === 'price') {
            line.price_unit = value
            line.price_overridden = true
        }
    }

    selectCustomer(partnerId) {
        const order = this.activeOrder

        if (order && !this.isCustomerLocked) {
            order.partner_id = partnerId
        }
    }

    createCustomer(draft) {
        if (!draft.name || !draft.name.trim()) {
            return null
        }

        const partner = {
            id: `local-${uuidv4()}`,
            name: draft.name.trim(),
            email: draft.email?.trim() || null,
            phone: draft.phone?.trim() || null,
            mobile: draft.mobile?.trim() || null,
            street1: draft.street1?.trim() || null,
            city: draft.city?.trim() || null,
            zip: draft.zip?.trim() || null,
            is_local: true,
        }

        this.master.partners.put(partner)

        this.selectCustomer(partner.id)

        return partner
    }

    partnerDraftFor(order) {
        if (!order.partner_id || typeof order.partner_id !== 'string') {
            return null
        }

        const partner = this.master.partners.get(order.partner_id)

        if (!partner || !partner.is_local) {
            return null
        }

        return {
            name: partner.name,
            email: partner.email,
            phone: partner.phone,
            mobile: partner.mobile,
            street1: partner.street1,
            city: partner.city,
            zip: partner.zip,
        }
    }

    searchPartners(term) {
        const needle = term.trim().toLowerCase()

        if (!needle) {
            return this.master.partners.all().slice(0, 50)
        }

        return this.master.partners
            .filter((partner) => [partner.name, partner.email, partner.phone, partner.mobile]
                .some((field) => field && String(field).toLowerCase().includes(needle)))
            .slice(0, 50)
    }

    rootCategories() {
        return this.master.categories.filter((category) => !this.master.categories.get(category.parent_id))
    }

    childCategories(categoryId) {
        return this.master.categories.getBy('parent_id', categoryId)
    }

    categoryPath(categoryId) {
        const path = []

        let category = this.master.categories.get(categoryId)

        while (category && !path.includes(category)) {
            path.unshift(category)

            category = this.master.categories.get(category.parent_id)
        }

        return path
    }

    visibleCategories(categoryId) {
        const path = this.categoryPath(categoryId)

        const expand = (categories) => categories.flatMap((category) => (
            path.includes(category)
                ? [category, ...expand(this.childCategories(category.id))]
                : [category]
        ))

        return expand(this.rootCategories())
    }

    categoryWithDescendantIds(categoryId) {
        const ids = new Set([categoryId])

        const pending = [categoryId]

        while (pending.length) {
            for (const child of this.childCategories(pending.shift())) {
                if (!ids.has(child.id)) {
                    ids.add(child.id)

                    pending.push(child.id)
                }
            }
        }

        return ids
    }

    selectCategory(categoryId) {
        if (this.state.categoryId !== categoryId) {
            this.state.categoryId = categoryId

            return
        }

        const parentId = this.master.categories.get(categoryId)?.parent_id

        this.state.categoryId = this.master.categories.get(parentId) ? parentId : null
    }

    searchProducts(term, categoryId) {
        const needle = term.trim().toLowerCase()

        const categoryIds = categoryId ? this.categoryWithDescendantIds(categoryId) : null

        return this.master.products.filter((product) => {
            if (product.parent_id || product.is_hidden) {
                return false
            }

            if (categoryIds && !(product.category_ids ?? []).some((id) => categoryIds.has(id))) {
                return false
            }

            if (!needle) {
                return true
            }

            return [product.name, product.reference, product.barcode]
                .some((field) => field && String(field).toLowerCase().includes(needle))
        })
    }

    get cameraScanSupported() {
        return typeof window !== 'undefined' && 'BarcodeDetector' in window
    }

    startWedge() {
        if (this.wedge) {
            return
        }

        let buffer = ''
        let timer = null

        this.wedge = (event) => {
            const editing = event.target instanceof HTMLInputElement
                || event.target instanceof HTMLTextAreaElement
                || event.target instanceof HTMLSelectElement
                || event.target?.isContentEditable

            if (event.key === 'Enter') {
                const code = buffer.trim()

                buffer = ''

                if (code.length < 3) {
                    return
                }

                if (this.scan(code) && editing) {
                    event.preventDefault()
                }

                return
            }

            if (event.key.length !== 1) {
                return
            }

            buffer += event.key

            window.clearTimeout(timer)

            timer = window.setTimeout(() => { buffer = '' }, 120)
        }

        window.addEventListener('keydown', this.wedge)
    }

    startKeyboard() {
        if (this.keyboard) {
            return
        }

        let keys = []

        const flush = () => {
            const pressed = keys

            keys = []

            if (pressed.length > KEYBOARD_BURST_LIMIT || pressed.some((key) => !BUFFER_KEYS.has(key))) {
                return
            }

            pressed.forEach((key) => this.pressKeyboardKey(key))
        }

        this.keyboard = (event) => {
            if (event.repeat || !this.acceptsKeyboard(event) || MODIFIER_KEYS.has(event.key)) {
                return
            }

            keys.push(event.key)

            window.clearTimeout(this.keyboardTimer)

            this.keyboardTimer = window.setTimeout(flush, KEYBOARD_BURST_WINDOW)
        }

        window.addEventListener('keydown', this.keyboard)
    }

    acceptsKeyboard(event) {
        if (!this.state.ready || event.ctrlKey || event.metaKey || event.altKey) {
            return false
        }

        const target = event.target

        const editing = target instanceof HTMLInputElement
            || target instanceof HTMLTextAreaElement
            || target instanceof HTMLSelectElement
            || target?.isContentEditable

        if (editing || this.state.cameraScanning || document.querySelector('.fi-modal-open')) {
            return false
        }

        return this.state.screen === 'products' || this.state.screen === 'payment'
    }

    pressKeyboardKey(key) {
        if (this.state.screen === 'payment') {
            this.pressPaymentKey(key)

            return
        }

        this.pressNumpad(key)
    }

    stop() {
        if (this.wedge) {
            window.removeEventListener('keydown', this.wedge)

            this.wedge = null
        }

        if (this.keyboard) {
            window.removeEventListener('keydown', this.keyboard)

            window.clearTimeout(this.keyboardTimer)

            this.keyboard = null
        }

        if (this.draftRefresh) {
            window.clearInterval(this.draftRefresh)

            this.draftRefresh = null
        }

        if (this.idleWatch) {
            window.removeEventListener('pointerdown', this.idleWatch)
            window.removeEventListener('keydown', this.idleWatch)

            window.clearTimeout(this.idleTimer)

            this.idleWatch = null
        }
    }

    scan(code) {
        const product = this.productByBarcode(code)

        if (product) {
            this.pickProduct(product.id)

            return true
        }

        const partner = this.master.partners.firstBy('barcode', code)

        if (partner) {
            this.selectCustomer(partner.id)

            return true
        }

        const line = this.activeLine

        if (line && this.isTracked(line.product_id) && this.lotsEnabled) {
            this.state.lotLineUuid = line.uuid
            this.state.lotRows = [...(line.lots ?? []).map((lot) => lot.lot_name), code]

            this.confirmLots()

            return true
        }

        return false
    }

    productByBarcode(barcode) {
        return this.master.products.firstBy('barcode', barcode)
    }

    freeQty(productId) {
        return Number(this.stock[String(productId)] ?? this.stock[productId] ?? 0)
    }

    addPayment(paymentMethodId, amount = null) {
        const order = this.activeOrder

        if (!order) {
            return null
        }

        const totals = this.orderTotals(order)

        if (amount === null) {
            const outstanding = this.outstandingFor(totals)

            const reusable = order.payments.find((line) => (
                line.payment_method_id === paymentMethodId
                && !line.is_change
                && (floatIsZero(line.amount, { precisionRounding: this.currency.rounding })
                    || floatIsZero(outstanding, { precisionRounding: this.currency.rounding }))
            ))

            if (reusable) {
                if (!floatIsZero(outstanding, { precisionRounding: this.currency.rounding })) {
                    reusable.amount = outstanding
                }

                this.selectPayment(reusable.uuid)

                return reusable
            }
        }

        const payment = reactive({
            uuid: uuidv4(),
            order_uuid: order.uuid,
            payment_method_id: paymentMethodId,
            amount: amount === null ? 0 : amount,
            is_change: false,
        })

        order.payments.push(payment)

        if (amount === null) {
            payment.amount = this.outstandingFor(this.orderTotals(order))
        }

        this.selectPayment(payment.uuid)

        return payment
    }

    outstandingFor(totals) {
        return totals.refund ? Math.min(totals.due, 0) : Math.max(totals.due, 0)
    }

    toggleToInvoice() {
        const order = this.activeOrder

        if (order) {
            order.to_invoice = !order.to_invoice
        }
    }

    canShipLater() {
        return Boolean(this.config.enable_ship_later)
    }

    get today() {
        return new Date().toISOString().split('T')[0]
    }

    toggleShipLater() {
        const order = this.activeOrder

        if (!order) {
            return
        }

        if (order.shipped_at) {
            order.shipped_at = null

            return
        }

        this.state.shipLaterDraft = this.today
        this.state.shipLaterModalOpen = true
    }

    closeShipLater() {
        this.state.shipLaterModalOpen = false
    }

    confirmShipLater() {
        const order = this.activeOrder

        if (order) {
            const draft = this.state.shipLaterDraft

            order.shipped_at = !draft || draft < this.today ? this.today : draft
        }

        this.state.shipLaterModalOpen = false
    }

    get activePayment() {
        return this.activeOrder?.payments.find((payment) => payment.uuid === this.state.activePaymentUuid)
    }

    selectPayment(uuid) {
        this.state.activePaymentUuid = uuid

        this.resetPaymentBuffer()
    }

    resetPaymentBuffer() {
        this.state.paymentBuffer = ''
        this.state.paymentFresh = true
    }

    pressPaymentKey(key) {
        const order = this.activeOrder

        if (order && !order.payments.length && this.startsPaymentLine(key)) {
            const method = this.master.payment_methods.all()[0]

            if (method) {
                this.addPayment(method.id)
            }
        }

        const payment = this.activePayment

        if (!payment) {
            return
        }

        if (key === 'Backspace' && this.state.paymentFresh) {
            this.state.paymentBuffer = this.paymentAmountBuffer(payment)
            this.state.paymentFresh = false
        }

        const buffer = nextBuffer(this.state.paymentBuffer, key, this.state.paymentFresh)

        this.state.paymentBuffer = buffer ?? ''
        this.state.paymentFresh = false

        payment.amount = this.bufferAmount(buffer ?? '')
    }

    startsPaymentLine(key) {
        return /^\d$/.test(key) || /^\+\d+(\.\d+)?$/.test(key)
    }

    paymentAmountBuffer(payment) {
        if (floatIsZero(payment.amount, { precisionRounding: this.currency.rounding })) {
            return ''
        }

        return String(floatRound(payment.amount, { precisionRounding: this.currency.rounding }))
    }

    removePayment(uuid) {
        const order = this.activeOrder

        if (!order) {
            return
        }

        this.database.remove('pos.payment', [uuid])

        order.payments = order.payments.filter((payment) => payment.uuid !== uuid)

        if (this.state.activePaymentUuid === uuid) {
            this.state.activePaymentUuid = order.payments[0]?.uuid ?? null

            this.resetPaymentBuffer()
        }
    }

    sellableLines(order) {
        return order.lines.filter((line) => !floatIsZero(line.qty, { precisionDigits: 4 }))
    }

    canValidate(order = this.activeOrder) {
        if (!order || !this.sellableLines(order).length) {
            return false
        }

        if (this.config.enable_customer_required && !order.partner_id) {
            return false
        }

        if ((order.to_invoice || order.shipped_at) && !order.partner_id) {
            return false
        }

        return this.orderTotals(order).covered
    }

    orderPayload(order) {
        const payments = order.payments
            .filter((payment) => !payment.is_change)
            .map((payment) => ({
                uuid: payment.uuid,
                payment_method_id: payment.payment_method_id,
                amount: payment.amount,
            }))

        const totals = this.orderTotals(order)

        const partnerDraft = this.partnerDraftFor(order)

        return {
            uuid: order.uuid,
            reference: order.pos_reference,
            tracking_number: order.tracking_number,
            sequence_number: order.sequence_number,
            config_id: this.config.id,
            session_id: this.boot.session.id,
            partner_id: partnerDraft ? null : order.partner_id,
            floating_name: order.floating_name || null,
            is_booked: Boolean(order.is_booked),
            partner: partnerDraft,
            price_list_id: order.price_list_id,
            fiscal_position_id: order.fiscal_position_id,
            is_takeaway: order.is_takeaway,
            table_id: order.table_id ?? null,
            customer_count: order.customer_count ?? 0,
            is_to_invoice: order.to_invoice,
            refunded_order_id: order.refunded_order_id ?? null,
            shipped_at: order.shipped_at,
            ordered_at: order.validated_at ?? null,
            note: order.note,
            amount_total: totals.total,
            lines: this.sellableLines(order).map((line) => {
                const payload = {
                    uuid: line.uuid,
                    product_id: line.product_id,
                    qty: line.qty,
                    discount: line.discount,
                    note: line.note,
                    tax_ids: line.tax_ids,
                }

                if (line.price_overridden) {
                    payload.price_unit = line.price_unit
                    payload.price_type = 'manual'
                }

                if (line.refunded_order_line_id) {
                    payload.refunded_order_line_id = line.refunded_order_line_id
                }

                if (line.lots?.length) {
                    payload.lots = line.lots.map((lot) => ({
                        lot_name: lot.lot_name,
                        lot_id: lot.lot_id ?? null,
                        qty: Number(lot.qty ?? 1),
                    }))
                }

                return payload
            }),
            payments,
        }
    }

    validate() {
        const order = this.activeOrder

        if (!order || !this.canValidate(order)) {
            return null
        }

        const totals = this.orderTotals(order)

        if (totals.change > 0) {
            order.payments.push(reactive({
                uuid: `${order.uuid}-change`,
                order_uuid: order.uuid,
                payment_method_id: this.master.payment_methods.find((method) => method.is_cash_count)?.id ?? null,
                amount: -totals.change,
                is_change: true,
            }))
        }

        order.state = 'paid'
        order.validated_at = new Date().toISOString()

        this.queue.push({ uuid: order.uuid, payload: this.orderPayload(order) })

        this.state.autoPrintOrderUuid = this.printsReceiptAutomatically ? order.uuid : null

        this.state.screen = 'receipt'

        this.queue.flush()

        return order
    }
}

export function useTotals(till) {
    return computed(() => till.orderTotals())
}
