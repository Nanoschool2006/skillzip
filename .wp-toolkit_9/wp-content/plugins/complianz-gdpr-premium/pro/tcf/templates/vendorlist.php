<div id="cmplz-tcf-wrapper-nojavascript"><?php esc_html_e(_x("The TCF vendorlist is not available when JavaScript is disabled, like on AMP.",'legal document',"complianz-gdpr"))?></div>
<div id="cmplz-tcf-wrapper">
	<div id="cmplz-tcf-buttons-template">
		<div class="cmplz-tcf-buttons">
			<button id="cmplz-tcf-selectall"><?php esc_html_e(__("Select all","complianz-gdpr"))?></button>
			<button id="cmplz-tcf-deselectall"><?php esc_html_e(__("Deselect all","complianz-gdpr"))?></button>
		</div>
	</div>
	<div id="cmplz-tcf-vendor-template" class="cmplz-tcf-template">{vendor_template}</div>
	<div id="cmplz-tcf-type-template" class="cmplz-tcf-template">{checkbox}</div>
	<p><?php esc_html_e(__("These are the partners we share data with. By clicking into each partner, you can see which purposes they are requesting consent and/or which purposes they are claiming legitimate interest for.","complianz-gdpr"))?></p>
	<p><?php esc_html_e(__("You can provide or withdraw consent, and object to legitimate interest purposes for processing your personal data. However, please note that by disabling all data processing, some site functionality may be affected.","complianz-gdpr"))?></p>
	<p class="cmplz-subtitle">7.2.1 <?php esc_html_e(__("Consent","complianz-gdpr"))?></p>
	<p><?php esc_html_e(__("Below you can give and withdraw your consent on a per purpose basis.","complianz-gdpr"))?></p>
	<b><?php esc_html_e(__("Statistics","complianz-gdpr"))?></b>
	<p id="cmplz-tcf-statistics-purpose_consents-container" class="cmplz-tcf-container"></p>
	<b><?php esc_html_e(__("Marketing","complianz-gdpr"))?></b>
	<p id="cmplz-tcf-marketing-purpose_consents-container" class="cmplz-tcf-container"></p>

	<p class="cmplz-subtitle">7.2.2 <?php esc_html_e(__("Legitimate Interest","complianz-gdpr"))?></p>
	<p><?php esc_html_e(__("Some Vendors set purposes with legitimate interest, a legal basis under the GDPR for data processing. You have the \"Right to Object\" to this data processing and can do so below per purpose.","complianz-gdpr"))?></p>
	<b><?php esc_html_e(__("Statistics","complianz-gdpr"))?></b>
	<p id="cmplz-tcf-statistics-purpose_legitimate_interests-container" class="cmplz-tcf-container"></p>

	<b><?php esc_html_e(__("Marketing","complianz-gdpr"))?></b>
	<p id="cmplz-tcf-marketing-purpose_legitimate_interests-container" class="cmplz-tcf-container"></p>

	<p class="cmplz-subtitle">7.2.2 <?php esc_html_e(__("Special features and purposes","complianz-gdpr"))?></p>
	<div id="cmplz-tcf-specialfeatures-wrapper">
		<b><?php esc_html_e(__("Special features","complianz-gdpr"))?></b>
		<p><?php esc_html_e(__("For some of the purposes we and/or our partners use below features.", "complianz-gdpr"))?></p>
		<p id="cmplz-tcf-specialfeatures-container" class="cmplz-tcf-container"></p>
	</div>

	<div id="cmplz-tcf-specialpurposes-wrapper">
		<b><?php esc_html_e(__("Special purposes","complianz-gdpr"))?></b>
		<p><?php esc_html_e(__( "We and/or our partners have a legitimate interest for the following two purposes:", "complianz-gdpr" ))?></p>
		<p id="cmplz-tcf-specialpurposes-container" class="cmplz-tcf-container"></p>
	</div>

	<div id="cmplz-tcf-features-wrapper">
		<b><?php esc_html_e(__("Features","complianz-gdpr"))?></b>
		<p><?php esc_html_e(__("For some of the purposes above we and our partners", "complianz-gdpr"))?></p>
		<p id="cmplz-tcf-features-container" class="cmplz-tcf-container"></p>
	</div>

	<p class="cmplz-subtitle">7.2.3 <?php esc_html_e(__("Vendors","complianz-gdpr"))?></p>

	<div id="cmplz-tcf-vendor-container" class="cmplz-tcf-container"></div>
</div>
<style>#cmplz-tcf-wrapper {display:none;}</style>
