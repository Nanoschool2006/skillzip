( function ( $ ) {
    let BaseView = require( './Base' );

    module.exports = BaseView.extend( {
        data: {
            notification_type: 'Active',
            notification_number: 0,
            active_count: 0,
            dismissed_count: 0
        },
        events: {
            'click .toggle-to-dismissed': 'toggleNotification',
            'click .toggle-to-active': 'toggleNotification'
        },
        initialize: function ( options ) {
            if ( options ) {
                this.data.active_count = options.activeCount;
                this.data.dismissed_count = options.dismissedCount;
                this.data.notification_type = options.notificationType;
            }

            if ( this.data.notification_type === 'Dismissed' ) {
                this.collection.each( function ( item ) {
                    if ( item.get( 'dismissed' ) == 0 ) {
                        item.set( 'dismissed', 1 );
                    }
                });
            }

            this.data.notification_number = this.collection.length;
        },
        render: function () {
            this.$el.html( this.getHtml() );

            return this;
        },
        getHtml: function () {
            let html = '';
            let theme_class = TD_Notification.notify_class ? TD_Notification.notify_class : '';

            if ( this.data.notification_type === 'Active' ) {
                html += '<span class="tvd-notification-number notification-number-'+ theme_class +'">(' + this.data.notification_number + ') New Notifications</span>';
            } else if ( this.data.notification_type === 'Dismissed' ) {
                html += '<span class="tvd-notification-number notification-number-'+ theme_class +'"> (' + this.data.notification_number + ') Notifications</span>';
            }

            html += '<div class="toggle-notifications">';

            //If current tab is active and no of dismissed notification is zero, no need to show
            if ( this.data.notification_type === 'Active' && this.data.dismissed_count > 0 ) {
                html += '<span class="toggle-to-dismissed toggle-notifications toggle-'+ theme_class +'">Dismissed Notifications</span>';
            }
            //If Current tab is Dismissed should have option to go to active notification
            if ( this.data.notification_type === 'Dismissed' ) {
                html += '<span class="toggle-to-active toggle-notifications toggle-'+ theme_class +'">Active Notifications</span>';
            }

            html += '</div>';
            html += '<span class="tvd-close-notification-drawer close-notification-'+ theme_class +'">';
            html += '<svg class="icon-tve-dash-notification-cross"><use xlink:href="#icon-tvd-notification-cross"></use></svg>';
            html += '</span>';

            return html;
        },

        toggleNotification: function ( event ) {
            const notificationType = $( event.currentTarget ).hasClass( 'toggle-to-dismissed' ) ? 'Dismissed' : 'Active';
            if (this.data.notification_type !== notificationType) {
                this.data.notification_type = notificationType;
                this.trigger('notificationTypeChanged', { notification_type: notificationType });
            }
            event.stopPropagation();
        }
    } );
} )( jQuery );
