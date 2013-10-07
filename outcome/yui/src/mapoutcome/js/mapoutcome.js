/**
 * UI for Selecting Outcomes
 *
 * @module moodle-core_outcome-mapoutcome
 */

var Lang = Y.Lang,
    OUTCOME_PANEL,
    URL_IMG_DELETE = M.util.image_url('t/delete', 'core'),
    TEMPLATE_COMPILED,
    NODE_CONTENT,
    RETURN_FOCUS,
    BOX = 'contentBox',
    INPUT_HIDDEN = 'input[type=hidden]',
    LINK_ADD = "a[data-action=add]",
    LINK_DELETE = "a[data-action=delete]",

// Templates
    TEMPLATE = '{{#unless isFrozen}}' +
        '<a href="#" data-id="0" data-action="add" role="button">{{strselectoutcomes}}</a> ' +
        '{{/unless}}' +
        '{{#unless outcomeSetList}}' +
        '<div>{{strnoselectedoutcomes}}</div>' +
        '{{else}}' +
        '<ul class="{{classMappedOutcomes}}" tabindex="-1">' +
        '{{#each outcomeSetList}}' +
        '<li>{{{name}}}<ul>' +
        '{{#each outcomeList}}' +
        '<li tabindex="-1" class="outcome" data-outcomeid="{{id}}" data-outcomesetid="{{outcomesetid}}">' +
        '{{#unless ../../../isFrozen}}' +
        '<span class="actions">' +
        '<a href="#" data-id="{{id}}" role="button" data-action="delete">' +
        '<img src="{{../../../../urlImgDelete}}" />' +
        '<span class="accesshide">{{{getString "deletex" description}}}</span>' +
        '</a>' +
        '</span>&nbsp;' +
        '{{/unless}}' +
        '{{{description}}}</li>' +
        '{{/each}}' +
        '</ul></li>' +
        '{{/each}}' +
        '</ul>' +
        '{{/unless}}',

// Render helpers
    renderGetStringHelper = function(identifier, a) {
        return M.util.get_string(identifier, 'outcome', a);
    };

/**
 * 1. Renders a UI to display currently selected outcome.
 * In this UI, the user can remove these outcomes.
 *
 * 2. A panel to select new outcomes.
 *
 * @constructor
 * @namespace M.core_outcome
 * @class mapoutcome
 * @extends Y.Widget
 */
var MAPOUTCOME = function() {
    MAPOUTCOME.superclass.constructor.apply(this, arguments);
};

