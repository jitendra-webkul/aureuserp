import { reactive } from 'vue'

const FLUSH_INTERVAL = 15000

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
}

export class SyncQueue {
    constructor(database, { endpoint }) {
        this.database = database
        this.endpoint = endpoint
        this.flushing = false
        this.timer = null

        this.state = reactive({
            offline: !navigator.onLine,
            pending: [],
            lastError: null,
            lastSyncedAt: null,
        })
    }

    get pendingCount() {
        return this.state.pending.length
    }

    push(entry) {
        const existing = this.state.pending.find((pending) => pending.uuid === entry.uuid)

        if (existing) {
            return existing
        }

        const queued = {
            uuid: entry.uuid,
            payload: entry.payload,
            attempts: 0,
            lastError: null,
            queuedAt: new Date().toISOString(),
        }

        this.state.pending.push(queued)

        this.persist()

        return queued
    }

    remove(uuid) {
        const position = this.state.pending.findIndex((pending) => pending.uuid === uuid)

        if (position !== -1) {
            this.state.pending.splice(position, 1)

            this.persist()
        }
    }

    persist() {
        try {
            window.localStorage.setItem(
                `pos.sync-queue.${this.endpoint}`,
                JSON.stringify(this.state.pending),
            )
        } catch {
            this.state.lastError = 'queue could not be persisted'
        }
    }

    restore() {
        try {
            const raw = window.localStorage.getItem(`pos.sync-queue.${this.endpoint}`)

            if (raw) {
                this.state.pending = JSON.parse(raw)
            }
        } catch {
            this.state.pending = []
        }
    }

    watch() {
        this.restore()

        window.addEventListener('online', () => {
            this.state.offline = false

            this.flush()
        })

        window.addEventListener('offline', () => {
            this.state.offline = true
        })

        this.timer = window.setInterval(() => this.flush(), FLUSH_INTERVAL)

        this.flush()
    }

    stop() {
        if (this.timer) {
            window.clearInterval(this.timer)

            this.timer = null
        }
    }

    async flush() {
        if (this.flushing || this.state.pending.length === 0 || !navigator.onLine) {
            return { synced: 0, failed: 0, pending: this.pendingCount }
        }

        this.flushing = true

        try {
            const orders = this.state.pending.map((pending) => pending.payload)

            const response = await fetch(this.endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
                body: JSON.stringify({ orders }),
            })

            if (!response.ok) {
                this.state.lastError = `sync failed with status ${response.status}`

                return { synced: 0, failed: 0, pending: this.pendingCount }
            }

            const body = await response.json()

            const synced = (body.data ?? []).map((entry) => entry.uuid).filter(Boolean)

            const failed = (body.errors ?? []).filter((entry) => entry.uuid)

            for (const uuid of synced) {
                this.remove(uuid)
            }

            for (const failure of failed) {
                const pending = this.state.pending.find((entry) => entry.uuid === failure.uuid)

                if (pending) {
                    pending.attempts += 1
                    pending.lastError = failure.message ?? 'unknown error'
                }
            }

            this.persist()

            this.state.lastSyncedAt = new Date().toISOString()

            this.state.lastError = failed.length ? `${failed.length} order(s) rejected` : null

            window.dispatchEvent(new CustomEvent('point-of-sale:queue-flushed', {
                detail: { synced: synced.length, failed: failed.length, pending: this.pendingCount, uuids: synced },
            }))

            return { synced: synced.length, failed: failed.length, pending: this.pendingCount }
        } catch (error) {
            this.state.offline = !navigator.onLine

            this.state.lastError = error.message

            return { synced: 0, failed: 0, pending: this.pendingCount }
        } finally {
            this.flushing = false
        }
    }
}
