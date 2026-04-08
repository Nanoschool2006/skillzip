<?php
/**
 * Show the AI field when it's not hidden.
 *
 * @package FrmAI
 *
 * @var array $settings The info about the field.
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}
?>
<style>.frm-show-form{--ai-loader:url(<?php echo FrmAIAppHelper::loading_svg(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>);}</style>
<script>if(typeof __FRMAI==='undefined'){__FRMAI=[];}__FRMAI.push(<?php echo wp_json_encode( $settings ); ?>);</script>
