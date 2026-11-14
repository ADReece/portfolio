/*
 * Downloading several photos as one ZIP (selections, sets, collections,
 * favourites), and choosing photos in a gallery grid.
 *
 *   window.portfolioDownload({ collection_id, set_id, photo_ids })
 *
 * asks the server what to do: small downloads start straight away (the ZIP
 * streams), big ones are prepared in the background while a dialog shows
 * progress and offers the file when it's ready (or emails the link).
 */

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';
const toast = (message, type = 'info') => (window.showToast ? window.showToast(message, type) : window.alert(message));
const plural = (n, word) => `${n} ${word}${n === 1 ? '' : 's'}`;

function request(url, { method = 'GET', body } = {}) {
    return fetch(url, {
        method,
        credentials: 'same-origin',
        headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
        body: body ? JSON.stringify(body) : undefined,
    }).then((r) => r.json().catch(() => ({})).then((data) => {
        if (!r.ok) {
            const message = data.message || (r.status === 429 ? 'Too many downloads at once. Please wait a minute and try again.' : 'Something went wrong. Please try again.');
            throw Object.assign(new Error(message), { data });
        }
        return data;
    }));
}

/* ---------- the progress dialog for big downloads ---------- */

let dialog;
function progressDialog() {
    if (dialog) return dialog;
    dialog = document.createElement('dialog');
    dialog.setAttribute('aria-labelledby', 'pf-dl-title');
    dialog.className = 'portfolio-neutral w-[min(30rem,calc(100%-2rem))] rounded-card bg-white p-0 text-gray-900 shadow-2xl backdrop:bg-black/50 dark:bg-gray-900 dark:text-gray-100';
    dialog.innerHTML = `
        <div class="space-y-4 p-6">
            <h2 id="pf-dl-title" class="text-lg font-semibold">Preparing your download</h2>
            <p data-dl-status role="status" aria-live="polite" class="text-sm"></p>
            <div class="h-2 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700" aria-hidden="true">
                <div data-dl-bar class="h-full w-0 rounded-full bg-gray-900 transition-[width] dark:bg-white"></div>
            </div>
            <a data-dl-file hidden class="inline-flex items-center gap-2 rounded-control bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700 dark:bg-white dark:text-gray-900">Download</a>
            <form data-dl-email class="border-t border-gray-200 pt-4 dark:border-gray-700">
                <label for="pf-dl-email" class="block text-sm font-medium">Email me the link too <span class="font-normal text-gray-500">(optional)</span></label>
                <div class="mt-1 flex flex-wrap gap-2">
                    <input id="pf-dl-email" name="email" type="email" required maxlength="255" autocomplete="email" class="min-w-0 flex-1 rounded-control border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-800">
                    <button type="submit" class="rounded-control border border-gray-300 px-3 py-2 text-sm font-semibold hover:bg-gray-100 dark:border-gray-600 dark:hover:bg-gray-800">Email me</button>
                </div>
                <p data-dl-email-status role="status" class="mt-2 text-sm"></p>
            </form>
            <p class="text-sm text-gray-600 dark:text-gray-400">You can keep browsing. <a data-dl-page class="underline underline-offset-4" target="_blank" rel="noopener">Open this download in a new tab</a> to come back to it later.</p>
            <div class="flex justify-end"><button type="button" data-dl-close class="rounded-control px-4 py-2 text-sm font-semibold hover:bg-gray-100 dark:hover:bg-gray-800">Close</button></div>
        </div>`;
    document.body.appendChild(dialog);
    dialog.querySelector('[data-dl-close]').addEventListener('click', () => dialog.close());
    return dialog;
}

