(function ( $ ) {
	const BaseView = require( './Base' );

	module.exports = BaseView.extend( {
		dismissedCount: 0,
		activeCount: 0,
		events: {
			'click .tvd-notifications-btn': 'open',
			'click .td-app-notification-counter-holder': 'open',
			'click .text-notify-t-automator': 'open',
		},

		initialize: function ( options ) {
			$( window ).on('scroll', this.adjustDrawerPosition.bind(this));
			$( document ).on('click', this.handleDocumentClick.bind(this));
			if ( options ) {
				this.activeCount = options.activeCount;
				this.dismissedCount = options.dismissedCount;
			}
		},

		adjustDrawerPosition: function () {
			const scrollTop = $( window ).scrollTop();
			const newTop = 124 + scrollTop + 'px';
			this.$( '.td-app-notification-drawer' ).css( 'top', newTop );
		},

		render: function () {
			this.$( '.td-app-notification-counter-holder' ).text( this.activeCount );

			this.activeCount >= 1 ? this.$( '.td-app-notification-counter-holder' ).show() : this.$( '.td-app-notification-counter-holder' ).hide();

			return this;
		},

		open: function () {
			$( '.td-app-notification-overlay' ).removeClass( 'close' );
			$( '.td-app-notification-drawer' ).addClass( 'open' );
		},

		handleDocumentClick: function (event) {
			if ( $(event.target).closest('.td-app-notification-overlay.overlay').length) {
				$( '.td-app-notification-overlay' ).addClass( 'close' );
				$( '.td-app-notification-drawer' ).removeClass( 'open' );
			}
		}
	} );
} )( jQuery );
