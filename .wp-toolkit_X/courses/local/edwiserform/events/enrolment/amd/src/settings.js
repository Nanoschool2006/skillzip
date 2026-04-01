/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


define(['jquery'], function ($) {
    return {
        get_settings: function() {
            var courses = $('#id_enrolment-courses').val();
            if (courses.length == 0) {
                return false;
            }
            return courses;
        }
    };
});
