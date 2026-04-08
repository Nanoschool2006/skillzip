<?php
/**
 * Shows a single user meta row.
 *
 * @package FrmReg
 *
 * @var int          $meta_key
 * @var int          $meta_name
 * @var FrmRegAction $action_control
 * @var int          $field_id
 * @var array        $fields
 * @var bool         $echo
 */
?>

<div id="frm_user_meta_<?php echo esc_attr( $meta_key ); ?>" class="frm_user_meta_row frm_grid_container">
	<div class="frm4 frm_form_field">
		<label class="screen-reader-text" for="<?php echo esc_attr( 'meta_name_' . $meta_key ); ?>">
			<?php esc_html_e( 'Name', 'frmreg' ); ?>
		</label>
		<input type="text" id="<?php echo esc_attr( 'meta_name_' . $meta_key ); ?>" value="<?php echo esc_attr( $echo ? $meta_name : '' ); ?>" name="<?php echo esc_attr( $action_control->get_field_name( 'reg_usermeta' ) ); ?>[<?php echo esc_attr( $meta_key ); ?>][meta_name]"/>
	</div>

	<div class="frm7 frm_form_field">
		<label class="screen-reader-text" for="<?php echo esc_attr( 'meta_value_' . $meta_key ); ?>">
			<?php esc_html_e( 'Value', 'frmreg' ); ?>
		</label>
		<select id="<?php echo esc_attr( 'meta_value_' . $meta_key ); ?>" name="<?php echo esc_attr( $action_control->get_field_name( 'reg_usermeta' ) ); ?>[<?php echo esc_attr( $meta_key ); ?>][field_id]">
			<option value="">- <?php esc_html_e( 'Select Field', 'frmreg' ); ?> -</option>
			<?php
			if ( is_array( $fields ) ) {
				foreach ( $fields as $field ) {
					if ( ! FrmField::is_no_save_field( $field->type ) && FrmRegActionHelper::include_in_user_meta( $field, $field_id ) ) { ?>
						<option value="<?php echo esc_attr( $field->id ); ?>" <?php selected( $field_id, $field->id ); ?>><?php echo esc_html( FrmAppHelper::truncate( $field->name, 50 ) ); ?></option>
						<?php
					}
				}
			}
			?>
		</select>
	</div>
	<div class="frm1 frm_form_field frm-inline-select">
			<a class="frm_remove_tag reg_remove_user_meta_row frm_icon_font" data-removeid="frm_user_meta_<?php echo esc_attr( $meta_key ); ?>" data-showlast="#frm_user_meta_add" data-hidelast="#frm_user_meta_table"></a>
			<a class="frm_add_tag frm_icon_font reg_add_user_meta_row" href="javascript:void(0)"></a>
	</div>
</div>
