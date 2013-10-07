/**
 * Simple IO
 *
 * @module moodle-core_outcome-simpleio
 */

var Lang = Y.Lang,
    URL_AJAX = M.cfg.wwwroot + '/outcome/ajax.php';

/**
 * This provides a simple wrapper around Y.io to fetch
 * data from the server and handle any errors.
 *
 * @constructor
 * @namespace M.core_outcome
 * @class SimpleIO
 * @extends Y.Base
 */
var SIMPLEIO = function() {
    SIMPLEIO.superclass.constructor.apply(this, arguments);
};

Y.extend(SIMPLEIO, Y.Base,
    {
        /**
         * Helper method to do a AJAX request and to do error handling
         * @method send
         * @param {Object} data
         * @param {Function} fn
         * @param {Object} context Optional argument that specifies what 'this' refers to.
         * @param {String} method POST, GET, etc
         */
        send: function(data, fn, context, method) {
            if (!Lang.isString(method)) {
                method = 'GET';
            }
            if (Lang.isUndefined(data.contextid) && !Lang.isUndefined(this.get('contextId'))) {
                data.contextid = this.get('contextId');
            }
            Y.io(this.get('url'), {
                method: method,
                data: data,
                on: {
                    complete: function(id, response) {
                        var data = {};
                        try {
                            data = Y.JSON.parse(response.responseText);
                        } catch (e) {
                            e.visible = true;
                            new M.core.exception(e);
                            return;
                        }
                        if (Lang.isValue(data.error)) {
                            data.visible = true;
                            new M.core.ajaxException(data);
                        } else {
                            fn.call(context, data);
                        }
                    }
                }
            });
        }
    },
    {
        NAME: NAME,
        ATTRS: {
            /**
             * Current context ID
             *
             * If set, will be passed along in AJAX
             * requests.
             *
             * @attribute contextId
             * @type Number
             * @default undefined
             * @optional
             */
            contextId: { value: undefined },
            /**
             * Used for requests
             *
             * @attribute url
             * @type String
             * @default '/outcome/ajax.php'
             * @optional
             */
            url: { value: URL_AJAX, validator: Lang.isString }
        }
    }
);

M.core_outcome = M.core_outcome || {};
M.core_outcome.SimpleIO = SIMPLEIO;
