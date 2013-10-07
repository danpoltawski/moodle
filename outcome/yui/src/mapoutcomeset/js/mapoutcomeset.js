/**
 * UI for Selecting Outcome Sets
 *
 * @module moodle-core_outcome-mapoutcomeset
 */

var Lang = Y.Lang,
    IO,
    NODE_CONTENT,
    RETURN_FOCUS,
    NEW_LIST,
    EDU_LEVELS_MENU = [],
    SUBJECTS_MENU = [],
    METADATA_MENU_CACHE = {},
    SELECTED_OUTCOMESET,
    OUTCOME_SETS_MENU,
    URL_IMG_DELETE = M.util.image_url('t/delete', 'core'),
    FILTER_LIST = 'filterList',
    BOX = 'contentBox',
    SRC_NODE = 'srcNode',
    PANEL = 'panel',
    INPUT_HIDDEN = 'input[type=hidden]',
    LINK_ADD = "a[data-action=add]",
    LINK_DELETE = "a[data-action=delete]",
    BUTTON_ADD = '#add_button',
    SELECT_OUTCOMESET = '#outcomesets',
    SELECT_EDU_LEVELS = '#edulevels',
    SELECT_SUBJECTS = '#subjects',
    LIST_TEMPLATE_COMPILED,
    ADD_TEMPLATE_COMPILED,

// Templates
    LIST_TEMPLATE = '{{#unless isFrozen}}' +
        '<a href="#" data-id="0" data-action="add" role="button">{{strselectoutcomesets}}</a> ' +
        '{{/unless}}' +
        '{{#if filters}}' +
        '<ul class="{{ulClass}}" tabindex="-1">' +
        '{{#filters}}' +
        '<li>' +
        '{{#unless ../isFrozen}}' +
        '<span class="actions">' +
        '<a href="#" data-id="{{id}}" role="button" data-action="delete">' +
        '<img src="{{../../urlImgDelete}}" />' +
        '<span class="accesshide">{{{getString "deletex" name}}}</span>' +
        '</a>' +
        '</span>' +
        '{{/unless}}' +
        '&nbsp;<span id="outcomeset_{{id}}" tabindex="-1">{{{name}}}</span>' +
        '</li>' +
        '{{/filters}}' +
        '</ul>' +
        '{{/if}}',
    ADD_TEMPLATE = '<table role="presentation">' +
        '{{#if filters}}' +
        '<tr><th>{{stroutcomeset}}</th><th>{{strsubject}}</th><th>{{streducationlevel}}</th><th></th></tr>' +
        '{{/if}}' +
        '{{#filters}}' +
        '<tr><td>{{{shorten name}}}</td>' +
        '<td>{{#if subjects}}{{{subjects}}}{{else}}{{../../strallsubjects}}{{/if}}</td>' +
        '<td>{{#if edulevels}}{{{edulevels}}}{{else}}{{../../stralleducationlevels}}{{/if}}</td>' +
        '<td></td></tr>' +
        '{{/filters}}' +
        '<tr>' +
        '<td>' +
        '<label for="outcomesets" class="accesshide">{{stroutcomeset}}</label>' +
        '<select id="outcomesets">' +
        '{{#outcomesets}}' +
        '<option value="{{id}}" {{selected id}}>{{{shorten name}}}</option>' +
        '{{/outcomesets}}' +
        '</select>' +
        '</td>' +
        '<td>' +
        '<label for="subjects" class="accesshide">{{strsubjects}}</label>' +
        '<select id="subjects" {{disableifempty subjects}}>' +
        '<option value="0">{{strallsubjects}}</option>' +
        '{{#each subjects}}' +
        '<option value="{{this.rawname}}">{{{this.name}}}</option>' +
        '{{/each}}' +
        '</select>' +
        '</td>' +
        '<td>' +
        '<label for="edulevels" class="accesshide">{{streducationlevels}}</label>' +
        '<select id="edulevels" {{disableifempty edulevels}}>' +
        '<option value="0">{{stralleducationlevels}}</option>' +
        '{{#each edulevels}}' +
        '<option value="{{this.rawname}}">{{{this.name}}}</option>' +
        '{{/each}}' +
        '</select>' +
        '</td>' +
        '<td>' +
        '<button id="add_button" {{disableifempty selectedoutcomeset}}>{{stradd}}</button></td></tr>' +
        '</table>',

