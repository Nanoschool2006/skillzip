<div id="cmplz-tcf-wrapper-nojavascript">
	<?php esc_html_e(_x( "The TCF vendorlist is not available when JavaScript is disabled, for example when using AMP.",'legal document', "complianz-gdpr" )) ?>
</div>
<div id="cmplz-tcf-wrapper">
	<div id="cmplz-tcf-vendor-template" class="cmplz-tcf-template">
		<div class="cmplz-tcf-vendor-container cmplz-tcf-optout cmplz-tcf-checkbox-container">
			<label for="cmplz-tcf-vendor-{vendor_id}">
				{vendor_name}
			</label>
			<div class="cmplz-tcf-links">
				<div class="cmplz-tcf-optout-url">
					<a target="_blank" rel="noopener noreferrer nofollow" href="{optout_url}">
						<?php esc_html_e(__( "Opt-out", "complianz-gdpr" )) ?>
					</a>
				</div>
				<div class="cmplz-tcf-optout-string">{optout_string}</div>
			</div>
		</div>
	</div>
	<div id="cmplz-tcf-type-template" class="cmplz-tcf-template"></div>
	<p id="cmplz-tcf-us-vendor-container" class="cmplz-tcf-container"></p>
</div>
<style>#cmplz-tcf-wrapper {display: none;}</style>
