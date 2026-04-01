<div class="error" id="frm_pll_install_message">
	<p>
		<?php
		printf(
			/* translators: %1$s: line break, %2$s: Start link HTML, %3$s: end link HTML */
			esc_html__( 'Your Formidable Polylang database needs to be updated.%1$sPlease deactivate and reactivate the plugin or %2$sUpdate Now%3$s', 'frm_pll' ),
			'<br/>',
			'<a id="frm_pll_install_link" href="javascript:frm_pll_install_now()">',
			'</a>'
		);
		?>
	</p>
</div>

<script type="text/javascript">
	function frm_pll_install_now(){
		jQuery('#frm_pll_install_link').replaceWith('<img src="<?php echo esc_url_raw( $url ); ?>/images/wpspin_light.gif" alt="<?php esc_attr_e( 'Loading&hellip;' ); ?>" />');
		jQuery.ajax({type:'POST',url:"<?php echo esc_url_raw( admin_url( 'admin-ajax.php' ) ); ?>",data:'action=frm_pll_install',
			success:function(msg){jQuery("#frm_pll_install_message").fadeOut('slow');}
		});
	}
</script>
