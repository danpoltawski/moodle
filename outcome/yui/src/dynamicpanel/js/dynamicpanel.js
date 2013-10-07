/**
 * Dynamic Panel - the content of the panel is the dynamic part!
 *
 * @module moodle-core_outcome-dynamicpanel
 */

var IO,
    Lang = Y.Lang,
    PANEL = 'panel',
    DELEGATE = 'delegateSelector',
    ACTION = 'actionSelector',
    EVENT = 'delegateEvent';

/**
 * This module can setup a configurable delegate even listener
 * and on the specified event, show a panel.
 *
 * Before the panel is shown, the event handler looks for "data-request-"
 * attributes on the event target.  These are aggregated and sent with
 * an AJAX request to get data to populate the panel with content.
 *
 * @constructor
 * @namespace M.core_outcome
 * @class dynamicpanel
 * @extends Y.Base
 */
var DYNAMICPANEL = function() {
    DYNAMICPANEL.superclass.constructor.apply(this, arguments);
};

Y.extend(DYNAMICPANEL, Y.Base,
    {
        /**
         * Setup the event listener
         *
         * @method initializer
         */
        initializer: function() {
            if (this.get(DELEGATE).length > 0 && this.get(ACTION).length > 0) {
                var delegateNode = Y.one(this.get(DELEGATE));
                if (delegateNode) {
                    IO = new M.core_outcome.SimpleIO({ contextId: this.get('contextId') });
                    delegateNode.delegate(this.get(EVENT), this.handle_action, this.get(ACTION), this);
                }
            }
        },

        /**
         * Handle the event that shows the panel
         * @method handle_action
         * @param {Object} e
         */
        handle_action: function(e) {
            e.preventDefault();

            var requestData = {};
            Y.Object.each(e.target.getData(), function(value, key) {
                var name = key.replace('request-', '');
                if (name !== key) {
                    requestData[name] = value;
                }
            });
            this.show_panel(requestData);
        },

        /**
         * Show the panel
         *
         * @method show_panel
         * @param {Object} data Request data
         */
        show_panel: function(data) {
            IO.send(data, function(response) {
                this.get(PANEL).set('headerContent', response.header);
                this.get(PANEL).set('bodyContent', response.body);
                this.get(PANEL).show();
                this.get(PANEL).centered();
            }, this);
        },

        /**
         * Creates our panel and attaches listeners
         *
         * @method _create_panel
         * @returns {M.core.dialogue}
         * @private
         */
        _create_panel: function() {
            var panel = new M.core.dialogue({
                centered: true,
                width: "",
                render: true,
                visible: false,
                modal: true
            });

            panel.get('srcNode').addClass(Y.ClassNameManager.getClassName(NAME, 'panel'));
            panel.plug(M.core_outcome.scrollpanel);
            panel.addButton({
                value: M.str.outcome.close,
                action: function(e) {
                    e.preventDefault();
                    this.hide();
                }
            });

            return panel;
        }
    },
    {
        NAME: NAME,
        ATTRS: {
            /**
             * Current context ID, used for AJAX requests
             *
             * @attribute contextId
             * @type Number
             * @default undefined
             * @optional
             */
            contextId: {},
            /**
             * Attach an event delegate listener to the node
             * that results from this CSS selector
             *
             * @attribute delegateSelector
             * @type String
             * @optional
             */
            delegateSelector: { validator: Lang.isString },
            /**
             * A selector that must match the target of the event.  Used
             * with the delegate selector.
             *
             * @attribute actionSelector
             * @type String
             * @optional
             */
            actionSelector: { validator: Lang.isString },
            /**
             * The event type to delegate
             *
             * @attribute delegateEvent
             * @type String
             * @default click
             * @optional
             */
            delegateEvent: { value: 'click', validator: Lang.isString },
            /**
             * The actual panel
             *
             * @attribute panel
             * @type M.core.dialogue
             * @default M.core.dialogue
             * @optional
             * @readOnly
             */
            panel: { readOnly: true, valueFn: '_create_panel' }
        }
    }
);

M.core_outcome = M.core_outcome || {};
M.core_outcome.dynamicpanel = DYNAMICPANEL;
M.core_outcome.init_dynamicpanel = function(config) {
    return new DYNAMICPANEL(config);
};
