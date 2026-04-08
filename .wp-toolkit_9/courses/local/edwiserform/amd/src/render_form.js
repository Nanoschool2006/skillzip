/*
 * Edwiser_Forms - https://edwiser.org/
 * Version: 0.1.0
 * Author: Yogesh Shirsath
 */
define([
    'jquery',
    'core/ajax',
    'core/notification',
    'local_edwiserform/form_styles',
    'local_edwiserform/iefixes',
    'local_edwiserform/formviewer'
], function ($, Ajax, Notification, formStyles) {
    var SELECTORS = {
        CONTAINER: '.edwiserform-container',
        FULLPAGE: '#edwiserform-fullpage',
        UPLOADED_FILE: 'efb-uploaded-file',
        DELETE_FILE: 'efb-delete-user-file',
        DELETE_CANCEL: 'efb-delete-user-file-cancel',
        FILE: 'input[type="file"]',
        COMPONENT: 'local_edwiserform'
    };

    var ATTRIBUTE = {
        DATA_DELETING: 'data-deleting',
        DATA_PROCESSING: 'data-processing'
    };

    let formeoOpts = {
        container: '',
        countries: typeof countries == 'undefined' ? null : countries,
        localStorage: false, // Changed from session storage to local storage.
    };

    var formeo = [];
    var forms = $(SELECTORS.CONTAINER);
    var fullpage = $(SELECTORS.FULLPAGE) && $(SELECTORS.FULLPAGE).val();

    var PROMISES = {
        /**
         * Call moodle service edwiser_get_form_definition
         * @param  {Number}  id Form id
         * @return {Promise}    Ajax promise call
         */
        GET_FORM_DEFINITION: function(id) {
            var promise = Ajax.call([{
                methodname: 'edwiserform_get_form_definition',
                args: {
                    form_id: id,
                    countries: typeof window['countries'] == 'undefined' || true
                }   
            }]);
            return promise[0];
        },
        /**
         * Call moodle service edwiser_submit_form_data
         * @param  {Number}  formid   Form id
         * @param  {String}  formdata Submitted form data
         * @return {Promise}    Ajax promise call
         */
        SUBMIT_FORM_DATA: function(formid, formdata) {
            var promise = Ajax.call([{
                methodname: 'edwiserform_submit_form_data',
                args: {
                    formid: formid,
                    data: formdata
                }
            }]);
            return promise[0];
        },
        /**
         * Call moodle service edwiser_submit_form_data
         * @param  {Number}  formid   Form id
         * @param  {String}  formdata Submitted form data
         * @return {Promise}    Ajax promise call
         */
        UPLOAD_FILE: function(file) {
            var data = new FormData();
            data.append('sesskey', M.cfg.sesskey);
            data.append(0, file);
            return $.ajax({
                url: M.cfg.wwwroot + '/local/edwiserform/upload.php',
                type: 'POST',
                data: data,
                cache: false,
                async: false,
                dataType: 'json',
                processData: false, // Don't process the files.
                contentType: false // Set content type to false as jQuery will tell the server its a query string request.
            });
        }
    }

    /**
     * Apply action to form container
     * @param  {DOM}    form   Form DOM element
     * @param  {STRING} action Action link
     */
    function apply_action(form, action) {
        $(form).attr('action', action);
    }

    /**
     * Load data to form fields and trigger events
     * @param  {DOM} form DOM form object
     * @param  {Object} data Previously + Default data of user
     */
    function load_form_data(form, data) {
        var formData = JSON.parse(data);
        $.each(formData, function(index, attr) {
            $.each($(form).find('[name="' + attr.name + '"]'), function(i, elem) {
                switch (elem.tagName) {
                    case 'INPUT':
                        switch (elem.type) {
                            case 'radio':
                                if (elem.value == attr.value) {
                                    $(elem).prop('checked', true);
                                }
                                var changeEvent = new CustomEvent("click", {target: $(elem)[0]});
                                $(elem)[0].dispatchEvent(changeEvent);
                                break;
                            case 'checkbox':
                                if (elem.value == attr.value) {
                                    $(elem).prop('checked', true);
                                }
                                break;
                            case 'file':
                                if (attr.value != 0) {
                                    $(elem).after('<span class="efb-delete-user-file-notice">' + M.util.get_string('delete-userfile-on-save-notice', SELECTORS.COMPONENT) + '</span>');
                                    $(elem).after('<a href="#" class="' + SELECTORS.DELETE_CANCEL + '">' + M.util.get_string('cancel-delete-userfile', SELECTORS.COMPONENT) + '</a>');
                                    $(elem).after('<a href="#"  class="' + SELECTORS.DELETE_FILE + '">' + M.util.get_string('efb-form-action-delete-title', SELECTORS.COMPONENT) + '</a>');
                                    $(elem).after('<a class="' + SELECTORS.UPLOADED_FILE + '" target="_blank" href="' + attr.value + '">' + attr.filename + '</a>');
                                }
                                break;
                            default:
                                $(elem).val(attr.value);
                                break;
                        }
                        break;
                    case 'SELECT':
                        if ($(elem).is('[multiple="true"]')) {
                            let value = $(elem).val();
                            value.push(attr.value);
                            attr.value = value;
                        }
                    case 'TEXTAREA':
                        $(elem).val(attr.value);
                        break;
                }
                var changeEvent = new CustomEvent("change", {target: $(elem)[0]});
                $(elem)[0].dispatchEvent(changeEvent);
                if ($(elem).val() != '') {
                    $(elem).parents('.f-field-group').addClass('active');
                }
            });
        });
    }

    /**
     * Display validation errors to form element
     * @param {DOM}   form Form DOM element object
     * @param {Array}      errors errors list
     */
    function display_validation_errors(form, errors) {
        var errors = JSON.parse(errors);
        $('.custom-validation-error').remove();
        $.each(errors, function(name, error) {
            var errorview = $(form).find('#' + name + '-error');
            if (errorview.length == 0) {
                $(form).find('[name="' + name + '"]').after('<span id="' + name + '-error" class="text-error custom-validation-error"></span>');
                errorview = $(form).find("#" + name + "-error");
            }
            errorview.text(error);
        });
    }

    /**
     * Upload form data to service
     * @param {DOM}      form            Form DOM element object
     * @param {Formeo}   formeo          Formeo object
     * @param {DOM}      submitButton    Submit button DOM object
     * @param {String}   label           Label for submit button
     * @param {String}   processingLabel Label for submit button when form data is being submit
     * @param {Number}   formid          Form id
     * @param {String}   formdata        Submitted form data
     * @param {Function} afterSubmit     Function to be called after form submission
     */
    function upload_form_data(form, formeo, submitButton, label, processingLabel, formid, formdata, afterSubmit = null) {
        $(submitButton).text(processingLabel);
        PROMISES.SUBMIT_FORM_DATA(formid, formdata)
        .done(function(response) {
            if (response.status) {
                if (typeof response.data != 'undefined' && response.data != '') {
                    data = JSON.parse(response.data);
                    if (data.action == 'redirect') {
                        window.location.href = data.url;
                        return;
                    } else if (data.action == 'confirmredirect') {
                        if (response.msg != '') {
                            formeo.dom.alert('warning', response.msg, function() {
                                window.location.href = data.url;
                            });
                        } else {
                            window.location.href = data.url;
                        }
                        return;
                    }
                }
                $(form).html(response.msg);
                formeo.dom.alert('success', response.msg);
                if (afterSubmit != null) {
                    afterSubmit(form, formdata);
                }
                if ($('#edwiser-activity') && $('#edwiser-activity').val() == true) {
                    $('body').trigger({
                        type: 'formSubmitted',
                        cmid: $('#cmid').val()
                    });
                }
            } else {
                if (response.msg != '') {
                    formeo.dom.alert('warning', response.msg);
                }
                if (response.hasOwnProperty('errors')) {
                    display_validation_errors(form, response.errors);
                }
            }
            formeo.dom.loadingClose();
            $(submitButton).text(label);
        }).fail(function(ex) {
            $(submitButton).text(label);
            formeo.dom.loadingClose();
            $(submitButton).text(label);
        });
    }

    /**
     * Filter form data
     * @param  {Array} formdata Submitted form data array
     * @return {String}           JSON formdata
     */
    function filter_formdata(formdata) {
        var removeList = ['g-recaptcha-response'];
        var filteredList = [];
        formdata.forEach(function(element, index) {
            if (removeList.indexOf(element.name) == -1) {
                filteredList.push(element);
            }
        });
        return JSON.stringify(filteredList);
    }

    /**
     * Upload file to server
     * @param  {DOM}   form     Form DOM element object
     * @param  {Array} formdata Form data
     * @return {Array}          Form data
     */
    function upload_files(form, formdata) {
        var fileinputs = $(form).find(SELECTORS.FILE);
        $.each(fileinputs, function(index, fileinput) {
            if (fileinput.files.length != 0) {
                PROMISES.UPLOAD_FILE(fileinput.files[0])
                .done(function(response) {
                    if (response.status == true) {
                        formdata.push({
                            name: $(fileinput).attr('name'),
                            type: 'file',
                            value: response.itemid
                        });
                    }
                })
                .fail(function(ex) {
                    console.log(ex);
                });
            } else if ($(fileinput).attr(ATTRIBUTE.DATA_DELETING) == "true") {
                formdata.push({
                    name: $(fileinput).attr('name'),
                    type: 'file',
                    value: 0,
                    delete: true
                });
            } else {
                formdata.push({
                    name: $(fileinput).attr('name'),
                    type: 'file',
                    value: 0
                });
            }
        });
        return formdata;
    }

    /**
     * Prepare and submit form data to server
     * @param  {DOM}    _this  Clicked form object
     * @param  {Formeo} formeo Formeo object
     * @param  {Event}  event  Event triggered on form
     * @return {Boolen}        True if form submit is allowed
     */
    function submit_form(_this, formeo, event) {
        var form = $(_this).closest('form');
        var submitButton = _this;
        var valid = formeo.dom.checkValidity(form[0]);
        var label = $(_this).text();
        var processingLabel = $(_this).attr(ATTRIBUTE.DATA_PROCESSING);
        var formid = $(form).parent().find('.id').val();
        if (valid) {
            if ($(form).attr('action') != '') {
                $(form).submit();
                return true;
            }
            event.preventDefault();
            formeo.dom.loading();
            setTimeout(function() {
                var formdata = form.serializeArray();
                formdata = upload_files(form, formdata);
                formdata = filter_formdata(formdata);
                upload_form_data(form, formeo, submitButton, label, processingLabel, formid, formdata);
            }, 1000);
        }
        return false;
    }

    /**
     * Initialize events on elements
     */
    function initializeEvents() {
        // Handle form field arrangement on resize.
        $(window).resize(function() {
            $.each(forms, function(index) {
                if (typeof formeo[index] != 'undefined') {
                    formeo[index].dom.manageFormWidth(fullpage);
                }
            });
        });

        $('.step-navigation #previous-step, .step-navigation #next-step').click(function() {
            return;
        });

        $('body').on('click', '.' + SELECTORS.DELETE_FILE, function(event) {
            event.preventDefault();
            $(this).siblings(SELECTORS.FILE).attr(ATTRIBUTE.DATA_DELETING, true);
        }).on('click', '.' + SELECTORS.DELETE_CANCEL, function(event) {
            event.preventDefault();
            $(this).siblings(SELECTORS.FILE).attr(ATTRIBUTE.DATA_DELETING, false);
        }).on('change', SELECTORS.FILE, function() {
            // Add file.
            let _this = this;
            if ($(this)[0].files.length == 0) {
                return;
            }
            if ($(this).next().hasClass(SELECTORS.UPLOADED_FILE)) {
                let index = $.inArray($(_this).parents(SELECTORS.CONTAINER)[0], $(SELECTORS.CONTAINER));
                formeo[index].dom.multiActions(
                    'warning',
                    M.util.get_string("attention", SELECTORS.COMPONENT),
                    M.util.get_string("user-file-replace", SELECTORS.COMPONENT, $(this).find('h4').text()),
                    [{
                        title: M.util.get_string('proceed', SELECTORS.COMPONENT),
                        type: 'warning'
                    }, {
                        title: M.util.get_string('cancel', SELECTORS.COMPONENT),
                        type: 'primary',
                        action: function() {
                            $(_this).val('');
                        }
                    }]
                );
            }
        }).on('click', '.efb-view-fullpage', function() {
            // View form on full page.
            var id = $(this).closest(SELECTORS.CONTAINER).parent().find('input[class="id"]').val();
            window.open(M.cfg.wwwroot + '/local/edwiserform/form.php?id=' + id);
            $(this).closest(SELECTORS.CONTAINER).html(M.util.get_string('fullpage-link-clicked', SELECTORS.COMPONENT));
        });
    }

    /**
     * Render all forms on the page
     * @param  {String} sitekey sitekey for Google recaptcha
     */
    function render_forms(sitekey) {
        formeoOpts.sitekey = sitekey;
        $.each(forms, function(index, form) {
            var idElement = $(form).parent().find('.id');
            var id = idElement.val();
            PROMISES.GET_FORM_DEFINITION(id)
            .done(function(response) {
                if (response.status != false) {
                    if ((typeof window['countries'] == 'undefined' ||window['countries'] == false) &&
                        (typeof response.countries != 'undefined' && response.countries != false)) {
                        window['countries'] = JSON.parse(response.countries)
                        formeoOpts.countries = countries;
                    }
                    formeoOpts.container = form;
                    formeo[index] = new Formeo(formeoOpts, response.definition);
                    formeo[index].render(form);
                    $(form).prepend("<h2 class='form-header'>" + response.title + "</h2>");
                    if (response.data) {
                        load_form_data(form, response.data);
                    }
                    formStyles.apply($(form).find('.formeo-render'), 'add', response.style);
                    if (response.action && response.action != '') {
                        apply_action(form, response.action);
                    }
                    $(form).submit(function(event) {
                        return submit_form(this, formeo[index], event);
                    })
                    .find('#submit-form').click(function() {
                        return submit_form(this, formeo[index], event);
                    });
                } else {
                    $(form).html(response.msg).addClass("empty");
                }
            }).fail(Notification.exception);
        });
    }
    return {
        init: function(sitekey) {
            $(document).ready(function (e) {
                if ($('#edwiserform-fullpage').length != 0 && $('#edwiserform-fullpage').val() == true) {
                    $('body').addClass('edwiserform-fullpage');
                }
                render_forms(sitekey);
                initializeEvents();
            });
        }
    };
});
