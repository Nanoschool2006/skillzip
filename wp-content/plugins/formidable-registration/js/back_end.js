function frmRegBackEnd(){

	const hookNamespace = 'frm_reg';

	/**
	 * Create an admin email, on click
	 *
	 * @since 2.0
	 */
	function createAdminEmail() {
		addEmailAction( 'admin' );
	}

	/**
	 * Create a user email, on click
	 *
	 * @since 2.0
	 */
	function createUserEmail() {
		addEmailAction( 'user' );
	}

	/**
	 * Create an email notification, on click
	 *
	 * @since 2.0
	 * @param {string} emailType
	 */
	function addEmailAction( emailType ){

		// Get number of last action
		var len = 0;
		var lastAction = jQuery('.frm_form_action_settings:last');
		if ( lastAction.length ) {
			len = lastAction.attr('id').replace('frm_form_action_', '');
		}

		var currentFormId = document.getElementById('form_id').value;

		jQuery.ajax({
			type:'POST',
			url:ajaxurl,
			data:{
				action:'frm_add_form_action',
				type:'email',
				list_id:(parseInt(len)+1),
				form_id:currentFormId,
				reg_email_type:emailType,
				nonce:frmRegGlobal.nonce
			},
			success:function(html){
				jQuery('#frm_notification_settings').append(html);
				jQuery('.frm_form_action_settings').fadeIn('slow');
				jQuery('#frm_form_action_' + (parseInt(len)+1) + ' .widget-inside').css('display','block');
				jQuery('#frm_form_action_' + (parseInt(len)+1) + ' .frm_multiselect' ).hide().each( frmDom.bootstrap.multiselect.init );
			}
		});
	}

	/**
	 * Hide the user meta Add button
	 *
	 * @since 2.0
	 */
	function hideUserMetaAdd() {
		var addTable = document.getElementById( 'frm_user_meta_add' );
		addTable.style.display = 'none';
	}

	/**
	 * Show the user meta table
	 *
	 * @since 2.0
	 */
	function showUserMetaTable() {
		var table = document.getElementById( 'frm_user_meta_table' );
		table.style.display = 'block';
	}


	/**
	 * Add a new row of user meta
	 *
	 * @since 2.0
	 */
	function addUserMetaRow(){
		var formId = document.getElementById('form_id').value;
		var actionKey = this.closest( '.frm_single_register_settings' ).dataset.actionkey;

		var rowNumber = 0;
		var userMetaRows = document.querySelectorAll( '#frm_user_meta_rows .frm_user_meta_row' );
		if ( userMetaRows.length > 0 ) {
			var lastItem = userMetaRows[ userMetaRows.length - 1 ];
			rowNumber = 1 + parseInt( lastItem.id.replace('frm_user_meta_', '') );
		}

		jQuery.ajax({
			type:"POST",
			url:ajaxurl,
			data:{
				action:'frm_add_user_meta_row',
				form_id:formId,
				action_key:actionKey,
				meta_name:rowNumber
			},
			success:function(html){

				var $userMetaTable = jQuery('#frm_user_meta_rows');
				$userMetaTable.append(html);

				showUserMetaTable();
				hideUserMetaAdd();
			}
		});
	}

	/**
	 * Hide and show the redirect option in Register User action
	 *
	 * @since 2.0
	 */
	function displayRedirectOption( e ) {
		const confirmationSettingContainer = e.target.closest( 'td' ).nextElementSibling;
		confirmationSettingContainer.style.display = this.checked ? 'block' : 'none';
	}

	/**
	 * Hide and show the auto login option in Register User action
	 * if password is set to automatically generate or repeater entry is selected.
	 *
	 * @since 2.0
	 */
	function hideAutoLoginOption() {
		const formAction = this.closest( '.frm_form_action_settings' );
		const childForm  = formAction.querySelector( 'select[id^="child_form_"]' ).value;
		const password   = formAction.querySelector( '.frm_reg_password' ).value;
		const autoLoginRow = formAction.querySelector( '.frm_reg_auto_login_row' );

		if ( childForm || ! password ) {
			autoLoginRow.style.display = 'none';
			uncheckAutoLogin( formAction );
			showHideAutoLoginWarning( formAction, 'table-row' );

		} else {
			autoLoginRow.style.display = 'table-row';
			showHideAutoLoginWarning( formAction, 'none' );
		}
	}

	/**
	 * Uncheck the auto login option
	 *
	 * @since 2.0
	 * @since 3.0.1 Added `formAction` param.
	 */
	function uncheckAutoLogin( formAction ) {
		const autoLoginCheckbox = formAction.querySelector( '.frm_reg_auto_login' );

		if ( autoLoginCheckbox !== null && autoLoginCheckbox.checked ) {
			autoLoginCheckbox.checked = false;
		}
	}

	/**
	 * Show the auto login warning
	 *
	 * @since 2.0
	 * @since 3.0.1 Added `formAction` as the first param, moved `display` param to the second position.
	 */
	function showHideAutoLoginWarning( formAction, display ) {
		const autoLoginWarning = formAction.querySelector( '.frm_reg_auto_login_msg' );

		if ( autoLoginWarning !== null ) {
			autoLoginWarning.style.display = display;
		}
	}

	/**
	 * Hide and show the User Moderation section in Register User action
	 * if password is set to automatically generate
	 *
	 * @since 2.02
	 */
	function hideUserModerationSection() {
		const formAction = this.closest( '.frm_form_action_settings' );
		const userModerationSection = formAction.querySelector( '.frm_reg_user_moderation_section' );

		if ( this !== null && this.value === '' ) {
			userModerationSection.style.display = 'none';
			uncheckEmailConfirmation( formAction );
			showHideUserModerationWarning( formAction, 'block' );
			return;
		}

		userModerationSection.style.display = 'table';
		showHideUserModerationWarning( formAction, 'none' );
	}

	/**
	 * Show or hide the user moderation warning
	 *
	 * @since 2.02
	 * @since 3.0.1 Added `formAction` as the first param, moved `display` param to the second position.
	 */
	function showHideUserModerationWarning( formAction, display ) {
		const userModerationWarning = formAction.querySelector( '.frm_reg_user_moderation_msg' );

		if ( userModerationWarning !== null ) {
			userModerationWarning.style.display = display;
		}
	}

	/**
	 * Uncheck the email confirmation option
	 *
	 * @since 2.02
	 * @since 3.0.1 Added `formAction` param.
	 */
	function uncheckEmailConfirmation( formAction ) {
		var emailConfirmationCheckbox = formAction.querySelector( '.frm_reg_moderate_email' );

		if ( emailConfirmationCheckbox !== null && emailConfirmationCheckbox.checked ) {
			emailConfirmationCheckbox.checked = false;
		}
	}


	/**
	 * Hide and show the multi-site options in Register user action
	 *
	 * @since 2.0
	 */
	function displayMultiSiteOptions() {
		var i;
		var l;
		var multiSiteOptions = this.closest( '.frm_form_action_settings' ).querySelector( '.reg_multisite_options' );

		if ( this.checked ) {
			for ( i = 0, l = multiSiteOptions.length; i < l; i++ ) {
				multiSiteOptions[i].style.display = 'block';
			}
		} else {
			for ( i = 0, l = multiSiteOptions.length; i < l; i++ ) {
				multiSiteOptions[i].style.display = 'none';
			}
		}
	}

	/**
	 * Hide and show the permission options in Register user action
	 *
	 * @since 2.0
	 */
	function displayPermissionOptions() {
		var permissionOptions = document.getElementById( 'reg_create_role_tr' );

		if ( this.checked ) {
			permissionOptions.style.display = 'table-row';
		} else {
			permissionOptions.style.display = 'none';
		}
	}

	function initOnSubmitAction() {
		const onChangeType = event => {
			if ( ! event.target.checked ) {
				return;
			}

			const actionEl = event.target.closest( '.frm_form_action_settings' );
			actionEl.querySelectorAll( '.frm_on_email_confirmation_dependent_setting:not(.frm_hidden)' ).forEach( el => {
				el.classList.add( 'frm_hidden' );
			});

			const activeEls = actionEl.querySelectorAll( '.frm_on_email_confirmation_dependent_setting[data-show-if-' + event.target.value + ']' );
			activeEls.forEach( activeEl => {
				activeEl.classList.remove( 'frm_hidden' );
			});
		};

		frmDom.util.documentOn( 'change', '.frm_on_email_confirmation_type input[type="radio"]', onChangeType );
	}

	function handleRepeaterActions() {

		/**
		 * RepeaterActionsCount class.
		 *
		 * @constructor
		 */
		const RepeaterActionsCount = () => {
			/**
			 * Counts data.
			 *
			 * @type {Object} Object with keys are repeater ID or `main`, values are action count.
			 */
			const counts = {};

			/**
			 * Maybe add default count when it doesn't exist.
			 *
			 * @param {String} key Count key.
			 */
			const maybeAddDefault = key => {
				if ( 'undefined' === typeof counts[ key ] ) {
					counts[ key ] = 0;
				}
			};

			/**
			 * Parses count key.
			 *
			 * @param {String} key Repeater ID or empty.
			 * @return {string} Count key.
			 */
			const parseKey = key => {
				return key || 'main';
			};

			return {
				/**
				 * Gets all counts.
				 *
				 * @return {Object}
				 */
				getAll: function() {
					return counts;
				},

				/**
				 * Gets count for a key.
				 *
				 * @param {String} key Count key.
				 * @return {Number}
				 */
				getCount: function( key ) {
					key = parseKey( key );
					return 'undefined' === typeof counts[ key ] ? 0 : counts[ key ];
				},

				/**
				 * Plus one.
				 *
				 * @param {String} key Count key.
				 */
				plus: function( key ) {
					key = parseKey( key );
					maybeAddDefault( key );
					counts[ key ]++;
				},

				/**
				 * Minus one.
				 *
				 * @param {String} key Count key.
				 */
				minus: function( key ) {
					key = parseKey( key );
					maybeAddDefault( key );
					counts[ key ]--;
					if ( counts[ key ] <= 0 ) {
						delete counts[ key ];
					}
				},

				/**
				 * Gets number of repeaters in count data.
				 *
				 * @return {Number}
				 */
				getRepeaterCount: function() {
					return Object.keys( counts ).length;
				},

				/**
				 * Checks if action is at limit.
				 *
				 * @param {Number} limit Limit number.
				 * @return {Boolean}
				 */
				atLimit: function( limit ) {
					if ( this.getRepeaterCount() < frmRegGlobal.repeaters.length + 1 ) {
						return false;
					}
					const countValues = Object.values( counts );
					for ( let i = 0; i < countValues; i++ ) {
						if ( countValues[ i ] < limit ) {
							return false;
						}
					}

					return true;
				}
			};
		};

		const limit = 1; // TODO: maybe get this from PHP.

		/**
		 * Gets current action count.
		 *
		 * @param {String} excludeId ID of form action that is excluded from the counts.
		 * @return {Object}
		 */
		const getCurrentActionsCount = excludeId => {
			const counts    = RepeaterActionsCount();
			const actionEls = document.querySelectorAll( '.frm_single_register_settings' );

			actionEls.forEach( actionEl => {
				if ( actionEl.querySelector( 'input[id^="action_post_title_"]' ) ) {
					if ( ! excludeId || actionEl.id !== excludeId ) {
						updateCountFromFilledAction( counts, actionEl );
					}
				} else {
					updateCountFromActionsData( counts );
				}
			});

			return counts;
		};

		/**
		 * Checks active action.
		 */
		const checkActiveAction = () => {
			const counts = getCurrentActionsCount();
			const actionLinks = document.querySelectorAll( '.frm_actions_list .frm_register_action' );
			const atLimit     = counts.atLimit( limit );

			actionLinks.forEach( actionLink => {
				actionLink.classList.toggle( 'frm_inactive_action', atLimit );
				actionLink.classList.toggle( 'frm_already_used', atLimit );
			});
		};

		/**
		 * Updates count data from a filled action.
		 *
		 * @param {object}      counts   Count object.
		 * @param {HTMLElement} actionEl Form action element.
		 */
		const updateCountFromFilledAction = ( counts, actionEl ) => {
			const selectEl = actionEl.querySelector( 'select[id^="child_form_"]' );
			if ( ! selectEl ) {
				// Do nothing if there is no Repeater Actions dropdown.
				return;
			}

			counts.plus( selectEl.value );
		};

		/**
		 * Updates count data from form action data.
		 *
		 * @param {object} counts Count object.
		 */
		const updateCountFromActionsData = counts => {
			const formActions = frmRegGlobal.formActions;

			formActions.forEach( formAction => {
				counts.plus( formAction.post_content.child_form );
			});
		};

		/**
		 * Filters at limit value.
		 *
		 * @param {Boolean} atLimit Is true if at limit.
		 * @param {Object}  args    Filter args.
		 * @return {Boolean}
		 */
		const filterAtLimit = ( atLimit, args ) => {
			if ( ! atLimit || 'register' !== args.type ) {
				// Do nothing if the action limitation isn't reached.
				return atLimit;
			}

			const counts = getCurrentActionsCount();

			return counts.atLimit( 1 );
		};

		/**
		 * Maybe remove Repeater Actions options based on the form action limitation.
		 *
		 * @param {HTMLElement} actionEl     Form action element.
		 * @param {Boolean}     keepSelected Keep the selected option.
		 */
		const maybeDisableOptions = ( actionEl, keepSelected ) => {
			if ( 'register' !== actionEl.querySelector( '.frm_action_name' ).value ) {
				// Do nothing if this isn't User Registration action.
				return;
			}

			const selectEl = actionEl.querySelector( 'select[id^="child_form_"]' );
			if ( ! selectEl ) {
				// Do nothing if there is no Repeater Actions dropdown.
				return;
			}

			const counts = getCurrentActionsCount( actionEl.id );

			// Disable options that reach the limitation.
			selectEl.querySelectorAll( 'option' ).forEach( option => {
				if ( option.selected && keepSelected ) {
					return;
				}

				if ( counts.getCount( option.value ) >= limit ) {
					option.setAttribute( 'disabled', true );
					option.selected = false;
				} else {
					option.removeAttribute( 'disabled' );
				}
			});
		};

		const onFilledFormAction = inside => {
			maybeDisableOptions( inside[0], true );
		};

		const onAddedFormAction = newAction => {
			maybeDisableOptions( newAction );
			checkActiveAction();
		};

		const setBeforeChangeValue = event => {
			event.target.setAttribute( 'data-before-value', event.target.value );
		};

		const onChangeRepeaterActions = event => {
			swapDisabledRepeaterActionsOptions( event.target.getAttribute( 'data-before-value' ), event.target.value, event.target );
			setBeforeChangeValue( event );
		};

		const swapDisabledRepeaterActionsOptions = ( oldValue, newValue, currentTarget ) => {
			const selectEls = document.querySelectorAll( '.frm_single_register_settings select[id^="child_form_"]' );
			selectEls.forEach( selectEl => {
				if ( selectEl.id === currentTarget.id ) {
					// Do not update the current select.
					return;
				}

				const oldOption = selectEl.querySelector( 'option[value="' + oldValue + '"]' );
				if ( oldOption ) {
					oldOption.disabled = false;
				}
				const newOption = selectEl.querySelector( 'option[value="' + newValue + '"]' );
				if ( newOption ) {
					newOption.disabled = true;
					newOption.selected = false;
				}
			});
		};

		wp.hooks.addFilter( 'frm_action_at_limit', hookNamespace, filterAtLimit );
		wp.hooks.addAction( 'frm_filled_form_action', hookNamespace, onFilledFormAction );
		wp.hooks.addAction( 'frm_added_form_action', hookNamespace, onAddedFormAction );
		frmDom.util.documentOn( 'focusin', '.frm_single_register_settings select[id^="child_form_"]', setBeforeChangeValue );
		frmDom.util.documentOn( 'change', '.frm_single_register_settings select[id^="child_form_"]', onChangeRepeaterActions );
	}

	return{
		init: function(){
			if ( document.getElementById('frm_notification_settings') !== null ) {
				// Bind event handlers for form Settings page
				frmRegBackEndJS.formActionsInit();
			}
		},

		formActionsInit: function(){

			var $formActions = jQuery(document.getElementById('frm_notification_settings'));

			$formActions.on( 'click', '.frmreg_admin_email', createAdminEmail );
			$formActions.on( 'click', '.frmreg_user_email', createUserEmail );
			$formActions.on( 'click', '.reg_user_meta_add_button', addUserMetaRow );
			$formActions.on( 'click', '.reg_add_user_meta_row', addUserMetaRow );
			$formActions.on( 'change', '.frm_reg_moderate_email', displayRedirectOption );
			frmDom.util.documentOn( 'change', 'select[id^="child_form_"]', hideAutoLoginOption );
			$formActions.on( 'change', '.frm_reg_password', hideAutoLoginOption );
			$formActions.on( 'change', '.frm_reg_password', hideUserModerationSection );
			$formActions.on( 'change', '.frm_reg_create_subsite', displayMultiSiteOptions );
			$formActions.on( 'change', '.frm_reg_create_users', displayPermissionOptions );

			initOnSubmitAction();

			if ( Array.isArray( frmRegGlobal.repeaters ) && frmRegGlobal.repeaters.length ) {
				handleRepeaterActions();
			}
		}
	};
}

var frmRegBackEndJS = frmRegBackEnd();
jQuery(document).ready(function($){
	frmRegBackEndJS.init();
});
