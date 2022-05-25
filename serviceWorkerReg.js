(function ($) {
  $(document).ready(function () {
    if ('serviceWorker' in navigator) {
      if (isBrowserTest()) {
        window.addEventListener('load', function () {
          navigator.serviceWorker.register('wp-content/plugins/wp-static-manager/sw.js').then(function (registration) {
            // Registration was successful
            console.log('ServiceWorker registration successful with scope: ', registration.scope);
          }, function (err) {
            // registration failed :(
            console.log('ServiceWorker registration failed: ', err);
          });
        });
      }
      if (isMobile()) {
        window.addEventListener('load', function () {
          navigator.serviceWorker.register('/sw.js').then(function (registration) {
            // Registration was successful
            console.log('ServiceWorker registration successful with scope: ', registration.scope);
          }, function (err) {
            // registration failed :(
            console.log('ServiceWorker registration failed: ', err);
          });
        });
      }
    }
  });
})(jQuery)
