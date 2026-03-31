jQuery(document).ready(function($){

	// Jquery table sortable intialization.
    $( ".eb_wi_custom_field_tbl>tbody" ).disableSelection();


    // Pop up design.
    $('.eb_wi_cf_add_new_field_btn').click(function(event){
		event.preventDefault();
	    row_obj = this;
	    // Now empty the dialog box.
	    wi_cf_clear_pop_up_fields_data();

    	$('.wi_cf_error_msg').css('display', 'none');
    	$('.wi_cf_empty_table').css('display', 'none');
    	$('.eb-wi-cf-pop-up-cont').dialog({
    		height: 600,
		    width: 750,
		    modal: true,
		    resizable: false,
		    dialogClass: 'eb-wi-cf-dialog',
		    buttons: [
	            {
	                text: wi_custom_fields.dialog_save_btn,
	                class: 'eb-wi-cf-dialog-btn button-primary',
	                click: function () {
	                	// Validate fields check if name exists in other rows.
	                	var field_name = $('input[name="eb-wi-cf-dialog-name"]').val();
	                	var validtion_msg = validate_field_name(field_name, row_obj);

	                	if('' == field_name || 1 != validtion_msg){
	                		$('.wi_cf_error_msg').html(validtion_msg);
	                		return;
	                	}

	                	// Functionality to add new column in table.
	                	wi_cf_create_new_row();

	                	// Now empty the dialog box.
	                	// wi_cf_clear_pop_up_fields_data();

	                    $(this).dialog("close");
	                }
	            },
	            {
	            	text: wi_custom_fields.dialog_cancel_btn,
	            	class: 'eb-wi-cf-dialog-cancel-btn',
	            	click: function(){
	            		// Empty the dialog box.
	                	wi_cf_clear_pop_up_fields_data();
	            		// now close the dialog box.
	                    $(this).dialog("close");
	            	}
	            }
	        ]
		});
    });



    // $('.eb-wi-cf-edit').click(function(event){
    $(document).on('click', '.eb-wi-cf-edit', function (event) {
		event.preventDefault();

    	$('.wi_cf_error_msg').css('display', 'none');

	    // Get data of the existing column.
	    edit_row_obj = this;
	    wi_cf_set_pop_up_data(edit_row_obj);

    	$('.eb-wi-cf-pop-up-cont').dialog({
    		height: 600,
		    width: 700,
		    modal: true,
		    resizable: false,
		    dialogClass: 'eb-wi-cf-dialog',
		    buttons: [
	            {
	                text: wi_custom_fields.dialog_save_btn,
	                class: 'eb-wi-cf-dialog-btn button-primary',
	                click: function () {
	                	var field_name = $('input[name="eb-wi-cf-dialog-name"]').val();
	                	var validtion_msg = validate_field_name(field_name, edit_row_obj);

	                	if('' == field_name || 1 != validtion_msg){
	                		$('.wi_cf_error_msg').html(validtion_msg);
	                		return;
	                	}

	                	// Modify curret row with the pop up data for data save functionality.
	                	wi_cf_modify_existing_row(edit_row_obj);

	                	// Now empty the dialog box.
	                	wi_cf_clear_pop_up_fields_data();

	                    $(this).dialog("close");
	                }
	            },
	            {
	            	text: wi_custom_fields.dialog_cancel_btn,
	            	class: 'eb-wi-cf-dialog-cancel-btn',
	            	click: function(){
	            		// Empty the dialog box.
	                	wi_cf_clear_pop_up_fields_data();

	            		// now close the dialog box.
	                    $(this).dialog("close");
	            	}
	            }
	        ]
		});
    });



    function validate_field_name(dialog_value, row_obj){
		var msg = 1;
    	parent_tr  = $(row_obj).parent().parent();
    	parent_tr  = $(parent_tr).find('.eb-wi-cf-tbl-name');

    	if('' == dialog_value){
    		$('.wi_cf_error_msg').css('display', 'block');
		    msg = wi_custom_fields.dialog_field_name_validation;
    	} else {
		    $('.eb-wi-cf-tbl-name').not(parent_tr).each(function(index, element){
		    	if (dialog_value == $(element).val()) {
    				$('.wi_cf_error_msg').css('display', 'block');
		    		msg = wi_custom_fields.dialog_field_name_validation;
		    		return;
		    	}
		    });
    	}

	    return msg;
	}


    $("input[name='eb-wi-cf-dialog-name']").on({
	  keydown: function(e) {
	    if (e.which === 32)
	      return false;
	  },
	  change: function() {
	    this.value = this.value.replace(/\s/g, "");
	  }
	});


    // jQuery('.eb_wi_custom_fields_wrap').bind('DOMSubtreeModified', function(){
    /*jQuery('.eb_wi_custom_fields_wrap').bind('beforeunload', function(){

	});*/



    /* Create new row and add in table from the dialog data, on the clieck of ad new field button. */
    function wi_cf_create_new_row()
    {
    	data_array = wi_cf_get_pop_up_data();
    	html = '<tr class="ui-sortable-handle">'+
    				'<td style="width: 5%; text-align:center;"> <span class="dashicons dashicons-menu"></span> </td>'+
					'<td><input type="checkbox" name="" class="wi_cf_bulk_action_cb"></td>'+
					'<td>'+
						'<span class="eb-wi-cf-tbl-name-lbl">'+ data_array['name'] +'</span>'+
						'<input type="hidden" class="eb-wi-cf-tbl-name" name="eb-wi-cf-tbl-name[]" value="'+ data_array['name'] +'">'+
					'</td>'+
					'<td>'+
						'<span class="eb-wi-cf-tbl-type-lbl">'+ data_array['type'] +'</span>'+
						'<input type="hidden" class="eb-wi-cf-tbl-type" name="eb-wi-cf-tbl-type[]" value="'+ data_array['type'] +'">'+
					'</td>'+
					'<td>'+
						'<span class="eb-wi-cf-tbl-label-lbl">'+ data_array['label'] +'</span>'+
						'<input type="hidden" class="eb-wi-cf-tbl-label" name="eb-wi-cf-tbl-label[]" value="'+ data_array['label'] +'">'+
					'</td>'+
					'<td>'+
						'<span class="eb-wi-cf-tbl-placeholder-lbl">'+ data_array['placeholder'] +'</span>'+
						'<input type="hidden" class="eb-wi-cf-tbl-placeholder" name="eb-wi-cf-tbl-placeholder[]" value="'+ data_array['placeholder'] +'">'+
					'</td>'+
					'<td>';


					if(data_array['required']){
						// html += '<span class="eb-wi-cf-tbl-enabled-lbl"> <span class="dashicons dashicons-saved"></span> </span>';
						html += '<span class="eb-wi-cf-tbl-required-lbl"> <span class="dashicons dashicons-saved"></span> </span>';
					} else {
						html += '<span class="eb-wi-cf-tbl-required-lbl"> - </span>';
					}

					html +=	'<input type="hidden" class="eb-wi-cf-tbl-required" name="eb-wi-cf-tbl-required[]" value="'+ data_array['required'] +'">'+
					'</td>'+
					'<td>';


					if(data_array['enabled']){
						html += '<span class="eb-wi-cf-tbl-enabled-lbl"> <span class="dashicons dashicons-saved"></span> </span>';
					} else {
						html += '<span class="eb-wi-cf-tbl-enabled-lbl"> - </span>';
					}
					html += '<input type="hidden" class="eb-wi-cf-tbl-enabled" name="eb-wi-cf-tbl-enabled[]" value="'+ data_array['enabled'] +'">'+
					'</td>';

					// Check if the selected field is select if yes then create one associative array for it and save it in the input value with json encode function. 
					if('select' == data_array['type']){

					}

					/*'<td> <span class="eb-wi-cf-edit button"> Edit </span> </td>'+*/
					html += '<td>'+
								'<span class="dashicons dashicons-edit-page eb-wi-cf-edit"></span>'+
								'<span class="dashicons dashicons-trash eb-wi-cf-remove"></span>'+
								'<input type="hidden" class="eb-wi-cf-tbl-class" name="eb-wi-cf-tbl-class[]" value="'+ data_array['class'] +'">'+
								'<input type="hidden" class="eb-wi-cf-tbl-default-val" name="eb-wi-cf-tbl-default-val[]" value="' + data_array['default-val'] +'">'+
								'<input type="hidden" class="eb-wi-cf-tbl-sync-on-moodle" name="eb-wi-cf-tbl-sync-on-moodle[]" value="'+ data_array['sync-on-moodle'] +'">'+
								'<input type="hidden" class="eb-wi-cf-tbl-woo-my-accnt" name="eb-wi-cf-tbl-woo-my-accnt[]" value="'+ data_array['woo-my-accnt'] +'">'+
								'<input type="hidden" class="eb-wi-cf-tbl-edwiser-user-accnt" name="eb-wi-cf-tbl-edwiser-user-accnt[]" value="'+ data_array['edwiser-user-accnt'] +'">'+
								'<input type="hidden" class="eb-wi-cf-tbl-options" name="eb-wi-cf-tbl-options[]" value="'+ data_array['options'] +'">'+
							'</td>';
					html += '</tr>';
		$('.eb_wi_custom_field_tbl tbody').append(html);

    }

    // This function used for setting pop-up data with the existing available data .
    function wi_cf_set_pop_up_data(edit_obj)
    {
    	parent_tr = $(edit_obj).parent().parent();
    	$('select[name="eb-wi-cf-dialog-type"]').val($(parent_tr).find('.eb-wi-cf-tbl-type').val());
    	$('input[name="eb-wi-cf-dialog-name"]').val($(parent_tr).find('.eb-wi-cf-tbl-name').val());
    	$('input[name="eb-wi-cf-dialog-label"]').val($(parent_tr).find('.eb-wi-cf-tbl-label').val());
    	$('input[name="eb-wi-cf-dialog-placeholder"]').val($(parent_tr).find('.eb-wi-cf-tbl-placeholder').val());
    	$('input[name="eb-wi-cf-dialog-class"]').val($(parent_tr).find('.eb-wi-cf-tbl-class').val());
    	$('input[name="eb-wi-cf-dialog-default-val"]').val($(parent_tr).find('.eb-wi-cf-tbl-default-val').val());


	   	$('input[name="eb-wi-cf-dialog-enabled"]').prop('checked', false);
    	$('input[name="eb-wi-cf-dialog-required"]').prop('checked', false);
    	$('input[name="eb-wi-cf-dialog-sync-moo"]').prop('checked', false);
    	$('input[name="eb-wi-cf-dialog-woo-my-accnt"]').prop('checked', false);
    	$('input[name="eb-wi-cf-dialog-eb-user-accnt"]').prop('checked', false);

    	// If field type is select then set the dialog box options data.

       	if ($(parent_tr).find('.eb-wi-cf-tbl-enabled').val() != 0) {
	    	// $('input[name="eb-wi-cf-dialog-enabled"]').val($(parent_tr).find('input[name="eb-wi-cf-dialog-enabled"]').val());
	    	$('input[name="eb-wi-cf-dialog-enabled"]').prop('checked', true);
    	}

    	if ($(parent_tr).find('.eb-wi-cf-tbl-required').val() != 0) {
	    	// $('input[name="eb-wi-cf-dialog-enabled"]').val($(parent_tr).find('input[name="eb-wi-cf-dialog-enabled"]').val());
	    	$('input[name="eb-wi-cf-dialog-required"]').prop('checked', true);
    	}

    	if ($(parent_tr).find('.eb-wi-cf-tbl-sync-on-moodle').val() != 0) {
    		$('input[name="eb-wi-cf-dialog-sync-moo"]').prop('checked', true);
    	}

    	if ($(parent_tr).find('.eb-wi-cf-tbl-woo-my-accnt').val() != 0) {
    		$('input[name="eb-wi-cf-dialog-woo-my-accnt"]').prop('checked', true);
    	} else {
    	}

    	if ($(parent_tr).find('.eb-wi-cf-tbl-edwiser-user-accnt').val() != 0) {
    		$('input[name="eb-wi-cf-dialog-eb-user-accnt"]').prop('checked', true);
    	}

    	if ('select' == $(parent_tr).find('.eb-wi-cf-tbl-type').val()) {
    		// If selected type is select then start to show the options fields, Get options value and show them.
    		var options = $(parent_tr).find('.eb-wi-cf-tbl-options').val();
    		options = JSON.parse(options.replace(/\'/g, '"'));

	    	if($('.eb-wi-cf-pop-up-options-field').length){
	    		$('.eb-wi-cf-pop-up-options-field').removeClass('eb-wi-cf-hide');


	    		// Below code removes all option fields except the first one.
	    		var counter = 1;
	    		$('.eb-wi-cf-pop-up-options-field').each(function(index, element){
	    			if(counter > 1){
	    				$(element).remove();
	    			}
	    			counter++;
	    		});


	    		// Now as in above code all options fields are removed lets add it again one by one according to the options provided.
	    		var counter = 0;
	    		// for (var i = 0; i < options.length; i++) {
				$.each(options, function(index, value) {
	    			if (counter == 0) {
			    		$('.eb-wi-cf-pop-up-options-field').find('.eb-wi-cf-dialog-option-val').val(index);
			    		$('.eb-wi-cf-pop-up-options-field').find('.eb-wi-cf-dialog-option-txt').val(value);
	    			} else {
	    				var html_content = wi_cf_get_dynamic_options(index, value);
        				$('.eb-wi-cf-pop-up-options-field:last').after(html_cont);
	    			}

	    			counter++;
	    		});

	    	}
    	}

    }

    /* On the click of Edit button this function modifies the data in the table  */
    function wi_cf_modify_existing_row(edit_obj)
    {
    	parent_tr  = $(edit_obj).parent().parent();

    	$(parent_tr).find('.eb-wi-cf-tbl-type').val($('select[name="eb-wi-cf-dialog-type"]').val());
    	$(parent_tr).find('.eb-wi-cf-tbl-type-lbl').html($('select[name="eb-wi-cf-dialog-type"]').val());

    	$(parent_tr).find('.eb-wi-cf-tbl-name').val($('input[name="eb-wi-cf-dialog-name"]').val());
    	$(parent_tr).find('.eb-wi-cf-tbl-name-lbl').html($('input[name="eb-wi-cf-dialog-name"]').val());
    	
    	$(parent_tr).find('.eb-wi-cf-tbl-label').val($('input[name="eb-wi-cf-dialog-label"]').val());
    	$(parent_tr).find('.eb-wi-cf-tbl-label-lbl').html($('input[name="eb-wi-cf-dialog-label"]').val());
    	
    	$(parent_tr).find('.eb-wi-cf-tbl-placeholder').val($('input[name="eb-wi-cf-dialog-placeholder"]').val());
    	$(parent_tr).find('.eb-wi-cf-tbl-placeholder-lbl').html($('input[name="eb-wi-cf-dialog-placeholder"]').val());

    	$(parent_tr).find('.eb-wi-cf-tbl-class').val($('input[name="eb-wi-cf-dialog-class"]').val());
    	$(parent_tr).find('.eb-wi-cf-tbl-default-val').val($('input[name="eb-wi-cf-dialog-default-val"]').val());

		/*eb-wi-cf-tbl-validation-lbl*/
	    $(parent_tr).find('.eb-wi-cf-tbl-enabled').val(0);
    	$(parent_tr).find('.eb-wi-cf-tbl-enabled-lbl').html('-');
    	$(parent_tr).find('.eb-wi-cf-tbl-required').val(0);
    	$(parent_tr).find('.eb-wi-cf-tbl-required-lbl').html('-');
		$(parent_tr).find('.eb-wi-cf-tbl-sync-on-moodle').val(0);
    	$(parent_tr).find('.eb-wi-cf-tbl-woo-my-accnt').val(0);
    	$(parent_tr).find('.eb-wi-cf-tbl-edwiser-user-accnt').val(0);

    	/*eb-wi-cf-tbl-enabled-lbl*/
		if( $('input[name="eb-wi-cf-dialog-enabled"]').prop("checked") == true ) {
	    	$(parent_tr).find('.eb-wi-cf-tbl-enabled').val(1);
    		$(parent_tr).find('.eb-wi-cf-tbl-enabled-lbl').html('<span class="dashicons dashicons-saved"></span>');
    	}

    	if( $('input[name="eb-wi-cf-dialog-required"]').prop("checked") == true ) {
	    	$(parent_tr).find('.eb-wi-cf-tbl-required').val(1);
    		$(parent_tr).find('.eb-wi-cf-tbl-required-lbl').html('<span class="dashicons dashicons-saved"></span>');
    	} 

    	if ($('input[name="eb-wi-cf-dialog-sync-moo"]').prop("checked") == true) {
	    	$(parent_tr).find('.eb-wi-cf-tbl-sync-on-moodle').val(1);
    	}

    	if ($('input[name="eb-wi-cf-dialog-woo-my-accnt"]').prop("checked") == true) {
	    	$(parent_tr).find('.eb-wi-cf-tbl-woo-my-accnt').val(1);
    	}

    	if ($('input[name="eb-wi-cf-dialog-eb-user-accnt"]').prop("checked") == true) {
	    	$(parent_tr).find('.eb-wi-cf-tbl-edwiser-user-accnt').val(1);
    	}

    	data_array = {};
    	// Creating options array if selected field type is select
    	if ('select' == $('select[name="eb-wi-cf-dialog-type"]').val()) {
	    	jQuery('.eb-wi-cf-pop-up-options-field').each(function(index, element){
	    		if ('' != $(element).find('.eb-wi-cf-dialog-option-val').val()) {
					data_array[$(element).find('.eb-wi-cf-dialog-option-val').val()] = $(element).find('.eb-wi-cf-dialog-option-txt').val();
	    		}
			});

    		// data_array['options'] = JSON.stringify(data_array['options']).replace('"', "'");
    		data_array = JSON.stringify(data_array,null,0).replace(/\"/g, "'");
	    	$(parent_tr).find('.eb-wi-cf-tbl-options').val(data_array);
    	}

    }

    /* This function create data for the pop-up and returns it.*/
    function wi_cf_get_pop_up_data()
    {
	    data_array = {};
    	data_array['type']               = $('select[name="eb-wi-cf-dialog-type"]').val();
    	data_array['name']               = $('input[name="eb-wi-cf-dialog-name"]').val();
    	data_array['label']              = $('input[name="eb-wi-cf-dialog-label"]').val();
    	data_array['placeholder']        = $('input[name="eb-wi-cf-dialog-placeholder"]').val();
    	data_array['default-val']        = $('input[name="eb-wi-cf-dialog-default-val"]').val();

    	// data_array['required']           = $('select[name="eb-wi-cf-dialog-required"]').val();
    	data_array['class']              = $('input[name="eb-wi-cf-dialog-class"]').val();
    	data_array['enabled']            = 0;
    	data_array['required']           = 0;
    	data_array['sync-on-moodle']     = 0;
    	data_array['woo-my-accnt']       = 0;
    	data_array['edwiser-user-accnt'] = 0;
    	data_array['options']            = {};

    	// Creating options array if selected field type is select
    	if ('select' == data_array['type']) {
	    	jQuery('.eb-wi-cf-pop-up-options-field').each(function(index, element){
				data_array['options'][$(element).find('.eb-wi-cf-dialog-option-val').val()] = $(element).find('.eb-wi-cf-dialog-option-txt').val();
			});

    		// data_array['options'] = JSON.stringify(data_array['options']).replace('"', "'");
    		data_array['options'] = JSON.stringify(data_array['options'],null,0).replace(/\"/g, "'");
    	}

    	if( $('input[name="eb-wi-cf-dialog-enabled"]').prop("checked") == true ) {
    		data_array['enabled'] = 1;
    	}

    	if( $('input[name="eb-wi-cf-dialog-required"]').prop("checked") == true ) {
    		data_array['required'] = 1;
    	}

    	if( $('input[name="eb-wi-cf-dialog-sync-moo"]').prop("checked") == true ) {
    		data_array['sync-on-moodle'] = 1;
    	}

    	if( $('input[name="eb-wi-cf-dialog-woo-my-accnt"]').prop("checked") == true ) {
    		data_array['woo-my-accnt'] = 1;
    	}

    	if( $('input[name="eb-wi-cf-dialog-eb-user-accnt"]').prop("checked") == true ) {
    		data_array['edwiser-user-accnt'] = 1;
    	}

    	return data_array;
    }

    /* This function clears all the data added in the pop-up. */
    function wi_cf_clear_pop_up_fields_data()
    {
    	var data_array = {
    		'select[name="eb-wi-cf-dialog-type"]' : 'text',
    		'input[name="eb-wi-cf-dialog-name"]' : '',
    		'input[name="eb-wi-cf-dialog-label"]' : '',
    		'input[name="eb-wi-cf-dialog-placeholder"]' : '',
    		'input[name="eb-wi-cf-dialog-class"]' : '',
    		'input[name="eb-wi-cf-dialog-default-val"]' : '',
    		'input[name="eb-wi-cf-dialog-enabled"]' : 0,
    		'input[name="eb-wi-cf-dialog-required"]' : 0,
    		'input[name="eb-wi-cf-dialog-sync-moo"]' : 0,
    		'input[name="eb-wi-cf-dialog-woo-my-accnt"]' : 0,
    		'input[name="eb-wi-cf-dialog-eb-user-accnt"]' : 0,
    	};

    	$.each(data_array, function(index, value){
    		$(index).val(value);
    	});

    	// Clearing checkboxes data.
    	$('input[name="eb-wi-cf-dialog-enabled"]').prop('checked', false);
    	$('input[name="eb-wi-cf-dialog-required"]').prop('checked', false);
    	$('input[name="eb-wi-cf-dialog-sync-moo"]').prop('checked', false);
    	$('input[name="eb-wi-cf-dialog-woo-my-accnt"]').prop('checked', false);
    	$('input[name="eb-wi-cf-dialog-eb-user-accnt"]').prop('checked', false);

    	// remove extraa added options.
    	if($('.eb-wi-cf-pop-up-options-field').length){
    		if (!$('.eb-wi-cf-pop-up-options-field').hasClass('eb-wi-cf-hide')) {
	    		$('.eb-wi-cf-pop-up-options-field').addClass('eb-wi-cf-hide');
    		}

    		var counter = 1;
    		$('.eb-wi-cf-pop-up-options-field').each(function(index, element){
    			if(counter == 1){
		    		$(element).find('.eb-wi-cf-dialog-option-val').val('');
		    		$(element).find('.eb-wi-cf-dialog-option-txt').val('');
    			} else {
    				$(element).remove();
    			}
    			counter++;
    		});
    	}
    }

    /* This function shows drop down options if type is select */
    $(document).on('change', 'select[name="eb-wi-cf-dialog-type"]', function(event){
		event.preventDefault();
    	if ('select' == $(this).val()) {
    		$('.eb-wi-cf-pop-up-options-field').css('display', 'flex');
    		$('.eb-wi-cf-dialog-placeholder-wrap').css('display', 'none');
    		$('.eb-wi-cf-dialog-def-val-wrap').css('display', 'flex');
    	} else if('checkbox' == $(this).val()) {
    		$('.eb-wi-cf-dialog-placeholder-wrap').css('display', 'none');
    		$('.eb-wi-cf-pop-up-options-field').css('display', 'none');
    		$('.eb-wi-cf-dialog-def-val-wrap').css('display', 'none');
    	} else {
    		$('.eb-wi-cf-dialog-placeholder-wrap').css('display', 'flex');
    		$('.eb-wi-cf-pop-up-options-field').css('display', 'none');
    		$('.eb-wi-cf-dialog-def-val-wrap').css('display', 'flex');
    	}
    });


    /* This function ads new options row dynamically. */
    // $(document).on('click', '.wi-cf-dialog-option-add-new', function(event){
	$(document).on('click','.wi-cf-dialog-option-add-new',function(event){
		event.preventDefault();
    	// creating html sturcture to add new field.
    	html_cont = wi_cf_get_dynamic_options();

        options_parent = $(this).parent().parent();
        options_parent = options_parent.parent();
        options_parent.after(html_cont);

    });

	/* This is the html structure for options. */
	function wi_cf_get_dynamic_options(option_val = '', option_text='')
	{
		html_cont = '<div class="eb-wi-cf-pop-up-fields eb-wi-cf-pop-up-options-field">'+
                    '<div class="eb-wi-cf-pop-up-lbl">'+
                        '<label></label>'+
					'</div>'+
                    '<div class="eb-wi-cf-pop-up-right-field">'+
                    	'<input value="'+ option_val +'" class="eb-wi-cf-dialog-option-inp eb-wi-cf-dialog-option-val" type="text" name="eb-wi-cf-dialog-option-val[]" placeholder="'+ wi_custom_fields.dialog_option_value +'">'+
                    	'<input value="'+ option_text +'" class="eb-wi-cf-dialog-option-inp eb-wi-cf-dialog-option-txt" type="text" name="eb-wi-cf-dialog-option-txt[]" placeholder="'+ wi_custom_fields.dialog_option_text +'">'+
                    	'<span class="eb-wi-cf-dialog-option-btn">'+
                    		'<span class="wi-cf-dialog-option-add-new dashicons dashicons-plus-alt"></span>'+
                    		'<span class="wi-cf-dialog-option-remove dashicons dashicons-dismiss"></span>'+
                    	'</span>'+
                	'</div>'+
                '</div>';

        return html_cont;
	}

	/* This function removes the added dynamic options  */
	$(document).on('click','.wi-cf-dialog-option-remove',function(event){
		event.preventDefault();
		options_parent = $(this).parent().parent();
        options_parent = options_parent.parent();
        options_parent.remove();

	});

	/* This function just empties the first option element as we are showing by default one option  */
	$(document).on('click','.wi-cf-dialog-option-first-remove',function(event){
		event.preventDefault();
		options_parent = $(this).parent().parent();
		options_parent.find('.eb-wi-cf-dialog-option-val').val('');
		options_parent.find('.eb-wi-cf-dialog-option-txt').val('');

        // options_parent = options_parent.parent();

	});

	/* This the function which is used to check all checkboxes on the click of header checkbox. */
	$(document).on('click', '.wi_cf_bulk_action_header_cb', function(event){
		// event.preventDefault();

		if($(this).prop("checked") == true){
            $('.wi_cf_bulk_action_cb').prop('checked', true);
        } else {
            $('.wi_cf_bulk_action_cb').prop('checked', false);
        }
	});

	/* function to handle Bulk actions */
	$(document).on('click', '.eb_wi_cf_bulk_action_btns', function(event){
		event.preventDefault();

		var bulk_action = $(this).parent().find('.eb_wi_cf_bulk_action_select').val();
		if ( bulk_action != '' ) {

			var checked_rows = $('.wi_cf_bulk_action_cb:checkbox:checked');
			$.each(checked_rows, function(key, value) {
				value = $(value).parent().parent();

				var row_enable_field = $(value).find('.eb-wi-cf-tbl-enabled');

				if ( 'enable' == bulk_action ) {

					if ($(value).hasClass('wi-cf-disable-row')) {
						$(value).removeClass('wi-cf-disable-row');
					}

					// Set hidden input value of the enable input field accordung the bulk action value.
					row_enable_field.val(1);

					// Now set the field value which is shown in the table.
					var row_enable_field_lbl = row_enable_field.parent().find('.eb-wi-cf-tbl-enabled-lbl').html('<span class="dashicons dashicons-saved"></span>');

				} else if ( 'disable' == bulk_action ) {
					$(value).addClass('wi-cf-disable-row');
					// Set input value of the enable input field accordung the bulk action value.
					row_enable_field.val(0);

					// Now set the field value which is shown in the table.
					var row_enable_field_lbl = row_enable_field.parent().find('.eb-wi-cf-tbl-enabled-lbl').html(' - ');

				} else if ( 'remove' == bulk_action ) {
					$(value).css('background-color', '#f3d8d8');

					// If action selected is 'remove' then remove element from dom with fadeout effect
					$(value).fadeOut(500, function(){ 
						$(value).remove();
					});
				}

			});
		}
	});



	$(document).on('click', '.eb-wi-cf-remove', function(){
		$(this).parent().parent().remove();
	});


});
