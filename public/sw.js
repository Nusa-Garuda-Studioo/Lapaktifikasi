const CACHE_NAME = 'lapaktifikasi-pwa-v3';
const OFFLINE_URL = '/offline';

// Minimal critical precache to prevent delaying first visit on Slow 4G
const PRECACHE_ASSETS = [
    OFFLINE_URL,
    '/assets/img/favicon.png',
    '/assets/img/pwa/icon-192x192.png'
];

// 1. Install Event: Pre-cache core offline assets only
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(PRECACHE_ASSETS).catch((err) => {
                console.warn('[PWA SW] Pre-cache warning:', err);
            });
        }).then(() => self.skipWaiting())
    );
});

// 2. Activate Event: Clean up outdated caches
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cache) => {
                    if (cache !== CACHE_NAME) {
                        console.log('[PWA SW] Purging old cache version:', cache);
                        return caches.delete(cache);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

// 3. Fetch Event: Multi-tier caching strategy
self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);

    // Skip non-GET requests (POST, PUT, DELETE should never be intercepted)
    if (request.method !== 'GET') {
        return;
    }

    // Never intercept or cache sw.js and manifest.json (Guarantees instant PWA updates)
    if (url.pathname === '/sw.js' || url.pathname === '/manifest.json') {
        return;
    }

    // Skip dynamic APIs, payment gateways, admin mutation routes, webpush, and extensions
    if (
        url.pathname.startsWith('/api/') ||
        url.pathname.startsWith('/webpush/') ||
        url.pathname.startsWith('/metode_pembayaran/') ||
        url.pathname.startsWith('/admin/') ||
        url.pathname.startsWith('/seller/mutasi') ||
        url.pathname.startsWith('/livewire/') ||
        url.origin.includes('midtrans') ||
        url.origin.includes('tripay') ||
        url.origin.includes('pakasir') ||
        url.protocol.startsWith('chrome-extension')
    ) {
        return;
    }

    // A. Navigation / HTML Requests (Network-First with Offline Fallback)
    if (request.mode === 'navigate' || (request.headers.get('accept') && request.headers.get('accept').includes('text/html'))) {
        event.respondWith(
            fetch(request)
                .then((networkResponse) => {
                    // Only cache successful public GET requests (skip redirects or auth-gated errors)
                    if (networkResponse && networkResponse.status === 200) {
                        const responseClone = networkResponse.clone();
                        caches.open(CACHE_NAME).then((cache) => {
                            cache.put(request, responseClone);
                        });
                    }
                    return networkResponse;
                })
                .catch(async () => {
                    // Network failed: Serve cached copy of the page if available
                    const cachedResponse = await caches.match(request);
                    if (cachedResponse) {
                        return cachedResponse;
                    }
                    // Fallback to custom offline view
                    const offlineFallback = await caches.match(OFFLINE_URL);
                    return offlineFallback || new Response('Koneksi terputus. Silakan periksa jaringan Anda.', {
                        headers: { 'Content-Type': 'text/plain; charset=utf-8' }
                    });
                })
        );
        return;
    }

    // B. Vite Hashed Assets (/build/*): Cache-First (Immutable)
    // Filename hash ensures content uniqueness forever. If in cache, return instantly.
    if (url.pathname.startsWith('/build/')) {
        event.respondWith(
            caches.match(request).then((cachedResponse) => {
                if (cachedResponse) {
                    return cachedResponse;
                }
                return fetch(request).then((networkResponse) => {
                    if (networkResponse && networkResponse.status === 200) {
                        const responseClone = networkResponse.clone();
                        caches.open(CACHE_NAME).then((cache) => {
                            cache.put(request, responseClone);
                        });
                    }
                    return networkResponse;
                });
            })
        );
        return;
    }

    // C. Static Unhashed Assets (/assets/*, /css/*, fonts, images): Stale-While-Revalidate
    if (
        request.destination === 'style' ||
        request.destination === 'script' ||
        request.destination === 'font' ||
        request.destination === 'image' ||
        url.pathname.startsWith('/assets/') ||
        url.pathname.startsWith('/css/')
    ) {
        event.respondWith(
            caches.match(request).then((cachedResponse) => {
                const fetchPromise = fetch(request).then((networkResponse) => {
                    if (networkResponse && networkResponse.status === 200) {
                        const responseToCache = networkResponse.clone();
                        caches.open(CACHE_NAME).then((cache) => {
                            cache.put(request, responseToCache);
                        });
                    }
                    return networkResponse;
                }).catch(() => {
                    // Offline fallback handled by cached response
                });

                return cachedResponse || fetchPromise;
            })
        );
        return;
    }

    // Default network fallback
    event.respondWith(
        fetch(request).catch(() => caches.match(request))
    );
});

// 4. Message Event: Skip waiting when new version ready
self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});

// 5. Push Event: Show native Web Push Notification
self.addEventListener('push', (event) => {
    let data = {
        title: 'Lapaktifikasi',
        body: 'Ada pembaruan penting untuk Anda.',
        icon: '/assets/img/pwa/icon-192x192.png',
        badge: '/assets/img/pwa/icon-96x96.png',
        url: '/'
    };

    if (event.data) {
        try {
            const parsed = event.data.json();
            data = Object.assign(data, parsed);
        } catch (e) {
            data.body = event.data.text();
        }
    }

    const options = {
        body: data.body,
        icon: data.icon || '/assets/img/pwa/icon-192x192.png',
        badge: data.badge || '/assets/img/pwa/icon-96x96.png',
        vibrate: [100, 50, 100],
        data: {
            url: data.url || '/'
        },
        actions: [
            { action: 'open', title: 'Buka' },
            { action: 'close', title: 'Tutup' }
        ]
    };

    event.waitUntil(
        self.registration.showNotification(data.title, options)
    );
});

// 6. Notification Click Event: Navigate to target URL
self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    if (event.action === 'close') {
        return;
    }

    const targetUrl = event.notification.data && event.notification.data.url
        ? event.notification.data.url
        : '/';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windowClients) => {
            for (let client of windowClients) {
                if (client.url === targetUrl && 'focus' in client) {
                    return client.focus();
                }
            }
            if (clients.openWindow) {
                return clients.openWindow(targetUrl);
            }
        })
    );
});
