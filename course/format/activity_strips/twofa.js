var STRINGS = {};

$(document).ready(function() {
    require(['core/str'], function(stringlib) {
        stringlib.get_strings([
            {component: 'format_activity_strips', key:'twofa-sign'},
            {component: 'format_activity_strips', key:'twofa-description'},
            {component: 'format_activity_strips', key:'twofa-login'},
            {component: 'format_activity_strips', key:'twofa-password'},
            {component: 'format_activity_strips', key:'twofa-close'},
            {component: 'format_activity_strips', key:'twofa-submit'},
            {component: 'format_activity_strips', key:'twofa-error'}
        ]).then(function(strings) {
            STRINGS = {
                'twofa-sign': strings[0],
                'twofa-description': strings[1],
                'twofa-login': strings[2],
                'twofa-password': strings[3],
                'twofa-close': strings[4],
                'twofa-submit': strings[5],
                'twofa-error': strings[6]
            }
        })
    });

    $('.twofa').each(function() {
        $(this).off('click').on('click', function(e) {handleCheckboxClick(e);})
    });

    $('#tfiid_completed_format_activity_strips_activity_completion').on("click", handleEmbeddedCompletionClick);
});

function handleCheckboxClick(e) {
    e.preventDefault();
    e.stopPropagation();

    var $li = $(e.currentTarget).closest('li.activity');
    var completionstate = $li.find('form input[name="completionstate"]').val();

    if (completionstate == 0) {
        return;
    }

    var $modal = getLoginModal();
    var $form = $modal.find('form');

    $modal.find('alert').hide();

    $(document.body).append($modal);

    $form.off('submit').submit(onSubmitForm.bind(null, $form, $modal, $li));

    $modal.modal();
}

function onSubmitForm($form, $modal, $li, e) {
    e.preventDefault();

    var login = $form.find('#login-2fa').val();
    var pass = $form.find('#pass-2fa').val();
    var modId = $li.find('form input[name="id"]').val();
    var modulename = $li.find('from input[name="modulename"]').val();

    $.postJSON($form.attr('action')+'?'+$.param({ login: login, pass: pass, id: modId }), {}, function(res) {
        if (res.Status != 1) {
            $modal.find('.alert').text(res.Error).show();

            return;
        }

        $form.off('submit', onSubmitForm);

        require(['core/templates', 'core/str'], function(templates, stringlib) {
            var altstr   = {component: 'completion', key:'completion-alt-manual-y'  , param: modulename};
            var titlestr = {component: 'completion', key:'completion-title-manual-y', param: modulename};
            var icon     = 'completion-manual-y';

            stringlib.get_strings([altstr, titlestr]).then(function(strings) {
                altstr = strings[0];
                titlestr = strings[1];

                return templates.renderIcon(icon, {alt: altstr, title: titlestr});
            }).then(function(html) {
                var completionicon = $li.find('.completion-icon');

                completionicon.html(html);

                $li.find('form input[name="completionstate"]').val('0');

                $modal.modal('hide');
            });
        });
    });
}

function handleEmbeddedCompletionClick(e) {
    e.preventDefault();

    var $checkbox = $(e.target);

    if (!$checkbox.prop('checked')) {
        $checkbox.attr("disabled", true);
        return;
    }

    var modId = $('#tfiid_activity_id_format_activity_strips_activity_completion').val();

    var $modal = getLoginModal();
    var $form = $modal.find('form');

    $modal.find('alert').hide();

    $(document.body).append($modal);

    $form.off('submit').submit(onEmbeddedSubmitForm.bind(null, $form, $modal, modId, $checkbox));

    $modal.modal();
}

function onEmbeddedSubmitForm($form, $modal, modId, $checkbox, e) {
    e.preventDefault();

    var login = $form.find('#login-2fa').val();
    var pass = $form.find('#pass-2fa').val();

    $.postJSON($form.attr('action')+'?'+$.param({ login: login, pass: pass, id: modId }), {}, function(res) {
        if (res.Status != 1) {
            $modal.find('.alert').text(res.Error).show();

            return;
        }

        $form.off('submit', onSubmitForm);
        $modal.modal('hide');
        $checkbox.prop('checked', true);
    });

    return false;
}

function getLoginModal() {
    var $modal = $('.login-modal');

    if ($modal.length) {
        $modal.find('.alert').hide();
        $modal.find('input').val('');

        return $modal;
    }

    return $(
        '<div class="modal fade login-modal" tabindex="-1" role="dialog">'+
        '<div class="modal-dialog">'+
        '<div class="modal-content">'+
        '<form autocomplete="new-form" action="/course/format/activity_strips/togglecompletion.php" class="twofa-form">'+
        '<div class="modal-header">'+
        '<button type="button" class="close" data-dismiss="modal" aria-label="Close">'+
        '<span aria-hidden="true">&times;</span>'+
        '</button>'+
        '<h2>'+STRINGS['twofa-sign']+'</h2>'+
        '</div>'+
        '<div class="modal-body">'+
        '<p><b>'+STRINGS['twofa-description']+'</b></p>'+
        '<div class="alert alert-danger" role="alert" style="display: none">'+
        '</div>'+
        '<input type="password" style="height: 1px; width: 1px; display: block; padding: 0; border: none;" >'+
        '<div class="form-group">'+
        '<label for="login-2fa">'+STRINGS['twofa-login']+'</label>'+
        '<input id="login-2fa" style="width:100%;" type="text" autocomplete="new-login" '+
        'class="form-control">'+
        '</div>'+
        '<div class="form-group">'+
        '<label for="pass-2fa">'+STRINGS['twofa-password']+'</label>'+
        '<input id="pass-2fa" style="width:100%;" type="password" autocomplete="new-pass" '+
        'class="form-control">'+
        '</div>'+
        '</div>'+
        '<div class="modal-footer">'+
        '<button type="button" class="btn btn-default" data-dismiss="modal">'+STRINGS['twofa-close']+'</button>'+
        '<button type="submit" class="btn btn-default btn-submit">'+STRINGS['twofa-submit']+'</button>'+
        '</div>'+
        '</form>'+
        '</div>'+
        '</div>'+
        '</div>'
    );
}


