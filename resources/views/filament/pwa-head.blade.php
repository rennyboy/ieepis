<!-- PWA Manifest -->
<link rel="manifest" href="/manifest.json">

<!-- iOS Support -->
<meta name="apple-mobile-web-app-status-bar" content="#16a34a">
<meta name="apple-mobile-web-app-capable" content="yes">
<link rel="apple-touch-icon" href="/images/ieepis-logo.png">

<!-- PWA Theme Color -->
<meta name="theme-color" content="#16a34a">

<!-- Register Service Worker -->
<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function() {
            navigator.serviceWorker.register('/sw.js').then(function(registration) {
                // Registration was successful
                console.log('ServiceWorker registration successful with scope: ', registration.scope);
            }, function(err) {
                // registration failed :(
                console.log('ServiceWorker registration failed: ', err);
            });
        });
    }
</script>
