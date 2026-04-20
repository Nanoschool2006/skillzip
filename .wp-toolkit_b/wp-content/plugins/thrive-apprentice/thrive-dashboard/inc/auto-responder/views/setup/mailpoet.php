<?php

/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-dashboard
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

/**
 * MailPoet setup form template
 */
?>
<h2 class="tvd-card-title"><?php echo esc_html( $this->get_title() ) ?></h2>
<div class="tvd-row">
	<form class="tvd-col tvd-s12">
		<input type="hidden" name="api" value="mailpoet"/>
		<div class="tvd-row">
			<div class="tvd-col tvd-s12">
				<p><?php echo esc_html__( 'MailPoet integration allows you to add subscribers to your MailPoet lists directly from Thrive Architect lead generation elements.', 'thrive-dash' ) ?></p>
				<p><strong><?php echo esc_html__( 'Requirements:', 'thrive-dash' ) ?></strong></p>
				<ul class="tvd-left-list">
					<li><?php echo esc_html__( 'MailPoet plugin must be installed and activated', 'thrive-dash' ) ?></li>
					<li><?php echo esc_html__( 'At least one MailPoet list/segment must exist', 'thrive-dash' ) ?></li>
				</ul>
			</div>
		</div>
		
		<?php if ( ! $this->pluginInstalled() ) : ?>
			<div class="tvd-row">
				<div class="tvd-col tvd-s12">
					<div class="tvd-card-panel tvd-red tvd-white-text">
						<p><strong><?php echo esc_html__( 'MailPoet Plugin Required', 'thrive-dash' ) ?></strong></p>
						<p><?php echo esc_html__( 'Please install and activate the MailPoet plugin before setting up this integration.', 'thrive-dash' ) ?></p>
						<p><a href="<?php echo esc_url( admin_url( 'plugin-install.php?s=mailpoet&tab=search&type=term' ) ) ?>" class="tvd-btn tvd-btn-flat tvd-white tvd-black-text" target="_blank"><?php echo esc_html__( 'Install MailPoet', 'thrive-dash' ) ?></a></p>
					</div>
				</div>
			</div>
		<?php endif; ?>

		<div class="tvd-row">
			<div class="tvd-col tvd-s12">
				<span class="tvd-left">
					<?php echo esc_html__( 'MailPoet', 'thrive-dash' ) ?>
				</span>
				<a href="https://help.thrivethemes.com/en/articles/4625431-how-to-connect-mailpoet-with-thrive-architect" target="_blank" class="tvd-right">
					<?php echo esc_html__( 'Need help?', 'thrive-dash' ) ?>
				</a>
			</div>
		</div>

		<div class="tvd-input-field">
			<input type="text" id="tvd-mailpoet-connection-name" name="connection[name]" value="<?php echo esc_attr( $this->param( 'name' ) ) ?>">
			<label for="tvd-mailpoet-connection-name"><?php echo esc_html__( 'Connection list/segment', 'thrive-dash' ) ?></label>
		</div>
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