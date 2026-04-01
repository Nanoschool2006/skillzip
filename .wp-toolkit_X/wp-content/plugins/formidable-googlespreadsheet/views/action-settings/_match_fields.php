<?php if ( is_array( $headers ) ) : ?>
	<?php foreach ( $headers as $header ) : ?>
			<p class="frm6">
				<label>
					<?php echo esc_html( $header['label'] ? ucfirst( $header['label'] ) : '&nbsp;' ); ?>
				</label>
				<input type="text" value="<?php echo isset( $list_options['fields'][ $header['id'] ] ) ? esc_attr( $list_options['fields'][ $header['id'] ] ) : ''; ?>" name="<?php echo esc_attr( $action_control->get_field_name( 'fields' ) ); ?>[<?php echo esc_attr( $header['id'] ); ?>]" id="<?php echo esc_attr( $action_control->get_field_id( 'fields' ) ); ?>[<?php echo esc_attr( $header['id'] ); ?>]" class="frm_not_email_message" />
			</p>
	<?php endforeach; ?>
<?php endif; ?>
