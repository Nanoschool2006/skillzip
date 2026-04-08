<div class="cmplz-tcf-{type}-container cmplz-tcf-checkbox-container">
	<label for="cmplz-tcf-{type}-{type_id}">
		<input id="cmplz-tcf-{type}-{type_id}" class="cmplz-tcf-{type}-input cmplz-tcf-input" value="1" type="checkbox" name="cmplz-tcf-{type}-{type_id}">
		{type_name} | <?php esc_html_e(sprintf(__("Used by %s vendors", "complianz-gdpr"), '{type_count}'))?><a href="#" class="cmplz-tcf-toggle cmplz-tcf-rm"></a>
		<div id="cmplz-tcf-{type}-{type_id}-desc" class="cmplz-tcf-type-description">
			<div class="cmplz-tcf-type-desc__text">{type_description}</div>
			<div class="cmplz-tcf-type-desc__example-title"><?php esc_html_e(__("Example","complianz-gdpr"))?></div>
			<div class="cmplz-tcf-type-desc__example">{type_example}</div>
		</div>

		<div id="cmplz-tcf-{type}-{type_id}-desc" class="cmplz-tcf-type-description">{type_description}</div>
	</label>
</div>
