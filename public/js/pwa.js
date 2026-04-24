(function () {
    if (!('serviceWorker' in navigator)) {
        return;
    }

    const baseUrl = window.APP_BASE_URL || '/stores/';
    const swUrl = baseUrl.replace(/\/?$/, '/') + 'sw.js';

    window.addEventListener('load', function () {
        navigator.serviceWorker.register(swUrl).catch(function (error) {
            console.warn('Service worker registration failed:', error);
        });
    });
})();

(function () {
    const DB_NAME = 'stores-pwa';
    const DB_VERSION = 1;
    const STORE_NAME = 'outbox';

    function isIndexedDbAvailable() {
        return 'indexedDB' in window;
    }

    function openDatabase() {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open(DB_NAME, DB_VERSION);

            request.onupgradeneeded = function () {
                const db = request.result;
                if (!db.objectStoreNames.contains(STORE_NAME)) {
                    const store = db.createObjectStore(STORE_NAME, { keyPath: 'id' });
                    store.createIndex('createdAt', 'createdAt', { unique: false });
                    store.createIndex('status', 'status', { unique: false });
                }
            };

            request.onsuccess = function () {
                resolve(request.result);
            };

            request.onerror = function () {
                reject(request.error);
            };
        });
    }

    function readFormEntries(form) {
        return Array.from(new FormData(form).entries()).map(([name, value]) => [name, String(value)]);
    }

    function createRecord(form) {
        return {
            id: window.crypto && crypto.randomUUID ? crypto.randomUUID() : ('outbox-' + Date.now() + '-' + Math.random().toString(36).slice(2)),
            title: form.dataset.offlineQueue || form.getAttribute('data-offline-queue') || 'Queued transaction',
            action: form.action,
            method: (form.method || 'POST').toUpperCase(),
            entries: readFormEntries(form),
            status: 'pending',
            createdAt: new Date().toISOString(),
            pageUrl: window.location.href
        };
    }

    async function saveRecord(record) {
        if (!isIndexedDbAvailable()) {
            return false;
        }

        const db = await openDatabase();
        return new Promise((resolve, reject) => {
            const tx = db.transaction(STORE_NAME, 'readwrite');
            tx.objectStore(STORE_NAME).put(record);
            tx.oncomplete = function () {
                resolve(true);
            };
            tx.onerror = function () {
                reject(tx.error);
            };
        });
    }

    async function getAllRecords() {
        if (!isIndexedDbAvailable()) {
            return [];
        }

        const db = await openDatabase();
        return new Promise((resolve, reject) => {
            const tx = db.transaction(STORE_NAME, 'readonly');
            const request = tx.objectStore(STORE_NAME).getAll();
            request.onsuccess = function () {
                resolve(request.result || []);
            };
            request.onerror = function () {
                reject(request.error);
            };
        });
    }

    async function deleteRecord(id) {
        if (!isIndexedDbAvailable()) {
            return false;
        }

        const db = await openDatabase();
        return new Promise((resolve, reject) => {
            const tx = db.transaction(STORE_NAME, 'readwrite');
            tx.objectStore(STORE_NAME).delete(id);
            tx.oncomplete = function () {
                resolve(true);
            };
            tx.onerror = function () {
                reject(tx.error);
            };
        });
    }

    function showPwaNotice(message) {
        const existing = document.getElementById('pwa-offline-notice');
        if (existing) {
            existing.remove();
        }

        const notice = document.createElement('div');
        notice.id = 'pwa-offline-notice';
        notice.className = 'alert alert-info';
        notice.style.position = 'fixed';
        notice.style.right = '20px';
        notice.style.bottom = '20px';
        notice.style.zIndex = '9999';
        notice.style.maxWidth = '360px';
        notice.textContent = message;
        document.body.appendChild(notice);

        setTimeout(() => {
            notice.remove();
        }, 5000);
    }

    async function queueOfflineSubmission(form) {
        const record = createRecord(form);
        await saveRecord(record);
        showPwaNotice('Saved offline. This transaction will stay in the outbox until sync is enabled.');
    }

    async function flushOutbox() {
        if (!navigator.onLine) {
            return;
        }

        const records = await getAllRecords();
        if (records.length === 0) {
            return;
        }

        const response = await fetch((window.APP_BASE_URL || '/stores/') + 'api/sync-outbox.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('input[name="csrf_token"]')?.value || ''
            },
            body: JSON.stringify({ records: records })
        });

        const data = await response.json();
        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Unable to sync offline queue');
        }

        const results = Array.isArray(data.results) ? data.results : [];
        for (const result of results) {
            if (result && result.success && result.id) {
                await deleteRecord(result.id);
            }
        }

        const syncedCount = results.filter(result => result && result.success).length;
        if (syncedCount > 0) {
            showPwaNotice(`Synced ${syncedCount} offline transaction${syncedCount === 1 ? '' : 's'} successfully.`);
        }
    }

    function bindOfflineForms() {
        const forms = document.querySelectorAll('form[data-offline-queue]');
        forms.forEach(form => {
            form.addEventListener('submit', function (event) {
                if (navigator.onLine) {
                    return;
                }

                event.preventDefault();
                queueOfflineSubmission(form).catch(error => {
                    console.error('Failed to store offline submission:', error);
                    showPwaNotice('Could not save the transaction offline. Please reconnect and try again.');
                });
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bindOfflineForms);
    } else {
        bindOfflineForms();
    }

    window.addEventListener('online', function () {
        flushOutbox().catch(error => {
            console.warn('Offline queue sync failed:', error);
        });
    });

    window.addEventListener('load', function () {
        flushOutbox().catch(error => {
            console.warn('Offline queue sync failed:', error);
        });
    });

    window.PwaOutbox = {
        saveRecord: queueOfflineSubmission,
        openDatabase,
        flushOutbox,
        getAllRecords,
        deleteRecord
    };
})();