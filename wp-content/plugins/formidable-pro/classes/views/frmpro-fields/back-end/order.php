<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

$field_option_order = isset( $field['option_order'] ) ? 'option_order' : 'lookup_option_order';
?>
<p class="frm6 frm_form_field">
	<label class="frm-h-stack-xs">
		<span><?php esc_html_e( 'Option order', 'formidable-pro' ); ?></span>
		<?php
		FrmAppHelper::tooltip_icon(
			__( 'Set the order for the values in your field.', 'formidable-pro' ),
			array(
				'data-placement' => 'right',
				'class'          => 'frm-flex',
			)
		);
		?>
	</label>
	<select name="field_options[<?php echo esc_attr( $field_option_order ) . '_' . esc_attr( $field['id'] ); ?>]">
		<option value="ascending" <?php selected( $field[ $field_option_order ], 'ascending' ); ?>>
			<?php esc_html_e( 'Ascending (A-Z)', 'formidable-pro' ); ?>
		</option>
		<option value="descending" <?php selected( $field[ $field_option_order ], 'descending' ); ?>>
			<?php esc_html_e( 'Descending (Z-A)', 'formidable-pro' ); ?>
		</option>
		<option value="no_order" <?php selected( $field[ $field_option_order ], 'no_order' ); ?>>
			<?php esc_html_e( 'No order set', 'formidable-pro' ); ?>
		</option>
	</select>
</p>
