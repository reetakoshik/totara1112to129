
M.core_completion = {};

M.core_completion.init = function(Y) {
    // Check the reload-forcing
    var changeDetector = Y.one('#completion_dynamic_change');
    if (changeDetector.get('value') > 0) {
        changeDetector.set('value', 0);
        window.location.reload();
        return;
    }

    var handle_success = function(id, o, args) {
        Y.one('#completion_dynamic_change').set('value', 1);

        if (o.responseText != 'OK') {
            alert('An error occurred when attempting to save your tick mark.\n\n('+o.responseText+'.)'); //TODO: localize

        } else {
            require(['core/templates', 'core/str'], function(templates, stringlib) {
                var current = args.state.get('value');
                var modulename = args.modulename.get('value'),
                    altstr,
                    titlestr,
                    icon;
                if (current == 1) {
                    args.state.set('value', 0);
                    altstr = {component: 'completion', key:'completion-alt-manual-y', param: modulename};
                    titlestr = {component: 'completion', key:'completion-title-manual-y', param: modulename};
                    icon = 'completion-manual-y';
                } else {
                    args.state.set('value', 1);
                    altstr = {component: 'completion', key:'completion-alt-manual-n', param: modulename};
                    titlestr = {component: 'completion', key:'completion-title-manual-n', param: modulename};
                    icon = 'completion-manual-n';
                }
                stringlib.get_strings([altstr, titlestr]).then(function (strings) {
                    altstr = strings[0];
                    titlestr = strings[1];

                    return templates.renderIcon(icon, {alt: altstr, title: titlestr});
                }).then(function (html) {
                    var completionicon = args.modulename.ancestor('form').ancestor().one('.completion-icon');
                    completionicon.set('data-loading', 'false');
                    completionicon.setContent(html);
                });
            });
        }

        args.ajax.remove();
    };

    var handle_failure = function(id, o, args) {
        alert('An error occurred when attempting to save your tick mark.\n\n('+o.responseText+'.)'); //TODO: localize
        args.ajax.remove();
    };

    var toggle = function(e) {
        e.preventDefault();

        var form = e.target;
        var cmid = 0;
        var completionstate = 0;
        var state = null;
        var image = null;
        var modulename = null;

        var inputs = Y.Node.getDOMNode(form).getElementsByTagName('input');
        for (var i=0; i<inputs.length; i++) {
            switch (inputs[i].name) {
                 case 'id':
                     cmid = inputs[i].value;
                     break;
                  case 'completionstate':
                     completionstate = inputs[i].value;
                     state = Y.one(inputs[i]);
                     break;
                  case 'modulename':
                     modulename = Y.one(inputs[i]);
                     break;
            }
            if (inputs[i].type == 'image') {
                image = Y.one(inputs[i]);
            }
        }

        // start spinning the ajax indicator
        var ajax = Y.Node.create('<div class="ajaxworking" />');
        form.append(ajax);

        var cfg = {
            method: "POST",
            data: 'id='+cmid+'&completionstate='+completionstate+'&fromajax=1&sesskey='+M.cfg.sesskey,
            on: {
                success: handle_success,
                failure: handle_failure
            },
            arguments: {state: state, image: image, ajax: ajax, modulename: modulename}
        };

        Y.use('io-base', function(Y) {
            Y.io(M.cfg.wwwroot+'/course/togglecompletion.php', cfg);
        });
    };

    // register submit handlers on manual tick completion forms
    Y.all('form.togglecompletion').each(function(form) {
        if (!form.hasClass('preventjs')) {
            Y.on('submit', toggle, form);
        }
    });

    Y.all('.activity .completion-icon').each(function (element) {
        var form = element.get('parentNode').one('form.togglecompletion');
        element.on('click', function (e) {
            e.preventDefault();
            element.set('data-loading', 'true');

            if (form.hasClass('preventjs')) {
                form.submit();
            } else {
                form.simulate('submit');
                require(['core/templates', 'core/str'], function(templates, stringlib) {
                    stringlib.get_string('loading', 'admin').then(function(loading) {
                        return templates.renderIcon('loading', {alt: loading});
                    }).then(function(html) {
                        if (element.get('data-loading') === 'true') {
                            element.setContent(html);
                        }
                    });
                });
            }
        });
    });

    // hide the help if there are no completion toggles or icons
    var help = Y.one('#completionprogressid');
    if (help && !(Y.one('form.togglecompletion') || Y.one('.autocompletion'))) {
        help.setStyle('display', 'none');
    }
};


