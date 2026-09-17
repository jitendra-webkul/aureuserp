const QUEUE_KEY = 'point-of-sale.pending-orders'

const readQueue = () => {
    try {
        return JSON.parse(window.localStorage.getItem(QUEUE_KEY) || '[]')
    } catch (error) {
        return []
    }
}

const writeQueue = (orders) => {
    try {
        window.localStorage.setItem(QUEUE_KEY, JSON.stringify(orders))
    } catch (error) {
        return
    }
}

const removeFromQueue = (uuids) => {
    if (! uuids.length) {
        return
    }

    writeQueue(readQueue().filter((order) => ! uuids.includes(order.uuid)))
}

const markFailed = (errors) => {
    if (! errors.length) {
        return
    }

    const messages = new Map(errors.filter((error) => error.uuid).map((error) => [error.uuid, error.message]))

    writeQueue(readQueue().map((order) => messages.has(order.uuid)
        ? { ...order, attempts: (order.attempts ?? 0) + 1, last_error: messages.get(order.uuid) }
        : order))
}

const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''

window.pointOfSaleQueue = {
    key: QUEUE_KEY,

    all() {
        return readQueue()
    },

    size() {
        return readQueue().length
    },

    failures() {
        return readQueue().filter((order) => order.last_error)
    },

    remove(uuid) {
        removeFromQueue([uuid])

        return this.size()
    },

    push(order) {
        const orders = readQueue()

        if (orders.some((queued) => queued.uuid === order.uuid)) {
            return this.size()
        }

        orders.push(order)

        writeQueue(orders)

        return orders.length
    },

    clear() {
        writeQueue([])
    },

    async flush(endpoint) {
        const orders = readQueue()

        if (! orders.length || ! navigator.onLine) {
            return { synced: 0, failed: 0, pending: orders.length }
        }

        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
                body: JSON.stringify({ orders }),
            })

            if (! response.ok) {
                return { synced: 0, failed: 0, pending: orders.length }
            }

            const payload = await response.json()

            const synced = (payload.data ?? []).map((order) => order.uuid)

            const errors = (payload.errors ?? []).filter((error) => error.uuid)

            const failed = errors.map((error) => error.uuid)

            removeFromQueue(synced)

            markFailed(errors)

            window.dispatchEvent(new CustomEvent('point-of-sale:queue-flushed', {
                detail: { synced: synced.length, failed: failed.length, pending: this.size() },
            }))

            return { synced: synced.length, failed: failed.length, pending: this.size() }
        } catch (error) {
            return { synced: 0, failed: 0, pending: orders.length }
        }
    },

    watch(endpoint, intervalMs = 15000) {
        const flush = () => this.flush(endpoint)

        window.addEventListener('online', flush)

        window.setInterval(flush, intervalMs)

        flush()
    },
}
