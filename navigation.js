const prefix = '/wp-content/plugins/static-manager/mobile-html'
function isMobile() {
    return (
        window.flutter_inappwebview ||
        window.location.protocol.includes('file:')
    )
}
function isBrowserTest() {
    return window.location.pathname.includes('mobile-html')
}

function handleLocalredirectsWithUrl(url) {
    if (isMobile() || isBrowserTest()) {
        console.log('redirecting mobile way to ' + url)
        var html = getContentFromUrl(url)
        window.history.pushState({ html: html }, '', getPathFromUrl(url, true))
        window.jQuery('#main').html(html)
        handleNeededStaticHtmlChangesForRedirect()
        return
    } else {
        window.location.href = url
    }
}

// reletive url /index.php/something
function getContentFromUrl(url) {
    var dataObject = JSON.parse(localStorage.getItem('dataObject'))
    if (!dataObject || !dataObject.pages || !dataObject.pages.data) {
        return null
    }
    if (url.endsWith('/')) {
        url = url.substring(0, url.length - 1)
    }
    console.log(dataObject.pages.data)
    var page = dataObject.pages.data.find((i) => {
        if (i.link.endsWith('/')) {
            i.link = i.link.substring(0, i.link.length - 1)
        }

        return getPathFromUrl(i.link) == getPathFromUrl(url)
    })
    if (page) {
        return page.content
    }
    var post = dataObject.posts.data.find((i) => {
        if (i.link.endsWith('/')) {
            i.link = i.link.substring(0, i.link.length - 1)
        }
        return getPathFromUrl(i.link) == getPathFromUrl(url)
    })
    if (post) {
        return post.content
    }

    return null
}

function getPathFromUrl(url, withAnchor = false) {
    var path = new URL(url).pathname
    path = url.indexOf('#') > -1 ? path.substring(0, path.length - 1) : path
    if (path.startsWith('//')) {
        //some urls are double // for some reason??
        path = path.substring(1)
    }
    if (withAnchor) {
        if (url.indexOf('#') > -1) {
            path = path + '/' + new URL(url).hash
        }
    }
    if (isBrowserTest()) {
        return prefix + path
    }
    return path
}
function getHostFromUrl(url) {
    return new URL(url).hostname
}

function handleNeededStaticHtmlChangesForRedirect() {
    console.log('handleNeededStaticHtmlChangesForRedirect')
    window.jQuery('a').unbind()
    window.jQuery('.more-button').unbind()
    window.jQuery('.back-button').unbind()

    window.jQuery('#primary-mobile-menu').attr('aria-expanded', 'false')
    window.jQuery('body').removeClass('primary-navigation-open')
    window.jQuery('body').removeClass('lock-scrolling')
    window.jQuery('#primary-menu-list li').removeClass('current-menu-item')
    window.jQuery('#primary-menu-list li').removeClass('current_page_item')
    window.scrollTo(0, 0)
    window.jQuery('#primary-menu-list li a').each((a, value) => {
        console.log("value href: " + value.href)
        if (getPathFromUrl(value.href) === window.location.pathname) {
            window.jQuery(value).parent().addClass('current-menu-item')
            window.jQuery(value).parent().addClass('current_page_item')
        }
    })
    // clearNavigationUi()
    window.jQuery(document).trigger(PRIVATE_JQUERY_EVENT, [window.jQuery])

    setTimeout(() => {
        window.jQuery(document.body).trigger('post-load', [window.jQuery])
    }, 200)
    console.log('handleNeededStaticHtmlChangesForRedirect done')
}

function getApiHost() {
    if (typeof siteUrl !== 'undefined') {
        // site url is set by the app
        return siteUrl
    }
    return window.location.origin + '/'
}

