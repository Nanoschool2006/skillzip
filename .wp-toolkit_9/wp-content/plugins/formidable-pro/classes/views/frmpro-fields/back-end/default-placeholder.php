<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

$has_more_icon_tabindex = isset( FrmAppHelper::add_allowed_icon_tags( array() )['svg']['tabindex'] );
?>
<h4 id="<?php echo esc_attr( $name . '_' . $field['id'] ); ?>" class="frm_primary_label frm-font-semibold frm-text-grey-600 frm-mt-sm frm-mb-xs">
	<?php echo esc_html( $field_label ); ?>
</h4>
<?php
if ( isset( $default_value ) && is_array( $default_value ) ) {
	?>
	<p class="frm6 frm_form_field">
		<label for="default_value_<?php echo esc_attr( $name . '_' . $field['id'] ); ?>" class="frm_description" id="label_default_<?php echo esc_attr( $name . '_' . $field['id'] ); ?>">
			<?php esc_html_e( 'Default Value', 'formidable' ); ?>
		</label>
		<span class="frm-with-right-icon frm-block">
			<?php if ( $has_more_icon_tabindex ) { // Backwards compatibility condition "@since 6.25". ?>
				<input type="text" name="default_value_<?php echo esc_attr( $field['id'] ); ?>[<?php echo esc_attr( $name ); ?>]" id="default_value_<?php echo esc_attr( $name . '_' . $field['id'] ); ?>" value="<?php echo esc_attr( $default_value[ $name ] ?? '' ); ?>" aria-labelledby="<?php echo esc_attr( $name . '_' . $field['id'] ); ?> label_default_<?php echo esc_attr( $name . '_' . $field['id'] ); ?>" data-changeme="field_<?php echo esc_attr( $field['field_key'] . '_' . $name ); ?>" data-changeatt="value" />
<?php
			}

			FrmAppHelper::icon_by_class(
				'frmfont frm_more_horiz_solid_icon frm-show-inline-modal frm-input-icon',
				array(
					'data-open' => 'frm-smart-values-box',
					'tabindex'  => 0,
				)
		 	);

			// Backwards compatibility "@since 6.25".
			if ( ! $has_more_icon_tabindex ) {
		?>
				<input type="text" name="default_value_<?php echo esc_attr( $field['id'] ); ?>[<?php echo esc_attr( $name ); ?>]" id="default_value_<?php echo esc_attr( $name . '_' . $field['id'] ); ?>" value="<?php echo esc_attr( $default_value[ $name ] ?? '' ); ?>" aria-labelledby="<?php echo esc_attr( $name . '_' . $field['id'] ); ?> label_default_<?php echo esc_attr( $name . '_' . $field['id'] ); ?>" data-changeme="field_<?php echo esc_attr( $field['field_key'] . '_' . $name ); ?>" data-changeatt="value" />
	<?php } ?>
		</span>
	</p>
	<?php
}

$sub      = 'placeholder';
$label    = __( 'Placeholder Text', 'formidable' );
$subname  = $name . '_' . $sub;
$field_id = 'field_options_' . $subname . '_' . $field['id'];
?>
<p class="frm6 frm_form_field">
	<label for="<?php echo esc_attr( $field_id ); ?>" class="frm_description" id="label_<?php echo esc_attr( $subname . '_' . $field['id'] ); ?>">
		<?php echo esc_html( $label ); ?>
	</label>
	<input type="text" name="field_options[<?php echo esc_attr( $sub . '_' . $field['id'] ); ?>][<?php echo esc_attr( $name ); ?>]" id="<?php echo esc_attr( $field_id ); ?>" value="<?php echo esc_attr( $field[ $sub ][ $name ] ?? '' ); ?>" aria-labelledby="<?php echo esc_attr( $name . '_' . $field['id'] ); ?> label_<?php echo esc_attr( $subname . '_' . $field['id'] ); ?>" data-changeme="field_<?php echo esc_attr( $field['field_key'] . '_' . $name ); ?>" data-changeatt="placeholder" />
</p>

<?php
$sub      = 'desc';
$label    = __( 'Description', 'formidable' );
$subname  = $name . '_' . $sub;
$field_id = 'field_options_' . $subname . '_' . $field['id'];
?>
<p class="frm_form_field">
	<label for="<?php echo esc_attr( $field_id ); ?>" class="frm_description" id="label_<?php echo esc_attr( $subname . '_' . $field['id'] ); ?>">
		<?php echo esc_html( $label ); ?>
	</label>
	<input type="text" name="field_options[<?php echo esc_attr( $subname . '_' . $field['id'] ); ?>]" id="<?php echo esc_attr( $field_id ); ?>" value="<?php echo esc_attr( $field[ $subname ] ?? '' ); ?>" aria-labelledby="<?php echo esc_attr( $name . '_' . $field['id'] ); ?> label_<?php echo esc_attr( $subname . '_' . $field['id'] ); ?>" data-changeme="field_<?php echo esc_attr( $subname . '_' . $field['id'] ); ?>" />
</p>
