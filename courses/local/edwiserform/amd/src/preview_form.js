/*
 * Edwiser_Forms - https://edwiser.org/
 * Version: 0.1.0
 * Author: Yogesh Shirsath
 */
define(['jquery', 'core/ajax', "local_edwiserform/form_styles", 'local_edwiserform/iefixes', 'local_edwiserform/formviewer'], function ($, ajax, formStyles) {
    return {
        init: function(title, sitekey) {
            $(document).ready(function (e) {
                $('body').addClass('edwiserform-fullpage');
                if (typeof definition != 'undefined') {
                    let form = $('#preview-form')[0];
                    let formeoOpts = {
                        container: form,
                        countries: countries,
                        sitekey: sitekey,
                        localStorage: false, // Changed from session storage to local storage.
                    };
                    var formeo = new Formeo(formeoOpts, definition);
                    formeo.render(form);
                    $(form).prepend('<h2>' + title + '</h2>');
                    formStyles.apply($(form).find('.formeo-render'), 'add', style);
                }
            });
        }
    };
});
