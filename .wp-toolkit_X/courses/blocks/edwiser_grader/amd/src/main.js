define(['jquery', 'core/log', 'core/ajax', 'core/templates'], function($, Log, Ajax, templates) {
    /**
     * Initializes the code fiddle controls.
     */
    function init() {
        let quizdata;
        let courseid;
        $(document).ready(function() {
            updateCourseId();
            courseid = $("#edg-quizlist-content").data("cid");

            // Course dropdown change event.
            $('.edg-course-select').change(function (){
                courseid = $(this).val();
                $('.edg-quiz-skeleton').show();
                $('.edg-quiz-list').addClass('hidden');
                getCourseQuizzes(courseid);
            });
            if (courseid != '' && courseid != undefined) {
                getCourseQuizzes(courseid);
            }
            // Function to update course id when back is pressed after.
            // selecting any course from dropdown.
            function updateCourseId() {
                let updatedcid = $('.edg-course-select').val();
                $('#edg-quizlist-content').attr('data-cid', updatedcid);
            }

            // Ajax Call to add question.
            function getCourseQuizzes(cid) {
                let moduleid = 0;
                if ($('#edg-main-content').attr('data-page') !== undefined) {
                    moduleid = getUrlParameter('id');
                }
                var service_name = 'block_edwiser_grader_get_course_quizzes';
                var getQuizzes = Ajax.call([
                    {
                        methodname: service_name,
                        args: { courseid : cid, moduleid: moduleid }
                    }
                ]);
                getQuizzes[0].done(function (response) {
                    quizdata = response;
                    loadDataToTemplate(response, cid);
                });
            }

            // Function to update the block template details.
            function loadDataToTemplate(response, cid) {
                let template = "block_edwiser_grader/quizlist";
                let context  = [];
                context['courseid'] = cid;
                context['quizzes'] = response;
                if ($('#edg-main-content').attr('data-page') !== undefined) {
                    context['quizpage'] = true;
                }
                if ($('#edg-search-input').val().trim() != "") {
                    context['search'] = true;
                }
                templates.render(template, context).then(
                    (html, js)  => {
                        templates.replaceNode($("#edg-quizlist-content"), html , js);
                        $('.edg-quiz-skeleton').hide();
                        $('.edg-quiz-list').removeClass('hidden');
                        if (response.length <= 1 && $('#edg-search-input').val().trim() == "") {
                            $('.edg-search-container').hide();
                        } else {
                            $('.edg-search-container').show();
                        }
                    }
                );
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

            // Search Filter change event.
            $(document).on('input', '#edg-search-input', function () {
                let searchText = $(this).val();
                let filteredData = quizdata.filter((quiz) => {
                    return quiz.quizname.toLowerCase().includes(searchText.toLowerCase(), 0);
                });
                loadDataToTemplate(filteredData, courseid);
            });
        });
    }
    // Must return the init function.
    return {
        init: init
    };
});