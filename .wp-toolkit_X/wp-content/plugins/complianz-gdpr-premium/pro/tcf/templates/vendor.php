<div class="cmplz-tcf-vendor-container cmplz-tcf-optin cmplz-tcf-checkbox-container">
	<label for="cmplz-tcf-vendor-{vendor_id}">
		<input id="cmplz-tcf-{vendor_id}" class="cmplz-tcf-vendor-input" value="1" type="checkbox" name="cmplz-tcf-vendor-{vendor_id}">
		{vendor_name}
		<div class="cmplz-vendortype-label"><?php esc_html_e(__("Non-TCF vendor","complianz-gdpr"))?></div>
		<a href="#" class="cmplz-tcf-toggle-vendor cmplz-tcf-rm"></a>
	</label>
	<div class="cmplz-tcf-links">
        <div class="cmplz-tcf-policy-url cmplz-tcf-privacy-link"><a target="_blank" rel="noopener noreferrer nofollow" href="{privacy_policy}"><?php esc_html_e(__("View Privacy Policy","complianz-gdpr"))?></a></div>
        <div class="cmplz-tcf-policy-url cmplz-tcf-legint-link"><a target="_blank" rel="noopener noreferrer nofollow" href="{legitimate_interest}"><?php esc_html_e(__("View Legitimate Interest Claim","complianz-gdpr"))?></a></div>
	</div>
	<div class="cmplz-tcf-info">
		<div class="cmplz-tcf-info-content">
			<div class="cmplz-tcf-header"><?php esc_html_e(__("Legal bases", 'complianz-gdpr'))?></div>
			<div class="cmplz-tcf-description">
				<label for="consent_{vendor_id}">
					<input type="checkbox" name="consent_{vendor_id}" class="cmplz-tcf-consent-input">
					<a target="_blank" rel="noopener noreferrer nofollow" href="https://cookiedatabase.org/tcf/consent"><?php esc_html_e(__("Consent", 'complianz-gdpr'))?></a>
				</label>
				<label for="legitimate_interest_{vendor_id}" class="cmplz_tcf_legitimate_interest_checkbox">
					<input type="checkbox" name="legitimate_interest_{vendor_id}" class="cmplz-tcf-legitimate-interest-input">
					<a target="_blank" rel="noopener noreferrer nofollow" href="https://cookiedatabase.org/tcf/legitimate-interest"><?php esc_html_e(__("Legitimate Interest", 'complianz-gdpr'))?></a>
				</label>
			</div>
		</div>
		<div class="cmplz-tcf-info-content">
			<div class="cmplz-tcf-header"><?php esc_html_e(__("Maximum cookie expiration:", 'complianz-gdpr'))?></div>
			<div class="cmplz-tcf-description">&nbsp;
				<span class="session-storage"><?php esc_html_e(__("Session Storage", "complianz-gdpr"))?></span>
				<span class="retention_days"><?php esc_html_e(cmplz_sprintf(__("%s Days", "complianz-gdpr"), '{cookie_retention_days}'))?></span>
				<span class="retention_seconds"><?php esc_html_e(cmplz_sprintf(__("%s Seconds", "complianz-gdpr"), '{cookie_retention_seconds}'))?></span>
			</div>
		</div>
		<div class="cmplz-tcf-info-content">
			<div class="cmplz-tcf-header"><?php esc_html_e(__("Non-cookie storage and access:", 'complianz-gdpr'))?></div>
			<div class="cmplz-tcf-description">&nbsp;<span class="non-cookie-storage-active"><?php esc_html_e(__("Yes", "complianz-gdpr"))?></span>
				<span class="non-cookie-storage-inactive"><?php esc_html_e(__("No", "complianz-gdpr"))?></span></div>
		</div>
		<div class="cmplz-tcf-info-content">
			<div class="cmplz-tcf-header"><?php esc_html_e(__("Cookie Refresh:", 'complianz-gdpr'))?></div>
			<div class="cmplz-tcf-description">&nbsp;<span class="non-cookie-refresh-active"><?php esc_html_e(__("Yes", "complianz-gdpr"))?></span>
				<span class="non-cookie-refresh-inactive"><?php esc_html_e(__("No", "complianz-gdpr"))?></span></div>
		</div>
		<div class="cmplz-tcf-info-content">
			<div class="cmplz-tcf-header"><?php esc_html_e(__("Categories:", 'complianz-gdpr'))?></div>
			<div class="cmplz-tcf-description">{vendor_categories}</div>
		</div>
		<div class="cmplz-tcf-info-content">
			<div class="cmplz-tcf-header"><?php esc_html_e(__("Purposes", 'complianz-gdpr'))?></div>
			<div class="cmplz-tcf-description">{purposes}</div>
		</div>
		<div class="cmplz-tcf-info-content cmplz-tcf-data-retention-section">
			<div class="cmplz-tcf-header"><?php esc_html_e(__("Data Retention", 'complianz-gdpr'))?></div>
			<div class="cmplz-tcf-description">{data_retention}</div>
		</div>
	</div>
</div>