function showBackground(result) {
    const d = progressDialog();
    const status = d.querySelector('[data-dl-status]');
    const bar = d.querySelector('[data-dl-bar]');
    const file = d.querySelector('[data-dl-file]');
    const form = d.querySelector('[data-dl-email]');
    const emailStatus = d.querySelector('[data-dl-email-status]');
    const token = {};
    d.currentToken = token;

    d.querySelector('#pf-dl-title').textContent = 'Preparing your download';
    d.querySelector('[data-dl-page]').href = result.status_url;
    status.textContent = `Gathering ${plural(result.count, 'photo')} into ${result.name}…` + (result.note ? ` ${result.note}` : '');
    bar.style.width = '0%';
    file.hidden = true;
    form.hidden = false;
    emailStatus.textContent = '';
    form.onsubmit = (e) => {
        e.preventDefault();
        emailStatus.textContent = 'Saving…';
        request(result.email_url, { method: 'POST', body: { email: form.elements.namedItem('email').value } })
            .then((r) => { emailStatus.textContent = r.message; })
            .catch((err) => { emailStatus.textContent = err.message; });
    };
    if (!d.open) d.showModal();

    const poll = () => {
        if (d.currentToken !== token) return; // a newer download took over the dialog
        request(result.status_url).then((s) => {
            bar.style.width = `${s.total ? Math.round((s.done / s.total) * 100) : 0}%`;
            if (s.status === 'ready') {
                d.querySelector('#pf-dl-title').textContent = 'Your download is ready';
                status.textContent = `${plural(s.total, 'photo')} in ${s.name}.`;
                file.href = s.download_url;
                file.textContent = `Download ${s.name}`;
                file.hidden = false;
                file.focus();
                return;
            }
            if (s.status === 'failed' || s.status === 'expired') {
                status.textContent = s.message || "We couldn't prepare this download. Please try again.";
                form.hidden = true;
                return;
            }
            status.textContent = `Preparing your photos… ${s.done} of ${s.total}`;
            setTimeout(poll, 2500);
        }).catch(() => setTimeout(poll, 5000));
    };
    setTimeout(poll, 1500);
}

/* ---------- starting a download ---------- */

window.portfolioDownload = function (payload, button) {
    if (button) button.disabled = true;
    return request(window.portfolioDownloadUrl || '/downloads/prepare', { method: 'POST', body: payload })
        .then((result) => {
            if (result.mode === 'instant') {
                toast(`Downloading ${plural(result.count, 'photo')} as ${result.name}` + (result.note ? `. ${result.note}` : ''), 'success');
                // The ZIP is sent as an attachment, so the page stays where it is.
                window.location.assign(result.url);
            } else {
                showBackground(result);
            }
            return result;
        })
        .catch((err) => {
            if (err.data?.buy_url) {
                toast(err.message, 'info');
                setTimeout(() => window.location.assign(err.data.buy_url), 1200);
            } else {
                toast(err.message, 'error');
            }
        })
        .finally(() => { if (button) button.disabled = false; });
};

// Buttons anywhere: <button data-download='{"collection_id": "…"}'>Download all</button>
document.addEventListener('click', (e) => {
    const button = e.target.closest('[data-download]');
    if (!button) return;
    e.preventDefault();
    let payload = {};
    try { payload = JSON.parse(button.getAttribute('data-download') || '{}'); } catch { /* empty payload */ }
    button.closest('details')?.removeAttribute('open');
    window.portfolioDownload(payload, button);
});

/* ---------- choosing photos in a gallery grid ---------- */

