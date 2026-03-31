/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


define([
    'jquery',
    'core/ajax',
    'core/notification',
    'core/templates',
    'local_edwiserform/jquery.dataTables',
    'local_edwiserform/dataTables.bootstrap4',
    'local_edwiserform/buttons.bootstrap4',
    'local_edwiserform/fixedColumns.bootstrap4',
    'local_edwiserform/formviewer'
], function ($, Ajax, Notification, Templates) {
    var update_separator = function() {
        var actions = null;
        if ($('.DTFC_LeftBodyLiner tr').length == 0) {
            return;
        }
        $('.DTFC_LeftBodyLiner tr').each(function(index, tr) {
            $(tr).find('.efb-data-actions span').remove();
            var actions = $(tr).find('.efb-data-action.show');
            if (actions.length < 2) {
                return;
            }
            actions.slice(0, actions.length - 1).after('<span> | </span>');
        });
    };

    /**
     * Show errors as a list in modal
     * @param {Array} errors Errors string list
     */
    var show_errors = function(errors) {
        var list = $('<ul></ul>');
        for (var i = 0; i < errors.length; i++) {
            list.append($('<li>' + errors[i] + '</li>'));
        }
        Formeo.dom.alert(
            'warning',
            list.get(0)
        );
    };
    return {
        update_separator: update_separator,
        init: function(formid, allowed) {
            var table;
            var PROMISES = {
                /**
                 * Ajax promise to delete form submission by ids
                 * @param  {Array}   ids Ids array of submission
                 * @return {Promise}     Ajax promise
                 */
                DELETE_SUBMISSION: function(ids) {
                    return Ajax.call([{
                        methodname: 'edwiserform_delete_submissions',
                        args: {
                            id: formid,
                            submissions: ids
                        }
                    }])[0]
                },

                /**
                 * Get form data using ajax
                 * @param  {String}  search Search query
                 * @param  {Number}  length Number of courses
                 * @param  {Number}  start  Start index of courses
                 * @return {Promise}        Ajax promise
                 */
                GET_FORM_SUBMISSIONS: function(search, start, length) {
                    return Ajax.call([{
                        methodname: 'edwiserform_get_form_submissions',
                        args: {
                            formid : formid,
                            search : search,
                            start  : start,
                            length : length
                        }
                    }])[0];
                },
            };

            $.fn.dataTable.ext.errMode = 'none';

            $(document).ready(function (e) {
                if (!allowed) {
                    $('body').addClass('user-view');
                }
                table = $("#efb-form-submissions").DataTable({
                    paging          : true,
                    ordering        : false,
                    bProcessing     : true,
                    bServerSide     : true,
                    rowId           : 'DT_RowId',
                    bDeferRender    : true,
                    scrollY         : "400px",
                    scrollX         : true,
                    scrollCollapse  : true,
                    classes: {
                        sScrollHeadInner: 'efb_dataTables_scrollHeadInner'
                    },
                    dom             : '<"efb-top"<"efb-listing"l><"efb-list-filtering"f>>t<"efb-bottom"<"efb-list-pagination"p><B>' + (allowed ? '<"efb-bulk">' : '') + '>i',
                    language        : {
                        sSearch: M.util.get_string('efb-search-form', 'local_edwiserform'),
                        emptyTable: M.util.get_string('listformdata-empty', 'local_edwiserform'),
                        info: M.util.get_string('efb-heading-listforms-showing', 'local_edwiserform', {'start': '_START_', 'end': '_END_', 'total': '_TOTAL_'}),
                        infoEmpty: M.util.get_string('efb-heading-listforms-showing', 'local_edwiserform', {'start': '0', 'end': '0', 'total': '0'}),
                    },
                    buttons         : [],
                    ajax: function(data, callback, settings) {
                        PROMISES.GET_FORM_SUBMISSIONS(
                            data.search.value,
                            data.start,
                            data.length
                        ).done(function(response) {
                            if (response.errors.length != 0) {
                                show_errors(response.errors);
                            }
                            delete response.errors;
                            callback(response);
                            update_separator();
                        }).fail(Notification.exception);
                    },
                    drawCallback    : function( settings ) {
                        update_separator();
                        if (allowed) {
                            $('.efb-bottom .dt-buttons').removeClass('btn-group');
                            Templates.render('local_edwiserform/bulk-actions', {
                                formid: formid,
                                wwwroot: M.cfg.wwwroot,
                                license: license == 'available' ? '' : M.util.get_string('activate-license', 'local_edwiserform')
                            }, 'theme_remui')
                            .done(function(html, js) {
                                Templates.replaceNode($('.efb-bulk'), html, js);
                            })
                            .fail(Notification.exception);
                        }
                        setTimeout(function() {
                            var element = $('.DTFC_LeftHeadWrapper .efb-table tr th:first-child');
                            var element2 = $('.DTFC_LeftBodyLiner .efb-table tr td:first-child');
                            $(element2).css('width', window.getComputedStyle(element[0]).width);
                        }, 0);
                    }
                });
                new $.fn.dataTable.FixedColumns(table, {
                    iLeftColumns: 2
                });
            });

            /**
             * Delete submission from ids passed in parameter
             * @param  {Array} ids Ids array of submissions
             */
            function delete_submissions(ids) {
                Formeo.dom.multiActions(
                    'warning',
                    M.util.get_string('deletesubmission', 'local_edwiserform'),
                    M.util.get_string('deletesubmissionmsg', 'local_edwiserform'),
                    [{
                        title: M.util.get_string('proceed', 'local_edwiserform'),
                        type: 'danger',
                        action: function() {
                            Formeo.dom.loading();
                            PROMISES.DELETE_SUBMISSION(ids).done(function(response) {
                                if (response.status == true) {
                                    Formeo.dom.alert('success', '<div class="col-12">' + response.msg + '</div>');
                                    table.draw();
                                    $('.submission-check-all').prop('checked', false);
                                }
                                Formeo.dom.loadingClose();
                            }).fail(function(ex) {
                                Notification.exception(ex);
                                Formeo.dom.loadingClose();
                            });
                        }
                    }, {
                        title: M.util.get_string('cancel', 'local_edwiserform'),
                        type: 'success'
                    }]
                );
            }

            $('body').on('click', '.efb-csv-export', function() {
                if (license != 'available') {
                    window.location.href = M.cfg.wwwroot + '/admin/settings.php?section=local_edwiserform&activetab=local_edwiserform_license_status';
                    return;
                }
                window.open(M.cfg.wwwroot + "/local/edwiserform/export.php?id=" + formid + "&action=data");
            });

            // Select All/None checkbox.
            $('body').on('change', '.submission-check-all', function() {
                $('.DTFC_Cloned .submission-check').prop('checked', $(this).is(':checked'));
            });

            // Apply bulk actions.
            $('body').on('click', '#efb-apply-actions', function() {
                switch($('#efb-bulk-actions').val()) {
                    case 'bulkaction':
                        // Show toaster if bulk action is not selected.
                        Formeo.dom.toaster(M.util.get_string('selectbulkaction', 'local_edwiserform'));
                        break;
                    case 'delete':
                        // Show toaster if deletiong submission without selecting any.
                        if (!$('.submission-check').is(':checked')) {
                            Formeo.dom.toaster(M.util.get_string('emptysubmission', 'local_edwiserform'));
                            return;
                        }

                        var ids = [];
                        // Prepare ids array to delete.
                        $('.submission-check:checked').each(function(i, e) {
                            ids.push($(e).data('value'));
                        });
                        delete_submissions(ids);
                        break;
                }
            });

            $('body').on('click', '.efb-data-action.delete-action', function() {
                delete_submissions([$(this).data('value')]);
            });

            $('body').on('click', '.efb-data-action', function(event) {
                event.preventDefault();
                return;
            });
        }
    };
});
