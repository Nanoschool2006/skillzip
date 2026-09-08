<h2 class="tvd-card-title"><?php echo esc_html( $this->get_title() ); ?></h2>
<div class="tvd-row">
	<form class="tvd-col tvd-s12">
		<input type="hidden" name="api" value="<?php echo esc_attr( $this->get_key() ) ?>"/>
		<div class="tvd-input-field">
			<input id="tvd-ck-api-url" type="text" name="connection[key]" value="<?php echo esc_attr( $this->param( 'key' ) ) ?>">
			<label for="tvd-ck-api-url"><?php echo esc_html__( "API key", 'thrive-dash' ) ?></label>
		</div>
		<div class="tvd-input-field">
			<input id="tvd-ck-api-secret" type="text" name="connection[secret]" value="<?php echo esc_attr( $this->param( 'secret' ) ) ?>">
			<label for="tvd-ck-api-secret"><?php echo esc_html__( "API Secret (optional)", 'thrive-dash' ) ?></label>
		</div>
		<p class="tve-form-description tvd-note-text">
			<?php echo __( 'Add your', 'thrive-dash' ); ?> <a href="https://app.kit.com/account_settings/advanced_settings" target="_blank" rel="noopener" style="color: #4bb35e;"><?php echo __( 'Secret API Key', 'thrive-dash' ); ?></a> <?php echo __( 'to securely sync and manage your subscriber tags right from here', 'thrive-dash' ); ?>
		</p>
		<?php $this->display_video_link(); ?>
	</form>
</div>
<div class="tvd-card-action">
	<div class="tvd-row tvd-no-margin">
		<div class="tvd-col tvd-s12 tvd-m6">
			<a class="tvd-api-cancel tvd-btn-flat tvd-btn-flat-secondary tvd-btn-flat-dark tvd-full-btn tvd-waves-effect"><?php echo esc_html__( "Cancel", 'thrive-dash' ) ?></a>
		</div>
		<div class="tvd-col tvd-s12 tvd-m6">
			<a class="tvd-api-connect tvd-waves-effect tvd-waves-light tvd-btn tvd-btn-green tvd-full-btn"><?php echo esc_html__( "Connect", 'thrive-dash' ) ?></a>
		</div>
	</div>
</div>

