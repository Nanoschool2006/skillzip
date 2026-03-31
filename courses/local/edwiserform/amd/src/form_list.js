/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


define([
   'jquery',
   'core/ajax',
   'core/notification',
   'local_edwiserform/jquery.dataTables',
   'local_edwiserform/dataTables.bootstrap4',
   'local_edwiserform/buttons.bootstrap4',
   'local_edwiserform/fixedColumns.bootstrap4',
   'local_edwiserform/formviewer'
], function ($, Ajax, Notification) {
    return {
        init: function() {

            var table;

            var PROMISES = {

                /**
                 * Get forms using ajax
                 * @param  {String}  search Search query
                 * @param  {Number}  length Number of courses
                 * @param  {Number}  start  Start index of courses
                 * @param  {Array}   order  Column order
                 * @return {Promise}        Ajax promise
                 */
                GET_FORMS: function(search, start, length, order) {
                    return Ajax.call([{
                        methodname: 'edwiserform_get_forms',
                        args: {
                            search : search,
                            start  : start,
                            length : length,
                            order: order
                        }
                    }])[0];
                },
            };
            $(document).ready(function (e) {
                table = $("#efb-forms").DataTable({
                    paging          : true,
                    ordering        : true,
                    bProcessing     : true,
                    bServerSide     : true,
                    rowId           : 'DT_RowId',
                    bDeferRender    : true,
                    scrollY         : "400px",
                    scrollX         : true,
                    scrollCollapse  : true,
                    fixedColumns    : {
                        leftColumns     : 1,
                        rightColumns    : 1
                    },
                    classes: {
                        sScrollHeadInner: 'efb_dataTables_scrollHeadInner'
                    },
                    dom             : '<"efb-top"<"efb-listing"l><"efb-list-filtering"f>>t<"efb-bottom"<"efb-form-list-info"i><"efb-list-pagination"p>><"efb-shortcode-copy-note">',
                    columns: [
                        { data: "title" },
                        { data: "type" },
                        { data: "shortcode" , "orderable" : false},
                        { data: "author" },
                        { data: "created" },
                        { data: "author2" },
                        { data: "modified" },
                        { data: "allowsubmissionsfromdate" },
                        { data: "allowsubmissionstodate" },
                        { data: "actions" , "orderable" : false}
                    ],
                    ajax: function(data, callback, settings) {
                        PROMISES.GET_FORMS(
                            data.search.value,
                            data.start,
                            data.length,
                            data.order[0]
                        ).done(function(response) {
                            callback(response);
                        }).fail(Notification.exception);
                    },
                    language        : {
                        sSearch: M.util.get_string('efb-search-form', 'local_edwiserform'),
                        emptyTable: M.util.get_string('listforms-empty', 'local_edwiserform'),
                        info: M.util.get_string('efb-heading-listforms-showing', 'local_edwiserform', {'start': '_START_', 'end': '_END_', 'total': '_TOTAL_'}),
                        infoEmpty: M.util.get_string('efb-heading-listforms-showing', 'local_edwiserform', {'start': '0', 'end': '0', 'total': '0'}),
                    },
                    "fnRowCallback" : function( nRow, aData, iDisplayIndex, iDisplayIndexFull ) {
                        $('td:eq(0)', nRow).addClass( "efb-tbl-col-title");
                        $('td:eq(2)', nRow).addClass( "efb-tbl-col-shortcode").attr('title', M.util.get_string('clickonshortcode', 'local_edwiserform'));
                    },
                    drawCallback    : function( settings ) {
                        $('.efb-shortcode-copy-note').html(M.util.get_string('note', 'local_edwiserform') + ' ' + M.util.get_string('clickonshortcode', 'local_edwiserform'));
                    }
                });
                $('.efb-modal-close').click(function() {
                    $('#efb-modal').removeClass('show');
                });
                $('body').on('click', '.efb-form-delete', function(event) {
                    event.preventDefault();
                    var id = $(this).data('formid');
                    var row = $(this).parents('tr');
                    var title = $(row).children('.efb-tbl-col-title').text();
                    $('#efb-modal .efb-modal-header').removeClass('bg-success').addClass('bg-warning').children('.efb-modal-title').html(M.util.get_string('warning', 'local_edwiserform'));
                    $('#efb-modal .efb-modal-body').html('<h5>' + M.util.get_string('efb-delete-form-and-data', 'local_edwiserform', {
                        title: title,
                        id: id
                    }) + '</h5>');
                    $('#efb-modal').addClass('show delete').removeClass('pro');
                    $('#efb-modal .efb-modal-delete-form').data('formid', id);
                    return;
                }).on('click', '.efb-form-export', function(event) {
                    if (license != 'available') {
                        $('#efb-modal .efb-modal-header').removeClass('bg-success').addClass('bg-warning');
                        $('#efb-modal .efb-modal-title').html(M.util.get_string('hey-wait', 'local_edwiserform'));
                        $('#efb-modal .efb-modal-body').html('<h5>' + M.util.get_string('efb-template-inactive-license', 'local_edwiserform', M.util.get_string('efb-form-action-export-title', 'local_edwiserform')) + '</h5>');
                        $('#efb-modal').addClass('show pro').removeClass('delete');
                        event.preventDefault();
                        return;
                    }
                }).on('click', '.efb-modal-pro-activate', function() {
                    window.location.href = M.cfg.wwwroot + '/admin/settings.php?section=local_edwiserform&activetab=local_edwiserform_license_status';
                });
                $('body').on('click', '.efb-modal-delete-form', function(event) {
                    event.preventDefault();
                    var id = $(this).data('formid');
                    var reqDeleteForm = Ajax.call([{
                        methodname: 'edwiserform_delete_form',
                        args: {
                            id: id
                        }
                    }]);
                    reqDeleteForm[0].done(function(response) {
                        if (response.status == true) {
                            table.draw();
                        }
                    }).fail(Notification.exception);
                    $('.efb-modal-close').click();
                });

                /**
                 * Enable or disable form on the basis on form id
                 * @param  {DOM} input DOM element
                 */
                function enable_disable_form(input) {
                    var formid = $(input).data('formid');
                    var enabledisableform = Ajax.call([{
                        methodname: 'edwiserform_enable_disable_form',
                        args: {
                            id: formid,
                            action: !$(input).is(':checked')
                        }
                    }]);
                    enabledisableform[0].done(function(response) {
                        if (response.status) {
                            $(input).prop('checked', $(input).is(':checked'));
                        } else {
                            $(input).prop('checked', !$(input).is(':checked'));
                        }
                        $(input).parent().attr('title', $(input).is(':checked') ? $(input).data('disable-title') : $(input).data('enable-title'))
                    }).fail(Notification.exception);
                }

                $('body').on('click', '.efb-enable-disable-form', function(event) {
                    var input = $(this).prev();
                    enable_disable_form(input);
                });

                $('body').on("click", ".efb-tbl-col-shortcode", function(event) {
                    var temp = $('<input>');
                    $('body').append(temp);
                    var shortcode = $(this).text();
                    temp.val(shortcode).select();
                    document.execCommand('copy');
                    temp.remove();
                    Formeo.dom.toaster(M.util.get_string('shortcodecopied', 'local_edwiserform', shortcode));
                });
            });
        }
    };
});