Y.extend(MAPOUTCOME, Y.Widget,
    {
        /**
         * Parse data from the save node to populate our lists
         * Also init the panel
         *
         * @method initializer
         */
        initializer: function() {
            try {
                var jsonString = this.get('saveNode').get('value');
                if (jsonString.length > 0) {
                    var data = Y.JSON.parse(jsonString);
                    this.get('outcomeList').add(data.outcomes);
                    this.get('outcomeSetList').add(data.outcomesets);
                }
            } catch (e) {
                e.visible = true;
                new M.core.exception(e);
            }

            // Create out outcome selection panel
            OUTCOME_PANEL = M.core_outcome.init_outcomepanel({
                contextId: this.get('contextId')
            });
        },

        /**
         * Compile our templates and create
         * a node to hold our rendered HTML
         *
         * @method renderUI
         */
        renderUI: function() {
            Y.Handlebars.registerHelper('getString', renderGetStringHelper);
            TEMPLATE_COMPILED = Y.Handlebars.compile(TEMPLATE);

            NODE_CONTENT = Y.Node.create('<div></div>');
            NODE_CONTENT.addClass(this.getClassName('content', 'wrapper'));
            this.get(BOX).append(NODE_CONTENT);

            this._render_template();
        },

        /**
         * Bind events to the UI only if not frozen
         *
         * @method bindUI
         */
        bindUI: function() {
            if (!this.get('isFrozen')) {
                this.get(BOX).delegate('click', this._handle_select_outcomes, LINK_ADD, this);
                this.get(BOX).delegate('click', this._handle_remove_outcome, LINK_DELETE, this);

                // Listen to when the outcome selection panel saves
                OUTCOME_PANEL.on('save', this._handle_save_selected_outcomes, this);
                OUTCOME_PANEL.get('panel').after('visibleChange', function(e) {
                    if (!e.newVal && RETURN_FOCUS instanceof Y.Node) {
                        RETURN_FOCUS.focus();
                        RETURN_FOCUS = undefined;
                    }
                });

                // Auto-render when we add/remove from our list of outcomes
                this.get('outcomeList').after(['remove', 'reset'], this._render_template, this);

                // Auto-save our outcome list whenever it changes
                this.get('outcomeList').after(['add', 'remove', 'reset'], this._update_save_node, this);
            }
        },

        /**
         * Renders or re-renders the template
         *
         * @method _render_template
         * @private
         */
        _render_template: function() {
            var list = this.get('outcomeSetList').map(function(outcomeset) {
                var data = outcomeset.toJSON();
                data.outcomeList = [];

                this.get('outcomeList').each(function(outcome) {
                    if (outcome.get('outcomesetid') == outcomeset.get('id')) {
                        data.outcomeList.push(outcome.toJSON());
                    }
                });
                return data;
            }, this);

            NODE_CONTENT.setHTML(TEMPLATE_COMPILED({
                isFrozen: this.get('isFrozen'),
                outcomeSetList: list,
                strselectoutcomes: M.str.outcome.selectoutcomes,
                strnoselectedoutcomes: M.str.outcome.noselectedoutcomes,
                classMappedOutcomes: this.getClassName('mapped', 'outcomes'),
                urlImgDelete: URL_IMG_DELETE
            }));
        },

        /**
         * Sends the outcome list and outcome set list back to our save node
         *
         * @method _update_save_node
         * @private
         */
        _update_save_node: function() {
            var data = {
                outcomes: this.get('outcomeList').toJSON(),
                outcomesets: this.get('outcomeSetList').toJSON()
            };

            this.get('saveNode').set('value', Y.JSON.stringify(data));
        },

        /**
         * Select outcome handler - shows the outcome selection
         * modal with our currently selected outcomes
         *
         * @method _handle_select_outcomes
         * @param e
         * @private
         */
        _handle_select_outcomes: function(e) {
            e.preventDefault();

            RETURN_FOCUS = e.target;

            var selectedOutcomeIds = this.get('outcomeList').map(function(model) {
                return model.get('id');
            });
            OUTCOME_PANEL.show_panel(selectedOutcomeIds);
        },

        /**
         * Save handler for getting the outcomes that the user
         * has selected from the outcome panel
         *
         * @method _handle_save_selected_outcomes
         * @private
         */
        _handle_save_selected_outcomes: function() {
            this.get('outcomeSetList').reset(OUTCOME_PANEL.get('selectedOutcomeSets').toArray());
            this.get('outcomeList').reset(OUTCOME_PANEL.get('selectedOutcomes').toArray());

            if (this.get('outcomeList').isEmpty()) {
                RETURN_FOCUS = NODE_CONTENT.one(LINK_ADD);
            } else {
                RETURN_FOCUS = this.get(BOX).one('.' + this.getClassName('mapped', 'outcomes'));
            }
        },

        /**
         * Handler for removing a mapped outcome
         *
         * @method _handle_remove_outcome
         * @param {Object} e
         * @private
         */
        _handle_remove_outcome: function(e) {
            e.preventDefault();
            var target = e.target;
            if (!target.test('a')) {
                target = target.ancestor('a');
            }
            var outcome = this.get('outcomeList').getById(target.getData('id'));

            var outcomesInSet = this.get('outcomeList').filter(function(model) {
                return model.get('outcomesetid') == outcome.get('outcomesetid');
            });
            if (outcomesInSet.length === 1) {
                var outcomeset = this.get('outcomeSetList').getById(outcome.get('outcomesetid'));
                this.get('outcomeSetList').remove(outcomeset);
            }
            this.get('outcomeList').remove(outcome);

            var focusNode = this.get(BOX).one('.' + this.getClassName('mapped', 'outcomes'));
            if (!(focusNode instanceof Y.Node)) {
                focusNode = this.get(BOX).one(LINK_ADD);
            }
            focusNode.focus();
        }
    },
    {
        HTML_PARSER: {
            saveNode: INPUT_HIDDEN
        },
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
             * If the form is frozen or not
             *
             * @attribute isFrozen
             * @type Boolean
             * @default false
             * @optional
             */
            isFrozen: { value: false, validator: Lang.isBoolean },
            /**
             * We read and write our data to this node
             *
             * @attribute saveNode
             * @type Y.Node
             * @default undefined
             * @required
             */
            saveNode: {
                validator: function(value) {
                    return value instanceof Y.Node;
                }
            },
            /**
             * Our list of selected outcome sets (EG: any outcome selected, gets its outcome set added here)
             *
             * @attribute outcomeSetList
             * @type M.core_outcome.OutcomeSetList
             * @default M.core_outcome.OutcomeSetList
             * @optional
             */
            outcomeSetList: { value: new M.core_outcome.OutcomeSetList() },
            /**
             * Our mapped outcomes
             *
             * @attribute outcomeList
             * @type M.core_outcome.OutcomeList
             * @default M.core_outcome.OutcomeList
             * @optional
             */
            outcomeList: { value: new M.core_outcome.OutcomeList() }
        }
    }
);

M.core_outcome = M.core_outcome || {};
M.core_outcome.mapoutcome = MAPOUTCOME;
M.core_outcome.init_mapoutcome = function(config) {
    var widget = new MAPOUTCOME(config);
    widget.render();
    return widget;
};
