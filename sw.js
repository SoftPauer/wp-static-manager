const staticCacheName = "apicache";
const prefix = "/wp-content/plugins/static-manager/mobile-html/";


self.addEventListener('activate', function (event) {
  console.log('Claiming control');
  return self.clients.claim();
});


self.addEventListener('install', function (event) {
  console.log("install");
});

self.addEventListener("fetch", event => {
  if (event.request.destination === 'document' && event.request.method === 'GET' && event.request.mode === 'navigate') {
    console.log('WORKER: Fetching', event.request);
    console.log(new URL(event.request.url).origin + prefix);
    
    if (event.request.url.includes("mobile-html")) {
     var req = new Request(new URL(event.request.url).origin + prefix, {
        method: 'GET',
        headers: event.request.headers,
        mode: event.request.mode == 'navigate' ? 'cors' : event.request.mode,
        credentials: event.request.credentials,
        redirect: event.request.redirect
      });
      console.log(new URL(event.request.url).pathname);

      if (new URL(event.request.url).pathname !== prefix)
        event.respondWith(fetch(req).then(response => {
          return response;
        }));
    } else {
      var req = new Request(new URL(event.request.url).origin , {
        method: 'GET',
        headers: event.request.headers,
        mode: event.request.mode == 'navigate' ? 'cors' : event.request.mode,
        credentials: event.request.credentials,
        redirect: event.request.redirect
      });
      console.log(req);
      if (new URL(event.request.url).pathname !== "/")
        event.respondWith(fetch(req).then(response => {
          return response;
        }).catch(error => {
          console.log(error);
        }));
    }
  }
});