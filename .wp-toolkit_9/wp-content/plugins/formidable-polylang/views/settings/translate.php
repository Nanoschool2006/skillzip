<div id="form_settings_page" class="frm_wrap frm_list_entry_page">
	<div class="frm_page_container">
	<?php
	FrmAppHelper::get_admin_header(
		array(
			'form'       => $form,
			'label'      => __( 'Translate Form', 'formidable' ),
			'hide_title' => true,
		)
	);
	?>

	<div class="wrap">
		<?php include( FrmAppHelper::plugin_path() . '/classes/views/shared/errors.php' ); ?>
		<div id="poststuff" class="metabox-holder">
			<div id="post-body">
			<?php
			if ( empty( $listlanguages ) ) {
				esc_html_e( 'Please add a language in Polylang for translations to appear.', 'formidable-polylang' );
			} else {
				if ( file_exists( PLL_ADMIN_INC . '/view-tab-strings.php' ) ) {
					$file_name = PLL_ADMIN_INC . '/view-tab-strings.php';
				} else {
					$file_name = PLL_SETTINGS_INC . '/view-tab-strings.php';
				}
				ob_start();
				include( $file_name );
				$form = ob_get_contents();
				ob_end_clean();

				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo str_replace(
					array( 'frm_action=translate', 'noheader=true' ),
					array( 'frm_action=update_translations', '' ),
					$form
				);
			}
			?>
			</div>
		</div>

	</div>
</div>
</div>

<style>
.tablenav .bulkactions, .tablenav .actions, .search-box, .check-column,
#string-translation > label, #string-translation > p{display:none;}
#string-translation > p.submit{display:block;}
textarea{min-height:60px;}
</style>
