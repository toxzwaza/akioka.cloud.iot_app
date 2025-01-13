self.addEventListener('install', event => {
    console.log('Service Worker: Installing...');

    event.waitUntil(
        caches.open('pwa-cache-v1').then(cache => {
            console.log('Service Worker: Caching files...');
            return cache.addAll([
                '/', // ホームページ
                '{{ Vite::asset("resources/css/app.css") }}', // ビルド後のCSSファイル
                '{{ Vite::asset("resources/js/app.js") }}',  // ビルド後のJSファイル
                '/offline.html', // オフラインページ（必要に応じて作成）
            ]);
        }).then(() => {
            console.log('Service Worker: Install completed');
        }).catch(error => {
            console.error('Service Worker: Install failed', error);
        })
    );
});

self.addEventListener('activate', event => {
    console.log('Service Worker: Activating...');
    const cacheWhitelist = ['pwa-cache-v1'];

    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cacheName => {
                    if (!cacheWhitelist.includes(cacheName)) {
                        console.log('Service Worker: Deleting old cache:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        }).then(() => {
            console.log('Service Worker: Activation completed');
        })
    );
});

self.addEventListener('fetch', event => {
    console.log('Service Worker: Fetching:', event.request.url);

    event.respondWith(
        caches.match(event.request).then(response => {
            return response || fetch(event.request).catch(() => {
                if (event.request.mode === 'navigate') {
                    return caches.match('/offline.html');
                }
            });
        })
    );
});
