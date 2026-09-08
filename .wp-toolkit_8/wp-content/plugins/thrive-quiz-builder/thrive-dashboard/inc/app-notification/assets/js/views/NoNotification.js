/**
 * Created by Russel Hussain on 05/05/2024.
 */

( function ( $ ) {
    const BaseView = require( './Base' );

    module.exports = BaseView.extend( {
        notificationType: 'Active',
        translate: TD_Notification.t,
        initialize: function ( options ) {
            if (options && options.notificationType) {
                this.notificationType = options.notificationType;
            }
        },
        render: function () {
            this.$el.html( this.getHtml() );

            return this;
        },

        getHtml: function () {
            let theme_class = TD_Notification.notify_class ? TD_Notification.notify_class : '';
            let html = '<div class="no-notifications no-notifications-'+ theme_class +'">';
            let notificationType = this.notificationType;
            if ( notificationType === 'Active' ) {
                html += '<img alt="Dannie the Detective" src="'+ TD_Notification?.image_url +'">' +
                    '<div class="great-scott great-scott-'+ theme_class +'">' + this.translate.no_new_title + '</div>' +
                    '<div class="no-new-notifications no-new-notification-'+ theme_class +'">' + this.translate.no_new + '</div>' +
                    '<span class="switch-notification-type toggle-to-dismissed switch-notification-type-'+ theme_class + '">' + this.translate.see_dismissed +'</span>';
            }

            if (notificationType === 'Dismissed') {
                html += '<img alt="Dannie the Detective" src="'+ TD_Notification?.image_url +'">' +
                    '<div class="great-scott great-scott-'+ theme_class +'">' + this.translate.no_new_title + '</div>' +
                    '<div class="no-new-notifications no-new-notification-'+ theme_class +'">' + this.translate.no_dismissed + '</div>' +
                    '<span class="switch-notification-type toggle-to-active switch-notification-type-'+ theme_class +'">' + this.translate.see_new + '</span>';
            }

            html += '</div>';

            return html;

        }
    } );

} )( jQuery );