// Render helpers
    renderGetStringHelper = function(identifier, a) {
        return M.util.get_string(identifier, 'outcome', a);
    },

    renderSelectedHelper = function(value) {
        return SELECTED_OUTCOMESET == value ? 'selected="selected"' : '';
    },

    renderShortText = function(text) {
        return M.core_outcome.shortenText(text, 50);
    },

    renderDisableIfEmptyHelper = function(value) {
        var empty = true;
        if (Lang.isArray(value)) {
            empty = value.length === 0;
        } else if (Lang.isString(value)) {
            empty = value === '0' || value === '';
        }
        return empty ? 'disabled="disabled"' : '';
    };

/**
 * This does a couple of things:
 *
 * 1. Renders a UI to display currently selected outcome sets.
 * In this UI, the user can remove these outcome sets.
 *
 * 2. A panel to select new outcome sets.
 *
 * @constructor
 * @namespace M.core_outcome
 * @class mapoutcomeset
 * @extends Y.Widget
 */
var MAPOUTCOMESET = function() {
    MAPOUTCOMESET.superclass.constructor.apply(this, arguments);
};

Y.extend(MAPOUTCOMESET, Y.Widget,
    {
        /**
         * Setup IO
         *
         * @method initializer
         */
        initializer: function() {
            IO = new M.core_outcome.SimpleIO({ contextId: this.get('contextId') });
        },

        /**
         * Compile our templates and create
         * a node to hold our rendered HTML
         *
         * @method renderUI
         */
        renderUI: function() {
            // Register helpers and compile templates
            Y.Handlebars.registerHelper('getString', renderGetStringHelper);
            Y.Handlebars.registerHelper('selected', renderSelectedHelper);
            Y.Handlebars.registerHelper('disableifempty', renderDisableIfEmptyHelper);
            Y.Handlebars.registerHelper('shorten', renderShortText);
            LIST_TEMPLATE_COMPILED = Y.Handlebars.compile(LIST_TEMPLATE);
            ADD_TEMPLATE_COMPILED = Y.Handlebars.compile(ADD_TEMPLATE);

            // We build our UI inside of this node (otherwise we can blow away our hidden input field)
            NODE_CONTENT = Y.Node.create('<div></div>');
            NODE_CONTENT.addClass(this.getClassName('content', 'wrapper'));
            this.get(BOX).appendChild(NODE_CONTENT);

            this._update_ui();
        },

        /**
         * Event handlers - don't register if we are frozen
         *
         * @method bindUI
         */
        bindUI: function() {
            if (!this.get('isFrozen')) {
                this.get(BOX).delegate('click', this._handle_add, LINK_ADD, this);
                this.get(BOX).delegate('click', this._handle_delete, LINK_DELETE, this);
            }
        },

        /**
         * Syncs our main UI by rendering our list of mapped
         * outcome sets.  Also sets our save node's value to
         * the JSON value of our list.
         *
         * @method _update_ui
         * @private
         */
        _update_ui: function() {
            this.get(FILTER_LIST).sort();

            var html = LIST_TEMPLATE_COMPILED({
                isFrozen: this.get('isFrozen'),
                filters: this.get(FILTER_LIST).display_list(),
                ulClass: this.getClassName('filter', 'list'),
                urlImgDelete: URL_IMG_DELETE,
                strselectoutcomesets: M.str.outcome.selectoutcomesets
            });
            NODE_CONTENT.setHTML(html);
            this.get('saveNode').set('value', Y.JSON.stringify(this.get(FILTER_LIST).toJSON()));
        },

        /**
         * This syncs our panel UI.  This shows options for
         * adding new outcome sets and also displays one's
         * that we have just added.
         *
         * @method _update_panel_ui
         * @private
         */
        _update_panel_ui: function() {
            var html = ADD_TEMPLATE_COMPILED({
                filters: NEW_LIST.toJSON(),
                outcomesets: OUTCOME_SETS_MENU,
                edulevels: EDU_LEVELS_MENU,
                subjects: SUBJECTS_MENU,
                selectedoutcomeset: SELECTED_OUTCOMESET,
                stroutcomeset: M.str.outcome.outcomeset,
                streducationlevel: M.str.outcome.educationlevel,
                strsubject: M.str.outcome.subject,
                strsubjects: M.str.outcome.subjects,
                strallsubjects: M.str.outcome.allsubjects,
                streducationlevels: M.str.outcome.educationlevels,
                stralleducationlevels: M.str.outcome.alleducationlevels,
                stradd: M.str.outcome.add
            });
            this.get(PANEL).set('bodyContent', html);
            this.get(PANEL).centered();
        },

        /**
         * Resets the panel UI by clearing out the menus
         *
         * @method _reset_panel_ui
         * @private
         */
        _reset_panel_ui: function() {
            EDU_LEVELS_MENU = [];
            SUBJECTS_MENU = [];
            SELECTED_OUTCOMESET = undefined;
            this._update_panel_ui();
            this._do_default_panel_focus();
        },

        /**
         * Updates the panel UI menus from cache
         * The education levels and subjects are loaded via AJAX
         *
         * @method _update_panel_ui_from_cache
         * @param {Number} outcomesetid
         * @private
         */
        _update_panel_ui_from_cache: function(outcomesetid) {
            EDU_LEVELS_MENU = METADATA_MENU_CACHE[outcomesetid].edulevels;
            SUBJECTS_MENU = METADATA_MENU_CACHE[outcomesetid].subjects;
            SELECTED_OUTCOMESET = outcomesetid;
            this._update_panel_ui();
            this._do_default_panel_focus();
        },

        /**
         * Default panel focus - moves focus to outcome set menu
         *
         * @method _do_default_panel_focus
         * @private
         */
        _do_default_panel_focus: function() {
            this.get(PANEL).get(SRC_NODE).one(SELECT_OUTCOMESET).focus();
        },

        /**
         * Handler for adding additional outcome sets
         *
         * @method _handle_add
         * @param e
         * @private
         */
        _handle_add: function(e) {
            e.preventDefault();

            RETURN_FOCUS = e.target;
            NEW_LIST = new FilterList();
            this._ensure_outcome_set_menu(function() {
                this._update_panel_ui();
                this.get(PANEL).show();
            });
        },

        /**
         * Handler for removing an outcome set
         *
         * @method _handle_delete
         * @param {Event} e
         * @private
         */
        _handle_delete: function(e) {
            e.preventDefault();

            var target = e.target;
            if (!target.test('a')) {
                target = target.ancestor('a');
            }
            var model = this.get(FILTER_LIST).getById(target.getData('id'));
            this.get(FILTER_LIST).remove(model);
            this._update_ui();
            var focusNode = this.get(BOX).one('ul');
            if (!(focusNode instanceof Y.Node)) {
                focusNode = this.get(BOX).one(LINK_ADD);
            }
            focusNode.focus();
        },

        /**
         * Handles saving the panel
         *
         * @method _handle_panel_save
         * @param e
         * @private
         */
        _handle_panel_save: function(e) {
            e.preventDefault();

            // If not zero, then "push" the add button for them.
            if (this.get(PANEL).get(SRC_NODE).one(SELECT_OUTCOMESET).get('value') != 0) {
                this._handle_add_button(e);
            }
            this.get(FILTER_LIST).add(NEW_LIST);
            this.get(PANEL).hide();
            this._update_ui();
            this.get(BOX).one('ul').focus();
        },

        /**
         * Handles when the panel is canceled
         *
         * @method _handle_panel_cancel
         * @param e
         * @private
         */
        _handle_panel_cancel: function(e) {
            e.preventDefault();
            this.get(PANEL).hide();
        },

        /**
         * Handles when the outcome set menu in the panel has changed
         * On change, we want to load education levels and subjects menus
         *
         * @method _handle_outcome_set_change
         * @param e
         * @private
         */
        _handle_outcome_set_change: function(e) {
            var value = e.target.get('value');

            if (value === '0') { // Invalid
                this._reset_panel_ui();
                return;
            } else if (METADATA_MENU_CACHE[value] !== undefined) {
                this._update_panel_ui_from_cache(value);
                return;
            }
            IO.send({
                action: 'get_outcome_set_filter_menus',
                outcomesetid: value
            }, function(data) {
                METADATA_MENU_CACHE[value] = {
                    edulevels: data.edulevels,
                    subjects: data.subjects
                };
                this._update_panel_ui_from_cache(value);
            }, this);
        },

        /**
         * Handles when the add button is clicked in the panel
         * Reads the menus and adds a new model to our temp list
         *
         * @method _handle_add_button
         * @param {Event} e
         * @private
         */
        _handle_add_button: function(e) {
            e.preventDefault();

            var srcNode = this.get(PANEL).get(SRC_NODE),
                id = srcNode.one(SELECT_OUTCOMESET).get('value'),
                subjectselect = srcNode.one(SELECT_SUBJECTS),
                edulevelselect = srcNode.one(SELECT_EDU_LEVELS),
                rawedulevel = edulevelselect.get('value'),
                edulevel = edulevelselect.get('options').item(edulevelselect.get('selectedIndex')).getHTML(),
                rawsubject = subjectselect.get('value'),
                subject = subjectselect.get('options').item(subjectselect.get('selectedIndex')).getHTML();

            if (id === '0') {
                return;
            }

            var name = 'notFound';
            Y.Array.some(OUTCOME_SETS_MENU, function(value) {
                if (value.id == id) {
                    name = value.name;
                    return true;
                }
                return false;
            });

            NEW_LIST.add({
                outcomesetid: id,
                name: name,
                edulevels: edulevel,
                rawedulevels: rawedulevel,
                subjects: subject,
                rawsubjects: rawsubject
            });
            this._reset_panel_ui();
        },

        /**
         * Ensures that the outcome set menu is populated with data
         * Does an AJAX request to get the menu
         *
         * @method _ensure_outcome_set_menu
         * @param callback
         * @private
         */
        _ensure_outcome_set_menu: function(callback) {
            if (OUTCOME_SETS_MENU !== undefined) {
                callback.call(this);
                return;
            }
            IO.send({ action: 'get_mappable_outcome_sets_menu' }, function(data) {
                if (!Lang.isArray(data) || data.length === 0) {
                    new M.core.alert({
                        title: M.str.moodle.error,
                        message: M.str.outcome.nooutcomesetsfound,
                        yesLabel: M.str.outcome.close,
                        visible: true
                    });
                } else {
                    OUTCOME_SETS_MENU = data;
                    callback.call(this);
                }
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
                headerContent: M.str.outcome.selectoutcomesets,
                width: "",
                centered: true,
                render: true,
                visible: false,
                modal: true
            });

            panel.get(SRC_NODE).addClass(this.getClassName('panel'));
            panel.after('visibleChange', function(e) {
                if (!e.newVal && RETURN_FOCUS instanceof Y.Node) {
                    RETURN_FOCUS.focus();
                    RETURN_FOCUS = undefined;
                }
            });
            panel.addButton({
                value: M.str.outcome.ok,
                action: this._handle_panel_save,
                context: this
            });
            panel.addButton({
                value: M.str.moodle.cancel,
                action: this._handle_panel_cancel,
                context: this
            });

            // Listeners
            panel.get(SRC_NODE).delegate('change', this._handle_outcome_set_change, SELECT_OUTCOMESET, this);
            panel.get(SRC_NODE).delegate('click', this._handle_add_button, BUTTON_ADD, this);

            return panel;
        }
    },
    {
        /**
         * This reads in our save node (EG: we read/write to this via JSON)
         * by storing the save node itself and reading it to initialize
         * our filterList.
         */
        HTML_PARSER: {
            saveNode: INPUT_HIDDEN,
            filterList: function(srcNode) {
                var filterList = new FilterList();
                try {
                    var jsonString = srcNode.one(INPUT_HIDDEN).get('value');
                    if (jsonString.length > 0) {
                        filterList.add(Y.JSON.parse(jsonString));
                    }
                } catch (e) {
                    e.visible = true;
                    new M.core.exception(e);
                }
                return filterList;
            }
        },
        NAME: NAME,
        ATTRS: {
            /**
             * Moodle context ID - used for security checks in AJAX requests
             *
             * @attribute contextId
             * @type Number
             * @default undefined
             * @optional
             */
            contextId: {},
            /**
             * If the form is froze - if yes, then no event handlers, etc
             *
             * @attribute isFrozen
             * @type Boolean
             * @default false
             * @optional
             */
            isFrozen: { value: false, validator: Lang.isBoolean },
            /**
             * This is the node that we read/write our JSON list of mapped outcome sets
             *
             * @attribute saveNode
             * @type Y.Node
             * @required
             */
            saveNode: {
                validator: function(value) {
                    return value instanceof Y.Node;
                }
            },
            /**
             * The list of mapped outcome sets
             *
             * @attribute filterList
             * @type FilterList
             * @default FilterList
             * @optional
             */
            filterList: {
                valueFn: function() {
                    return new FilterList();
                }
            },
            /**
             * Our panel for adding new outcome sets
             *
             * @attribute panel
             * @type M.core.dialogue
             * @default M.core.dialogue
             * @optional
             */
            panel: { readOnly: true, valueFn: '_create_panel' }
        }
    }
);

