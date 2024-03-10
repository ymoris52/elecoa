function include(jsfile)
{
    var script = document.createElement('script');
    script.src = jsfile;
    script.type = 'text/javascript';
    document.getElementsByTagName('head').item(0).appendChild(script);
}

if (navigator.userAgent.indexOf('Trident') > 0 || navigator.userAgent.indexOf('Android 2') > 0) {
    if (typeof XPathResult === 'undefined') {
        include('./js/javascript-xpath.js?exportInstaller=true&useNative=false');
    }
}