function gallerySelection(root) {
    const selected = new Set();
    let anchor = null;
    const collectionId = root.dataset.collectionId || null;
    const buyUrl = root.dataset.buyUrl || null;
    const selecting = () => document.body.classList.contains('pf-selecting');

    // The order photos appear in (the grid's own data, not its columns).
    const order = () => Array.from(document.querySelectorAll('#masonry-template .masonry-item[data-photo-id]'), (el) => el.dataset.photoId);

    const bar = document.createElement('div');
    bar.className = 'pf-select-bar portfolio-neutral fixed inset-x-0 bottom-4 z-50 mx-auto flex w-fit max-w-[calc(100%-1rem)] flex-wrap items-center justify-center gap-2 rounded-2xl bg-white px-3 py-2 text-sm text-gray-900 shadow-xl ring-1 ring-gray-200 dark:bg-gray-900 dark:text-gray-100 dark:ring-gray-700';
    bar.hidden = true;
    bar.setAttribute('role', 'region');
    bar.setAttribute('aria-label', 'Selected photos');
    const btn = 'rounded-full px-3 py-1.5 font-semibold hover:bg-gray-100 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-gray-500 dark:hover:bg-gray-800';
    bar.innerHTML = `
        <span aria-live="polite" class="px-1"><span data-sel-count>0</span> selected</span>
        <button type="button" data-sel-all class="${btn}">Select all</button>
        <button type="button" data-sel-clear class="${btn}">Clear</button>
        <button type="button" data-sel-go class="rounded-full bg-gray-900 px-4 py-1.5 font-semibold text-white hover:bg-gray-700 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-gray-500 disabled:opacity-50 dark:bg-white dark:text-gray-900"></button>
        <button type="button" data-sel-done class="${btn}">Done</button>`;
    document.body.appendChild(bar);
    const go = bar.querySelector('[data-sel-go]');

    function sync(scope = document) {
        scope.querySelectorAll('#masonry .masonry-item[data-photo-id]').forEach((item) => {
            const on = selected.has(item.dataset.photoId);
            item.classList.toggle('is-selected', on);
            item.querySelector('[data-select-photo]')?.setAttribute('aria-pressed', on ? 'true' : 'false');
        });
        const n = selected.size;
        bar.querySelector('[data-sel-count]').textContent = n;
        go.textContent = buyUrl ? `Buy selected (${n})` : `Download selected (${n})`;
        go.disabled = n === 0;
        const loaded = order().length;
        bar.querySelector('[data-sel-all]').textContent = Number(root.dataset.total || loaded) > loaded ? `Select all ${loaded} shown` : 'Select all';
    }

    function setMode(on) {
        document.body.classList.toggle('pf-selecting', on);
        bar.hidden = !on;
        root.querySelectorAll('[data-select-mode]').forEach((b) => b.setAttribute('aria-pressed', on ? 'true' : 'false'));
        if (!on) { selected.clear(); anchor = null; }
        sync();
        if (on) bar.querySelector('[data-sel-all]').focus({ preventScroll: true });
    }

    function toggle(id, range) {
        const ids = order();
        if (range && anchor && ids.includes(anchor)) {
            const [a, b] = [ids.indexOf(anchor), ids.indexOf(id)].sort((x, y) => x - y);
            const turnOn = !selected.has(id) || selected.has(anchor);
            ids.slice(a, b + 1).forEach((x) => (turnOn ? selected.add(x) : selected.delete(x)));
        } else {
            selected.has(id) ? selected.delete(id) : selected.add(id);
        }
        anchor = id;
        sync();
    }

    // Entering select mode: the toolbar's "Select" button, or any [data-select-mode] button.
    document.addEventListener('click', (e) => {
        const modeButton = e.target.closest('[data-select-mode]');
        if (modeButton) {
            e.preventDefault();
            setMode(!selecting());
            if (selecting()) document.getElementById(root.dataset.scrollTo || '')?.scrollIntoView({ behavior: 'smooth' });
            return;
        }
        if (!selecting()) return;
        // In select mode a tap or click on a photo selects it (rather than opening it).
        const item = e.target.closest('#masonry .masonry-item[data-photo-id]');
        if (item) {
            e.preventDefault();
            e.stopPropagation();
            toggle(item.dataset.photoId, e.shiftKey);
        }
    }, true);

    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && selecting()) setMode(false); });

    bar.querySelector('[data-sel-all]').addEventListener('click', () => { order().forEach((id) => selected.add(id)); sync(); });
    bar.querySelector('[data-sel-clear]').addEventListener('click', () => { selected.clear(); anchor = null; sync(); });
    bar.querySelector('[data-sel-done]').addEventListener('click', () => setMode(false));
    go.addEventListener('click', () => {
        const ids = order().filter((id) => selected.has(id));
        if (!ids.length) return;
        if (buyUrl) {
            const url = new URL(buyUrl, window.location.href);
            ids.forEach((id) => url.searchParams.append('photo[]', id));
            window.location.assign(url.toString());
            return;
        }
        window.portfolioDownload({ collection_id: collectionId, photo_ids: ids }, go);
    });

    // The grid copies photos into its columns as it lays them out: keep new ones in step.
    new MutationObserver((records) => {
        if (!selecting()) return;
        records.forEach((r) => r.addedNodes.forEach((n) => { if (n.querySelectorAll) sync(n.parentElement || document); }));
    }).observe(document.body, { childList: true, subtree: true });
}

function initGallerySelection() {
    const root = document.querySelector('[data-gallery-select]');
    if (root && !root.dataset.selectReady) {
        root.dataset.selectReady = '1';
        gallerySelection(root);
    }
}
document.addEventListener('DOMContentLoaded', initGallerySelection);
document.addEventListener('livewire:navigated', initGallerySelection);
