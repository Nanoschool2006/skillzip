<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

$form_id                                  = $values['id'];
list( $landing_page_url, $url_is_active ) = FrmLandingSettingsController::get_landing_page_url( $form_id );
$landing_page_post_id = FrmLandingAppHelper::get_landing_page_post_id( $form_id );

$delete_post_url = add_query_arg(
	array(
		'page'        => 'formidable',
		'frm_action'  => 'settings',
		'id'          => $form_id,
		'delete_landing_page' => 1,
		'_wpnonce'    => wp_create_nonce( 'frm_delete_landing_nonce' ),
	),
	admin_url( 'admin.php' )
);
?>

<p class="howto"><?php esc_html_e( 'Create form landing pages without distractions.', 'formidable-landing' ); ?></p>

<p class="with_frm_style frm_form_field">
	<?php
	self::toggle(
		'frm_landing_toggle',
		'options[landing_page_id]',
		array(
			'div_class' => 'with_frm_style frm_toggle',
			'checked'   => ! empty( $values['landing_page_id'] ),
			'echo'      => true,
		)
	);
	?>
	<label id="frm_landing_toggle_label" for="frm_landing_toggle">
		<?php esc_html_e( 'Generate form landing page', 'formidable-landing' ); ?>
	</label>
</p>

<p>
	<?php if ( current_user_can( 'delete_posts' ) ) { ?>
	<a
		href="<?php echo esc_url( $delete_post_url ); ?>"
		data-has-landing-post="<?php echo esc_attr( ! empty( $landing_page_post_id ) ); ?>"
		class="<?php echo empty( $values['landing_page_id'] ) && ! empty( $landing_page_post_id ) ? '' : esc_attr( 'frm_hidden' ); ?>"
		id="delete_landing_page_post"
		data-frmverify="<?php esc_attr_e( 'Delete the landing page?', 'formidable-landing' ); ?>"
		data-frmverify-btn="frm-button-red"
	>
		<?php esc_html_e( 'Click here to delete your landing page', 'formidable-landing' ); ?>
	</a>
	<?php } ?>
</p>

<div id="hide_landing_page" class="<?php echo empty( $values['landing_page_id'] ) ? 'frm_hidden' : ''; ?>">
<div class="frm_grid_container">

	<p class="frm_form_field frm6">
		<label for="frm_landing_page_url">
			<?php esc_html_e( 'Page URL', 'formidable-landing' ); ?>
		</label>
		<span id="frm_landing_page_url_input_wrapper">
			<span><?php echo esc_html( home_url() ); ?>/</span>
			<input type="text" name="frm_landing_page_url" value="<?php echo esc_attr( $landing_page_url ); ?>" original-page-url="<?php echo $url_is_active ? esc_attr( $landing_page_url ) : ''; ?>" placeholder="<?php esc_html_e( 'my-form', 'formidable-landing' ); ?>" />
		</span>
		<span id="frm_landing_page_url_validation"></span>
	</p>

	<p class="frm6 frm_form_field" style="align-self:end">
	<?php if ( $url_is_active ) { ?>
		<a href="<?php echo esc_attr( admin_url( 'post.php?post=' . $landing_page_post_id . '&action=edit' ) ); ?>" target="_blank" rel="noopener">
			<?php esc_html_e( 'Edit Landing Page', 'formidable-landing' ); ?>
		</a>
	<?php } ?>
	</p>

	<div class="frm6 frm_form_field frm_image_preview_wrapper">
		<input type="hidden" class="frm_image_id" name="options[landing_bg_image_id]" value="<?php echo esc_attr( $bg_image_id ); ?>" />
		<div class="frm_image_preview_frame <?php echo $bg_image_id ? '' : 'frm_hidden'; ?>">
			<div class="frm_image_styling_frame">
				<?php echo $bg_image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<div class="frm_image_data">
					<div class="frm_image_preview_title"><?php echo esc_attr( $bg_image_filename ); ?></div>
					<div href="javascript:void(0)" class="frm_remove_image_option" title="<?php esc_attr_e( 'Remove image', 'formidable-pro' ); ?>">
						<?php FrmAppHelper::icon_by_class( 'frm_icon_font frm_delete_icon' ); ?>
						<?php esc_attr_e( 'Delete', 'formidable-pro' ); ?>
					</div>
				</div>
			</div>
		</div>
		<p id="frm_choose_image_box_cont" class="<?php echo $bg_image_id ? ' frm_hidden' : ''; ?>" style="margin-top:0">
			<label class="frm_invisible">
				<?php esc_html_e( 'Upload Image', 'formidable-landing' ); ?>
			</label>
			<button type="button" class="frm_choose_image_box frm_button frm_no_style_button">
				<?php FrmAppHelper::icon_by_class( 'frm_icon_font frm_upload_icon' ); ?>
				<?php esc_attr_e( 'Upload background image', 'formidable-pro' ); ?>
			</button>
		</p>
	</div>

	<p class="frm6 frm_form_field hide_landing_layout hide_hide_landing_layout_left hide_hide_landing_layout_right <?php echo ( ( 'left' === $values['landing_layout'] || 'right' === $values['landing_layout'] ) ? 'frm_hidden' : '' ); ?>">
		<label for="frm_landing_opacity">
			<?php esc_html_e( 'Image Opacity', 'formidable-pro' ); ?>
		</label>
		<input type="number" min="0" max="100" step="1" placeholder="100" name="options[landing_opacity]" value="<?php echo esc_attr( $values['landing_opacity'] ); ?>" id="frm_landing_opacity" />
	</p>

	<div class="frm_image_options frm_form_field">
		<h3><?php esc_html_e( 'Select Design', 'formidable-landing' ); ?></h3>
		<div class="frm_grid_container">
			<?php
			foreach ( $layouts as $layout ) {
				include self::get_settings_views_path() . '_single_image.php';
			}
			?>
		</div>
	</div>
</div>
</div>
