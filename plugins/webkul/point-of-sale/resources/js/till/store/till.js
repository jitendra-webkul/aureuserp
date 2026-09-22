import { reactive, computed, watch } from 'vue'
import { computeAll } from '../tax/computer.js'
import { floatRound, floatCompare, floatIsZero } from '../tax/float.js'
import { TillDatabase } from './indexed-db.js'
import { createRecordSets, MASTER_INDEXES, TRANSACTIONAL_MODELS } from './records.js'
import { SyncQueue } from './sync-queue.js'

const PERSIST_DEBOUNCE = 200

const DATABASE_VERSION = 1

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
            numpadMode: 'qty',
            numpadBuffer: '',
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
            productModalOpen: false,
            actionsModalOpen: false,
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
        const restored = await this.restore()

        if (!restored.length) {
            for (const order of this.boot.orders ?? []) {
                this.state.orders.push(this.hydrateServerOrder(order))
            }
        }

        if (!this.state.orders.length) {
            this.newOrder()
        } else {
            const remembered = this.rememberedOrderUuid()

            this.state.activeOrderUuid = this.state.orders.some((order) => order.uuid === remembered)
                ? remembered
                : this.state.orders[0].uuid
        }

        this.watchActiveOrder()

        this.queue.watch()

        window.addEventListener('point-of-sale:queue-flushed', (event) => {
            this.evictSynced(event.detail.uuids ?? [])
        })

        this.watchPersistence()

        this.startWedge()

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

        const highest = restored.reduce((carry, order) => {
            const number = parseInt(order.tracking_number, 10)

            return Number.isNaN(number) ? carry : Math.max(carry, number % 100)
        }, this.state.sequenceNumber)

        this.state.sequenceNumber = highest

        const seen = new Set()

        for (const order of restored) {
            order.tracking_number ??= this.nextTrackingNumber()

            order.pos_reference ||= this.nextReference(this.state.sequenceNumber)

            if (seen.has(order.pos_reference)) {
                order.tracking_number = this.nextTrackingNumber()

                order.pos_reference = this.nextReference(this.state.sequenceNumber)
            }

            seen.add(order.pos_reference)

            this.state.orders.push(reactive(order))
        }

        return restored
    }

    hydrateServerOrder(order) {
        const trackingNumber = order.tracking_number ?? this.nextTrackingNumber()

        return reactive({
            uuid: order.uuid ?? uuidv4(),
            serverId: order.id ?? null,
            pos_reference: order.pos_reference || this.nextReference(this.state.sequenceNumber),
            tracking_number: trackingNumber,
            partner_id: order.partner_id ?? null,
            state: order.state ?? 'draft',
            note: order.note ?? '',
            is_takeaway: Boolean(order.is_takeaway),
            to_invoice: Boolean(order.to_invoice),
            shipped_at: order.shipped_at ?? null,
            price_list_id: order.price_list_id ?? this.state.priceListId,
            fiscal_position_id: order.fiscal_position_id ?? this.state.fiscalPositionId,
            created_at: order.created_at ?? new Date().toISOString(),
            lines: (order.lines ?? []).map((line) => ({
                uuid: line.uuid ?? uuidv4(),
                order_uuid: order.uuid,
                product_id: line.product_id,
                qty: Number(line.qty ?? 1),
                price_unit: Number(line.price_unit ?? 0),
                price_overridden: Boolean(line.price_overridden),
                discount: Number(line.discount ?? 0),
                note: line.note ?? '',
                lots: line.lots ?? [],
                tax_ids: line.tax_ids ?? [],
            })),
            payments: (order.payments ?? []).map((payment) => ({
                uuid: payment.uuid ?? uuidv4(),
                order_uuid: order.uuid,
                payment_method_id: payment.payment_method_id,
                amount: Number(payment.amount ?? 0),
                is_change: Boolean(payment.is_change),
            })),
        })
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
                partner_id: order.partner_id,
                state: order.state,
                note: order.note,
                is_takeaway: order.is_takeaway,
                to_invoice: order.to_invoice,
                shipped_at: order.shipped_at,
                price_list_id: order.price_list_id,
                fiscal_position_id: order.fiscal_position_id,
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

        if (!this.state.orders.length) {
            this.newOrder()
        } else if (!this.activeOrder) {
            this.state.activeOrderUuid = this.state.orders[0].uuid
        }
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

        const formatted = new Intl.NumberFormat(this.locale.code || document.documentElement.lang || 'en', {
            minimumFractionDigits: currency.decimal_places ?? 2,
            maximumFractionDigits: currency.decimal_places ?? 2,
        }).format(amount ?? 0)

        const symbol = currency.symbol ?? ''

        return currency.position === 'after' ? `${formatted} ${symbol}`.trim() : `${symbol}${formatted}`.trim()
    }

    openOrders() {
        this.state.ordersSearch = ''
        this.state.ordersModalOpen = true
    }

    closeOrders() {
        this.state.ordersModalOpen = false
    }

    orderLabel(order) {
        if (order.partner_id) {
            return this.master.partners.get(order.partner_id)?.name ?? order.pos_reference
        }

        return order.pos_reference ?? order.tracking_number
    }

    orderQuantity(order) {
        return order.lines.reduce((carry, line) => carry + line.qty, 0)
    }

    searchOrders(term) {
        const needle = term.trim().toLowerCase()

        if (!needle) {
            return this.drafts
        }

        return this.drafts.filter((order) => {
            const haystack = [
                this.orderLabel(order),
                order.pos_reference,
                order.tracking_number,
                ...order.lines.map((line) => this.master.products.get(line.product_id)?.name),
            ]

            return haystack.some((field) => field && String(field).toLowerCase().includes(needle))
        })
    }

    cartQuantityByProduct() {
        const quantities = new Map()

        for (const line of this.activeOrder?.lines ?? []) {
            quantities.set(line.product_id, (quantities.get(line.product_id) ?? 0) + line.qty)
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

        this.state.screen = 'payment'
    }

    goToProducts() {
        this.state.screen = 'products'
    }

    get activeOrder() {
        return this.state.orders.find((order) => order.uuid === this.state.activeOrderUuid)
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

    nextTrackingNumber() {
        this.state.sequenceNumber += 1

        return String(((this.boot.session.id % 10) * 100) + (this.state.sequenceNumber % 100))
    }

    newOrder() {
        this.flushDeferredEvictions()

        this.state.priceListId = this.config.price_list_id ?? null

        const order = this.hydrateServerOrder({
            uuid: uuidv4(),
            lines: [],
            payments: [],
            price_list_id: this.config.price_list_id ?? null,
            fiscal_position_id: this.state.fiscalPositionId,
            is_takeaway: this.state.isTakeaway,
        })

        this.state.orders.push(order)

        this.state.activeOrderUuid = order.uuid
        this.state.activeLineUuid = null
        this.state.screen = 'products'

        return order
    }

    selectOrder(uuid) {
        this.state.activeOrderUuid = uuid
        this.state.activeLineUuid = null
        this.state.screen = 'products'

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

    cancelActiveOrder() {
        const uuid = this.state.activeOrderUuid

        if (!uuid) {
            return
        }

        this.closeActions()

        this.discardOrder(uuid)
    }

    discardOrder(uuid) {
        const order = this.state.orders.find((entry) => entry.uuid === uuid)

        if (!order) {
            return
        }

        this.database.remove('pos.order.line', order.lines.map((line) => line.uuid))
        this.database.remove('pos.payment', order.payments.map((payment) => payment.uuid))
        this.database.remove('pos.order', [uuid])

        this.state.orders = this.state.orders.filter((entry) => entry.uuid !== uuid)

        if (!this.state.orders.length) {
            this.newOrder()
        } else if (this.state.activeOrderUuid === uuid) {
            this.state.activeOrderUuid = this.state.orders[0].uuid
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
        const mapped = this.mapTaxIds(line.tax_ids ?? [])

        return mapped.map((id) => this.taxById.get(id)).filter(Boolean)
    }

    mapTaxIds(taxIds) {
        if (!this.state.fiscalPositionId || !taxIds.length) {
            return taxIds
        }

        const position = this.master.fiscal_positions.get(this.state.fiscalPositionId)

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

        const change = floatCompare(settled, total, { precisionRounding: this.currency.rounding }) > 0
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
        const order = this.activeOrder ?? this.newOrder()

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

        if (existing) {
            existing.qty = floatRound(existing.qty + qty, { precisionDigits: 4 })

            this.state.activeLineUuid = existing.uuid

            return existing
        }

        const line = reactive({
            uuid: uuidv4(),
            order_uuid: order.uuid,
            product_id: productId,
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
        this.state.numpadBuffer = ''
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
        this.state.numpadBuffer = ''
    }

    pressNumpad(key) {
        const line = this.activeLine

        if (!line) {
            return
        }

        if (key === 'clear') {
            this.state.numpadBuffer = ''

            this.applyNumpad(line, 0)

            return
        }

        if (key === 'backspace') {
            if (this.state.numpadBuffer !== '') {
                this.state.numpadBuffer = this.state.numpadBuffer.slice(0, -1)

                this.applyNumpad(line, this.bufferAmount(this.state.numpadBuffer))

                return
            }

            this.resetActiveField(line)

            return
        }

        if (key === '+/-') {
            this.state.numpadBuffer = this.state.numpadBuffer.startsWith('-')
                ? this.state.numpadBuffer.slice(1)
                : `-${this.state.numpadBuffer}`

            this.applyNumpad(line, this.bufferAmount(this.state.numpadBuffer))

            return
        }

        if (key === '.' && this.state.numpadBuffer.includes('.')) {
            return
        }

        this.state.numpadBuffer += key

        this.applyNumpad(line, this.bufferAmount(this.state.numpadBuffer))
    }

    resetActiveField(line) {
        if (this.state.numpadMode === 'discount') {
            line.discount = 0

            return
        }

        if (this.state.numpadMode === 'price') {
            line.price_unit = 0
            line.price_overridden = true

            return
        }

        line.qty = 0
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

        if (order) {
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

    searchProducts(term, categoryId) {
        const needle = term.trim().toLowerCase()

        return this.master.products.filter((product) => {
            if (product.parent_id) {
                return false
            }

            if (categoryId && !(product.category_ids ?? []).includes(categoryId)) {
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
            const outstanding = Math.max(totals.due, 0)

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
            payment.amount = Math.max(this.orderTotals(order).due, 0)
        }

        this.selectPayment(payment.uuid)

        return payment
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
        this.state.paymentBuffer = ''
    }

    pressPaymentKey(key) {
        const payment = this.activePayment

        if (!payment) {
            return
        }

        if (key === 'backspace') {
            if (this.state.paymentBuffer.length > 1) {
                this.state.paymentBuffer = this.state.paymentBuffer.slice(0, -1)
                payment.amount = this.bufferAmount(this.state.paymentBuffer)

                return
            }

            this.state.paymentBuffer = ''
            payment.amount = 0

            return
        }

        if (key.startsWith('+')) {
            const bump = Number(key.slice(1))

            payment.amount = floatRound(payment.amount + bump, { precisionRounding: this.currency.rounding })
            this.state.paymentBuffer = String(payment.amount)

            return
        }

        if (key === '+/-') {
            payment.amount = -payment.amount
            this.state.paymentBuffer = String(payment.amount)

            return
        }

        if (key === '.' && this.state.paymentBuffer.includes('.')) {
            return
        }

        this.state.paymentBuffer += key
        payment.amount = this.bufferAmount(this.state.paymentBuffer)
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
            this.state.paymentBuffer = ''
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

        const totals = this.orderTotals(order)

        return floatCompare(totals.paid, totals.total, { precisionRounding: this.currency.rounding }) >= 0
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
            config_id: this.config.id,
            session_id: this.boot.session.id,
            partner_id: partnerDraft ? null : order.partner_id,
            partner: partnerDraft,
            price_list_id: order.price_list_id,
            fiscal_position_id: order.fiscal_position_id,
            is_takeaway: order.is_takeaway,
            is_to_invoice: order.to_invoice,
            shipped_at: order.shipped_at,
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

        this.state.screen = 'receipt'

        this.queue.flush()

        return order
    }
}

export function useTotals(till) {
    return computed(() => till.orderTotals())
}
