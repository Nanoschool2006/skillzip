/*
* Edwiser_Forms - https://edwiser.org/
* Version: 0.1.0
* Author: Yogesh Shirsath
*/
define([], function () {
    const assign = Object.assign || function assign(obj, ...argss) {
        let len;
        let args;
        let key;
        for (len = argss.length, args = Array(len > 1 ? len - 1 : 0), key = 1; key < len; key++) {
            args[key - 1] = argss[key];
        }

        if (typeof obj == 'object' && args.length > 0) {
            args.forEach(function(arg) {
                if (typeof arg == 'object') {
                    Object.keys(arg).forEach(function(key) {
                        obj[key] = arg[key];
                    });
                }
            });
        }

        return obj;
    };
    if (typeof window.CustomEvent === 'function') {
        return false;
    }

    /**
     * In IE if Custom Event function is not define
     * @param {String} event  Event name
     * @param {Array}  params Event parameters
     * @return {Event}        Event object
     */
    function CustomEvent(event, params) {
        params = assign({bubbles: false, cancelable: false, detail: undefined}, params);
        const evt = document.createEvent('CustomEvent');
        evt.initCustomEvent(event, params.bubbles, params.cancelable, params.detail);
        return evt;
    }

    CustomEvent.prototype = window.Event.prototype;

    window.CustomEvent = CustomEvent;
});