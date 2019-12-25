
/*
 *  ES6 custom events polyfill required for IE11 when not using jQuery
 *  https://developer.mozilla.org/en-US/docs/Web/API/CustomEvent/CustomEvent
 */

(function() {

    if (typeof window.CustomEvent === "function") {
        return false;
    }
    function CustomEvent(event, params) {
        params = params || {bubbles: false, cancelable: false, detail: undefined};
        var evt = document.createEvent('CustomEvent');
        evt.initCustomEvent(event, params.bubbles, params.cancelable, params.detail);
        return evt;
    }

   CustomEvent.prototype = window.Event.prototype;

   window.CustomEvent = CustomEvent;
 })();


/*
 *  ES6 Element.closest() polyfill required for IE11
 *  https://developer.mozilla.org/en-US/docs/Web/API/Element/closest
 */

if (!Element.prototype.matches) {
    Element.prototype.matches = Element.prototype.msMatchesSelector || Element.prototype.webkitMatchesSelector;
}

if (!Element.prototype.closest) {
    Element.prototype.closest = function(s) {
        var el = this;
        if (!document.documentElement.contains(el)) {
             return null;
        }
        do {
            if (el.matches(s)) {
                return el;
            }
            el = el.parentElement || el.parentNode;
        } while (el !== null && el.nodeType === 1);
        return null;
    };
}

/*
 *  ES6 Element.matches() polyfill required for IE11
 *  https://developer.mozilla.org/en-US/docs/Web/API/Element/matches
 */

 if (!Element.prototype.matches) {
     Element.prototype.matches = Element.prototype.msMatchesSelector;
 }


 /*
 *  ES6 ChildNode.remove() polyfill required for IE11
 *  https://developer.mozilla.org/en-US/docs/Web/API/ChildNode/remove
 */

(function(arr) {
    arr.forEach(function(item) {
        if (item.hasOwnProperty('remove')) {
            return;
        }
        Object.defineProperty(item, 'remove', {
            configurable: true,
            enumerable: true,
            writable: true,
            value: function remove() {
                if (this.parentNode !== null) {
                    this.parentNode.removeChild(this);
                }
            }
        });
    });
})([Element.prototype, CharacterData.prototype, DocumentType.prototype]);


/*
 *  ES6 Object.assign() polyfill required for IE11
 *  https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Object/assign
 */

 if (typeof Object.assign != 'function') {
    // Must be writable: true, enumerable: false, configurable: true
    Object.defineProperty(Object, "assign", {
        value: function assign(target, varArgs) { // .length of function is 2
            'use strict';
            if (target == null) { // TypeError if undefined or null
                throw new TypeError('Cannot convert undefined or null to object');
            }

            var to = Object(target);

            for (var index = 1; index < arguments.length; index++) {
                var nextSource = arguments[index];
                if (nextSource != null) { // Skip over if undefined or null
                    for (var nextKey in nextSource) {
                        // Avoid bugs when hasOwnProperty is shadowed
                        if (Object.prototype.hasOwnProperty.call(nextSource, nextKey)) {
                            to[nextKey] = nextSource[nextKey];
                        }
                    }
                }
            }
            return to;
        },
        writable: true,
        configurable: true
    });
}


/*
 *  ES6 String.prototype.startsWith() polyfill required for IE11
 *  https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/String/startsWith
 */

if (!String.prototype.startsWith) {
    String.prototype.startsWith = function(search, pos) {
        return this.substr(!pos || pos < 0 ? 0 : +pos, search.length) === search;
    };
}


/*
 *  ES6 String.prototype.endsWith() polyfill required for IE11
 *  https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/String/endsWith
 */

if (!String.prototype.endsWith) {
    String.prototype.endsWith = function(search, this_len) {
        if (this_len === undefined || this_len > this.length) {
            this_len = this.length;
        }
        return this.substring(this_len - search.length, this_len) === search;
    };
}