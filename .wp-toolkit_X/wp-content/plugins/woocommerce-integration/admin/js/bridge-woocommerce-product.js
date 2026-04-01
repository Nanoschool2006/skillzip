jQuery( document ).ready(function(){

	if (jQuery( '.moodle_post_course_id' ).length) {
		jQuery( '.moodle_post_course_id' ).select2({
		  placeholder: adminProduct.placeholder
		});
	}

	//jQuery("#variable_product_options_inner > div.woocommerce_variations.wc-metaboxes").on('click',function(){
    jQuery("#variable_product_options").on('click',function(){    
        if(!jQuery(this).find('.moodle_post_course_id' ).data('select2')){
            jQuery(this).find('.moodle_post_course_id' ).select2({
                placeholder: adminProduct.placeholder
            });    
        }
    });
		
});

