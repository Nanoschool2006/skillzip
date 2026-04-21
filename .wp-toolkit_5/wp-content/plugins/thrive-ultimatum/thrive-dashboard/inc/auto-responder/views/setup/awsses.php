<?php

/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-dashboard
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}
/** var $this Thrive_Dash_List_Connection_Awsses */
$admin_email = get_option( 'admin_email' );
?>
<h2 class="tvd-card-title"><?php echo esc_html( $this->get_title() ) ?></h2>
<div class="tvd-row">
	<form class="tvd-col tvd-s12">
		<input type="hidden" name="api" value="<?php echo esc_attr( $this->get_key() ) ?>"/>
		<div class="tvd-input-field">
			<input id="tvd-aw-api-email" type="text" name="connection[email]"
					value="<?php echo esc_attr( $this->param( 'email', $admin_email ) ) ?>">
			<label for="tvd-aw-api-email"><?php echo esc_html__( "Email", 'thrive-dash' ) ?></label>
		</div>
		<div class="tvd-input-field">
			<input id="tvd-aw-api-secret" type="text" name="connection[secretkey]"
					value="<?php echo esc_attr( $this->param( 'secretkey' ) ) ?>">
			<label for="tvd-aw-api-secret"><?php echo esc_html__( "Secret key", 'thrive-dash' ) ?></label>
		</div>
		<div class="tvd-input-field">
			<input id="tvd-aw-api-key" type="text" name="connection[key]" value="<?php echo esc_attr( $this->param( 'key' ) ) ?>">
			<label for="tvd-aw-api-key"><?php echo esc_html__( "Access key", 'thrive-dash' ) ?></label>
		</div>
		<div class="tvd-input-field">
			<select id="tvd-aw-api-country" class="tvd-browser-default" type="text" name="connection[country]">
				<optgroup label="<?php echo esc_attr__( 'US Regions', 'thrive-dash' ); ?>">
					<option value="useast" <?php echo $this->param( 'country' ) == "useast" ? 'selected="selected"' : '' ?> >US East (N. Virginia)</option>
					<option value="useast2" <?php echo $this->param( 'country' ) == "useast2" ? 'selected="selected"' : '' ?> >US East (Ohio)</option>
					<option value="uswest1" <?php echo $this->param( 'country' ) == "uswest1" ? 'selected="selected"' : '' ?> >US West (N. California)</option>
					<option value="uswest" <?php echo $this->param( 'country' ) == "uswest" ? 'selected="selected"' : '' ?> >US West (Oregon)</option>
				</optgroup>
				<optgroup label="<?php echo esc_attr__( 'Europe Regions', 'thrive-dash' ); ?>">
					<option value="ireland" <?php echo $this->param( 'country' ) == "ireland" ? 'selected="selected"' : '' ?> >Europe (Ireland)</option>
					<option value="frankfurt" <?php echo $this->param( 'country' ) == "frankfurt" ? 'selected="selected"' : '' ?> >Europe (Frankfurt)</option>
					<option value="london" <?php echo $this->param( 'country' ) == "london" ? 'selected="selected"' : '' ?> >Europe (London)</option>
					<option value="paris" <?php echo $this->param( 'country' ) == "paris" ? 'selected="selected"' : '' ?> >Europe (Paris)</option>
					<option value="stockholm" <?php echo $this->param( 'country' ) == "stockholm" ? 'selected="selected"' : '' ?> >Europe (Stockholm)</option>
					<option value="milan" <?php echo $this->param( 'country' ) == "milan" ? 'selected="selected"' : '' ?> >Europe (Milan)</option>
				</optgroup>
				<optgroup label="<?php echo esc_attr__( 'Asia Pacific Regions', 'thrive-dash' ); ?>">
					<option value="mumbai" <?php echo $this->param( 'country' ) == "mumbai" ? 'selected="selected"' : '' ?> >Asia Pacific (Mumbai)</option>
					<option value="singapore" <?php echo $this->param( 'country' ) == "singapore" ? 'selected="selected"' : '' ?> >Asia Pacific (Singapore)</option>
					<option value="sydney" <?php echo $this->param( 'country' ) == "sydney" ? 'selected="selected"' : '' ?> >Asia Pacific (Sydney)</option>
					<option value="tokyo" <?php echo $this->param( 'country' ) == "tokyo" ? 'selected="selected"' : '' ?> >Asia Pacific (Tokyo)</option>
					<option value="seoul" <?php echo $this->param( 'country' ) == "seoul" ? 'selected="selected"' : '' ?> >Asia Pacific (Seoul)</option>
					<option value="osaka" <?php echo $this->param( 'country' ) == "osaka" ? 'selected="selected"' : '' ?> >Asia Pacific (Osaka)</option>
					<option value="jakarta" <?php echo $this->param( 'country' ) == "jakarta" ? 'selected="selected"' : '' ?> >Asia Pacific (Jakarta)</option>
				</optgroup>
				<optgroup label="<?php echo esc_attr__( 'Other Regions', 'thrive-dash' ); ?>">
					<option value="canada" <?php echo $this->param( 'country' ) == "canada" ? 'selected="selected"' : '' ?> >Canada (Central)</option>
					<option value="saopaulo" <?php echo $this->param( 'country' ) == "saopaulo" ? 'selected="selected"' : '' ?> >South America (São Paulo)</option>
					<option value="capetown" <?php echo $this->param( 'country' ) == "capetown" ? 'selected="selected"' : '' ?> >Africa (Cape Town)</option>
					<option value="bahrain" <?php echo $this->param( 'country' ) == "bahrain" ? 'selected="selected"' : '' ?> >Middle East (Bahrain)</option>
				</optgroup>
			</select>
			<label for="tvd-aw-api-country"><?php echo esc_html__( "Email Zone", 'thrive-dash' ) ?></label>
		</div>
		<?php $this->display_video_link(); ?>
	</form>
</div>
<div class="tvd-row">
	<div class="tvd-col tvd-s12">
		<p class="tve-form-description tvd-note-text">
			<?php echo esc_html__( 'Note: sending email through SES will only work if your email address has been verified and you are not in sandbox mode.', 'thrive-dash' ) ?>
			<a href="https://docs.aws.amazon.com/ses/latest/DeveloperGuide/request-production-access.html"
					target="_blank"><?php echo esc_html__( 'Learn more', 'thrive-dash' ) ?></a>.
		</p>
	</div>
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
