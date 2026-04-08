'use strict';
define('local_edwiserbridge/eb_settings', ['jquery', 'core/ajax', 'core/url', 'core/str'], function ($, ajax, url, str) {
    function load_settings() {
        var translation = str.get_strings([
            { key: 'dailog_title', component: 'local_edwiserbridge' },
            { key: 'site_url', component: 'local_edwiserbridge' },
            { key: 'token', component: 'local_edwiserbridge' },
            { key: 'copy', component: 'local_edwiserbridge' },
            { key: 'copied', component: 'local_edwiserbridge' },
            { key: 'link', component: 'local_edwiserbridge' },
            { key: 'create', component: 'local_edwiserbridge' },
            { key: 'eb_empty_name_err', component: 'local_edwiserbridge' },
            { key: 'eb_empty_user_err', component: 'local_edwiserbridge' },
            { key: 'eb_service_select_err', component: 'local_edwiserbridge' },
            { key: 'click_to_copy', component: 'local_edwiserbridge' },
            { key: 'pop_up_info', component: 'local_edwiserbridge' },
            { key: 'eb_settings_msg', component: 'local_edwiserbridge' },
            { key: 'click_here', component: 'local_edwiserbridge' }
            // {key: 'manualsuccessuser', component: 'local_notifications'}
        ]);


        /*translation.then(function (results) {
            console.log(results);
        });*/

        $(document).ready(function () {

            function checkMissingServices(service_id, messge_ele = false) {
                var promises = ajax.call([
                    { methodname: 'eb_get_service_info', args: { service_id: service_id } }
                ]);

                promises[0].done(function (response) {
                    var message = '';
                    $("body").css("cursor", "default");
                    if (!response.status) {
                        $('.eb_summary_tab').removeClass('summary_tab_sucess');
                        $('.eb_summary_tab').addClass('summary_tab_error');
                        if (!messge_ele) {
                            $('#eb_common_err').text(response.msg);
                            $('#eb_common_err').css('display', 'block');
                        } else if (messge_ele) {
                            var link = window.location.origin + window.location.pathname + '?tab=service'
                            var fix_link = " Check more detials <a href='" + link + "'  target='_blank'>here</a>.";
                            message = "<span class='summ_error'>" + response.msg + fix_link + "</span>";
                            $(messge_ele).empty().append(message);
                        }
                    } else {
                        if (jQuery('#web_service_status span').hasClass('summ_error')) {
                            $('.eb_summary_tab').removeClass('summary_tab_sucess');
                            $('.eb_summary_tab').addClass('summary_tab_error');
                        } else {
                            $('.eb_summary_tab').addClass('summary_tab_sucess');
                            $('.eb_summary_tab').removeClass('summary_tab_error');
                        }
                        if (messge_ele) {
                            message = '<span style="color: #7ad03a;"><span class="summ_success" style="font-weight: bolder; color: #7ad03a; font-size: 22px;">&#10003;</span></span>';
                            $(messge_ele).empty().append(message);
                        }
                    }
                    return response;
                }).fail(function (response) {
                    $("body").css("cursor", "default");
                    return 0;
                });
            }
            /**
             * Check if the user is on edwiser bridge settings page.
             */
            if (window.location.href.indexOf('edwiserbridge.php') > 1) {
                let searchParams = new URLSearchParams(window.location.search)
                if (searchParams.has('tab') && 'service' === searchParams.get('tab')) {
                    var service_id = $("#id_eb_sevice_list").val();
                    if ("" != service_id && 'create' != service_id) {
                        checkMissingServices(service_id);
                    }
                }
                if (searchParams.has('tab') && 'summary' === searchParams.get('tab')) {
                    $('#web_service_status').empty();
                    var service_id = $("#web_service_status").data('serviceid');
                    checkMissingServices(service_id, '#web_service_status');
                }
            }

            /*
            * Functionality to show only tokens which are asscoiated with the service. 
            */
            $("#id_eb_sevice_list").change(function () {


                var service_id = $(this).val();
                $('#eb_common_success').css('display', 'none');
                $('#eb_common_err').css('display', 'none');

                $("#id_eb_token option:selected").removeAttr("selected");

                $('#id_eb_token option[value=""]').attr("selected", true);

                handlefieldsdisplay('create', service_id, '.eb_service_field', '#id_eb_mform_create_service');


                if ($(this).val() != '') {
                    $("#id_eb_token").children('option').hide();
                    $("#id_eb_token").children("option[data-id^=" + $(this).val() + "]").show();

                    if ($(this).val() != 'create') {
                        $("body").css("cursor", "progress");
                        checkMissingServices(service_id);
                    }
                }
            });



            /**---------------------------------------------
             * Web service drop down selection handler
             *----------------------------------------------*/

            /**
            * Capturing the drop down values for the further actions
            * On settings page.
            */
            /*$('#id_eb_sevice_list').change(function(event){
    
                var eb_service_val = $(this).val();
                handlefieldsdisplay('create', eb_service_val, '.eb_service_field', '#id_eb_mform_create_service');
            });
    */

            /****************   Web service drop down selection handler   ****************/







            // translation.then(function(){

            /*****************    Change Form Action URL   *******************/

            $("#conne_submit_continue").click(function () {
                $(this).closest("form").attr("action", M.cfg.wwwroot + '/local/edwiserbridge/edwiserbridge.php?tab=synchronization');
            });

            $("#sync_submit_continue").click(function () {
                $(this).closest("form").attr("action", M.cfg.wwwroot + '/local/edwiserbridge/edwiserbridge.php?tab=summary');
            });


            $("#settings_submit_continue").click(function () {
                $(this).closest("form").attr("action", M.cfg.wwwroot + '/local/edwiserbridge/edwiserbridge.php?tab=service');
            });



            /*********** END *********/
            // Add Settings field.
            if (!$('.eb_settings_btn_cont').length) {
                $("#admin-eb_test_field").before('<div class="eb_settings_btn_cont" style="padding: 30px;"> ' + M.util.get_string('eb_settings_msg', 'local_edwiserbridge') + ' <a target="_blank" style="border-radius: 4px;margin-left: 5px;padding: 7px 18px;" class="eb_settings_btn btn btn-primary" href="' + M.cfg.wwwroot + '/local/edwiserbridge/edwiserbridge.php?tab=service"> ' + M.util.get_string('click_here', 'local_edwiserbridge') + ' </a></div>');
            }
            // $('#admin-ebexistingserviceselect').css('display', 'none');
            $('#admin-eb_test_field').css('display', 'none');




            //Adds the link and create button on the set-up wizard
            if ($('#admin-ebnewserviceuserselect').length) {
                if (!$('#eb_create_service').length) {
                    $('#admin-ebnewserviceuserselect').after(
                        '<div class="row eb_create_service_wrap">'
                        + '  <div class="offset-sm-3 col-sm-3">'
                        + '    <button type="submit" id="eb_create_service" class="btn">' + M.util.get_string('link', 'local_edwiserbridge') + '</button>'
                        + '  </div>'
                        + '</div>'
                    );
                }
            }

            //This adds the error succes messages divs on the set-up wizard.
            if ($('.eb_create_service_wrap').length) {
                $('.eb_create_service_wrap').before(
                    '<div class="row eb_common_err_wrap">'
                    + '  <div class="offset-sm-3 col-sm-3">'
                    + '    <span id="eb_common_err" class="btn"></span>'
                    + '    <span id="eb_common_success" class="btn"></span>'
                    + '  </div>'
                    + '</div>'
                );
            }


            $('#id_eb_mform_create_service').click(function (event) {
                event.preventDefault();

                var error = 0;

                var web_service_name = $('#id_eb_service_inp').val();
                var user_id = $('#id_eb_auth_users_list').val();

                var service_id = $('#id_eb_sevice_list').val();
                var token = $('#id_eb_token').val();

                $('.eb_settings_err').remove();
                // $('#eb_common_err').text('');
                // $('#eb_common_success').text('');

                $('#eb_common_success').css('display', 'none');
                $('#eb_common_err').css('display', 'none');

                if (user_id == "") {
                    // $('#id_eb_auth_users_list').after('<span class="eb_settings_err">'+ M.util.get_string('eb_empty_user_err', 'local_edwiserbridge') +'</span>');

                    $('#eb_common_err').text(M.util.get_string('eb_empty_user_err', 'local_edwiserbridge'));

                    $('#eb_common_err').css('display', 'block');


                    error = 1;
                }





                //If the select box has a value to create the web service the create web service else
                if (service_id == 'create') {

                    if (web_service_name == "") {
                        $('#eb_common_err').css('display', 'block');

                        // $('#id_eb_service_inp').after('<span class="eb_settings_err">'+ M.util.get_string('eb_empty_name_err', 'local_edwiserbridge') +'</span>');
                        $('#eb_common_err').text(M.util.get_string('eb_empty_name_err', 'local_edwiserbridge'));
                        error = 1;
                    }



                    if (error) {
                        return;
                    }

                    create_web_service(web_service_name, user_id, '#id_eb_sevice_list', '#eb_common_err', 1);
                } else {


                    if ($('#id_eb_token').val() == '') {

                        $('#eb_common_err').css('display', 'block');
                        $('#eb_common_err').text(M.util.get_string('token_empty', 'local_edwiserbridge'));
                        error = 1;
                        return 0;
                    }


                    if (error) {
                        return;
                    }

                    //If select has selected existing web service
                    if (service_id != '') {
                        link_web_service(service_id, token, '#eb_common_err', '#eb_common_success');
                    } else {
                        //If the select box has been selected with the placeholder
                        $('#eb_common_err').text(M.util.get_string('eb_service_select_err', 'local_edwiserbridge'))
                    }
                }

            }); // event end

            /************************ Web service creation click handlers *******************************/








            /* -------------------------------------------
             *  Copy to clipboard functionality handler
             *---------------------------------------*/

            /**
             * This shows the copy test on the side
             */
            $(document).on("mouseenter", ".eb_copy_text_wrap", function () {
                // hover starts code here
                var parent = $(this).find('.eb_copy_btn');
                parent.css('visibility', 'visible');
            });


            $(document).on("mouseleave", ".eb_copy_text_wrap", function () {
                // hover ends code here
                var parent = $(this).find('.eb_copy_btn');
                parent.css('visibility', 'hidden');
            });

            /**
             * Copy to clipboard functionality.
             */
            $(document).on('click', '.eb_copy_text_wrap', function (event) {
                event.preventDefault();

                var copyText = $(this).find('.eb_copy_text').html();
                var temp = document.createElement('textarea');
                temp.textContent = copyText;

                document.body.appendChild(temp);
                var selection = document.getSelection();
                var range = document.createRange();
                //  range.selectNodeContents(textarea);
                range.selectNode(temp);
                selection.removeAllRanges();
                selection.addRange(range);

                document.execCommand('copy');

                temp.remove();
                toaster('Title', 400);
            });



            $(document).on('click', '.eb_primary_copy_btn', function (event) {
                event.preventDefault();

                // var copyText     = $(this).html();

                var parent = $(this).parent().parent();

                parent = parent.find('.eb_copy')

                if (parent.attr('id') == 'id_eb_token') {
                    var copyText = parent.val();
                } else {

                    var copyText = parent.text();
                }

                var temp = document.createElement('textarea');
                temp.textContent = copyText;

                document.body.appendChild(temp);
                var selection = document.getSelection();
                var range = document.createRange();
                //  range.selectNodeContents(textarea);
                range.selectNode(temp);
                selection.removeAllRanges();
                selection.addRange(range);

                document.execCommand('copy');

                temp.remove();
                toaster('Title', 200);
            });





            /*************   Copy to clipboard functionality handler  **************/




            /*----------------------------------------------------
             * Below are alll js functions
             *---------------------------------------------------*/

            /**
             * Toatser adde to show the successful copy message.
             */
            function toaster(title, time = 2000) {
                const id = 'local_edwiserbridge_copy';
                const toast = $('<div id="' + id + '">' + M.util.get_string('copied', 'local_edwiserbridge') + '<div>').get(0);
                document.querySelector('body').appendChild(toast);
                toast.classList.add('show');
                setTimeout(function () {
                    toast.classList.add('fade');
                    setTimeout(function () {
                        toast.classList.remove('fade');
                        setTimeout(function () {
                            toast.remove();
                        }, time);
                    }, time);
                });
            }



            /**
             * This function adds newly created web service in the drop down 
             */
            function add_new_service_in_select(element, name, id) {
                $(element + "option:selected").removeAttr("selected");
                $(element).append('<option value="' + id + '" selected> ' + name + ' </option>');
            }


            /**
             * This function adds newly created web service in the drop down 
             */
            function add_new_token_in_select(element, token, id) {
                $(element + "option:selected").removeAttr("selected");
                $(element).append('<option data-id="' + id + '" value="' + token + '" selected> ' + token + ' </option>');
            }


            /**
             * This function handles the display of the service creation form depending on the drop down value.
             */
            function handlefieldsdisplay(condition, condition_var, element, btn = '') {
                if (condition == condition_var) {
                    $(btn).text(M.util.get_string('create', 'local_edwiserbridge'));
                    $(element).css('display', 'flex');
                } else {
                    $(btn).text(M.util.get_string('link', 'local_edwiserbridge'));
                    $(element).css('display', 'none');
                }

            }


            /**
             * This functions link the existing wervices
             */
            function link_web_service(service_id, token, common_errr_fld, common_success_fld) {
                $("body").css("cursor", "progress");
                $('#eb_common_err').css('display', 'none');

                var promises = ajax.call([
                    { methodname: 'eb_link_service', args: { service_id: service_id, token: token } }
                ]);

                promises[0].done(function (response) {
                    $("body").css("cursor", "default");
                    if (response.status) {
                        $(common_success_fld).text(response.msg);
                        $(common_success_fld).css('display', 'block');
                    } else {
                        $(common_errr_fld).text(response.msg);
                        $(common_success_fld).css('display', 'block');

                    }

                    return response;

                }).fail(function (response) {
                    $("body").css("cursor", "default");
                    return 0;
                }); //promise end
            }




            $(document).on('click', '.eb_service_pop_up_close', function () {
                $(".eb_service_pop_up").hide();
            });


            /**
             * This functions regiters new web service.
             */
            function create_web_service(web_service_name, user_id, service_select_fld, common_errr_fld, is_mform) {
                $("body").css("cursor", "progress");
                $('#eb_common_err').css('display', 'none');

                $("#id_eb_token option:selected").removeAttr("selected");

                $('#id_eb_token option[value=""]').attr("selected", true);


                var promises = ajax.call([
                    { methodname: 'eb_create_service', args: { web_service_name: web_service_name, user_id: user_id } }
                ]);

                var validation_error = 0;


                if (!validation_error) {

                    promises[0].done(function (response) {
                        $("body").css("cursor", "default");
                        if (response.status) {

                            //Dialog box content.
                            var eb_dialog_content = '<div> ' + M.util.get_string('pop_up_info', 'local_edwiserbridge') + ' </div>'
                                + '<table class="eb_toke_detail_tbl">'
                                + '  <tr>'
                                + '     <th width="17%">' + M.util.get_string('site_url', 'local_edwiserbridge') + '</th>'
                                + '     <td> : <span class="eb_copy_text" title="' + M.util.get_string('click_to_copy', 'local_edwiserbridge') + '">' + response.site_url + '</span>'
                                + '        <span class="eb_copy_btn">' + M.util.get_string('copy', 'local_edwiserbridge') + '</span></td>'
                                + '  </tr>'
                                + '  <tr>'
                                + '     <th width="17%">' + M.util.get_string('token', 'local_edwiserbridge') + '</th>'
                                + '     <td> : <span class="eb_copy_text" title="' + M.util.get_string('click_to_copy', 'local_edwiserbridge') + '">' + response.token + '</span>'
                                + '        <span class="eb_copy_btn">' + M.util.get_string('copy', 'local_edwiserbridge') + '</span></td>'
                                + '  </tr>'
                                + '</table>';


                            $('body').append('<div class="eb_service_pop_up_cont">'
                                + '<div class="eb_service_pop_up">'
                                + '<span class="helper"></span>'
                                + '<div>'
                                + '<div class="eb_service_pop_up_close">&times;</div>'
                                + '<div>'
                                + '<div class="eb_service_pop_up_title"></div>'
                                + '<div class="eb_service_pop_up_content"></div>'
                                + '</div>'
                                + '</div>'
                                + '</div>'
                                + '</div>');



                            /* modalFactory.create({
                                 title: M.util.get_string('dailog_title', 'local_edwiserbridge'),
                                 body: eb_dialog_content,
                                 footer: '',
                                 keyboard: false,
                                 backdrop: 'static'
                             }).done(function(modal) {
                                 // Do what you want with your new modal.
                                 modal.show();
                             });*/

                            $('.eb_service_pop_up_content').html(eb_dialog_content);
                            $('.eb_service_pop_up').show();

                            // $('<div />').html(eb_dialog_content).dialog();

                            add_new_service_in_select(service_select_fld, web_service_name, response.service_id);
                            add_new_token_in_select('#id_eb_token', response.token, response.service_id);

                            /*if (is_mform) {
                                $('#eb_mform_token').text(response.token);
                            }*/


                        } else {
                            $('#eb_common_err').css('display', 'block');
                            $(common_errr_fld).text(response.msg);
                        }

                        return response;

                    }).fail(function (response) {
                        $("body").css("cursor", "default");
                        /*jQuery("body").css("cursor", "auto");
                        swal("error !", response.success, "error");*/

                        return 0;

                    }); //promise end

                }

            }



            /************************  FUnctions END  ****************************/


            // });

        });
    }
    return { init: load_settings };
});
