YUI.add('moodle-core_outcome-ariacontrol', function (Y, NAME) {

/**
 * Controls content that can be toggled to
 * be hidden or shown.
 *
 * @module moodle-core_outcome-ariacontrol
 */

    // Events
var EVT_BEFORE_TOGGLE = 'beforeToggle',
    EVT_AFTER_TOGGLE = 'afterToggle',
    EVT_BEFORE_LABEL_TOGGLE = 'beforeLabelToggle',
    EVT_AFTER_LABEL_TOGGLE = 'afterLabelToggle',

    // Shortcuts, etc
    HOST = 'host',
    ROLE_ATTR = 'role',
    ARIA_LABEL_ATTR = 'aria-label',
    ARIA_CONTROLS_ATTR = 'aria-controls',
    BOUNDING_BOX = 'boundingBox',
    Lang = Y.Lang;

/**
 * This can work in conjunction with M.core_outcome.ariacontrolled
 *
 * You can plug this into a node that is a widget that toggles
 * the visibility of some content or node.
 *
 * See http://oaa-accessibility.org/example/20/
 *
 * @constructor
 * @namespace M.core_outcome
 * @class ariacontrol
 * @extends Y.Plugin.Base
 */
var ARIACONTROL = function() {
    ARIACONTROL.superclass.constructor.apply(this, arguments);
};

Y.extend(ARIACONTROL, Y.Plugin.Base,
    {
        /**
         * Wire things up!
         *
         * @method initializer
         */
        initializer: function() {
            // Optionally update role
            if (this.get('role') !== '') {
                this.get('box').setAttribute(ROLE_ATTR, this.get('role'));
            }
            // Optionally wire up event
            if (this.get('event') !== '') {
                this.onHostEvent(this.get('event'), this.handle_host_event);
            }
            // Optionally wire up key event
            if (this.get('key') !== '') {
                this.get(HOST).on('key', this.handle_host_event, this.get('key'), this);
            }
            // Setup tabindex for focus
            if (this.get('autoFocus')) {
                this.get(HOST).setAttribute('tabindex', this.get('tabIndex'));
            }
            // Optionally update aria-label
            this.toggle_aria_label();

            // Optionally update aria-controls
            this._init_aria_controls();

            // If the ariaControls ATTR is modified, update our host
            this.after('ariaControlsChange', this._init_aria_controls);
        },

        /**
         * Optionally update aria-controls attribute
         *
         * @method _init_aria_controls
         * @private
         */
        _init_aria_controls: function() {
            if (!Lang.isNull(this.get('ariaControls'))) {
                this.get('box').setAttribute(ARIA_CONTROLS_ATTR, this.get('ariaControls').generateID());
            }
        },

        /**
         * Determine if we are updating the label or not
         *
         * @method is_label_updating
         * @return {Boolean}
         */
        is_label_updating: function() {
            return (this.get('beforeAriaLabel') !== '' && this.get('afterAriaLabel') !== '');
        },

        /**
         * Event handler for when an action is taken on the host
         *
         * This will update the aria-label and prevent default on the
         * event.
         *
         * @method handle_host_event
         * @param e
         */
        handle_host_event: function(e) {
            e.preventDefault();
            this.toggle_state();
        },

        /**
         * Toggle the state of the host by swapping labels
         * and notifying the controlled element to also
         * update its state.
         *
         * @method toggle_state
         */
        toggle_state: function() {
            this.fire(EVT_BEFORE_TOGGLE);

            this.toggle_aria_label();

            if (!Lang.isNull(this.get('ariaControls')) && !Lang.isUndefined(this.get('ariaControls').core_outcome_ariacontrolled)) {
                var ariacontrolled = this.get('ariaControls').core_outcome_ariacontrolled;
                ariacontrolled.toggle_state();

                if (this.get('autoFocus') && !ariacontrolled.get('visible')) {
                    this.get(HOST).focus();
                }
            }
            this.fire(EVT_AFTER_TOGGLE);
        },

        /**
         * Updates the aria-label attribute on the host.  This is
         * handy when the label needs to change to reflect the current
         * state.  EG: swap "Hide topic Foo" with "Show topic Foo"
         *
         * Optionally, it can also update the title attribute
         * if the host is a link, the alt attribute if the host
         * is an image or the innerHTML if the host is a button.
         *
         * @method toggle_aria_label
         */
        toggle_aria_label: function() {
            if (!this.is_label_updating()) {
                return;
            }
            this.fire(EVT_BEFORE_LABEL_TOGGLE);

            var box = this.get('box');
            var newLabel = '';
            if (!box.hasAttribute(ARIA_LABEL_ATTR) || box.getAttribute(ARIA_LABEL_ATTR) === this.get('afterAriaLabel')) {
                newLabel = this.get('beforeAriaLabel');
            } else {
                newLabel = this.get('afterAriaLabel');
            }
            box.setAttribute(ARIA_LABEL_ATTR, newLabel);

            if (!this.get('updateAriaLabelOnly')) {
                if (box.test('a')) {
                    box.setAttribute('title', newLabel);
                } else if (box.test('img')) {
                    box.setAttribute('alt', newLabel);
                } else if (box.test('button')) {
                    box.set('text', newLabel);
                }
            }

            this.fire(EVT_AFTER_LABEL_TOGGLE);
        },

        /**
         * Extract data from the host (EG: "data-" attributes)
         *
         * @method _get_data
         * @param {String} name
         * @param {mixed} defaultValue
         * @return {*}
         */
        _get_data: function(name, defaultValue) {
            var data = this.get(HOST).getData(name);
            if (!Lang.isUndefined(data)) {
                return data;
            }
            return defaultValue;
        },

        /**
         * aria-controls setter - if the value
         * is null, preserve the null.  Otherwise,
         * transform it to a Node instance.
         *
         * @method _setAriaControls
         * @param {null|String|Y.Node} value
         * @return {*}
         */
        _setAriaControls: function(value) {
            if (Lang.isNull(value)) {
                return null;
            }
            return Y.one(value);
        },

        /**
         * Validate aria-controls, either must be null
         * or can be resolved to a Node instance
         *
         * @method _validateAriaControls
         * @param {null|String|Y.Node} value
         * @return {Boolean}
         */
        _validateAriaControls: function(value) {
            if (Lang.isNull(value)) {
                return true;
            }
            return (Y.one(value) instanceof Y.Node);
        }
    },
    {
        NAME: NAME,
        NS: 'core_outcome_ariacontrol',
        ATTRS: {
            /**
             * The role attribute to set on the host.
             *
             * If set to an empty string, then the role attribute will
             * not be set.
             *
             * Defaults to the hosts role attribute value if it exists,
             * otherwise defaults to button.
             *
             * Possible roles: http://oaa-accessibility.org/examples/roles/
             *
             * @attribute role
             * @type String
             * @default region
             * @writeOnce
             */
            role: {
                valueFn: function() {
                    if (this.get(HOST).hasAttribute('role')) {
                        return this.get(HOST).getAttribute('role');
                    }
                    return 'button';
                },
                validator: Lang.isString,
                writeOnce: true
            },
            /**
             * The tabindex attribute to set on the host.
             *
             * This is only used if autoFocus is turned on.
             *
             * Defaults to the hosts tabindex attribute value if it exists,
             * otherwise defaults to -1.
             *
             * @attribute tabIndex
             * @type Number
             * @default -1
             * @optional
             */
            tabIndex: {
                valueFn: function() {
                    if (this.get(HOST).hasAttribute('tabindex')) {
                        return this.get(HOST).getAttribute('tabindex');
                    }
                    return '-1';
                },
                validator: Lang.isString,
                writeOnce: true
            },
            /**
             * The node or CSS selector string of the element that
             * is controlled by the host.
             *
             * The host will get an aria-controls="ID" attribute.
             *
             * Examples: http://oaa-accessibility.org/examples/prop/162/
             *
             * @attribute ariaControls
             * @type Y.Node
             * @default null|Y.Node
             * @optional
             */
            ariaControls: {
                value: null,
                validator: '_validateAriaControls',
                setter: '_setAriaControls'
            },
            /**
             * The event that triggers the change of the aria-label
             * attribute on the host.
             *
             * If set to an empty string, then no event will be wired.
             *
             * Requires both beforeAriaLabel and afterAriaLabel to be set
             * to non-empty strings.
             *
             * @attribute event
             * @type String
             * @default click
             * @optional
             */
            event: {
                value: 'click',
                validator: Lang.isString,
                writeOnce: true
            },
            /**
             * The keys that trigger the change of the aria-label
             * attribute on the host.
             *
             * If set to an empty string, then no key event will be wired.
             *
             * Requires both beforeAriaLabel and afterAriaLabel to be set
             * to non-empty strings.
             *
             * @attribute key
             * @type String
             * @default ''
             * @optional
             */
            key: {
                value: '',
                validator: Lang.isString,
                writeOnce: true
            },
            /**
             * Update the aria-label attribute on the host to this
             * value.  This is the initial label value.
             *
             * The default value is whatever is in the host's "data-before-aria-label"
             * attribute, otherwise, empty string.  Example: the host is:
             * <div data-before-aria-label="Show topic foo"> then the default would be
             * "Show topic foo"
             *
             * Example value: "Show topic Foo"
             *
             * If set to an empty string, then no label updating will happen.
             *
             * @attribute beforeAriaLabel
             * @type String
             * @default ''
             * @optional
             */
            beforeAriaLabel: {
                valueFn: function() {
                    return this._get_data('before-aria-label', '');
                },
                validator: Lang.isString,
                setter: Y.Escape.html
            },

            /**
             * Update the aria-label attribute on the host to this
             * value after an event.
             *
             * The default value is whatever is in the host's "data-after-aria-label"
             * attribute, otherwise, empty string.  Example: the host is:
             * <div data-after-aria-label="Hide topic foo"> then the default would be
             * "Hide topic foo"
             *
             * Example value: "Hide topic Foo"
             *
             * If set to an empty string, then no label updating will happen.
             *
             * @attribute afterAriaLabel
             * @type String
             * @default ''
             * @optional
             */
            afterAriaLabel: {
                valueFn: function() {
                    return this._get_data('after-aria-label', '');
                },
                validator: Lang.isString,
                setter: Y.Escape.html
            },
            /**
             * When updating aria-label, this plugin can also update the
             * title attribute if the host is a link, the alt attribute
             * if the host is an image or the innerHTML if the host is a button.
             *
             * Set this to true to disable this extra functionality
             *
             * @attribute updateAriaLabelOnly
             * @type Boolean
             * @default false
             * @optional
             */
            updateAriaLabelOnly: {
                value: false,
                validator: Lang.isBoolean
            },
            /**
             * Automatically focus on the host when ariaControls is hidden
             *
             * @attribute autoFocus
             * @type Boolean
             * @default true
             * @optional
             */
            autoFocus: {
                value: true,
                validator: Lang.isBoolean
            },
            /**
             * Private and for internal use
             *
             * Holds the value of the element that receives the
             * event listener, attributes, etc.
             *
             * @attribute box
             * @type Y.Node
             * @default Y.Node
             * @readOnly
             */
            box: {
                readOnly: true,
                valueFn: function() {
                    if (this.get(HOST) instanceof Y.Widget) {
                        return this.get(HOST).get(BOUNDING_BOX);
                    }
                    return this.get(HOST);
                }
            }
        }
    }
);

M.core_outcome = M.core_outcome || {};
M.core_outcome.ariacontrol = ARIACONTROL;


}, '@VERSION@', {"requires": ["plugin", "widget", "escape"]});
