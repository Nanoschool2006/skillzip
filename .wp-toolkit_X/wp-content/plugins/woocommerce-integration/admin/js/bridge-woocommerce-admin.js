jQuery(document).ready(function($){


	$('.contains_enrolment').parents('tr.type-eb_course').addClass('contains_enrolment');

	// Single action - Trash - Show confirmation message.	
	$( '.type-eb_course' ).on( 'click', '.contains_enrolment', function(event){
		return confirm(adminStrings.singleTrashWarning);
	} );

	// Single action - Trash - Show confirmation message.
	$('#posts-filter').submit(function(){
		if($('[name="post_type"]').val()=='eb_course' && ($('[name="action"]').val()=='trash'||$('[name="action2"]').val()=='trash')){
			var containsEnrolment = false;
			$('[name="post[]"]:checked').each(function(){
				if ($(this).parents('tr.type-eb_course').hasClass('contains_enrolment')) {
					containsEnrolment = true;
				}
				return (false === containsEnrolment);
			});

			if(containsEnrolment){
				return confirm(adminStrings.bulkTrashWarning);
			}
		}
	});

	if ($(".column-associated_courses").length) {
		var parent = $(".column-associated_courses").closest('table');;
		parent.css("table-layout", "auto");
	}

});
