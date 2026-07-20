const CACHE = 'augusta-consent-v1';
const ASSETS = ['/consentimento/', '/consentimento/index.html', '/consentimento/manifest.json'];

self.addEventListener('install', e => {
    self.skipWaiting();
    e.waitUntil(
        caches.open(CACHE).then(c => c.addAll(ASSETS).catch(() => {}))
    );
});

self.addEventListener('activate', e => {
    e.waitUntil(clients.claim());
});

// Network-first: usa cache se offline
self.addEventListener('fetch', e => {
    if (e.request.method !== 'GET') return;
    e.respondWith(
        fetch(e.request)
            .then(r => {
                const clone = r.clone();
                caches.open(CACHE).then(c => c.put(e.request, clone));
                return r;
            })
            .catch(() => caches.match(e.request))
    );
});