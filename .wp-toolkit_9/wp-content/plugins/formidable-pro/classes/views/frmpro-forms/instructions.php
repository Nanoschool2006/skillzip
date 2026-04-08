<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

FrmAppHelper::show_search_box(
	array(
		'input_id'    => 'default-value-field',
		'placeholder' => __( 'Search Smart Tags', 'formidable-pro' ),
		'tosearch'    => 'search-smart-tags',
		'class'       => 'frm-mt-sm',
	)
);
?>

<div id="frm-dynamic-values">
	<ul class="frm_code_list frm-full-hover frm-short-list">
		<?php
		foreach ( $tags as $tag => $label ) {
			$title = '';

			if ( is_array( $label ) ) {
				$title = $label['title'] ?? '';
				$label = $label['label'] ?? reset( $label );
			}

			?>
			<li class="search-smart-tags">
				<a href="javascript:void(0)" data-code="<?php echo esc_attr( $tag ); ?>" class="show_dyn_default_value frm_insert_code
					<?php
					if ( ! empty( $title ) ) {
						echo ' frm_help" title="' . esc_attr( $title );
					}
					?>">
					<span><?php echo esc_html( $label ); ?></span>
					<span class="frm-text-grey-500">[<?php echo esc_html( $tag ); ?>]</span>
				</a>
			</li>
			<?php
			unset( $tag, $label );
		}
		?>
	</ul>
	<p class="howto frm-italic frm-mt-sm">
		<?php esc_html_e( 'Click smart value to dynamically populate this field. Smart values are not used when editing entries.', 'formidable-pro' ); ?>
	</p>
</div>
