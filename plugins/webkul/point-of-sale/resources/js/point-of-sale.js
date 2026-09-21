const stylesheetsReady = (frameDocument) => {
    const links = [...frameDocument.querySelectorAll('link[rel="stylesheet"]')]

    if (! links.length) {
        return Promise.resolve()
    }

    return Promise.race([
        Promise.all(links.map((link) => link.sheet
            ? Promise.resolve()
            : new Promise((resolve) => {
                link.addEventListener('load', resolve, { once: true })
                link.addEventListener('error', resolve, { once: true })
            }))),
        new Promise((resolve) => window.setTimeout(resolve, 3000)),
    ])
}

window.pointOfSaleReceipt = {
    async print(target) {
        const source = typeof target === 'string' ? document.querySelector(target) : target

        if (! source) {
            return
        }

        const receipt = source.cloneNode(true)

        receipt.classList.remove('hidden')

        const frame = document.createElement('iframe')

        frame.setAttribute('aria-hidden', 'true')
        frame.setAttribute('title', 'receipt')
        frame.style.cssText = 'position:fixed;inset-block-start:0;inset-inline-start:-10000px;width:210mm;height:297mm;border:0'

        document.body.appendChild(frame)

        const frameDocument = frame.contentDocument

        const dir = document.documentElement.dir || 'ltr'
        const lang = document.documentElement.lang || 'en'

        frameDocument.open()
        frameDocument.write(`<!doctype html><html dir="${dir}" lang="${lang}"><head><meta charset="utf-8"></head><body></body></html>`)
        frameDocument.close()

        for (const node of document.querySelectorAll('link[rel="stylesheet"], style')) {
            frameDocument.head.appendChild(node.cloneNode(true))
        }

        const overrides = frameDocument.createElement('style')

        overrides.textContent = '@page{margin:6mm}html,body{margin:0;padding:0;background:#fff;color:#000;color-scheme:light}.pos-receipt{display:block;margin:0;color:#000;background:#fff}.pos-receipt *{color:inherit;background:transparent;border-color:currentColor}'

        frameDocument.head.appendChild(overrides)
        frameDocument.body.appendChild(receipt)

        await stylesheetsReady(frameDocument)

        frame.contentWindow.focus()
        frame.contentWindow.print()

        window.setTimeout(() => frame.remove(), 1000)
    },
}
