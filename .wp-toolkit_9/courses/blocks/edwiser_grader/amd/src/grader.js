define([
    'jquery',
    'core/log',
    'core/ajax',
    'core/notification',
    'core/templates',
    'core/chartjs',
    'core/modal_factory',
    'core/modal_save_cancel',
    'core/modal_events',
    'core/fragment',
    'block_edwiser_grader/jquery.dataTables',
    'block_edwiser_grader/dataTables.bootstrap4',
    'block_edwiser_grader/bootstrap-select',
    'block_edwiser_grader/pagination'
], function(
    $,
    Log,
    Ajax,
    Notification,
    templates,
    Chart,
    ModalFactory,
    ModalSaveCancel,
    ModalEvents,
    Fragment
) {
    /**
     * Initializes the edwiser grader controls.
     */
    function init(contextid) {
        $(document).ready(function() {
            let defaultUser = 5;
            let quizid = getUrlParameter('id');
            let paginate = '';
            var selectedattemptsfrom = 'uaq';
            var selectedattempts = '';
            var page = 1;
            var soap = '';
            var needsregrade = '';
            let gdm = getUrlParameter('gdm');
            // Get Quiz Attempts.
            let selectedtab = '#edg-notgraded';
            let qbasedview = false;
            if (gdm == 'user') {
                edgGetQuizAttemptsCount('block_edwiser_grader_get_quiz_attempts', selectedattemptsfrom, selectedattempts, needsregrade);
                edgGetAttempts('block_edwiser_grader_get_not_graded_attempts', selectedattemptsfrom, selectedattempts, page, soap, needsregrade);
            }

            if (gdm == 'question') {
                qbasedview = true;
                getQuestionTableData();
            }
            $('#edg-notgraded-tab').click(function(){
                let service_name = 'block_edwiser_grader_get_not_graded_attempts';
                selectedtab = $(this).attr('href');
                edgGetQuizAttemptsCount('block_edwiser_grader_get_quiz_attempts', selectedattemptsfrom, selectedattempts, needsregrade);
                edgGetAttempts(service_name, selectedattemptsfrom, selectedattempts, page, soap, needsregrade);
                $('.edg-close').trigger('click');
            });
            $('#edg-graded-tab').click(function(){
                let service_name = 'block_edwiser_grader_get_graded_attempts';
                selectedtab = $(this).attr('href');
                edgGetQuizAttemptsCount('block_edwiser_grader_get_quiz_attempts', selectedattemptsfrom, selectedattempts, needsregrade);
                edgGetAttempts(service_name, selectedattemptsfrom, selectedattempts, page, soap, needsregrade);
                $('.edg-close').trigger('click');
            });

            $('body').on('click', '[data-target="#edgfiltermodal"], [data-target="#edg-quiz-chart-modal"], [data-target="#edg-response-history"]', function() {
                $($(this).data('target')).addClass('show');
            });

            $('body').on('click', '#edgfiltermodal .close, #edg-quiz-chart-modal .close, #edg-response-history .close', function() {
                $(this).closest('.modal').removeClass('show');
            });

            // Get quiz attempts count.
            function edgGetQuizAttemptsCount(service_name, selectedattemptsfrom, selectedattempts, needsregrade) {
                let username = $('#edg-search-input').val();
                var getQuizAttemptsCount = Ajax.call([
                    {
                        methodname: service_name,
                        args: {
                            quizid : quizid,
                            selectedattemptsfrom : selectedattemptsfrom,
                            selectedattempts : selectedattempts,
                            username : username,
                            needsregrade: needsregrade,
                        }
                    }
                ]);
                getQuizAttemptsCount[0].done(function (response) {
                    $('.edg-notgraded-count').html('(' + response.nongradedcount + ')');
                    $('.edg-graded-count').html('(' + response.gradedcount + ')');
                    if (selectedtab == '#edg-notgraded') {
                        loadUserPagination(response.nongradedcount);
                    } else {
                        loadUserPagination(response.gradedcount);
                    }
                }).fail(Notification.exception);
            }

            // Load User Pagination.
            function loadUserPagination(totalusers) {
                if (totalusers > defaultUser) {
                    $(selectedtab + ' .edg-user-pages-container').pagination({
                        dataSource: Array.from({length: totalusers}, (v, k) => k + 1),
                        pageSize: defaultUser,
                        prevText: '<i class="fa fa-chevron-left" aria-hidden="true"></i>',
                        nextText: '<i class="fa fa-chevron-right" aria-hidden="true"></i>',
                        callback: function (data, pagination) {
                            changeUserPage(pagination.pageNumber);
                        }
                    });
                } else {
                    $('.edg-user-pages-container').html("");
                }
            }

            // Search for the user.
            $(document).on('click', '#edg-search-btn', function () {
                searchUsers();
            });

            $(document).on('keypress', '#edg-search-input', function (event) {
                if (event.which == 13) {
                    searchUsers();
                }
            });

            function searchUsers() {
                if (selectedtab == "#edg-notgraded") {
                    let service_name = 'block_edwiser_grader_get_not_graded_attempts';
                    edgGetQuizAttemptsCount('block_edwiser_grader_get_quiz_attempts', selectedattemptsfrom, selectedattempts, needsregrade);
                    edgGetAttempts(service_name, selectedattemptsfrom, selectedattempts, page, soap, needsregrade);
                    $('.edg-close').trigger('click');
                } else if (selectedtab == "#edg-graded") {
                    let service_name = 'block_edwiser_grader_get_graded_attempts';
                    edgGetQuizAttemptsCount('block_edwiser_grader_get_quiz_attempts', selectedattemptsfrom, selectedattempts, needsregrade);
                    edgGetAttempts(service_name, selectedattemptsfrom, selectedattempts, page, soap, needsregrade);
                    $('.edg-close').trigger('click');
                }
            }

            // Sorting is changed.
            $(document).on('change', '#edg-sort-box', function () {
                if (selectedtab == "#edg-notgraded") {
                    let service_name = 'block_edwiser_grader_get_not_graded_attempts';
                    edgGetQuizAttemptsCount('block_edwiser_grader_get_quiz_attempts', selectedattemptsfrom, selectedattempts, needsregrade);
                    edgGetAttempts(service_name, selectedattemptsfrom, selectedattempts, page, soap, needsregrade);
                    $('.edg-close').trigger('click');
                } else if (selectedtab == "#edg-graded") {
                    let service_name = 'block_edwiser_grader_get_graded_attempts';
                    edgGetQuizAttemptsCount('block_edwiser_grader_get_quiz_attempts', selectedattemptsfrom, selectedattempts, needsregrade);
                    edgGetAttempts(service_name, selectedattemptsfrom, selectedattempts, page, soap, needsregrade);
                    $('.edg-close').trigger('click');
                }
            });

            // Get the User Info and attempt details.
            function edgGetAttempts(service_name, selectedattemptsfrom, selectedattempts, page = 1, soap = '', needsregrade = ''){
                let username = $('#edg-search-input').val();
                let sortfilter = $('#edg-sort-box').val();
                var getNotGradedQuestions = Ajax.call([
                    {
                        methodname: service_name,
                        args: {
                            quizid : quizid,
                            selectedattemptsfrom : selectedattemptsfrom,
                            selectedattempts : selectedattempts,
                            page: page,
                            username: username,
                            sortfilter: sortfilter,
                            soap: soap,
                            needsregrade: needsregrade,
                        }
                    }
                ]);
                getNotGradedQuestions[0].done(function (response) {
                    loadAttemptDataToTemplate(response);
                }).fail(Notification.exception);
            }

            // Function to get the details from the URL.
            function getUrlParameter(sParam) {
                var sPageURL = decodeURIComponent(window.location.search.substring(1)),
                sURLVariables = sPageURL.split('&'),
                sParameterName,
                i;
                for (i = 0; i < sURLVariables.length; i++) {
                    sParameterName = sURLVariables[i].split('=');
                    if (sParameterName[0] === sParam) {
                        return sParameterName[1] === undefined ? true : sParameterName[1];
                    }
                }
            }

            // Function to get the questions from attemptid.
            function getAttemptQuestions(attemptid, userid) {
                var service_name = 'block_edwiser_grader_get_attempt_questions';
                var getUserAttemptQuestions = Ajax.call([
                    {
                        methodname: service_name,
                        args: { attemptid : attemptid }
                    }
                ]);
                getUserAttemptQuestions[0].done(function (response) {
                    loadQuestionDataToTemplate(response, attemptid, userid);
                }).fail(Notification.exception);
            }

            // Function to update the block template details.
            function loadAttemptDataToTemplate(response) {
                let template = "block_edwiser_grader/studenttable";
                let context  = [];
                response.map((obj) => {
                    obj.userurl = M.cfg.wwwroot + '/user/profile.php?id=' + obj.userid;
                    return obj;
                });
                context['attempts'] = response;
                templates.render(template, context).then(
                    (html, js)  => {
                        templates.replaceNode($(selectedtab + " .edg-grader-attempt-table"), html , js);
                        $(selectedtab + ' .edg-ga-skeleton-content').hide();
                        $(selectedtab + ' .edg-grader-attempt-table-content').show();
                        $(selectedtab + " .edg-attempt-link.active").each(function(index, ele) {
                            $(ele).trigger("shown.bs.tab");
                        });
                        if (response.length > 0) {
                            $('.edg-not-graded-filter').removeClass('edg-hidden');
                        } else {
                            $('.edg-not-graded-filter').addClass('edg-hidden');
                        }
                    }
                );
            }

            // Function to get the questions of selected attempt.
            $(document).on('shown.bs.tab', '.edg-attempt-link', function(e){
                let attemptid = $(e.target).attr("data-attempt");
                let userid = $(e.target).attr("data-user");
                let grade = $(e.target).attr("data-grade");
                if (attemptid !== undefined) {
                    attemptid = parseInt(attemptid);
                }
                if (userid !== undefined) {
                    userid = parseInt(userid);
                }
                $(selectedtab + ' .edg-grade-' + userid).text(grade);
                $('body').css('margin-bottom', '0px');
                getAttemptQuestions(attemptid, userid);
                $('.edg-check-user').each(function (index, ele) {
                    $(ele).prop("checked", false);
                });
                $('#edg-check-all').prop('checked', false);
                toggleActionMenu();
            });

            // Function to update the block template details.
            function loadQuestionDataToTemplate(response, attemptid, userid) {
                let template = "block_edwiser_grader/studenttablequestions";
                let context  = [];
                context['questionlist'] = response;
                templates.render(template, context).then(
                    (html, js)  => {
                        let nodeelement = $(selectedtab + ' .edg-question-container-' + attemptid + ' .edg-question-pill-container');
                        templates.replaceNode(nodeelement, html , js);
                        $(selectedtab + ' .edg-student-' + userid).hide();
                        $(selectedtab + ' .edg-question-container-' + attemptid).css('display', 'flex');
                        toggleQuestionVisibility($('#edg-switch-questions'));
                        updateLoadMoreValue(attemptid);
                        $('.edg-close').trigger('click');
                    }
                );
            }

            // On Not Yet Graded Switch toggle.
            $('#edg-switch-questions').change(function() {
                toggleQuestionVisibility($(this));
                let attemptid = $(selectedtab + ' .edg-question-pill.selected').attr('data-attempt');
                let parent = $(selectedtab + ' .edg-question-container-' + attemptid);
                $('.edg-lm-pill').each(function (index, element) {
                    let attemptid = $(this).attr('data-attempt');
                    updateLoadMoreValue(attemptid);
                });
                loadQuestionPagination(parent, true);
                let selectedPill = $('.edg-question-pill.selected:visible');
                if (selectedPill.length == 0) {
                    $('.edg-close').trigger('click');
                }
            });

            // Function to update the question list based on the value of the not yet graded toggle switch.
            function toggleQuestionVisibility(el) {
                if (selectedtab != '#edg-graded') {
                    if ($(el).is(":checked")) {
                        $('.edg-question-pill').hide();
                        $('.edg-question-pill.requiresgrading').show();
                    } else {
                        $('.edg-question-pill').show();
                        $('.edg-question-pill.notyetanswered, .edg-question-pill.answersaved').hide();
                    }
                } else {
                    $('.edg-question-pill').show();
                }
            }

            // Function to calculate the number of question pills overflowed.
            function calculateOverflowValue(attemptid) {
                let devicewidth = $(window).width();
                let containerwidth = $(selectedtab + ' .edg-question-container-' + attemptid + ' .edg-question-pill-container').width();
                let parentwidth = $(selectedtab + ' .edg-question-container-' + attemptid).width();
                if ((containerwidth == parentwidth) && devicewidth > 1024) {
                    containerwidth = containerwidth - 110;
                }
                let buttonwidth = $(selectedtab + ' .edg-question-container-' + attemptid + ' .edg-question-pill-container .edg-question-pill').width();
                let limit = Math.floor(containerwidth / (buttonwidth + 15));
                return limit;
            }

            // On window resize update the value of More button.
            $(window).resize(function () {
                $('.edg-lm-pill').each(function (index, element) {
                    let attemptid = $(this).attr('data-attempt');
                    updateLoadMoreValue(attemptid);
                });

                if (gdm === 'question') {
                    getQuestionTableData();
                }
            });

            // Function to update the load more button count.
            function updateLoadMoreValue(attemptid) {
                let visiblepills = $(selectedtab + ' .edg-question-container-' + attemptid + ' .edg-question-pill:not([style*="display: none"])');
                let limit = calculateOverflowValue(attemptid);
                if (visiblepills.length >= (limit + 1) ) {
                    let count = visiblepills.length - limit;
                    $(selectedtab + ' .edg-load-more-container-' + attemptid + ' .edg-lm-pill').html('+' + count + ' ' + M.util.get_string('more', 'block_edwiser_grader'));
                    $(selectedtab + ' .edg-load-more-container-' + attemptid).show();
                    if ($(selectedtab + ' .edg-load-more-container-' + attemptid).hasClass('edg-vm')) {
                        $(selectedtab + ' .edg-question-container-' + attemptid + ' .edg-question-pill-container').addClass('expand');
                    }
                } else {
                    $(selectedtab + ' .edg-load-more-container-' + attemptid).hide();
                }
            }

            // On More button click.
            $(document).on('click', '.edg-lm-pill, .edg-view-less', function () {
                let attemptid = $(this).attr('data-attempt');
                $(selectedtab + ' .edg-question-container-' + attemptid + ' .edg-question-pill-container').toggleClass('expand');
                $(selectedtab + ' .edg-question-container-' + attemptid + ' .edg-load-more').toggleClass('edg-vm');
            });

            var slot, attemptid, cmid;
            // On Question Button Click.
            $(document).on('click', '.edg-question-pill', function() {
                slot         = $(this).attr('data-qnumber');
                attemptid    = $(this).attr('data-attempt');
                cmid         = getUrlParameter('id');
                $('.edg-question-pill').removeClass("selected");
                $(selectedtab + ' .edg-student-single-record').removeClass('selected');
                $(this).addClass('selected');
                let parent = $(selectedtab + ' .edg-question-container-' + attemptid).parent();
                let marginvalue = parent.offset().top;
                $('body').css('margin-bottom', marginvalue + 'px');
                $(parent).addClass('selected');
                $('html,body').delay(100).animate({scrollTop: marginvalue - 5 }, 100, 'swing');
                loadQuestionContent(slot, attemptid, cmid, null);
            });

            // AJAX Function to get the details of the question.
            function loadQuestionContent(slot, attemptid, cmid, paginationdata) {
                var service_name = 'block_edwiser_grader_get_question_details';
                var getQuestionDetails = Ajax.call([
                    {
                        methodname: service_name,
                        args: {
                            slot: slot,
                            attemptid : attemptid,
                            cmid : cmid,
                        }
                    }
                ]);
                getQuestionDetails[0].done(function (response) {
                    loadQuestionDetailsToTemplate(response, slot, attemptid, paginationdata);
                }).fail(Notification.exception);
            }

            // On close button click of question details section.
            $(document).on('click', '.edg-close', function() {
                $('.edg-question-pill').removeClass("selected");
                $('body').css('margin-bottom', '0px');
                $(selectedtab + ' .edg-student-single-record').removeClass('selected');
                $('.edg-question-details-section').removeClass('show expand');
                $('.generaltable tr').removeClass('highlighted');
            });

            // Function to load pagination in question details section.
            function loadQuestionPagination(el, start) {
                if ($(el).length == 0) {
                    return;
                }
                let allquestions = $(el).find('.edg-question-pill:not([style*="display: none"])');
                let pages = [];
                let pagesdata = [];
                let selectedPage = 1;
                $(allquestions).each(function (index, element) {
                    let attemptid = $(element).attr('data-attempt');
                    let qnumber = $(element).attr('data-qnumber');
                    if ($(element).hasClass('selected')) {
                        selectedPage = index + 1;
                    }
                    pages.push({ "attemptid" : attemptid, "qnumber" : qnumber});
                    pagesdata.push(parseInt(qnumber));
                });
                paginate = $('.edg-pages-container').pagination({
                    dataSource: pagesdata,
                    pageSize: 1,
                    totalNumber: pagesdata.length,
                    pageNumber: selectedPage,
                    pageRange: 4,
                    prevText: '<i class="fa fa-chevron-left" aria-hidden="true"></i>',
                    nextText: '<i class="fa fa-chevron-right" aria-hidden="true"></i>',
                    callback: function (data, pagination) {
                        $('.edg-pages-container li.paginationjs-page').each(function (index, element) {
                            let el = $(element).find('a');
                            let key = $(element).attr('data-num');
                            if (pages[key - 1] !== undefined) {
                                let qnumber = pages[key - 1].qnumber;
                                let attempt = pages[key - 1].attemptid;
                                $(el).text(qnumber);
                                $(el).attr('data-qnumber', qnumber);
                                $(el).attr('data-attempt', attempt);
                            }
                        });
                        currentele = $('.edg-pages-container li.paginationjs-page.active a');
                        let qnumber = $(currentele).attr('data-qnumber');
                        let attemptid = $(currentele).attr('data-attempt');
                        if (!start) {
                            changeQuestion(qnumber, attemptid);
                        } else {
                            start = false;
                        }
                    }
                });
            }

            // Load the Question details UI based on the value returned by the service.
            function loadQuestionDetailsToTemplate(response, slot, attemptid, paginationdata) {
                let context = [];
                let template = "block_edwiser_grader/questiondetails";
                let elements = $(response.quedata);
                let comment = $(elements).find('.felement.fhtmleditor textarea').val();
                let responsehistory = $(elements).find('.responsehistoryheader .generaltable')[0].outerHTML;
                context['user'] = response.user == undefined ? false : response.user;
                context['gdstate'] = response.gradestate;
                context['qnumber'] = slot;
                context['qtype'] = response.questiontype;
                context['gdstateclass'] = response.gradestate.replace(/ /g,'').toLowerCase();
                context['marks'] = response.marks;
                context['markshtml'] = $(elements).find('.fcontainer .fitem:last-child')[0].outerHTML;
                if ($(elements).find(".ablock").length) {
                    context['answer'] = $(elements).find(".ablock").get(0).outerHTML;
                } else if ($(elements).find(".formulation").length) {
                    context['answer'] = $(elements).find(".formulation").get(0).outerHTML;
                } else if ($(elements).find(".ddarea").length) {
                    context['answer'] = $(elements).find(".ddarea").get(0).outerHTML;
                }
                if ($(elements).find(".qtext").length) {
                    context['questiontext'] = $(elements).find(".qtext").get(0).outerHTML;
                } else {
                    context['questiontext'] = $(elements).find(".formulation").get(0).outerHTML;
                }
                context['state'] = $(elements).find('.state').text();
                context['gdinfo'] = $(elements).find('.graderinfo').html();
                context['qpaginate'] = (paginationdata == null) ? true : false;
                context['upaginate'] = !context['qpaginate'];
                context['queclass'] = $(elements).attr('class');
                context['jscode'] = response.jscode;
                context['slot'] = slot;
                context['attemptid'] = attemptid;
                if (!$('#edg-grader-page .edg-user-grading').is('.edg-hide')) {
                    $('#edg-grader-page').animate({
                        scrollTop: $('#edg-grader-page').scrollTop() + $('.edg-student-single-record.selected').position().top - 20
                    }, 300);
                }
                $('.edg-question-details-section').addClass('show');
                templates.render(template, context).then(
                    (html, js)  => {
                        let nodeelement = $('.edg-question-details-section-content');
                        templates.replaceNode(nodeelement, html , js);
                        setTimeout(function() {
                            if ($('.edg-question').is('.multianswer') || $('.edg-question').is('.gapselect')) {
                                let formulation = $('.edg-question .edg-question-text')
                                formulation.find('select option').attr('selected', false);
                                formulation.find('input').val('');
                            }
                        }, 0);
                        // Load Comment Box.
                        Fragment.loadFragment('block_edwiser_grader', 'comment_form', contextid, { commenttext: comment})
                        .done(function (html, js) {
                            templates.replaceNode($('.edg-comment-content'), html , js);
                            $('[data-fieldtype="editor"]').find('i').removeAttr('title');
                        });
                        $('#edg-response-history .modal-body').html("");
                        $('#edg-response-history .modal-body').append(responsehistory);
                        if (paginationdata == null) {
                            loadQuestionPagination($(selectedtab + ' .edg-question-container-' + attemptid), true);
                        } else {
                            loadUserlistslider(parent, paginationdata);
                        }
                    }
                );
            }

            // On click of qno in question details section.
            function changeQuestion(qnumber, attempt) {
                let cmid = getUrlParameter('id');
                slot = qnumber;
                attemptid = attempt;
                loadQuestionContent(qnumber, attempt, cmid, null);
                let parent = $(selectedtab + ' .edg-question-container-' + attemptid).parent();
                $(parent).find('.edg-question-pill').removeClass('selected');
                $(parent).find('.edg-question-pill[data-qnumber=' + qnumber + ']').addClass('selected');
                let limit = calculateOverflowValue(attemptid);
                if (qnumber > limit) {
                    let lmbtn = $(parent).find('.edg-load-more-container-' + attemptid + ' button.edg-lm-pill:visible');
                    if (lmbtn.length != 0) {
                        $(lmbtn).trigger('click');
                    }
                }
            };

            // On click of fullscreen button in question details section.
            $(document).on('click', '.edg-fullscreen', function () {
                $('.edg-question-details-section').toggleClass('expand');
                $('.edg-fullscreen .fa').toggleClass('fa-expand').toggleClass('fa-compress');
            });

            // On click of user page button.
            function changeUserPage (page) {
                if (selectedtab == '#edg-notgraded') {
                    edgGetAttempts('block_edwiser_grader_get_not_graded_attempts', selectedattemptsfrom, selectedattempts, page, soap, needsregrade);
                } else {
                    edgGetAttempts('block_edwiser_grader_get_graded_attempts', selectedattemptsfrom, selectedattempts, page, soap, needsregrade);
                }
            };

            // On click of chart icon in header.
            $(document).on('click', '.edg-quiz-chart', function (){
                let cmid         = getUrlParameter('id');
                var service_name = 'block_edwiser_grader_display_grade_chart';
                var displayGradeChart = Ajax.call([
                    {
                        methodname: service_name,
                        args: {
                            cmid : cmid,
                        }
                    }
                ]);
                displayGradeChart[0].done(function (response) {
                    respBar = $.parseJSON(JSON.stringify(response));
                    labels  = respBar.labels.replace(/[[\]"]/g,'');
                    data    = respBar.data.replace(/[[\]]/g,'');
                    var barcontext = $("#edg-quiz-chart").get(0).getContext("2d");
                    var ctx = document.getElementById("edg-quiz-chart");
                    ctx.height = 500;
                    barcontext.canvas.height = 400;
                    var barData = {
                        labels: labels.split(","),
                        datasets: [{
                            label: M.util.get_string('participants', 'block_edwiser_grader'),
                            data: data.split(","),
                            backgroundColor: '#87598E',
                        }]
                    };
                    barChart = new Chart(barcontext, {
                        type: 'bar',
                        data: barData,
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                xAxes: [{
                                    scaleLabel: {
                                        display: true,
                                        labelString: M.util.get_string('grade', 'block_edwiser_grader')
                                    },
                                    ticks: {
                                        autoSkip: false
                                    }
                                }],
                                yAxes: [{
                                    scaleLabel: {
                                        display: true,
                                        labelString: M.util.get_string('participants', 'block_edwiser_grader')
                                    },
                                    stacked: true,
                                    ticks: {
                                        min: 0,
                                        stepSize: 1,
                                    }
                                }]
                            }
                        }
                    });
                }).fail(Notification.exception);
            });

            // Marks validation.
            $(document).on('keyup', '.edg-marks .felement.ftext input[type="text"]', function () {
                let pattern = /^[0-9]\d*(\.\d+)?$/;
                let eleid = $(this).attr('id');
                eleid = eleid.split('-');
                let maxmarks = parseFloat($('input[name="' + eleid[0] + '-maxmark"]').val());
                let marks = $(this).val();
                if ((pattern.test(marks) && marks <= maxmarks ) || marks.trim() == "") {
                    $(this).removeClass('error');
                } else {
                    $(this).addClass('error');
                }
            });

            // On click of submit button in question details section.
            $(document).on('click', '.edg-submit-btn', function(){
                $('.edg-spinner').show();
                $('.edg-question-overlay').addClass('d-flex');
                $(this).prop('disabled', true);
                let comment = Y.one('#id_comment_editor').get('value');
                let marks = $('.edg-marks .felement.ftext input[type="text"]').val();
                if (marks != "") {
                    marks = parseFloat(marks);
                }
                if (marks === "") {
                    marks = -1;
                }
                let eleid = $('.edg-marks .felement.ftext input[type="text"]').attr('id');
                eleid = eleid.split('-');
                let maxmarks = parseFloat($('input[name="' + eleid[0] + '-maxmark"]').val());
                let studid;
                if (qbasedview) {
                    studid = $('#edg-students-select').find(':selected').attr('data-userid');
                } else {
                    studid  = $('.edg-student-single-record.selected').attr('data-studid');
                }
                if ($('.edg-marks .felement.ftext input[type="text"]').hasClass('error')) {
                    $('.edg-question-overlay').removeClass('d-flex');
                    $('.edg-enotification-text').html(M.util.get_string('markserrormessage', 'block_edwiser_grader'));
                    $('.edg-spinner').hide();
                    $(this).removeAttr('disabled');
                    $('.edg-notification-box.edg-error').addClass('edg-visible');
                    setTimeout(function() {
                        $('.edg-notification-box.edg-error').removeClass('edg-visible');
                    }, 1500);
                    return;
                }
                let slotnum = $('.edg-question-details-content .edg-question-number').text();
                updateGradeAndComment(slotnum, attemptid, comment, marks, studid, maxmarks);
            });

            function updateGradeAndComment(slot, attemptid, comment, marks, studid, maxmarks) {
                var service_name = 'block_edwiser_grader_grade_question';
                var gradeQuestion = Ajax.call([
                    {
                        methodname: service_name,
                        args: {
                            slot : slot,
                            attemptid : attemptid,
                            cmid : cmid,
                            comment : comment,
                            marks : marks,
                            studid : studid,
                        }
                    }
                ]);
                gradeQuestion[0].done(function (response) {
                    $('.edg-snotification-text').html(M.util.get_string('successupdatemsg', 'block_edwiser_grader'));
                    $('.edg-grade-' + studid).html(response.grade);
                    $('.edg-notification-box.edg-success').addClass('edg-visible');
                    $('.edg-spinner').hide();
                    $('.edg-submit-btn').removeAttr('disabled');
                    setTimeout(function(){
                        $('.edg-question-overlay').removeClass('d-flex');
                        $('.edg-notification-box.edg-success').removeClass('edg-visible');
                        if (qbasedview) {
                            qbUpdateAndNext();
                        } else {
                            if (marks == maxmarks) {
                                updateandnext($('.edg-question-pill.selected'), 'correct', marks, maxmarks);
                            } else if (marks > 0 && marks < maxmarks) {
                                updateandnext($('.edg-question-pill.selected'), 'partiallycorrect', marks, maxmarks);
                            } else {
                                updateandnext($('.edg-question-pill.selected'), 'incorrect', marks, maxmarks);
                            }
                        }
                    }, 1000);
                }).fail(function(ex) {
                    Notification.exception(ex);
                    $('.edg-question-overlay').removeClass('d-flex');
                });
            }

            // Submit grade on enter keypress.
            $(document).on('keypress', '.edg-marks .felement.ftext input[type="text"]', function (event) {
                if (event.which == 13) {
                    $('.edg-submit-btn').trigger('click');
                }
            });

            // Function to update the grade marks in question pill and select next question.
            function updateandnext(el, classname, marks, maxmarks) {
                if (marks != -1) {
                    $(el).removeClass('correct partiallycorrect incorrect requiresgrading selected');
                    $(el).addClass(classname);
                    if (Math.floor(marks) == marks) {
                        marks = Math.floor(marks);
                    } else {
                        marks = marks.toPrecision(Math.floor(marks) == 0 ? 2 : 3);
                    }
                    $(el).find('.edg-grade-status').html(marks + ' / ' + maxmarks);
                    $('.edg-marks-text').html(marks + ' / ' + maxmarks);
                }
                if ($('.edg-pages-container .paginationjs-next.disabled').length != 0) {
                    $('.edg-close').trigger('click');
                } else {
                    $(paginate).pagination('next');
                }
            }

            function qbUpdateAndNext() {
                if ($('#edg-students-select option:selected').next().length == 0) {
                    getQuestionTableData();
                    $('.edg-close').trigger('click');
                } else {
                    $('.edg-qb-userlist .edg-next').trigger('click');
                }
            }

            // On click of checkbox for user selection.
            $(document).on('change', '.edg-check-user', function () {
                let userid = $(this).attr('data-userid');
                if ($(this).is(":checked")) {
                    $('.edg-overlay-' + userid).show();
                    $(this).next().addClass('top');
                } else {
                    $('.edg-overlay-' + userid).hide();
                    $(this).next().removeClass('top');
                    $('#edg-check-all').prop('checked', false);
                }
                toggleActionMenu();
            });

            // Function to add overlay to selected user row.
            function toggleActionMenu() {
                if ($('input.edg-check-user:checked').length > 0 ) {
                    $('.edg-search-sort-menu').addClass('hide');
                    $('.edg-action-menu').removeClass('hide');
                } else {
                    $('.edg-search-sort-menu').removeClass('hide');
                    $('.edg-action-menu').addClass('hide');
                }
            }

            // On click of check all button in action menu.
            $(document).on('change', '#edg-check-all', function () {
                if ($(this).is(":checked")) {
                    $(selectedtab + ' input.edg-check-user').each(function (index , el) {
                        $(el).prop('checked', true).trigger('change');
                    });
                } else {
                    $(selectedtab + ' input.edg-check-user').each(function (index , el) {
                        $(el).prop('checked', false).trigger('change');
                    });
                }
            });

            // Delete Attempt.
            $('.edg-delete-attempt').click(function() {
                let triggermodal = $('#edg-delete-confirmation');
                ModalFactory.create({
                    title : M.util.get_string('deleteboxheader', 'block_edwiser_grader'),
                    type  : ModalFactory.types.SAVE_CANCEL,
                    body  : M.util.get_string('deletequestion', 'block_edwiser_grader')
                }, triggermodal).done(function(modal) {
                    modal.setSaveButtonText(M.util.get_string('ok', 'block_edwiser_grader'));
                    modal.header.addClass('bg-danger');
                    modal.header.children().addClass('text-white');
                    modal.getRoot().on(ModalEvents.hidden, function() {
                        modal.destroy();
                    });
                    modal.getRoot().on(ModalEvents.save, function(event) {
                        deleteAttempts();
                    });
                    modal.show();
                });
            });

            function deleteAttempts() {
                var attempts = [];
                var service_name = 'block_edwiser_grader_delete_quiz_attempt';
                $.each($(".edg-check-user:checked"), function(index, el) {
                    let userid = $(el).attr('data-userid');
                    let selectedAttempt = $(selectedtab + ' .edg-record-' + userid + ' #edg-attempt-nav-tabcontent .tab-pane.active');
                    let attemptid = $(selectedAttempt).attr('data-attempt');
                    attempts.push(attemptid);
                });
                var deleteQuizAttempt = Ajax.call([
                    {
                        methodname: service_name,
                        args: {
                            attempts : JSON.stringify(attempts),
                            quizid : quizid,
                        }
                    }
                ]);
                deleteQuizAttempt[0].done(function (response) {
                    $('.edg-snotification-text').html(response.delete);
                    $('.edg-notification-box.edg-success').addClass('edg-visible');
                    setTimeout(function() {
                        $('.edg-notification-box.edg-success').removeClass('edg-visible');
                        location.reload();
                    }, 1500);
                    // Display notification and page refresh.
                }).fail(Notification.exception);
            }

            // On click of filter checkboxes.
            $(document).on('change', '.edg-filter-check', function () {
                if ($(this).is(":checked")) {
                    $(this).next().next().addClass('edg-bolded');
                    if ($(this).attr('id') == 'edg-check-finished') {
                        $('#edg-check-soau').removeAttr('disabled');
                    }
                } else {
                    $(this).next().next().removeClass('edg-bolded');
                    if ($(this).attr('id') == 'edg-check-finished') {
                        $('#edg-check-soau').attr("disabled", true);
                    }
                }
            });

            // On Change of Show Attempts From Radio buttons.
            $(document).on('change', '.edg-filter-radio', function() {
                $('#edg-sort-box option').show();
                $('#edg-sort-box').val(1);
                $('.edg-filter-radio').next().next().removeClass('edg-bolded');
                if ($(this).is(":checked")) {
                    $(this).next().next().addClass('edg-bolded');
                }
                if ($('#edg-check-unaq').is(":checked")) {
                    $('.edg-ra-filters input[type="checkbox"]').each(function (index, el) {
                        $(el).attr("disabled", true);
                    });
                    $('#edg-check-rg').attr("disabled", true);
                    $('#edg-sort-box').val(3);
                    $('#edg-sort-box option[value="1"]').hide();
                    $('#edg-sort-box option[value="2"]').hide();
                } else {
                    $('.edg-ra-filters input[type="checkbox"]').each(function (index, el) {
                        $(el).removeAttr("disabled");
                        if ($(el).attr('id') == 'edg-check-soau' && !$('#edg-check-finished').is(":checked")) {
                            $(el).attr("disabled", true);
                        }
                    });
                    $('#edg-check-rg').removeAttr("disabled");
                }
            });

            // Function to download the grade report.
            $(document).on('click', '.edg-data-download button.dropdown-item', function () {
                let format = $(this).val();
                $('select.edg-download-select').val(format);
                $('#edg-data-download-form').submit();
            });

            // Regrade Attempt.
            $('.edg-regrade-attempt').click(function(){
                var attempts = [];
                var service_name = 'block_edwiser_grader_regrade_quiz_attempt';
                $.each($(".edg-check-user:checked"), function(index, el) {
                    let userid = $(el).attr('data-userid');
                    let selectedAttempt = $(selectedtab + ' .edg-record-' + userid + ' #edg-attempt-nav-tabcontent .tab-pane.active');
                    let attemptid = $(selectedAttempt).attr('data-attempt');
                    attempts.push(attemptid);
                });
                var regradeQuizAttempt = Ajax.call([
                    {
                        methodname: service_name,
                        args: {
                            attempts : JSON.stringify(attempts),
                            cmid : quizid,
                        }
                    }
                ]);
                regradeQuizAttempt[0].done(function (response) {
                    $('.edg-snotification-text').html(response.regrade);
                    $('.edg-notification-box.edg-success').addClass('edg-visible');
                    setTimeout(function() {
                        $('.edg-notification-box.edg-success').removeClass('edg-visible');
                        location.reload();
                    }, 1500);
                }).fail(Notification.exception);
            });

            // Dry run regrade all.
            $('.edg-dry-regrade-btn').click(function () {
                $('.edg-overlay-msg').html(M.util.get_string('dryruninprogress', 'block_edwiser_grader'));
                $('.edg-overlay').show();
                dryrunregrade(true);
            });

            // Regrade all.
            $('.edg-regrade-btn').click(function () {
                $('.edg-overlay-msg').html(M.util.get_string('regradeinprogress', 'block_edwiser_grader'));
                $('.edg-overlay').show();
                dryrunregrade(false);
            });

            // AJAX call for regrade all and dry run regrade all.
            function dryrunregrade(dryrun) {
                var service_name = 'block_edwiser_grader_dry_run_regrade';
                var dryrunall = Ajax.call([
                    {
                        methodname: service_name,
                        args: {
                            cmid : quizid,
                            dryrun : dryrun
                        }
                    }
                ]);
                dryrunall[0].done(function (response) {
                    // Display notification and page refresh.
                    $('.edg-snotification-text').html(response.regrade);
                    $('.edg-notification-box.edg-success').addClass('edg-visible');
                    $('.edg-overlay').hide();
                    setTimeout(function() {
                        $('.edg-notification-box.edg-success').removeClass('edg-visible');
                        location.reload();
                    }, 1500);
                }).fail(Notification.exception);
            }

            // Filter Model.
            $('#edgfiltermodal .btn-primary').click(function(event){
                if (!validateFilter()) {
                    event.preventDefault();
                    event.stopPropagation();
                    $('.edg-filter-error').removeClass('hide');
                    return ;
                }
                selectedattemptsfrom = $('#edgfiltermodal .edg-filter-radio:checked').attr('data-filter');
                soap = $('#edgfiltermodal #edg-check-soau:not(:disabled):checked').attr('data-filter');
                needsregrade = $('#edgfiltermodal #edg-check-rg:not(:disabled):checked').attr('data-filter');
                var showattempts = $('#edgfiltermodal .edg-filter-state:not(:disabled):checked');
                var length  = showattempts.length;
                if (length) {
                    selectedattempts = ' AND (';
                    $('#edgfiltermodal .edg-filter-state:not(:disabled):checked').each(function(index, ele) {
                        if (index === (length - 1)) {
                            selectedattempts += "qa.state = '" + $(this).attr('data-filter') + "' ";
                        } else {
                            selectedattempts += "qa.state = '" + $(this).attr('data-filter') + "' OR ";
                        }
                    });
                    selectedattempts += ') ';
                }
                $('#edg-notgraded-tab').trigger('click');
                $('#edgfiltermodal').removeClass('show');
            });

            function validateFilter() {
                let showattemptsfrom = $('#edgfiltermodal .edg-filter-radio:checked').attr('data-filter');
                let showattempts = $('#edgfiltermodal .edg-filter-state:not(:disabled):checked');
                if (showattemptsfrom != 'unaq' && showattempts.length == 0) {
                    return false;
                }
                return true;
            }

            $('#edgfiltermodal').on('hide.bs.modal', function (event) {
                if (!validateFilter()) {
                    event.preventDefault();
                    event.stopPropagation();
                    $('.edg-filter-error').removeClass('hide');
                }
            });

            $('#edgfiltermodal').on('show.bs.modal', function (event) { 
                $('.edg-filter-error').addClass('hide');
            });

            // On Change of Grading Method
            $('#edg-grading-method').change(function () {
                $('.edg-close').trigger('click');
                $('.edg-user-grading, .edg-question-grading').toggleClass('edg-hide');
                qbasedview = false;
                if ($(this).val() == 2) {
                    qbasedview = true;
                }
                updateURL();
            });

            function updateURL() {
                if (history.pushState) {
                    var newurl;
                    var cmid = getUrlParameter('id');
                    var newurl = window.location.protocol + "//" + window.location.host + window.location.pathname;
                    if (qbasedview) {
                        newurl += '?gdm=question&id='+cmid;
                    } else {
                        newurl += '?gdm=user&id='+cmid;
                    }
                    window.location = newurl;
                }
            }

            $('#edg-filter-questions').change(function () {
                getQuestionTableData();
            });

            function getQuestionTableData() {
                var service_name = 'block_edwiser_grader_question_based_grading';
                var includeauto = false;
                if ($('#edg-filter-questions').is(":checked")) {
                    includeauto = true;
                }
                var questionBasedGrading = Ajax.call([
                    {
                        methodname: service_name,
                        args: {
                            cmid : quizid,
                            includeauto: includeauto
                        }
                    }
                ]);
                questionBasedGrading[0].done(function (response) {
                    loadQuestionTable(response);
                }).fail(Notification.exception);
            }

            function loadQuestionTable(response) {
                $('#eg-qb-table').DataTable().destroy();
                let qtable = $('#eg-qb-table').DataTable({
                    "bPaginate": true,
                    "ordering": true,
                    "lengthMenu": [10, 20, 50, 100],
                    "pageLength": 10,
                    "processing": true,
                    "responsive": true,
                    "language": {
                        "searchPlaceholder": M.util.get_string('searchquestion', 'block_edwiser_grader'),
                        "processing": '<i class="fa fa-circle-o-notch fa-spin fa-3x fa-fw text-primary"></i>',
                        "emptyTable": M.util.get_string('noquestionsfound', 'block_edwiser_grader')
                    },
                    "data": response,
                    "columns": [
                        { "className": "pb-0 pt-0 dt-center", "data": "qnum" },
                        { "className": "pb-0 pt-0 dt-center", "data": "qtypeicon" },
                        { "className": "pb-0 pt-0 dt-center", "data": "qname" },
                        { "className": "pb-0 pt-0 dt-center edg-qb-grade", "data": "qgrade" },
                        { "className": "pb-0 pt-0 dt-center edg-qb-grade", "data": "qupdategrade" },
                        { "className": "pb-0 pt-0 dt-center edg-qb-grade", "data": "qautograde" },
                        { "className": "pb-0 pt-0 dt-center edg-qb-grade", "data": "qgradeall" }
                    ],
                    "createdRow": function(row, data, dataIndex) {
                        if (data.regradestatus) {
                            $(row).addClass('edg-regrade');
                        } 
                    },
                    "columnDefs": [
                        {
                            targets: [1, 2, 3, 4, 5, 6],
                            orderable: false
                        }
                    ]
                });
                $('#eg-qb-table').css('visibility', 'visible');
                qtable.column(5).visible(false);
                if ($('#edg-filter-questions').is(":checked")) {
                    qtable.column(5).visible(true);
                }
            }

            var studentPagination = '';
            function updatePaginationData(response, attemptid, slot) {
                response.map((el) => {
                    el.selected = (el.id == attemptid) ? 'selected' : '';
                    return el;
                });
                studentPagination = response;
            }
            $(document).on('click', '.edg-qb-grade a', function (e) {
                e.preventDefault();
                $('#eg-qb-table tbody tr').removeClass('highlighted');
                $(this).parent().parent().addClass('highlighted');
                let marginvalue = $(this).offset().top;
                $('body').css('margin-bottom', marginvalue + 'px');
                $('html,body').animate({scrollTop: marginvalue - 45 }, 250, 'swing');
                slot  = $(this).data('slot');
                let mode = $(this).data('mode');
                let qid   = $(this).data('qid');
                var service_name = 'block_edwiser_grader_question_based_grading_details';
                var questionBasedGradingDetails = Ajax.call([
                    {
                        methodname: service_name,
                        args: {
                            cmid : quizid,
                            slots : typeof slot == 'string' ? slot.split(',') : [slot],
                            mode: mode,
                            qid: qid,
                        }
                    }
                ]);
                questionBasedGradingDetails[0].done(function (response) {
                    if (response.length >= 1) {
                        attemptid = response[0].id;
                        updatePaginationData(response, attemptid);
                        loadQuestionContent(response[0].slot, attemptid, cmid, studentPagination);
                    }
                }).fail(Notification.exception);
            });

            function loadUserlistslider(parent, users) {
                let selectedValue;
                $.each(users, function(key, user) {
                    if (user.selected == 'selected') {
                        selectedValue = user.id;
                    }
                    let optionel = $("<option></option>")
                                    .attr('data-slot', user.slot)
                                    .attr('data-userid', user.userid)
                                    .attr('data-attemptnum', user.attempt)
                                    .attr("value",user.id)
                                    .text(user.firstname + " " + user.lastname);
                    $('#edg-students-select').append(optionel);
                });
                $('#edg-students-select').val(selectedValue);
                attempt = $('#edg-students-select').find(':selected').attr('data-attemptnum');
                $('.edg-sa-count').html(attempt);
                $('#edg-students-select').selectpicker('refresh');
                changePrevNextStatus();
            }

            function changePrevNextStatus() {
                let selecteduser = $('#edg-students-select option:selected');
                $('.edg-qb-userlist .edg-prev, .edg-qb-userlist .edg-next').removeClass('disabled');
                if (selecteduser.prev().length == 0) {
                    $('.edg-qb-userlist .edg-prev').addClass('disabled');
                }
                if (selecteduser.next().length == 0) {
                    $('.edg-qb-userlist .edg-next').addClass('disabled');
                }
            }

            $(document).on('change', '#edg-students-select', function () {
                attemptid = $(this).val();
                slot = $(this).find(':selected').attr('data-slot');
                updatePaginationData(studentPagination, attemptid, slot);
                loadQuestionContent(slot, attemptid, quizid, studentPagination);
            });

            $(document).on('click', '.edg-qb-userlist .edg-prev', function () {
                let selecteduser = $('#edg-students-select option:selected');
                if (selecteduser.prev().length != 0) {
                    let value = selecteduser.prev().val();
                    $('#edg-students-select').val(value).change();
                    changePrevNextStatus();
                }
            });

            $(document).on('click', '.edg-qb-userlist .edg-next', function () {
                let selecteduser = $('#edg-students-select option:selected');
                if (selecteduser.next().length != 0) {
                    let value = selecteduser.next().val();
                    $('#edg-students-select').val(value).change();
                    changePrevNextStatus();
                }
            });

            // Fix atto image uploader modal height and width issue
            $(document).on('blur', '.form-control.atto_image_urlentry', function() {
                $(this).parents('form.atto_form').addClass('image-properties');
            });

            // Fix bootstrap-select dropdown not closing on click.
            $('body').on('click', function(event) {
                $('.bootstrap-select').removeClass('open');
             });
        });
    }
    // Must return the init function.
    return {
        init: init
    };
});
