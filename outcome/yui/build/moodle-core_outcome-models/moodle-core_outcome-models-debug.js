YUI.add('moodle-core_outcome-models', function (Y, NAME) {

var Lang = Y.Lang,
    NEW_OUTCOME_ID = -1,

// Utility functions
    /**
     * @return {String}
     */
    SHORTEN_TEXT = function(text, length) {
        if (Lang.isUndefined(length)) {
            length = 30;
        }
        if (text.length > length) {
            var s = text.substr(0, length - 1);
            s = s.substr(0, s.lastIndexOf(' '));

            return s + '...';
        }
        return text;
    },

// Validators
    /**
     * @return {boolean}
     */
    INT_VALIDATOR = function(value) {
        return !isNaN(value) && (parseFloat(value) == parseInt(value, 10));
    },
    /**
     * @return {boolean}
     */
    NON_EMPTY_STRING_VALIDATOR = function(value) {
        return Lang.isString(value) && Lang.trim(value).length > 0;
    },
// Setters
    /**
     * @return {Number}
     */
    SET_INT = function(value) {
        return parseInt(value, 10);
    },
    /**
     * @return {Number}
     */
    SET_BINARY_INT = function(value) {
        value = parseInt(value, 10);
        return value === 1 ? value : 0;
    },
    /**
     * @return {Number}
     */
    SET_POSITIVE_INT = function(value) {
        value = parseInt(value, 10);
        return value < 0 ? 0 : value;
    },
    /**
     * @return {Number|null}
     */
    SET_OPTIONAL_INT = function(value) {
        if (Lang.isNull(value)) {
            return null;
        }
        if (!INT_VALIDATOR(value)) {
            return null;
        }
        value = parseInt(value, 10);
        return value === 0 ? null : value;
    },
    /**
     * @return {String|null}
     */
    SET_OPTIONAL_STRING = function(value) {
        if (!Lang.isString(value)) {
            return null;
        }
        value = Lang.trim(value);
        if (value.length === 0) {
            return null;
        }
        return value;
    };

/**
 * This model represents a single outcome
 * @type {*}
 */
var OUTCOME_MODEL = Y.Base.create('outcomeModel', Y.Model, [], {
    /**
     * Sends the model to the server for validation and cleaning.
     * Will update itself with new values from server if there are no
     * errors.
     * @param {Function} fn
     * @param context
     */
    validate_and_update: function(fn, context) {
        var io = new M.core_outcome.SimpleIO();
        io.send({
            action: 'validate_outcome',
            sesskey: M.cfg.sesskey,
            data: Y.JSON.stringify(this.toJSON())
        }, function(data) {
            if (Lang.isArray(data.errors)) {
                fn.call(context, data.errors);
            } else {
                this.setAttrs(data);
                fn.call(context);
            }
        }, this);
    },

    get_short_description: function(length) {
        return SHORTEN_TEXT(this.get('description'), length);
    },

    /**
     * Getter for hasChanged
     * @param currentValue
     * @returns {boolean}
     */
    _get_changed: function(currentValue) {
        if (currentValue === true) {
            return true;
        }
        return !Y.Object.isEmpty(this.changed);
    }
}, {
    ATTRS: {
        id: { value: null, validator: INT_VALIDATOR, setter: SET_INT },
        parentid: { value: null, setter: SET_OPTIONAL_INT },
        idnumber: { value: null, validator: NON_EMPTY_STRING_VALIDATOR, setter: SET_OPTIONAL_STRING },
        rawidnumber: { value: null, validator: NON_EMPTY_STRING_VALIDATOR, setter: SET_OPTIONAL_STRING },
        docnum: { value: null, setter: SET_OPTIONAL_STRING },
        rawdocnum: { value: null, setter: SET_OPTIONAL_STRING },
        description: { value: null, validator: NON_EMPTY_STRING_VALIDATOR, setter: SET_OPTIONAL_STRING },
        rawdescription: { value: null, validator: NON_EMPTY_STRING_VALIDATOR, setter: SET_OPTIONAL_STRING },
        assessable: { value: 1, validator: INT_VALIDATOR, setter: SET_BINARY_INT },
        deleted: { value: 0, validator: INT_VALIDATOR, setter: SET_BINARY_INT },
        sortorder: { value: 0, validator: INT_VALIDATOR, setter: SET_POSITIVE_INT },
        edulevels: { value: [], validator: Lang.isArray },
        rawedulevels: { value: [], validator: Lang.isArray },
        subjects: { value: [], validator: Lang.isArray },
        rawsubjects: { value: [], validator: Lang.isArray },
        hasChanged: { value: false, validator: Lang.isBoolean, getter: '_get_changed' }
    }
});

/**
 * This represents a list of outcomes
 * @type {*}
 */
var OUTCOME_LIST = Y.Base.create('outcomeList', Y.ModelList, [], {
    model: OUTCOME_MODEL,

    /**
     * This keeps our list sorted
     * @param outcome
     * @returns {Number}
     */
    comparator: function(outcome) {
        return outcome.get('sortorder');
    },

    /**
     * Get a list of outcomes that have been modified
     * @returns {Array}
     */
    filter_by_modified: function() {
        return this.filter(function(outcome) {
            return (outcome.get('id') < 0 || outcome.isModified() || outcome.get('hasChanged'));
        });
    },

    /**
     * Gets a list of outcomes without the passed outcome and any of its children
     * @param outcome
     * @returns {Array}
     */
    filter_out_branch: function (outcome) {
        return this.filter(function(model) {
            return (outcome.get('id') !== model.get('id') && !this.is_child_of(outcome, model));
        }, this);
    },

    /**
     * Returns an outcome and its children and grand children, etc
     * @param outcome
     * @returns {Array}
     */
    filter_by_branch: function (outcome) {
        return this.filter(function(model) {
            return (outcome.get('id') === model.get('id') || this.is_child_of(outcome, model));
        }, this);
    },

    /**
     * Gets a list of outcomes that have the same parent
     * (Does not include children!)
     * @param {Number|null} parentid
     * @returns {*}
     */
    filter_by_parentid: function(parentid) {
        return this.filter(function(model) {
            return (model.get('parentid') === parentid);
        }, this);
    },

    /**
     * Get the depth of an outcome in the hierarchy
     * @param model
     * @returns {number}
     */
    get_depth: function(model) {
        var parentid = model.get('parentid'), depth = 0;
        while (!Lang.isNull(parentid)) {
            depth++;
            parentid = this.getById(parentid).get('parentid');
        }
        return depth;
    },

    /**
     * Find a new parent ID and sort order based on
     * how we are inserting the outcome
     * @param referenceModel The outcome to be used as our point of reference (EG: move after this outcome)
     * @param {String} placement Can be "before", "after" or "child"
     * @returns {{parentid: null, sortorder: null}}
     */
    find_new_position: function(referenceModel, placement) {
        var position = {parentid: null, sortorder: null};

        if (placement === 'before') {
            position.parentid = referenceModel.get('parentid');
            position.sortorder = referenceModel.get('sortorder');
        } else if (placement === 'after') {
            var branch = this.filter_by_branch(referenceModel);
            position.parentid = referenceModel.get('parentid');
            position.sortorder = branch.pop().get('sortorder') + 1;
        } else if (placement === 'child') {
            position.parentid = referenceModel.get('id');
            position.sortorder = referenceModel.get('sortorder') + 1;
        }
        return position;
    },

    /**
     * Move an outcome
     * @param outcome
     * @param position See find_new_position
     */
    move_outcome: function(outcome, position) {
        var branch = this.filter_by_branch(outcome);
        var sortorder = position.sortorder;

        // Remove all outcomes that are being moved (we re-add below)
        this.remove(branch);

        // Re-assign to our new parent
        branch[0].set('parentid', position.parentid);

        Y.Array.each(branch, function(model) {
            model.set('sortorder', sortorder);
            this.open_sort_order_gap(sortorder);
            this.add(model);
            sortorder++;
        }, this);

        // We need this to close any gaps that we created from the move
        this.repair_sort_order();
    },

    /**
     * Determine if one outcome is a child of another
     * @param parent
     * @param child
     * @returns {boolean}
     */
    is_child_of: function(parent, child) {
        var parentid = child.get('parentid');
        while (!Lang.isNull(parentid)) {
            if (parentid === parent.get('id')) {
                return true;
            }
            parentid = this.getById(parentid).get('parentid');
        }
        return false;
    },

    /**
     * Add a new outcome - only new ones are processed
     * @param model
     */
    add_new_outcome: function(model) {
        if (!model.isNew()) {
            // Update existing model and bail.
            this.getById(model.get('id')).setAttrs(model.toJSON());
            return;
        }
        // Preserve for below
        var parentid = model.get('parentid');

        // Default, add to end of list
        model.set('parentid', null);
        model.set('id', NEW_OUTCOME_ID);
        model.set('sortorder', this.size());
        this.add(model);

        NEW_OUTCOME_ID--;

        // If parent ID is set, then move after last child
        if (parentid !== null) {
            var parent = this.getById(parentid);
            var children = this.filter_by_parentid(parent.get('id'));

            if (children.length > 0) {
                this.move_outcome(model, this.find_new_position(children.pop(), 'after'));
            } else {
                this.move_outcome(model, this.find_new_position(parent, 'child'));
            }
        }
    },

    /**
     * Remove an outcome
     * @param outcome
     */
    remove_outcome: function(outcome) {
        var children = this.filter_by_parentid(outcome.get('id'));

        if (outcome.get('id') < 0) {
            this.remove(outcome);

            // Ensure we create no orphans.
            Y.Array.each(children, function(child) {
                child.set('parentid', outcome.get('parentid'));
            });
        } else {
            outcome.set('deleted', 1);
        }
        Y.Array.each(children, function(child) {
            this.remove_outcome(child);
        }, this);

        this.repair_sort_order();
    },

    /**
     * Ensures that the sort order goes 0,1,2,3,etc
     */
    repair_sort_order: function() {
        var sortorder = 0;
        this.each(function(outcome) {
            outcome.set('sortorder', sortorder);
            sortorder++;
        });
    },

    /**
     * Creates a gap in the sort order
     * @param {Number} startSortOrder
     */
    open_sort_order_gap: function(startSortOrder) {
        this.each(function(outcome) {
            if (outcome.get('sortorder') >= startSortOrder) {
                outcome.set('sortorder', (outcome.get('sortorder') + 1));
            }
        });
    },

    /**
     * Closes a gap in the sort order
     * @param {Number} startSortOrder
     */
    close_sort_order_gap: function(startSortOrder) {
        this.each(function(outcome) {
            if (outcome.get('sortorder') > startSortOrder) {
                outcome.set('sortorder', (outcome.get('sortorder') - 1));
            }
        });
    }
});

/**
 * This model represents a single outcome set
 * @type {*}
 */
var OUTCOME_SET_MODEL = Y.Base.create('outcomeSetModel', Y.Model, [], {
    get_short_name: function(length) {
        return SHORTEN_TEXT(this.get('name'), length);
    }
}, {
    ATTRS: {
        id: { value: null, validator: INT_VALIDATOR, setter: SET_INT },
        idnumber: { value: null, validator: NON_EMPTY_STRING_VALIDATOR, setter: SET_OPTIONAL_STRING },
        name: { value: null, validator: NON_EMPTY_STRING_VALIDATOR, setter: SET_OPTIONAL_STRING },
        description: { value: null, setter: SET_OPTIONAL_STRING },
        provider: { value: null, setter: SET_OPTIONAL_STRING },
        revision: { value: null, setter: SET_OPTIONAL_STRING },
        region: { value: null, setter: SET_OPTIONAL_STRING },
        deleted: { value: 0, validator: INT_VALIDATOR, setter: SET_BINARY_INT },
        timecreated: { value: null, validator: INT_VALIDATOR, setter: SET_INT },
        timemodified: { value: null, validator: INT_VALIDATOR, setter: SET_INT }
    }
});

/**
 * This represents a list of outcome sets
 * @type {*}
 */
var OUTCOME_SET_LIST = Y.Base.create('outcomeSetList', Y.ModelList, [], {
    model: OUTCOME_SET_MODEL,

    comparator: function(model) {
        return model.get('name');
    }
});

M.core_outcome = M.core_outcome || {};
M.core_outcome.shortenText = SHORTEN_TEXT;
M.core_outcome.OutcomeModel = OUTCOME_MODEL;
M.core_outcome.OutcomeList = OUTCOME_LIST;
M.core_outcome.OutcomeSetModel = OUTCOME_SET_MODEL;
M.core_outcome.OutcomeSetList = OUTCOME_SET_LIST;


}, '@VERSION@', {"requires": ["base", "model", "model-list", "moodle-core_outcome-simpleio", "json-stringify"]});
