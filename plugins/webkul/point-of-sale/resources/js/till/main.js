import { createApp } from 'vue'
import App from './App.vue'
import { Till } from './store/till.js'

async function mountTill(element) {
    const boot = JSON.parse(element.dataset.boot)

    const till = new Till(boot.data ?? boot, {
        syncEndpoint: element.dataset.syncEndpoint,
        accessToken: element.dataset.accessToken ?? 'anonymous',
    })

    await till.start()

    const app = createApp(App)

    app.provide('till', till)

    app.mount(element)

    window.pointOfSaleTill = till
}

function boot() {
    const element = document.getElementById('pos-till')

    if (!element || element.dataset.mounted === 'true') {
        return
    }

    element.dataset.mounted = 'true'

    mountTill(element)
}

document.addEventListener('DOMContentLoaded', boot)
document.addEventListener('livewire:navigated', boot)

if (document.readyState !== 'loading') {
    boot()
}