/**
 * A filter model represents an outcome set with a single
 * set of filter options on an optional education level and
 * optional subject
 *
 * @class FilterModel
 * @extends Y.Model
 */
var FilterModel = Y.Base.create('filterModel', Y.Model, [], {
    /**
     * Generate an ID that prevents similar combinations from
     * being added to the list more than once.
     *
     * @method initializer
     */
    initializer: function() {
        this.set('id', [
            this.get('outcomesetid'),
            this.get('edulevels'),
            this.get('subjects')
        ].join('_'));
    },

    /**
     * Generate a nice display name
     *
     * @method get_display_name
     * @returns {*}
     */
    get_display_name: function() {
        var name = this.get('name');
        if (this.get('subjects') !== null && this.get('subjects') !== '') {
            name = name + ', ' + M.str.outcome.subject + ': ' + this.get('subjects');
        } else {
            name = name + ', ' + M.str.outcome.allsubjects;
        }
        if (this.get('edulevels') !== null && this.get('edulevels') !== '') {
            name = name + ', ' + M.str.outcome.educationlevel + ': ' + this.get('edulevels');
        } else {
            name = name + ', ' + M.str.outcome.alleducationlevels;
        }
        return name;
    },

    /**
     * Try to convert the education level to an integer - helps with sorting
     *
     * @method _edulevels_setter
     * @param value
     * @returns {*}
     * @private
     */
    _edulevels_setter: function(value) {
        if (value === '0') {
            return null;
        }
        // Try to convert to number if it actually is one
        if (!isNaN(value) && (parseFloat(value) == parseInt(value, 10))) {
            return parseInt(value, 10);
        }
        return value;
    },

    /**
     * Don't allow zero value
     *
     * @method _no_zero_setter
     * @param value
     * @returns {null}
     * @private
     */
    _no_zero_setter: function(value) {
        return value === '0' ? null : value;
    }
}, {
    ATTRS: {
        /**
         * The outcome set ID
         *
         * @attribute outcomesetid
         * @type String
         * @default null
         * @writeOnce
         */
        outcomesetid: { value: null, writeOnce: 'initOnly' },
        /**
         * The outcome set name
         *
         * @attribute contextId
         * @type String
         * @default null
         * @required
         */
        name: { value: null, writeOnce: 'initOnly' },
        /**
         * The education level filter option
         *
         * @attribute edulevels
         * @type Number
         * @default null
         * @required
         */
        edulevels: { value: null, writeOnce: 'initOnly', setter: '_edulevels_setter' },
        /**
         * The raw, unfiltered value of education level
         *
         * @attribute rawedulevels
         * @type Number
         * @default null
         * @required
         */
        rawedulevels: { value: null, writeOnce: 'initOnly', setter: '_edulevels_setter' },
        /**
         * The subject filter option
         *
         * @attribute subjects
         * @type String
         * @default null
         * @required
         */
        subjects: { value: null, writeOnce: 'initOnly', setter: '_no_zero_setter' },
        /**
         * The raw, unfiltered value of subject
         *
         * @attribute rawsubjects
         * @type String
         * @default null
         * @required
         */
        rawsubjects: { value: null, writeOnce: 'initOnly', setter: '_no_zero_setter' }
    }
});

