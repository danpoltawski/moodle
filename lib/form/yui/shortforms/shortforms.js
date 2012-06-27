YUI.add('moodle-form-shortforms', function(Y) {

    var CSS = {
            COLLAPSABLE : 'collapsable',
            COLLAPSED : 'collapsed',
            FHEADER : 'fheader',
            FTOGGLER : 'ftoggler',
            MFORM : 'mform'
        }

    var SHORTFORMS = function() {
        SHORTFORMS.superclass.constructor.apply(this, arguments);
    };

    Y.extend(SHORTFORMS, Y.Base, {
        initializer : function(params) {
            var fieldlist = Y.Node.all('.'+CSS.MFORM+' fieldset.'+CSS.COLLAPSABLE);
            // Look through collapsable fieldset divs
            fieldlist.each(function(fieldset) {
                // remove advanced button related stuff
                // TODO: remove them in the code
                fieldset.all('div.advancedbutton').remove();
                fieldset.all('div.fitem img.adv').remove();
                var fitems = fieldset.all('div.fitem');
                fitems.removeClass('advanced');
                fitems.removeClass('hide');

                fieldset.addClass('jsprocessed');
                // Get legend element
                var legendelement = fieldset.one('legend.'+CSS.FTOGGLER);

                // Turn headers to links for accessibility
                var headerlink = Y.Node.create('<a href="#"></a>');
                headerlink.addClass(CSS.FHEADER);
                headerlink.appendChild(legendelement.get('firstChild'));
                legendelement.prepend(headerlink);

                // Subscribe to click event
                headerlink.on('click', this.switch_state, this, fieldset);
            }, this);
        },
        switch_state : function(e, fieldset) {
            e.preventDefault();
            // toggle collapsed class
            fieldset.toggleClass(CSS.COLLAPSED);
            // get corresponding hidden variable
            var statuselement = new Y.NodeList(document.getElementsByName('mform_isexpanded_'+fieldset.get('id'))[0]);
            // and invert it
            statuselement.set('value', Math.abs(Number(statuselement.get('value'))-1));
        }
    }, {
        NAME : 'shortforms',
        ATTRS : {}
    });


    M.form = M.form || {};
    M.form.init_shortforms = function(params) {
        return new SHORTFORMS(params);
    }
}, '@VERSION@', {requires:['base', 'node', 'io', 'dom', 'moodle-enrol-notification']});