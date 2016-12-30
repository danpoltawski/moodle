
M.core_user = {};

M.core_user.init_participation = function(Y) {
	Y.on('change', function() {
		var action = Y.one('#formactionid');
		if (action.get('value') == '') {
			return;
		}
        var ok = false;
        Y.all('input.usercheckbox').each(function() {
            if (this.get('checked')) {
                ok = true;
            }
        });
        if (!ok) {
            // no checkbox selected
            return;
        }
        Y.one('#participantsform').submit();
	}, '#formactionid');

    Y.on('click', function(e) {
        // Presence of a show all link indicates we should redirect to
        // a page with all users listed and checked, otherwise just check
        // those already shown.
        var showallink = this.getAttribute('data-showallink');
        if (showallink) {
            window.location = showallink;
        }
        Y.all('input.usercheckbox').each(function() {
            this.set('checked', 'checked');
        });
    }, '#checkall, #checkallonpage');

    Y.on('click', function(e) {
        Y.all('input.usercheckbox').each(function() {
            this.set('checked', '');
        });
    }, '#checknone');
};