/**
 * The filter list
 *
 * @class FilterList
 * @extends Y.ModelList
 */
var FilterList = Y.Base.create('filterList', Y.ModelList, [], {
    model: FilterModel,

    /**
     * Returns a list to be used by the template
     *
     * @method display_list
     * @returns {Array}
     */
    display_list: function() {
        var filters = [];
        this.each(function(model) {
            filters.push({
                id: model.get('id'),
                name: model.get_display_name()
            });
        }, this);

        return filters;
    },

    /**
     * Override sorting - sort by name, education level and then subject
     *
     * @method _sort
     * @param a
     * @param b
     * @returns {*}
     * @private
     */
    _sort: function(a, b) {
        var result = this._compare(a.get('name'), b.get('name'));
        if (result === 0) {
            result = this._compare(a.get('edulevels'), b.get('edulevels'));

            if (result === 0) {
                result = this._compare(a.get('subjects'), b.get('subjects'));
            }
        }
        return result;
    },

    /**
     * Sort of a fake out - this is used when
     * adding new models.  The _sort() though is
     * used when .sort() is called on this list.
     *
     * @method comparator
     */
    comparator: function(model) {
        var result = this.indexOf(model);
        if (result === -1) {
            // Not added yet
            result = this.size();
        }
        return result;
    }
});

M.core_outcome = M.core_outcome || {};
M.core_outcome.mapoutcomeset = MAPOUTCOMESET;
M.core_outcome.init_mapoutcomeset = function(config) {
    var widget = new MAPOUTCOMESET(config);
    widget.render();
    return widget;
};