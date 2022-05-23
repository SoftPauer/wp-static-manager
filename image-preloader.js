/// image preloader added only by static manager for the mobile app

var images = []
var preload_urls = []
function preload() {
    for (var i = 0; i < arguments.length; i++) {
        images[i] = new Image()
        images[i].src = preload.arguments[i]
    }
}

preload(...preload_urls)