;(function ($) {
    window.jQuery(document).ready(function () {
        if (isMobile() || isBrowserTest()) {
            // Fetch the REST api call and save it into local storage for use later.
            var dataUrl = 'index.php/wp-json/staticManager/v1/data'
            if (isBrowserTest()) {
                // this is only for local testing in the browser
                dataUrl = dataUrl + '?prefix=' + prefix
            }
            fetch(getApiHost() + dataUrl).then(async (data) => {
                var dataJson = await data.text()
                // dataJson = dataJson.replace("\u00e2\u0080\u0099","\u2019");
                dataJson.timestamp = new Date().getTime()
                localStorage.setItem('dataObject', dataJson)
            })

            console.log('jquery.ready binding a tags')
            window.jQuery('a').click(function (e) {
                console.log("This is the url clicked: " + e.currentTarget.href)
                var url = e.currentTarget.href
                if (url === 'https://account.canadianefest.com/') {
                    window.flutter_inappwebview.callHandler('openWebview', url)
                    e.preventDefault()
                    return
                }
                if (
                    url.startsWith(
                        'https://account.canadianefest.com/mobile_ticket_for_event'
                    )
                ) {
                    window.flutter_inappwebview.callHandler('openWebview', url)
                    e.preventDefault()
                    return
                }
                if (url.startsWith('https://tickets.canadianefest.com/')) {
                    window.flutter_inappwebview.callHandler('openWebview', url)
                    e.preventDefault()
                    return
                }
                if(url.endsWith('/app')){
                    console.log("inside button if statement: " + url)
                    window.flutter_inappwebview.callHandler('login',)
                    e.preventDefault()
                    return
                }
                // as we get index.html from the inside of container localhost will be running on port 80
                // stage has weird bug where urls can be http or https
                if (
                    getHostFromUrl(url).startsWith(
                        getHostFromUrl(getApiHost())
                    ) ||
                    url.startsWith('http://localhost')
                ) {
                    console.log("local redirect: " + url);
                    e.preventDefault()
                    handleLocalredirectsWithUrl(url)
                }
            })
            window.onpopstate = function (e) {
                console.log('popstate')
                if (window.location.href.includes('#hfaq-post-')) {
                    return true
                }
                if (e.state && e.state.html) {
                    window.jQuery('#main').html(e.state.html)
                } else {
                    //if no pop state then go to home page
                    window.jQuery('#main').html(getContentFromUrl(getApiHost()))
                }
                handleNeededStaticHtmlChangesForRedirect()
            }
        }
    })

    /**
     *
     * FUNCTIONS FOR MOBILE APP NAVIGATION
     *
     */

    function getContentFromMenuItemId(menuItemId) {
        var dataObject = JSON.parse(localStorage.getItem('dataObject'))
        var menuJson = dataObject.menu
        var item = menuJson.items.find((i) => i.id == menuItemId)
        var page = dataObject.pages.data.find((i) => i.id == item.object_id)
        return page.content
    }

    function getContentFromPageId(pageId) {
        var dataObject = JSON.parse(localStorage.getItem('dataObject'))
        if (!dataObject || !dataObject.pages || !dataObject.pages.data) {
            return null
        }
        var page = dataObject.pages.data.find((i) => i.id == pageId)
        if (!page) {
            return null
        }
        return page.content
    }

    function replaceContentFromPageId(pageId) {
        if (!isMobile()) {
            window.location.href = '?page_id=' + pageId
            return
        }
        var html = getContentFromPageId(pageId)
        if (html === null) {
            console.error(
                'html for page ' + pagId + ', not found in local storage'
            )
            window.location.href = '?page_id=' + pageId
        } else {
            window.jQuery('#main').html(html)
        }
    }

    function getHtmlFromMenuItemId(menuItemId) {
        return fetch(
            getApiHost() + 'index.php/wp-json/wp-api-menus/v2/menus'
        ).then(async (menus) => {
            var menusjson = await menus.json()
            var top_menu = menusjson.find((m) => m.name === 'top-menu')
            console.log(top_menu)
            return fetch(
                '/index.php/wp-json/wp-api-menus/v2/menus/' + top_menu.ID
            ).then(async (menu) => {
                var menujson = await menu.json()
                console.log(menujson)
                var item = menujson.items.find((i) => i.id == menuItemId)
                console.log(item)
                return fetch(
                    '/index.php/wp-json/wp/v2/pages/' + item.object_id
                ).then(async (page) => {
                    var pagejson = await page.json()
                    return pagejson.content.rendered
                })
            })
        })
    }
})(jQuery)
