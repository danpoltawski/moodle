/**
 * Scroll Panel Plugin
 *
 * @module moodle-core_outcome-scrollpanel
 */

var HOST = "host",
    Lang = Y.Lang;

/**
 * This plugin enhances a Y.Panel by allowing the content of
 * the panel to be scrolled and keeps the main window from
 * scrolling.
 *
 * @constructor
 * @namespace M.core_outcome
 * @class scrollpanel
 * @extends Y.Plugin.Base
 */
var SCROLLPANEL = function() {
    SCROLLPANEL.superclass.constructor.apply(this, arguments);
};

Y.extend(SCROLLPANEL, Y.Plugin.Base,
    {
        /**
         * Setup the event listener
         * @method initializer
         */
        initializer: function() {
            if (this.get(HOST) instanceof Y.Widget) {
                this.get(HOST).after('visibleChange', this.apply_scroll, this);
            }
        },

        /**
         * Add scroll to the panel/widget content
         * @method apply_scroll
         */
        apply_scroll: function() {
            var node;
            if (this.get(HOST) instanceof Y.Panel) {
                node = this.get(HOST).get('boundingBox').one('.yui3-widget-bd');
            } else if (this.get(HOST) instanceof Y.Widget) {
                node = this.get(HOST).get('boundingBox');
            }
            if (!Lang.isUndefined(node)) {
                node.setStyle('maxHeight', this.get('maxHeight'));
                node.setStyle('overflow', this.get('overflow'));
            }
        }
    },
    {
        NAME: NAME,
        NS: 'core_outcome_scrollpanel',
        ATTRS: {
            /**
             * Applies this max height to the panel/widget content div
             *
             * @attribute maxHeight
             * @type String
             * @default '500px'
             * @optional
             */
            maxHeight: { value: '500px', validator: Lang.isString },
            /**
             * Applies this overflow to the panel/widget content div
             *
             * @attribute overflow
             * @type String
             * @default 'auto'
             * @optional
             */
            overflow: { value: 'auto', validator: Lang.isString }
        }
    }
);

M.core_outcome = M.core_outcome || {};
M.core_outcome.scrollpanel = SCROLLPANEL;