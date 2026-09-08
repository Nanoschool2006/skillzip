( function ( $, tcb ) {

	module.exports = {
		collapseClass: 'tve-state-expanded',
		init () {
			if ( ! tve_frontend_options.is_editor_page ) {
				this.frontendInit();
			}
		},
		frontendInit ( $root ) {
			$root = $root || tcb.$document;

			$root.find( '.tva-course' ).each( ( index, element ) => {
				const $element = $( element );

				$element.find( '.tva-course-chapter-list, .tva-course-lesson-list' ).wrap( '<div></div>' );

				this.initializeEmptyContainers( $element );

				this.bindEvents( $element );

				this.handleStates( $element );
			} );

			this.maybeHideCompletionPageButton();
		},

		bindEvents ( $course ) {
			const canCollapseModule = ! $course.attr( 'data-deny-collapse-module' ),
				canCollapseChapters = ! $course.attr( 'data-deny-collapse-chapter' ),
				autoCollapse = !! $course.attr( 'data-autocollapse' ),
				$modules = $course.find( '.tva-course-module-dropzone' ),
				$chapters = $course.find( '.tva-course-chapter-dropzone' );

			if ( canCollapseModule ) {
				$course.find( '.tva-course-module-dropzone' ).click( event => {
						if ( event.target.tagName !== 'A' ) {
							this.toggleItem( $( event.currentTarget ) );

							if ( autoCollapse ) {
								$modules.not( event.currentTarget ).addClass( 'tve-state-expanded' ).siblings().slideUp();
							}
						}
					}
				);
			}

			if ( canCollapseChapters ) {
				$course.find( '.tva-course-chapter-dropzone' ).click( event => {
					if ( event.target.tagName !== 'A' ) {
						this.toggleItem( $( event.currentTarget ) );

						if ( autoCollapse ) {
							$chapters.not( event.currentTarget ).addClass( 'tve-state-expanded' ).siblings().slideUp();

							if ( canCollapseModule && $( event.currentTarget ).closest( '.tva-course-module' ).length ) {
								$modules.not( $( event.currentTarget ).closest( '.tva-course-module' ).find( '.tva-course-module-dropzone' ) ).addClass( 'tve-state-expanded' ).siblings().slideUp();
							}
						}
					}
				} );
			}
		},

		/**
		 * Remove the states that are not for the active user
		 *
		 * @param $course
		 */
		handleStates ( $course ) {
			$course.find( '[data-tva-remove-state="1"]' ).remove();
		},
		/**
		 * Expand / Collapse the course item depending on the state
		 *
		 * @param {jQuery} $courseItem
		 * @param {string} method - jQuery method (toggleClass|addClass|removeClass)
		 */
		toggleItem ( $courseItem, method = 'toggleClass' ) {
			$courseItem[ method ]( this.collapseClass ).siblings()[ $courseItem.hasClass( this.collapseClass ) ? 'slideUp' : 'slideDown' ]();
		},

		/**
		 * If the inner content is empty, add an extra empty class for css styling ( we can't detect this in PHP )
		 *
		 * @param {jQuery} $element
		 */
		initializeEmptyContainers ( $element ) {
			const $courseItem = $element.find( '.tva-course-item-dropzone' );

			if ( $courseItem.find( '.tva-course-state-content:empty' ) ) {
				$courseItem.addClass( 'tva-empty-course-item' );
			}
		},

		/**
		 * Hide View completion page button if the href is empty.
		 * This means that the user has not completed the course yet.
		 * This issue occurs only in some sidebar themes.
		 */
		maybeHideCompletionPageButton () {
			$completionPageButton = $( '#theme-sidebar-section [data-shortcode-id="completion_page"][data-dynamic-link="tva_dynamic_actions_link"]' );
			const hrefs = [ '#', '', 'javascript:void(0);' ];

			if ( $completionPageButton.length && -1 !== $.inArray( $completionPageButton.attr( 'href' ), hrefs ) ) {
				$completionPageButton.closest( '.thrv-button' ).addClass( 'tcb-permanently-hidden' );
			}
		}
	};

} )( ThriveGlobal.$j, TCB_Front );
