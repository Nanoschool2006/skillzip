<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}
?>
<div class="<?php echo esc_attr( $class_attr ); ?>">
	<div class="frm_grid_container">
		<?php $field_obj->show_get_options( $field ); ?>

		<div class="frm_form_field">
			<p class="frm_primary_label<?php echo ! empty( $field['watch_lookup'] ) ? '' : ' frm-force-hidden'; ?>" id="frm_watch_lookup_label_<?php echo absint( $field['id'] ); ?>"><?php esc_html_e( 'Watch Lookup Fields', 'formidable-pro' ); ?></p>

			<div id="frm_watch_lookup_block_<?php echo absint( $field['id'] ); ?>" class="frm_add_remove">
				<?php
				if ( empty( $field['watch_lookup'] ) && ! empty( $lookup_fields ) ) {
					$field_id       = $field['id'];
					$row_key        = 0;
					$selected_field = '';
					include FrmProAppHelper::plugin_path() . '/classes/views/lookup-fields/back-end/watch-row.php';
				} elseif ( isset( $field['watch_lookup'] ) ) {
					$field_id = $field['id'];

					foreach ( $field['watch_lookup'] as $row_key => $selected_field ) {
						include FrmProAppHelper::plugin_path() . '/classes/views/lookup-fields/back-end/watch-row.php';
					}
				}
				?>
			</div>

			<a href="#" id="frm_add_watch_lookup_link_<?php echo esc_attr( $field['id'] ); ?>" class="frm_add_watch_lookup_row frm_add_watch_lookup_link frm-align-baseline">
				<span class="frm_add_tag frm-mr-2"><?php FrmAppHelper::icon_by_class( 'frmfont frm_plus1_icon frm_svg14' ); ?></span>
				<span><?php esc_html_e( 'Watch a Lookup Field', 'formidable-pro' ); ?></span>
			</a>
		</div>
	</div>

	<p class="frm-mt-xs">
		<label class="frm-flex frm-items-center" for="get_most_recent_value_<?php echo absint( $field['id'] ); ?>">
			<input type="checkbox" value="1" name="field_options[get_most_recent_value_<?php echo absint( $field['id'] ); ?>]" <?php checked( $field['get_most_recent_value'] ?? 0, 1 ); ?> id="get_most_recent_value_<?php echo absint( $field['id'] ); ?>" />
			<?php esc_html_e( 'Get only the most recent value', 'formidable-pro' ); ?>
		</label>
	</p>
</div>
