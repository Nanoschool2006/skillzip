.frm_ai_response .frm_ai_loading {
	height: 60px;
	margin: 20px 0;
	display: none;
	-webkit-mask-image: var(--ai-loader);
	mask-image: var(--ai-loader);
	-webkit-mask-repeat: no-repeat;
	mask-repeat: no-repeat;
	-webkit-mask-position: center;
	mask-position: center;
	background: var(--border-color);
}

div.frm_description .frm_ai_default p:last-child {
	margin-bottom: 0 !important;
}

div.frm_description.frm_ai_answer p:last-child {
	margin-bottom: var(--form-desc-margin-top) !important;
}

.with_frm_style div.frm_description.frm_ai_answer {
	border-radius: var(--border-radius);
	border: var(--border-width) solid var(--border-color);
	padding: var(--field-pad);
	background-color: <?php echo esc_html( $bg_color ); ?>;
}

.with_frm_style div.frm_description.frm_ai_answer p {
	font-size: var(--font-size) !important;
	color: var(--text-color) !important;
	margin-bottom: 0;
}

.with_frm_style div.frm_description.frm_ai_answer ul {
	margin-top: 0;
	padding-left: 15px;
}

.with_frm_style div.frm_description.frm_ai_answer h5 {
	margin-bottom: 0;
}

.with_frm_style .frm_none_container .frm_ai_response {
	display: none;
}

