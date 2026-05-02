( function ( $ ) {
    const BaseView = require( './Base' );

    module.exports = BaseView.extend( {
        hideDismissAll: false,

        events: {
            'click .dismiss-all': 'dismissAll',
        },

        initialize: function ( options ) {
            if ( options && options.hideDismissAll ) {
                this.hideDismissAll = options.hideDismissAll;
            }

            this.listenTo( this.collection, 'change:dismiss-all', this.collectionChanged );
        },

        render: function () {
            this.$el.html( this.getHtml() );
            return this;
        },

        getHtml: function () {
            let theme_class = TD_Notification.notify_class ? TD_Notification.notify_class : '';
            let html = '<div class="pagination pagination-'+ theme_class +'"></div>' +
                '<div class="dismiss-all dismiss-all-'+ theme_class +'">';

            if ( !this.hideDismissAll ) {
                html += '<span class="dismiss-all dismiss-all-'+ theme_class +'">Dismiss All</span>';
            }

            html += '</div>';

            return html;

        },

        dismissAll: function () {
            this.collection.each(function (item) {
                const index = TD_Notification.data.active.findIndex(notification => notification.notification_id == item.get('notification_id'));
                if (index !== -1) {
                    TD_Notification.data.active.splice(index, 1);
                }
                TD_Notification.data.dismissed.unshift(item.toJSON());
            });

            this.collectionChanged();

            this.dismissAllNotification();
        },

        dismissAllNotification: function () {
            $.ajax( {
                type: 'POST',
                url: TD_Notification.baseUrl + '/dismiss-all',
                headers: {
                    'X-WP-Nonce': TD_Notification?.dismiss_nonce // Pass the nonce in the headers
                },
                success: function (response) {
                    // Handle success response
                },
                error: function (xhr, status, error) {
                    console.error('Error marking notification as read:', error);
                }
            } );
        },

        collectionChanged: function () {
            this.trigger( 'collectionChanged', { notification_type: 'Active', collection: this.collection } );
        }
    });
} )( jQuery );
