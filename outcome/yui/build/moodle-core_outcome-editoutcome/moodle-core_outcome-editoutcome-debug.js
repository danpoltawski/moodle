YUI.add('moodle-core_outcome-editoutcome', function (Y, NAME) {

/**
 * UI for editing outcomes within an outcome set
 *
 * @module moodle-core_outcome-editoutcome
 */

var Lang = Y.Lang,
    RETURN_FOCUS,
    LISTENER,
    OUTCOME_LIST = 'outcomeList',
    OPEN_LIST = new M.core_outcome.OutcomeList(),
    OUTCOME_MOVE,
    BOX = 'contentBox',
    SRC_NODE = 'srcNode',
    PANEL_EDIT_SRC_NODE = '#outcome_edit_panel',
    PANEL_MOVE_SRC_NODE = '#outcome_move_panel',
    PANEL_EDIT = 'editPanel',
    PANEL_MOVE = 'movePanel',
    LINK_ADD = "a[data-action=add]",
    LINK_EDIT = "a[data-action=edit]",
    LINK_MOVE = "a[data-action=move]",
    LINK_DELETE = "a[data-action=delete]",
    LINK_FOLDER = "div[data-action=folder]",
    INPUT_HIDDEN = 'input[type=hidden]',
    INPUT_ID = '#outcome_id',
    INPUT_SET_ID = 'input[name=id]',
    INPUT_SET_IDNUMBER = '#id_idnumber',
    INPUT_PARENTID = '#outcome_parentid',
    INPUT_DOCNUM = 'input[name=outcome_docnum]',
    INPUT_IDNUMBER = 'input[name=outcome_idnumber]',
    INPUT_SUBJECTS = 'input[name=outcome_subjects]',
    INPUT_EDULEVELS = 'input[name=outcome_edulevels]',
    INPUT_ASSESSABLE = 'input[name=outcome_assessable]',
    INPUT_DESCRIPTION = 'textarea[name=outcome_description]',
    INPUT_REFERENCE = 'select[name=outcome_reference]',
    INPUT_PLACEMENT = 'select[name=outcome_placement]',
    ERROR_CODE_SET_IDNUMBER_CHANGE = 'div[data-errorcode=outcomesetidnumberchange]',
    ERROR_CODE_IDNUMBER_CHANGE = 'div[data-errorcode=outcomeidnumberchange]',
    LABEL_FORM = 'label.move_label',
    CSS_ERROR = '.error',
    OUTCOMELIST_TEMPLATE_COMPILED,
    OUTCOMELIST_PARTIAL_COMPILED,

// Templates
    OUTCOMELIST_TEMPLATE = '{{#if modified}}' +
        '<div class="' +
        '{{className "modified"}}' +
        '">' + M.str.outcome.outcomemodified + '</div>' +
        '{{/if}}' +
        '<a href="#" data-id="0" data-action="add">' + M.str.outcome.addoutcome + '</a> ' +
        '{{{outcomeList outcomes}}}',

    OUTCOMELIST_PARTIAL = '{{#if outcomes}}' +
        '<ul tabindex="-1">' +
        '{{#each outcomes}}' +
        '<li id="outcome_wrapper_{{id}}">' +
        '<div class="' +
        '{{className "row"}}' +
        '">' +
        '<div id="outcome_{{id}}" tabindex="0" data-id="{{id}}" class="' +
        '{{className "outcome"}} ' +
        '{{#if assessable}}{{className "assessable"}} {{/if}}' +
        '{{#if opened}}{{className "folder" "opened"}}{{/if}}' +
        '{{#if closed}}{{className "folder" "closed"}}{{/if}}' +
        '{{#if leaf}}{{className "leaf"}}{{/if}}' +
        '"' +
        '{{#if children}} data-action="folder" role="button"{{/if}}' +
        '>{{{docnum}}} {{{description}}}</div>' +
        '<div class="' +
        '{{className "actions"}}' +
        '">' +
        '<a href="#" data-id="{{id}}" role="button" data-action="add" title="' +
        '{{{title "addchildoutcome" description}}}' + '">' + M.str.outcome.add + '</a> ' +
        '<a href="#" data-id="{{id}}" role="button" data-action="edit" title="' +
        '{{{title "editx" description}}}' + '">' + M.str.outcome.edit + '</a> ' +
        '<a href="#" data-id="{{id}}" role="button" data-action="move" title="' +
        '{{{title "movex" description}}}' + '">' + M.str.outcome.move + '</a> ' +
        '<a href="#" data-id="{{id}}" role="button" data-action="delete" title="' +
        '{{{title "deletex" description}}}' + '">' + M.str.outcome.delete + '</a>' +
        '</div>' +
        '</div>' +
        '{{#if opened}}' +
        '{{{outcomeList children}}}' +
        '{{/if}}' +
        '</li>' +
        '{{/each}}' +
        '</ul>' +
        '{{/if}}',

// Render helpers
    renderTitleHelper = function(identifier, a) {
        return M.util.get_string(identifier, 'outcome', a);
    },
    renderOutcomeListHelper = function(outcomes) {
        return OUTCOMELIST_PARTIAL_COMPILED({outcomes: outcomes});
    },
    renderClassNameHelper = function() {
        var args = Y.Array(arguments);
        args.unshift(NAME);
        args.pop(); // This burns crap from arguments
        return Y.ClassNameManager.getClassName.apply(Y.ClassNameManager, args);
    };

/**
 * Manages a hierarchical list of outcomes and edit
 * actions that can be taken on them.
 *
 * @constructor
 * @namespace M.core_outcome
 * @class editoutcome
 * @extends Y.Widget
 */
var EDITOUTCOME = function() {
    EDITOUTCOME.superclass.constructor.apply(this, arguments);
};

Y.extend(EDITOUTCOME, Y.Widget,
    {
        /**
         * Extract outcome data from data node
         *
         * @method initializer
         */
        initializer: function() {
            try {
                if (this.get('dataNode')) {
                    var jsonString = this.get('dataNode').get('value');
                    if (jsonString.length > 0) {
                        var data = Y.JSON.parse(jsonString);
                        this.get(OUTCOME_LIST).add(data);
                    }
                }
            } catch (e) {
                e.visible = true;
                new M.core.exception(e);
            }
            // Warn users when they edit the outcome set unique ID.
            Y.one(INPUT_SET_IDNUMBER).on('valuechange', this._handle_unique_id_change, this, {
                original: Y.one(INPUT_SET_IDNUMBER).get('value'),
                errorNode: Y.one(ERROR_CODE_SET_IDNUMBER_CHANGE)
            });
        },

        /**
         * Register template helpers, compile templates and update UI
         *
         * @method renderUI
         */
        renderUI: function() {
            // Register helpers and compile templates
            Y.Handlebars.registerHelper('title', renderTitleHelper);
            Y.Handlebars.registerHelper('outcomeList', renderOutcomeListHelper);
            Y.Handlebars.registerHelper('className', renderClassNameHelper);
            OUTCOMELIST_PARTIAL_COMPILED = Y.Handlebars.compile(OUTCOMELIST_PARTIAL);
            OUTCOMELIST_TEMPLATE_COMPILED = Y.Handlebars.compile(OUTCOMELIST_TEMPLATE);

            this._update_ui();
        },

        /**
         * Add event delegates to content box
         *
         * @method bindUI
         */
        bindUI: function() {
            this.get(BOX).delegate('click', this._handle_add, LINK_ADD, this);
            this.get(BOX).delegate('click', this._handle_edit, LINK_EDIT, this);
            this.get(BOX).delegate('click', this._handle_move, LINK_MOVE, this);
            this.get(BOX).delegate('click', this._handle_delete, LINK_DELETE, this);
            this.get(BOX).delegate('click', this._handle_folder, LINK_FOLDER, this);
            this.get(BOX).delegate('key', this._handle_folder, 'enter', LINK_FOLDER, this);
        },

        /**
         * Updates UI by re-rendering the template
         * Also updates our save and data nodes for
         * submits or submit and validation error.
         *
         * @method _update_ui
         * @private
         */
        _update_ui: function() {
            this.get(OUTCOME_LIST).sort();

            var models = this.get(OUTCOME_LIST).toJSON(),
                modified = this.get(OUTCOME_LIST).filter_by_modified();

            // This builds the hierarchy by assigning children
            // to each model and then only returning the top level parents
            var parents = Y.Array.filter(models, function(model) {
                model.children = Y.Array.filter(models, function(child) {
                    return child.parentid === model.id && child.deleted === 0;
                });
                if (!Lang.isNull(OPEN_LIST.getById(model.id)) && model.children.length > 0) {
                    model.opened = true;
                } else if (model.children.length > 0) {
                    model.closed = true;
                } else {
                    model.leaf = true;
                }
                return Lang.isNull(model.parentid) && model.deleted === 0;
            });

            var html = OUTCOMELIST_TEMPLATE_COMPILED({
                outcomes: parents,
                modified: (modified.length > 0)
            });
            this.get(BOX).setHTML(html);

            // Cannot move if we only have one
            if (this.get(OUTCOME_LIST).size() === 1) {
                this.get(BOX).all(LINK_MOVE).hide();
            }
            this.get('saveNode').set('value', Y.JSON.stringify(modified));
            this.get('dataNode').set('value', Y.JSON.stringify(this.get(OUTCOME_LIST).toJSON()));
        },

        /**
         * This ensures that all parent outcomes are opened, the UI is fresh and then
         * focuses on the passed outcome.
         *
         * @method _update_ui_and_focus
         * @param model
         * @private
         */
        _update_ui_and_focus: function(model) {
            this._open_parents(model);
            this._update_ui();
            this.get(BOX).one('#outcome_' + model.get('id')).focus();
        },

        /**
         * Add new outcome handler - show the edit modal
         *
         * @method _handle_add
         * @param e
         * @private
         */
        _handle_add: function(e) {
            e.preventDefault();

            RETURN_FOCUS = e.target;
            this._populate_edit_panel(new M.core_outcome.OutcomeModel({parentid: e.target.getData('id')}));
            this.get(PANEL_EDIT).show();
        },

        /**
         * Edit existing outcome handler - show the edit modal
         *
         * @method _handle_edit
         * @param e
         * @private
         */
        _handle_edit: function(e) {
            e.preventDefault();

            RETURN_FOCUS = e.target;
            var outcome = this.get(OUTCOME_LIST).getById(e.target.getData('id'));
            this._populate_edit_panel(outcome);
            this.get(PANEL_EDIT).show();
        },

        /**
         * Move an outcome handler - show the move modal
         *
         * @method _handle_move
         * @param e
         * @private
         */
        _handle_move: function(e) {
            e.preventDefault();

            RETURN_FOCUS = e.target;
            OUTCOME_MOVE = this.get(OUTCOME_LIST).getById(e.target.getData('id'));
            var srcNode  = this.get(PANEL_MOVE).get(SRC_NODE);

            // Populate label
            srcNode.one(LABEL_FORM).set('text', M.util.get_string('movex', 'outcome', OUTCOME_MOVE.get_short_description()));
            // Reset placement drop down to 'child' option
            srcNode.one(INPUT_PLACEMENT).set('value', 'child');
            // Populate outcome list options - remove self and any children
            var select = srcNode.one(INPUT_REFERENCE);
            select.setContent('');
            Y.Array.each(this.get(OUTCOME_LIST).filter_out_branch(OUTCOME_MOVE), function(model) {
                if (model.get('deleted') == 1) {
                    return; // Don't display deleted outcomes.
                }
                select.appendChild(
                    Y.Node.create('<option></option>').
                    set('value', model.get('id')).
                    setContent(this._menu_prefix(model) + Y.Escape.html(model.get_short_description()))
                );
            }, this);

            this.get(PANEL_MOVE).show();
        },

        /**
         * Delete an outcome handler
         *
         * @method _handle_delete
         * @param e
         * @private
         */
        _handle_delete: function(e) {
            e.preventDefault();

            var model = this.get(OUTCOME_LIST).getById(e.target.getData('id'));
            this.get(OUTCOME_LIST).remove_outcome(model);
            this._update_ui();
        },

        /**
         * Toggle a folder handler
         *
         * @method _handle_folder
         * @param e
         * @private
         */
        _handle_folder: function(e) {
            e.preventDefault();

            var model = this.get(OUTCOME_LIST).getById(e.target.getData('id'));
            if (Lang.isNull(OPEN_LIST.getById(model.get('id')))) {
                OPEN_LIST.add(model);
                this._update_ui();

                // Focus on children
                this.get(BOX).one('#outcome_wrapper_' + model.get('id') + ' ul').focus();
            } else {
                OPEN_LIST.remove(model);
                this._update_ui();

                // Focus on outcome
                this.get(BOX).one('#outcome_' + model.get('id')).focus();
            }
        },

        /**
         * Populates the edit modal with data from an outcome
         *
         * @method _populate_edit_panel
         * @param {M.core_outcome.OutcomeModel|Y.Model} outcome
         * @private
         */
        _populate_edit_panel: function(outcome) {
            var srcNode = this.get(PANEL_EDIT).get(SRC_NODE);
            var id = 0;
            if (!outcome.isNew()) {
                id = outcome.get('id');
            }
            var nullToStr = function(value) {
                return Lang.isNull(value) ? '' : value;
            };

            Y.one(INPUT_ID).set('value', id);
            Y.one(INPUT_PARENTID).set('value', outcome.get('parentid'));
            srcNode.one(INPUT_IDNUMBER).set('value', nullToStr(outcome.get('rawidnumber')));
            srcNode.one(INPUT_DOCNUM).set('value', nullToStr(outcome.get('rawdocnum')));
            srcNode.one(INPUT_SUBJECTS).set('value', outcome.get('rawsubjects').join(', '));
            srcNode.one(INPUT_EDULEVELS).set('value', outcome.get('rawedulevels').join(', '));
            srcNode.one(INPUT_ASSESSABLE).set('checked', outcome.get('assessable'));
            srcNode.one(INPUT_DESCRIPTION).set('value', nullToStr(outcome.get('rawdescription')));

            // Detach listener if it already exists.
            if (!Lang.isUndefined(LISTENER)) {
                LISTENER.detach();
                LISTENER = undefined;
            }
            if (!outcome.isNew() && outcome.get('id') > 0) {
                // Warn users when they edit the outcome unique ID.
                LISTENER = srcNode.one(INPUT_IDNUMBER).on('valuechange', this._handle_unique_id_change, this, {
                    original: srcNode.one(INPUT_IDNUMBER).get('value'),
                    errorNode: Y.one(ERROR_CODE_IDNUMBER_CHANGE)
                });
            }
            this._clear_panel_errors(this.get(PANEL_EDIT));
        },

        /**
         * Edit modal save handler
         *
         * @method _handle_edit_panel_save
         * @param e
         * @private
         */
        _handle_edit_panel_save: function(e) {
            e.preventDefault();

            var srcNode = this.get(PANEL_EDIT).get(SRC_NODE),
                id = Y.one(INPUT_ID).get('value'),
                outcomeList = this.get(OUTCOME_LIST);

            var outcome = new M.core_outcome.OutcomeModel();
            if (id != 0) {
                outcome.setAttrs(this.get(OUTCOME_LIST).getById(id).toJSON());
            }
            var changes = {
                parentid: Y.one(INPUT_PARENTID).get('value'),
                outcomesetid: Y.one(INPUT_SET_ID).get('value'),
                rawidnumber: srcNode.one(INPUT_IDNUMBER).get('value'),
                rawdocnum: srcNode.one(INPUT_DOCNUM).get('value'),
                rawsubjects: this._split(srcNode.one(INPUT_SUBJECTS).get('value')),
                rawedulevels: this._split(srcNode.one(INPUT_EDULEVELS).get('value')),
                rawdescription: srcNode.one(INPUT_DESCRIPTION).get('value')
            };

            if (srcNode.one(INPUT_ASSESSABLE).get('checked')) {
                changes.assessable = 1;
            } else {
                changes.assessable = 0;
            }
            outcome.setAttrs(changes);
            outcome.validate_and_update(function(errors) {
                // Ensure that no other outcome on the client side has the same idnumber
                outcomeList.some(function(model) {
                    if (model.get('idnumber') === outcome.get('idnumber') && model.get('id') !== outcome.get('id')) {
                        if (!Lang.isArray(errors)) {
                            errors = [];
                        }
                        errors.push('outcomeidnumbernotunique');
                        return true;
                    }
                    return false;
                });
                this._handle_edit_panel_validation(outcome, errors);
            }, this);
        },

        /**
         * Handles validation errors (if any) from the edit modal.
         * If no errors, then updates the
         *
         * @method _handle_edit_panel_validation
         * @param outcome
         * @param errors
         * @private
         */
        _handle_edit_panel_validation: function(outcome, errors) {
            if (Lang.isArray(errors)) {
                this._clear_panel_errors(this.get(PANEL_EDIT));
                Y.Array.each(errors, function(errorCode) {
                    this.get(PANEL_EDIT).get(SRC_NODE).one('div[data-errorcode=' + errorCode + ']').show();
                }, this);
                return;
            }

            // Going to focus on the updated outcome
            RETURN_FOCUS = undefined;
            this.get(PANEL_EDIT).hide();

            // If the outcome is new, let's add it to the list
            this.get(OUTCOME_LIST).add_new_outcome(outcome);

            // Update UI and focus on the modified outcome
            this._update_ui_and_focus(outcome);
        },

        /**
         * Move outcome modal save handler
         *
         * @method _handle_move_panel_save
         * @param e
         * @private
         */
        _handle_move_panel_save: function(e) {
            e.preventDefault();

            // Going to focus on the moved outcome
            RETURN_FOCUS = undefined;
            this.get(PANEL_MOVE).hide();

            var srcNode   = this.get(PANEL_MOVE).get(SRC_NODE);
            var reference = this.get(OUTCOME_LIST).getById(srcNode.one(INPUT_REFERENCE).get('value'));
            var position  = this.get(OUTCOME_LIST).find_new_position(reference, srcNode.one(INPUT_PLACEMENT).get('value'));

            // Move the outcome to the selected position
            this.get(OUTCOME_LIST).move_outcome(OUTCOME_MOVE, position);

            // Update UI and focus on the modified outcome
            this._update_ui_and_focus(OUTCOME_MOVE);
        },

        /**
         * Show error node if value has changed
         *
         * @method _handle_unique_id_change
         * @param {Event} e
         * @param {Object} data
         * @private
         */
        _handle_unique_id_change: function(e, data) {
            if (data.original === e.target.get('value') || data.original === '') {
                data.errorNode.hide();
            } else {
                data.errorNode.show();
            }
        },

        /**
         * Create a panel
         *
         * @method _create_panel
         * @param {String|Y.Node} srcNode The source node for the panel content
         * @param {String} title The title for the panel
         * @param {Function} saveCallback On save, this will be called
         * @returns {M.core.dialogue}
         * @private
         */
        _create_panel: function(srcNode, title, saveCallback) {
            var panel = new M.core.dialogue({
                headerContent: title,
                centered: true,
                width: "",
                render: true,
                visible: false,
                modal: true
            });

            panel.set('bodyContent', Y.one(srcNode));
            panel.get('boundingBox').addClass(this.getClassName('panel'));
            panel.after('visibleChange', function(e) {
                if (!e.newVal && RETURN_FOCUS instanceof Y.Node) {
                    RETURN_FOCUS.focus();
                    RETURN_FOCUS = undefined;
                }
            });
            // Order matters, first button is pressed on return key
            panel.addButton({
                value: M.str.outcome.ok,
                action: saveCallback,
                context: this
            });
            panel.addButton({
                value: M.str.moodle.cancel,
                action: function(e) {
                    e.preventDefault();
                    this.hide();
                }
            });
            return panel;
        },

        /**
         * Creates the outcome edit panel
         *
         * @method _create_edit_panel
         * @returns {M.core.dialogue}
         */
        _create_edit_panel: function() {
            return this._create_panel(PANEL_EDIT_SRC_NODE, M.str.outcome.addoutcome, this._handle_edit_panel_save);
        },

        /**
         * Creates the outcome move panel
         *
         * @method _create_move_panel
         * @returns {M.core.dialogue}
         */
        _create_move_panel: function() {
            return this._create_panel(PANEL_MOVE_SRC_NODE, M.str.outcome.moveoutcome, this._handle_move_panel_save);
        },

        /**
         * Clear errors in a given modal
         *
         * @method _clear_panel_errors
         * @param panel
         * @private
         */
        _clear_panel_errors: function(panel) {
            panel.get(SRC_NODE).all(CSS_ERROR).hide();
        },

        /**
         * Split a string on commas, trim each subsequent string
         * and remove duplicates.
         *
         * @method _split
         * @param {String} value
         * @returns {Array}
         * @private
         */
        _split: function(value) {
            if (Lang.trim(value) === '') {
                return [];
            }
            return Y.Array.unique(Y.Array.map(value.split(','), Lang.trim), function(a, b) {
                return a.toLowerCase() === b.toLowerCase();
            });
        },

        /**
         * Utility function, repeat a string
         *
         * @method _repeat
         * @param {String} value
         * @param {Number} count
         * @returns {String}
         * @private
         */
        _repeat: function(value, count) {
            return new Array(1 + count).join(value);
        },

        /**
         * Generate a prefix string for a menu
         *
         * Example:
         * Outcome 1
         *     - Child Outcome
         *         - Grand Child Outcome
         *
         * @method _menu_prefix
         * @param {M.core_outcome.OutcomeModel} model
         * @returns {string}
         * @private
         */
        _menu_prefix: function(model) {
            var depth = this.get(OUTCOME_LIST).get_depth(model);
            return depth > 0 ? this._repeat('&nbsp;', depth * 2) + '-&nbsp;' : '';
        },

        /**
         * Ensure that all parents are opened so the outcome is visible
         *
         * @method _open_parents
         * @param model
         * @private
         */
        _open_parents: function(model) {
            var parent = model;
            while (!Lang.isNull(parent.get('parentid'))) {
                parent = this.get(OUTCOME_LIST).getById(parent.get('parentid'));
                OPEN_LIST.add(parent);
            }
        },

        /**
         * Validate that a value resolves to a hidden input
         *
         * @method _node_input_hidden_validator
         * @param {String|Y.Node} value
         * @returns {boolean}
         */
        _node_input_hidden_validator: function(value) {
            return (Y.one(value) instanceof Y.Node && Y.one(value).test(INPUT_HIDDEN));
        },

        /**
         * Convert the value to a node
         *
         * @method _node_setter
         * @param {String|Y.Node} value
         * @returns {Y.Node}
         */
        _node_setter: function(value) {
            return Y.one(value);
        }
    },
    {
        NAME: NAME,
        ATTRS: {
            /**
             * An hidden input field to get and store the outcome list as JSON
             *
             * @attribute dataNode
             * @type Y.Node
             * @writeOnce
             */
            dataNode: {
                validator: '_node_input_hidden_validator',
                setter: '_node_setter',
                writeOnce: true
            },
            /**
             * An hidden input field to store modified outcomes as JSON
             *
             * @attribute saveNode
             * @type Y.Node
             * @writeOnce
             */
            saveNode: {
                validator: '_node_input_hidden_validator',
                setter: '_node_setter',
                writeOnce: true
            },
            /**
             * The list of outcomes that belong to this set
             *
             * @attribute outcomeList
             * @type M.core_outcome.OutcomeList
             * @default M.core_outcome.OutcomeList
             * @readOnly
             */
            outcomeList: {
                readOnly: true,
                valueFn: function() {
                    return new M.core_outcome.OutcomeList();
                }
            },
            /**
             * The outcome edit panel
             *
             * @attribute editPanel
             * @type M.core.dialogue
             * @default M.core.dialogue
             * @readOnly
             */
            editPanel: {
                readOnly: true,
                valueFn: '_create_edit_panel'
            },
            /**
             * The outcome move panel
             *
             * @attribute movePanel
             * @type M.core.dialogue
             * @default M.core.dialogue
             * @readOnly
             */
            movePanel: {
                readOnly: true,
                valueFn: '_create_move_panel'
            }
        }
    }
);

M.core_outcome = M.core_outcome || {};
M.core_outcome.editoutcome = EDITOUTCOME;
M.core_outcome.init_editoutcome = function(config) {
    var widget = new EDITOUTCOME(config);
    if (widget.get('dataNode')) {
        widget.render();
    }
    return widget;
};


}, '@VERSION@', {
    "requires": [
        "widget",
        "event-valuechange",
        "handlebars",
        "json-parse",
        "json-stringify",
        "moodle-core_outcome-models",
        "moodle-core-notification-dialogue",
        "moodle-core-notification-exception"
    ]
});
