define(['jquery', 'core/ajax', 'local_edwiserform/form_data_list', 'local_edwiserform/formviewer'], function ($, ajax, formdata) {
    return {
        init: function() {
            $(document).ready(function (e) {
                $('body').on('click', '.registration-action', function() {
                    var _this = this;
                    var action = $(this).attr('data-action');
                    var formid = $(this).closest('table').data('formid');
                    var user = $(this).closest('tr').find('.formdata-user');
                    var userid = $(user).attr('data-userid');
                    Formeo.dom.loading();
                    var actionResponse = ajax.call([{
                        methodname: 'edwiserformevents_registration_action',
                        args : {
                            formid: formid,
                            userid: userid,
                            action: action
                        }
                    }]);
                    actionResponse[0].done(function(response) {
                        Formeo.dom.loadingClose();
                        if (response.status == true) {
                            $(_this).removeClass('show').parent().find('[data-action="' + response.type + '"]').addClass('show');
                            formdata.update_separator();
                            return;
                        }
                        alert(response.msg);
                    }).fail(function(ex) {
                        Formeo.dom.loadingClose();
                    });
                });
            });
        }
    };
});
