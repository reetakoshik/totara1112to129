$.postJSON = function(url, data, callback) {
    return jQuery.ajax({
        'type' : 'POST',
        'url': url,
        'data': data,
        'dataType' : 'json',
        'success' : callback
    });
};

$.deleteJSON = function(url, data, callback) {
    return jQuery.ajax({
        'type' : 'DELETE',
        'url': url,
        'data': data,
        'dataType' : 'json',
        'success' : callback
    });
};

/**
 * Detect IE
 * @returns {*}
 */
function ieDetected() {

    var ua = window.navigator.userAgent;

    if (ua.indexOf("MSIE ") > 0 || ua.indexOf('Trident/') > 0) {
        return true;
    }

    return false;
}