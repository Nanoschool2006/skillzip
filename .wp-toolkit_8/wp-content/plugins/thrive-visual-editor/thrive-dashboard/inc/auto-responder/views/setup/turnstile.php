<h2 class="tvd-card-title"><?php echo esc_html( $this->get_title() ); ?></h2>
<div class="tvd-row">
	<form class="tvd-col tvd-s12">
		<input type="hidden" name="api" value="<?php echo esc_attr( $this->get_key() ); ?>"/>

		<div class="tvd-row tvd-collapse">
			<div class="tve_lightbox_select_holder tve_lightbox_input_inline tve_lightbox_select_inline tvd-input-field">
				<label for="tvd-ac-api-secret-key" class="tvd-active tvd-label-dark-text"><?php echo esc_html__( 'Theme', 'thrive-dash' ) ?></label>
				<select class="tve-api-extra tl-api-connection-list" name="theme">
					<option value="auto"
					<#- (item && item.theme === 'auto') ? 'selected="selected"' : '' #>><?php echo esc_html__( 'Auto', 'thrive-dash' ) ?></option>
					<option value="light"
					<#- (item && item.theme === 'light') ? 'selected="selected"' : '' #>><?php echo esc_html__( 'Light', 'thrive-dash' ) ?></option>
					<option value="dark"
					<#- (item && item.theme === 'dark') ? 'selected="selected"' : '' #>><?php echo esc_html__( 'Dark', 'thrive-dash' ) ?></option>
				</select>
			</div>
		</div>

		<div class="tvd-row tvd-collapse">
			<div class="tve_lightbox_select_holder tve_lightbox_input_inline tve_lightbox_select_inline tvd-input-field">
				<label for="tvd-ac-api-secret-key" class="tvd-active tvd-label-dark-text"><?php echo esc_html__( 'Language', 'thrive-dash' ) ?></label>
				<select class="tve-api-extra tl-api-connection-list" name="language">
					<?php foreach ( $this->supportedLanguages() as $language ) {
						echo $language['html'];
					} ?>
				</select>
			</div>
		</div>

		<div class="tvd-row tvd-collapse">
			<div class="tve_lightbox_select_holder tve_lightbox_input_inline tve_lightbox_select_inline tvd-input-field">
				<label for="tvd-ac-api-secret-key" class="tvd-active tvd-label-dark-text"><?php echo esc_html__( 'Appearance mode', 'thrive-dash' ) ?></label>
				<select class="tve-api-extra tl-api-connection-list" name="appearance">
					<option value="always"
					<#- (item && item.appearance === 'always') ? 'selected="selected"' : '' #> ><?php echo esc_html__( 'Always', 'thrive-dash' ) ?></option>
					<option value="interaction-only"
					<#- (item && item.appearance === 'interaction-only') ? 'selected="selected"' : '' #> ><?php echo esc_html__( 'Interaction Only', 'thrive-dash' ) ?></option>
				</select>
			</div>
		</div>

		<div class="tvd-row tvd-collapse">
			<div class="tve_lightbox_select_holder tve_lightbox_input_inline tve_lightbox_select_inline tvd-input-field">
				<label for="tvd-ac-api-secret-key" class="tvd-active tvd-label-dark-text"><?php echo esc_html__( 'Size', 'thrive-dash' ) ?></label>
				<select class="tve-api-extra tl-api-connection-list" name="size" value="<#- item && item.size #>">
					<option value="normal"
					<#- (item && item.size === 'normal') ? 'selected="selected"' : '' #> ><?php echo esc_html__( 'Normal', 'thrive-dash' ) ?></option>
					<option value="compact"
					<#- (item && item.size === 'compact') ? 'selected="selected"' : '' #> ><?php echo esc_html__( 'Compact', 'thrive-dash' ) ?></option>
				</select>
			</div>
		</div>

		<div class="tvd-input-field tvd-row">
			<input id="tvd-tt-api-site-key" type="text" name="site_key" value="<#- item && item.site_key #>">
			<label for="tvd-tt-api-site-key"><?php echo esc_html__( 'Site key', 'thrive-dash' ) ?></label>
		</div>
		<div class="tvd-input-field tvd-row">
			<input id="tvd-tt-api-secret-key" type="text" name="secret_key" value="<#- item && item.secret_key #>">
			<label for="tvd-tt-api-secret-key"><?php echo esc_html__( 'Secret key', 'thrive-dash' ) ?></label>
		</div>

		<div class="tvd-row">
			<p class="tve-form-description tvd-note-text">
				<a href="https://developers.cloudflare.com/turnstile/get-started" target="_blank"><?php echo esc_html__( 'I need help with this', 'thrive-dash' ) ?></a>
			</p>
		</div>

		<div id="tvd-tt-api-notification"></div>

		<div class="cf-turnstile" id="turnstile" data-sitekey=""></div>
		<input id="tvd-tt-api-token" type="hidden" name="api_token" value="<#- item && item.api_token #>">
	</form>
</div>

<div class="tvd-card-action">
	<div class="tvd-row tvd-no-margin">
		<div class="tvd-col tvd-s12 tvd-m6">
			<a class="tvd-api-cancel tvd-btn-flat tvd-btn-flat-secondary tvd-btn-flat-dark tvd-full-btn tvd-waves-effect"><?php echo esc_html__( 'Cancel', 'thrive-dash' ) ?></a>
		</div>
		<div class="tvd-col tvd-s12 tvd-m6">
			<a class="tvd-api-connect tvd-waves-effect tvd-waves-light tvd-btn tvd-btn-green tvd-full-btn"><?php echo esc_html__( 'Connect', 'thrive-dash' ) ?></a>
		</div>
	</div>
</div>

<script type="text/javascript">
	(
		function ( $ ) {
			$( document ).ready( function () {
				$( '#tvd-tt-api-site-key' ).on( 'keyup', function () {
					loadTurnstile();
				} );

				$( '#tvd-tt-api-secret-key' ).on( 'keyup', function () {
					loadTurnstile();
				} );

				function valid( value ) {
					if ( ! value ) {
						return false;
					}

					return value && value.length
				}

				function showMessage( class_name, message ) {
					const $notification_wrapper = $( '#tvd-tt-api-notification' );
					$notification_wrapper.empty();
					if ( message.length === 0 ) {
						return;
					}
					$notification_wrapper.append( `<h4 class="${class_name}">${message}</h4>` )
				}

				function loadTurnstile() {
					let siteKey = $( '#tvd-tt-api-site-key' ).val(); // it is important for api settings in dashbaord
					let secretKey = $( '#tvd-tt-api-secret-key' ).val();

					if ( ! valid( siteKey ) || ! valid( secretKey ) ) {
						return;
					}

					let id = '#turnstile';
					let $turnstile = jQuery( id );

					$turnstile.attr( 'data-sitekey', siteKey );
					$turnstile.html( '' );

					const $input = $( '#tvd-tt-api-token' );
					const $btn = $( '#tvd-api-connect-btn' );
					$input.val( "invalid_token" );

					showMessage( 'tve-warning', 'Please wait! We are verifying your site key.' )
					turnstile.render( id, {
						sitekey: siteKey,
						'error-callback': errorCallback,
						callback: ( token ) => {
							$input.val( token );
							showMessage( '', '' );
						}
					} )
				}

				function errorCallback( error ) {
					showMessage( 'tve-error', 'Error! please enter valid credentials.' );
					if ( typeof turnstile.reset === 'function' ) {
						turnstile.reset();
					}
				}
			} );
		}
	)( jQuery );
</script>
