TD_Notification = TD_Notification || {};
// Import necessary modules
const NotificationDrawer = require( './views/NotificationDrawer' );
const NotificationsList = require( './views/NotificationsList' );
const NotificationCollection = require( './collections/Notifications' );
const FooterView = require( './views/NotificationFooter' );


( function ( $ ) {
	$(document).ready(function () {
		// Initialize variables
		let notificationType = 'Active';
		let notificationData = [];
		let notificationListView = null;
		let footerView = null;
		// Function to render the UI
		const renderUI = function () {
			// Filter data based on notification type
			const filteredData = notificationType === 'Active' ? TD_Notification?.data?.active : TD_Notification?.data?.dismissed;

			// Count dismissed and active notifications
			const dismissedCount = TD_Notification?.data?.dismissed?.length;
			const activeCount = TD_Notification?.data?.active?.length;

			if ( activeCount <= 0 ) {
                $('.notification-indicator').parent().remove();
			}

			// Create a new collection with filtered data
			const collection = new NotificationCollection( filteredData );

			// Render NotificationDrawer
			const notificationDrawer = new NotificationDrawer( {
				el: $( '.td-app-notification-counter' ),
				collection: collection,
				activeCount,
				dismissedCount
			} );
			notificationDrawer.render();

			// Render NotificationsList
			notificationListView = new NotificationsList( {
				el: $('.td-app-notification-holder'),
				collection,
				notificationType,
				dismissedCount,
				activeCount,
			} );
			notificationListView.render();

			footerView = new FooterView({
				el: $('.notification-footer'),
				collection: collection,
				notificationType: notificationType,
				hideDismissAll: notificationType === 'Dismissed' || activeCount < 2,
			});
			footerView.render();

			// Event handler for collection change
			notificationListView.on( 'collectionChanged', function ( data ) {
				notificationData = data.collection.toJSON();
				renderUI(); // Re-render UI with updated data
			} );

			footerView.on( 'collectionChanged', function ( data ) {
				notificationData = data.collection.toJSON();
				renderUI(); // Re-render UI with updated data
			} );

			// Event handler for notification type change
			notificationListView.on( 'notificationTypeChanged', function ( data ) {
				notificationType = data?.notification_type ? data?.notification_type : 'Active';
				renderUI(); // Re-render UI with updated notification type
			} );

			$('.tve-notification').last().css('border-bottom', 'none')
		};

		window.render_ui = renderUI;

		// Check visibility every 100 milliseconds
		const interval = setInterval(function () {
			if ($('.td-app-notification-counter').is(':visible')) {
				// Render the UI for the first time
				renderUI();
				clearInterval(interval);
			}
		}, 100);

		// Initial check in case the element is already visible when the script runs
		if ($('.td-app-notification-counter').is(':visible')) {
			renderUI();
			clearInterval(interval);
		}

		const checkEmptyOrNot = setInterval(function () {
			if ($('.td-app-notification-wrapper').is(':empty')) {
				renderUI();
			} else {
				clearInterval(checkEmptyOrNot);
			}
		}, 100);

		// Function to close the notification drawer
		const closeNotificationDrawer = function () {
			$( '.td-app-notification-overlay' ).addClass( 'close' );
			$( '.td-app-notification-drawer' ).removeClass( 'open' );
		};

		// Event listener for Esc key press
		$(document).on('keydown', function (e) {
			if (e.key === 'Escape') {
				closeNotificationDrawer();
			}
		});

		const getUrlParameter = function(name) {
			name = name.replace(/[\[\]]/g, '\\$&'); // Escape special characters for regex
			const regex = new RegExp('[?&]' + name + '(=([^&#]*)|&|#|$)');
			const results = regex.exec(window.location.href);
			if (!results) return null; // If no match is found
			if (!results[2]) return ''; // If the parameter exists but no value is set
			return decodeURIComponent(results[2].replace(/\+/g, ' '));
		}

		if ( getUrlParameter('notify') == 1 ) {
			$( '.td-app-notification-overlay' ).removeClass( 'close' );
			$( '.td-app-notification-drawer' ).addClass( 'open' );
		}
	});
} )( jQuery );
