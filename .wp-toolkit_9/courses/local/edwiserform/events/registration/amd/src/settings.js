define(['jquery'], function ($) {
    return {
        has_required_fields: function() {
            return true;
        },
        get_settings: function() {
            return $('#id_registration-disable-confirmation').is(':checked');
        }
    };
});
