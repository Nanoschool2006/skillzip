(function ( $ ) {
	let BaseView = require('./Base');
	let HeaderView = require('./NotificationHeader');
	let FooterView = require('./NotificationFooter');
	let NotificationView = require('./NotificationController');
	let NoNotificationView = require('./NoNotification');

	module.exports = BaseView.extend( {
		headerView: null,
		hideDismissAll: false,
		notificationType: 'Active',
		dismissedCount: 0,
		activeCount: 0,

		events: {
			'click .tvd-close-notification-drawer': 'close',
			'click .dismiss-notification': 'dismiss',
			'click .switch-notification-type': 'toggleNotification',
		},

		initialize: function ( options ) {
			if ( options ) {
				this.activeCount = options.activeCount;
				this.dismissedCount = options.dismissedCount;
				this.hideDismissAll = options.notificationType === 'Dismissed' || this.activeCount < 2;
				this.notificationType = options.notificationType;
			}

			this.listenTo( this.collection, 'change:dismiss', this.collectionChanged );
		},

		render: function () {
			this.renderHeader();
			const $wrapperContainer = this.$('.td-app-notification-wrapper');
			if (this.collection.length > 0) {
				$wrapperContainer.html('<div class="tvd-notifications-list"></div>');
				this.renderList();
			} else {
				$wrapperContainer.empty();
				new NoNotificationView( {
					el: $wrapperContainer,
					notificationType: this.notificationType
				} ).render();
			}

			if ($wrapperContainer.find('.notification-footer').length === 0) {
				this.renderFooter();
			}
			return this;
		},

		renderHeader: function () {
			this.headerView = new HeaderView( {
				el: this.$( '.td-app-notification-header' ),
				collection: this.collection,
				activeCount: this.activeCount,
				dismissedCount: this.dismissedCount,
				notificationType: this.notificationType
			} ).render();
			this.listenTo( this.headerView, 'notificationTypeChanged', this.notificationTypeChanged );
		},

		renderList: function () {
			const $listContainer = this.$( '.tvd-notifications-list' );
			$listContainer.empty();

			// Instantiate NotificationView outside the loop
			const notificationViews = this.collection.map(function(model) {
				return new NotificationView({
					model: model
				});
			});

			// Render each NotificationView instance within the loop
			notificationViews.forEach(function(notificationView) {
				$listContainer.append(notificationView.render().el);
			});

		},

		renderFooter: function () {
			const $notificationFooter = $( '.notification-footer' );
			$notificationFooter.empty();

			new FooterView({
				el: $notificationFooter,
				collection: this.collection,
				notificationType: this.notificationType,
				hideDismissAll: this.hideDismissAll,
			}).render();
		},

		toggleNotification: function ( event ) {
			const notificationType = $( event.currentTarget ).hasClass( 'toggle-to-dismissed' ) ? 'Dismissed' : 'Active';
			this.trigger('notificationTypeChanged', { notification_type: notificationType });
		},

		dismiss: function ( event ) {
			event.preventDefault();
			let notification_id = $( event.currentTarget ).data( 'id' );
			let item = this.collection.findWhere( { notification_id: String(notification_id) } );

			if ( item ) {
				this.collection.remove(item);
				const index = TD_Notification.data.active.findIndex(notification => notification.notification_id== notification_id);
				if (index !== -1) {
					TD_Notification.data.active.splice(index, 1);
				}
				TD_Notification.data.dismissed.unshift(item.toJSON());
				this.dismissNotification( notification_id );
				this.collectionChanged();
			}
		},

		dismissNotification: function ( remoteId ) {
			$.ajax( {
				type: 'POST',
				url: TD_Notification.baseUrl + '/dismiss',
				headers: {
					'X-WP-Nonce': TD_Notification?.dismiss_nonce // Pass the nonce in the headers
				},
				data: {
					notification_id: remoteId,
				},
				success: function ( response ) {
					// Handle success response
				},
				error: function (xhr, status, error) {
					console.error('Error marking notification as read:', error);
				}
			} );
		},

		close: function () {
			const notificationType = 'Active';
			this.trigger('notificationTypeChanged', { notification_type: notificationType });
			$( '.td-app-notification-overlay' ).addClass( 'close' );
			$( '.td-app-notification-drawer' ).removeClass( 'open' );
		},

		collectionChanged: function () {
			this.trigger( 'collectionChanged', { notification_type: 'Active', collection: this.collection } );
		},

		notificationTypeChanged: function ( data ) {
			this.trigger( 'notificationTypeChanged', data );
		}
	} );
} )( jQuery );
