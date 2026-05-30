( function ( $ ) {

	const EDITOR_SELECTORS = '.editor-block-list__layout,.block-editor-block-list__layout';

	const ThriveGutenbergSwitch = {

		/**
		 * Check if we're using the block editor
		 *
		 * @return {boolean}
		 */
		isGutenbergActive() {
			return typeof wp !== 'undefined' && typeof wp.blocks !== 'undefined';
		},

		init() {
			this.coreEditor = wp.data.select( 'core/editor' );
			this.$gutenberg = $( '#editor' );
			this.$architectNotificationContent = $( '#thrive-gutenberg-switch' ).html();
			this.$architectDisplay = $( '<div id="tar-display">' ).append( this.$architectNotificationContent );
			this.$architectLauncher = this.$architectDisplay.find( '#thrive_preview_button' );
			this.isPostBox = this.$architectNotificationContent.indexOf( 'postbox' ) !== - 1;

			$( window ).on( 'storage.tcb', event => {
				if ( this.coreEditor ) {
					const currentPost = this.coreEditor.getCurrentPost();
					let post;

					try {
						post = JSON.parse( event.originalEvent.newValue );
					} catch ( e ) {

					}

					if ( post && post.ID && event.originalEvent.key === 'tve_post_options_change' && post.ID === Number( currentPost.id ) ) {
						window.location.reload();
					}
				}
			} );

			wp.data.subscribe( () => {
				if ( this.coreEditor ) {
					const isSavingPost = this.coreEditor.isSavingPost(),
						isAutosavingPost = this.coreEditor.isAutosavingPost();

					if ( isSavingPost && ! isAutosavingPost ) {
						const data = JSON.stringify( this.coreEditor.getCurrentPost() );

						window.localStorage.setItem( 'tve_post_options_change', data );
					}
				}

				/**
				 * On data subscribe check if our elements exists
				 */
				setTimeout( () => {
					this.render();
				}, 1 );
			} );
		},
		/**
		 * Resolve the jQuery context that wraps the post title and block list.
		 *
		 * WP 7.0+ renders the post canvas inside `<iframe name="editor-canvas">`
		 * whenever the post contains no v2 blocks (TCB-built posts have no blocks
		 * at all, so this branch always triggers). Returns the iframe document in
		 * that case, the parent `#editor` otherwise, or null when the iframe is
		 * present but not yet loaded — the wp.data subscriber will retry.
		 *
		 * @return {jQuery|null}
		 */
		getCanvas() {
			const iframe = this.$gutenberg.find( 'iframe[name="editor-canvas"]' )[ 0 ];
			if ( ! iframe ) {
				return this.$gutenberg;
			}
			const doc = iframe.contentDocument;
			if ( ! doc || doc.readyState === 'loading' || ! doc.body ) {
				return null;
			}
			return $( doc );
		},

		/**
		 * Make the iframe document style-aware enough to render the notice.
		 *
		 * Only the block editor's own stylesheets ship inside the iframe canvas;
		 * the notice's `.postbox` (wp-admin) and `.thrive-architect-*` (the
		 * architect-edit-links stylesheet) live in the parent doc and don't
		 * propagate in.
		 *
		 * - Clone the edit-links `<link>` into the iframe so most of the rules
		 *   (`.inside.thrive-architect`, `.thrive-architect-admin-*`) apply.
		 * - Tag the iframe body with `id="wpbody"` so the qualified rule
		 *   `#wpbody .thrive-architect-edit-link` in that same stylesheet matches
		 *   — without it the green Launch button renders with no styling.
		 * - Inline minimal `.postbox` declarations (those live in wp-admin.css)
		 *   and lift the `.is-root-container` width cap on `#tar-display` so the
		 *   notice spans the full canvas width like the pre-7.0 layout.
		 */
		prepareIframeCanvas( iframeDoc ) {
			if ( ! iframeDoc || iframeDoc.getElementById( 'tar-notice-styles' ) ) {
				return;
			}
			if ( ! iframeDoc.body.id ) {
				iframeDoc.body.id = 'wpbody';
			}
			document.querySelectorAll( 'link[rel="stylesheet"][href*="thrive-architect-edit-links"]' ).forEach( link => {
				const clone = iframeDoc.createElement( 'link' );
				clone.rel = 'stylesheet';
				clone.href = link.href;
				iframeDoc.head.appendChild( clone );
			} );
			const style = iframeDoc.createElement( 'style' );
			style.id = 'tar-notice-styles';
			style.textContent =
				'.postbox{background:#fff;border:1px solid #c3c4c7;box-shadow:0 1px 1px rgba(0,0,0,.04);margin:0 0 20px;}' +
				'.postbox > .inside{padding:0 12px 12px;line-height:1.4;}' +
				/* Block editor's root container caps content to ~800px via the
				   theme content size variable. Let `#tar-display` span full width
				   so the notice matches the pre-7.0 layout. */
				'.is-root-container > #tar-display{max-width:none !important;}' +
				/* Force the wp-admin sans-serif font on the notice; otherwise it
				   inherits the iframe's theme body font (often serif). */
				'#tar-display, #tar-display *{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Oxygen-Sans,Ubuntu,Cantarell,"Helvetica Neue",sans-serif !important;}' +
				/* Restore the full vertical padding on the notice body. The
				   architect-edit-links stylesheet shrinks `.inside.thrive-architect`
				   to `padding: 10px 0` under a `max-width: 1400px` media query
				   measured against the iframe viewport (always narrower than the
				   admin window), so the small-padding branch always wins inside
				   the iframe. */
				'.inside.thrive-architect{padding:90px 0 !important;}';
			iframeDoc.head.appendChild( style );
		},

		render() {
			const $canvas = this.getCanvas();
			if ( ! $canvas ) {
				return;
			}
			const $iframe = this.$gutenberg.find( 'iframe[name="editor-canvas"]' );
			const $postTitle = $canvas.find( '.editor-post-title' ),
				$wpContent = $canvas.find( EDITOR_SELECTORS ).not( $postTitle.closest( EDITOR_SELECTORS ) ),
				$headerToolbar = this.$gutenberg.find( '.edit-post-header-toolbar, .editor-header__toolbar' );
			let shouldBindEvents = false;
			if ( this.isPostBox ) {
				if ( ! this.$architectDisplay[ 0 ].isConnected ) {
					if ( $iframe.length ) {
						/* About to append the notice inside the iframe — side-load
						   the stylesheets and `id` mapping it depends on. Gated here
						   so non-TCB pages (where `isPostBox` is false) don't get
						   the cloned stylesheet or the `body#wpbody` tag. */
						this.prepareIframeCanvas( $iframe[ 0 ].contentDocument );
					}
					if ( $postTitle.length ) {
						/* Legacy classic editor wrapped the title input in a
						   plain DIV — `.append()` placed the notice inside that
						   wrapper. In the block editor (and the WP 7.0+ iframed
						   canvas) `.editor-post-title` is the contenteditable
						   title block itself, so `.append()` would dump the
						   notice inside the editable area. Guard against that
						   by checking `isContentEditable`. */
						if ( $postTitle[ 0 ].tagName === 'DIV' && ! $postTitle[ 0 ].isContentEditable ) {
							$postTitle.append( this.$architectDisplay );
						} else {
							$postTitle.after( this.$architectDisplay );
						}
					}
					$wpContent.hide();
					$canvas.find( '.editor-post-title__block' ).css( 'margin-bottom', '0' );
					$canvas.find( '.editor-writing-flow__click-redirect,.block-editor-writing-flow__click-redirect' ).hide();
					$headerToolbar.css( 'visibility', 'hidden' );
					shouldBindEvents = true;
				}
			} else if ( ! $( '#thrive_preview_button' ).length ) {
				/* Place the launcher as a sibling immediately after the inner
				   `.edit-post-header-toolbar` (the [+/undo/redo/...] group) so it
				   sits to the right of those controls rather than inside the
				   button group. `.after()` on a single target also avoids
				   jQuery's clone-on-multi-target behavior that the prior
				   `$headerToolbar.append()` triggered when both the inner and
				   outer toolbar selectors matched. */
				this.$gutenberg.find( '.edit-post-header-toolbar' ).first().after( this.$architectLauncher );
				this.$architectLauncher.on( 'click', function () {
					$wpContent.hide();
				} );
				$headerToolbar.css( 'visibility', 'visible' );
				shouldBindEvents = true;
			}

			/* So we can use saved styles */
			$canvas.find( EDITOR_SELECTORS ).addClass( 'tcb-style-wrap' );
			if ( shouldBindEvents ) {
				this.bindEvents();
			}
		},

		bindEvents() {
			const self = this;
			self.$architectDisplay.find( '#tcb2-show-wp-editor' ).on( 'click', function () {
				const $editlink = self.$architectDisplay.find( '.tcb-enable-editor' ),
					$postbox = $editlink.closest( '.postbox' ),
					$canvas = self.getCanvas() || self.$gutenberg;

				$.ajax( {
					type: 'post',
					url: ajaxurl,
					dataType: 'json',
					data: {
						_nonce: TCB_Post_Edit_Data.admin_nonce,
						post_id: this.getAttribute( 'data-id' ),
						action: 'tcb_admin_ajax_controller',
						route: 'disable_tcb'
					}
				} );

				$postbox.next( '.tcb-flags' ).find( 'input' ).prop( 'disabled', false );
				$postbox.remove();
				$canvas.find( EDITOR_SELECTORS ).show();
				self.isPostBox = false;
				self.render();

				self.fixBlocksPreview();
			} );

			this.$architectLauncher.on( 'click', function () {
				$.ajax( {
					type: 'post',
					url: ajaxurl,
					dataType: 'json',
					data: {
						_nonce: TCB_Post_Edit_Data.admin_nonce,
						post_id: this.getAttribute( 'data-id' ),
						action: 'tcb_admin_ajax_controller',
						route: 'change_post_status_gutenberg'
					}
				} )
			} );
		},
		/**
		 * Fix block height once returning to gutenberg editor
		 */
		fixBlocksPreview() {
			const blocks = document.querySelectorAll( '[data-type*="thrive"] iframe' ),
				tveOuterHeight = function ( el ) {
					if ( ! el ) {
						return 0;
					}
					let height = el.offsetHeight;
					const style = getComputedStyle( el );

					height += parseInt( style.marginTop ) + parseInt( style.marginBottom );
					return height;
				};

			Array.prototype.forEach.call( blocks, function ( iframe ) {
				const iframeDocument = iframe.contentDocument;

				iframe.style.setProperty(
					'height',
					''
				);
				iframe.parentNode.style.setProperty(
					'height',
					''
				);
				/**
				 * Fix countdown resizing
				 */
				Array.prototype.forEach.call( iframeDocument.body.querySelectorAll( '.tve-countdown' ), function ( countdown ) {
					countdown.style.setProperty(
						'--tve-countdown-size',
						''
					);
				} );


				const height = tveOuterHeight(
					iframeDocument.body
				);

				iframe.style.setProperty(
					'height',
					height + 'px'
				);
				iframe.parentNode.style.setProperty(
					'height',
					height + 'px'
				);
			} );
		}
	};

	$( function () {
		if ( ThriveGutenbergSwitch.isGutenbergActive() ) {
			ThriveGutenbergSwitch.init();
		}
		window.addEventListener( 'load', function () {
			$( '.tcb-revert' ).on( 'click', function () {
				if ( confirm( 'Are you sure you want to DELETE all of the content that was created in this landing page and revert to the theme page? \n If you click OK, any custom content you added to the landing page will be deleted.' ) ) {
					location.href = location.href + '&tve_revert_theme=1&nonce=' + this.dataset.nonce;
					$( '#editor' ).find( '.edit-post-header-toolbar, .editor-header__toolbar' ).css( 'visibility', 'visible' );
				}
			} );
		} );
	} );
}( jQuery ) );
