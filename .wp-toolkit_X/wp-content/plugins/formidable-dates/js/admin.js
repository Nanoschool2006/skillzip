/* global frmDom, jQuery, wp */
jQuery( function( $ ) {
	const hookNamespace = 'frm_dates';

	const frmdatesAdmin = {
		init() {
			const $form = $( '#new_fields' );

			if ( 0 === $form.length ) {
				return;
			}

			wp.hooks.addAction( 'frmpro_after_field_created_when_range_enabled', hookNamespace, frmAdminDateRangesSyncFieldOptions.makeRelationshipBetweenStartEndDateFields );

			$form.on( 'click', '.frmdates_add_blackout_date_link, .frmdates_add_blackout_date_link + .frm-tokens', $.proxy( this.addBlackOutDatesHandler, this ) );
			$form.on( 'click', '.frmdates_add_exception_link, .frmdates_add_exception_link + .frm-tokens', $.proxy( this.addExceptionHandler, this ) );
			$form.on( 'click', '.frmdates_date_list .frmdates_remove_item', $.proxy( this.removeDateHandler, this ) );
			$form.on( 'click', '.frmdates_date_list .frmdates_show_all_placeholder', $.proxy( this.showAllHandler, this ) );
			$form.on( 'change', '.frmdates_days_of_the_week input[type="checkbox"]', $.proxy( this.daysOfTheWeekChangeHandler, this ) );
			$form.on( 'change', '.frmdates_days_of_the_week_toggle input[type="checkbox"]', $.proxy( this.daysOfTheWeekToggleHandler, this ) );
			$form.on( 'change', '.frm_date_show', $.proxy( this.showHide, this ) );
			$form.on( 'change', 'input[type="checkbox"].frm-admin-datepicker-display-inline', frmdatesAdmin.onDisplayInlineCheckboxChange );
			$form.on( 'change', '.frm_date_show', frmAdminDateRangesSyncFieldOptions.showHide );
			$form.on( 'change', '.frmdates_days_of_the_week input[type="checkbox"]', frmAdminDateRangesSyncFieldOptions.daysOfTheWeekChange );
			$form.on( 'change', '.frmdates_days_of_the_week_toggle input[type="checkbox"]', frmAdminDateRangesSyncFieldOptions.daysOfTheWeekToggle );
			$form.on( 'change', '.frm_sync_range_fields', frmAdminDateRangesSyncFieldOptions.synYearRangeValues );

			frmAdminDatepicker.jqueryPreventCalendarClose( $ );

			// Update date link styles when the Settings section content is visible
			document.querySelectorAll( '.frmdates_add_blackout_date_link, .frmdates_add_exception_link' ).forEach( ( addDateLink ) => {
				const collapseElement = addDateLink.closest( '.frm-collapse-me' );
				if ( collapseElement && collapseElement.previousElementSibling ) {
					collapseElement.previousElementSibling.addEventListener( 'click', () => {
						setTimeout( () => {
							this.updateDateLinkStyle( addDateLink.nextElementSibling, addDateLink );
						}, 50 );
					} );
				}
			} );

			this.dateCalc();
		},

		onDisplayInlineCheckboxChange( event ) {
			const fieldId = event.target.getAttribute( 'name' ).match( /\[.+?([0-9]+)?\]$/ )[ 1 ];
			const endRangeFieldId = document.querySelector( `#frm_range_end_field_${ fieldId }` ).value;
			const endRangeField = document.querySelector( `#frmdates_display_inline_${ endRangeFieldId }` );

			frmAdminDateRangesSyncFieldOptions.dateRangeMoveFieldSettings( endRangeFieldId );

			if ( event.target.checked ) {
				endRangeField.setAttribute( 'checked', true );
				endRangeField.checked = true;
				return;
			}
			endRangeField.removeAttribute( 'checked' );
			endRangeField.checked = false;
		},

		dateCalc() {
			const getTagEl = ( code, name ) => {
				return frmDom.tag( 'li', {
					className: 'search-smart-tags',
					data: {
						code,
					},
					children: [
						frmDom.a( {
							href: 'javascript:void(0)',
							data: {
								code,
							},
							className: 'show_dyn_default_value frm_insert_code',
							children: [
								name,
								frmDom.span( '[' + code + ']' ),
							],
						} ),
					],
				} );
			};

			/**
			 * Gets current fields list on the form builder.
			 * This is copied and modified from `getFieldList()` in `formidable_admin.js`.
			 *
			 * @return {Object[]} List of field objects
			 */
			const getFieldsList = () => {
				let i, fieldId;
				const fields = [];
				const allFields = document.querySelectorAll( 'li.frm_field_box' );

				const getPossibleValue = ( id ) => {
					const field = document.getElementById( id );
					if ( field ) {
						return field.value;
					}
					return '';
				};

				for ( i = 0; i < allFields.length; i++ ) {
					fieldId = allFields[ i ].getAttribute( 'data-fid' );
					if ( fieldId ) {
						fields.push( {
							id: fieldId,
							name: getPossibleValue( 'frm_name_' + fieldId ),
							type: getPossibleValue( 'field_options_type_' + fieldId ),
							key: getPossibleValue( 'field_options_field_key_' + fieldId ),
						} );
					}
				}

				return fields;
			};

			wp.hooks.addAction( 'frm_show_inline_modal', hookNamespace, function( box, icon ) {
				if ( 'frm_dates_shortcodes_box' !== icon.getAttribute( 'data-open' ) ) {
					return;
				}

				const isDiffInput = icon.classList.contains( 'frm_dates_show_calc_diff_shortcodes_box' );
				const codeListEl = box.querySelector( '.frm_code_list' );
				const fieldId = icon.getAttribute( 'data-fid' );
				const fields = getFieldsList();
				const includeTypes = [];

				// Remove all field tags, keep [date] and [get].
				codeListEl.querySelectorAll( 'li:not(:nth-child(1)):not(:nth-child(2))' ).forEach( ( li ) => li.remove() );

				if ( isDiffInput ) {
					includeTypes.push( 'text', 'number', 'select', 'radio', 'range', 'hidden' );
				} else {
					includeTypes.push( 'text', 'date', 'select', 'radio', 'hidden' );
				}

				fields.forEach( ( field ) => {
					if ( parseInt( field.id ) === parseInt( fieldId ) || -1 === includeTypes.indexOf( field.type ) ) {
						return;
					}

					codeListEl.append( getTagEl( field.id, field.name ) );
				} );
			} );
		},

		/**
		 * Updates the blackout date link style based on list height
		 *
		 * @param {HTMLElement} dateList    The date list element
		 * @param {HTMLElement} addDateLink The link element
		 * @return {void}
		 */
		updateDateLinkStyle( dateList, addDateLink ) {
			if ( dateList.scrollHeight > 36 ) {
				addDateLink.style.height = `${ dateList.scrollHeight }px`;
			} else {
				addDateLink.style.removeProperty( 'height' );
			}

			if ( dateList.children.length > 1 ) {
				addDateLink.style.color = 'transparent';
			} else {
				addDateLink.style.removeProperty( 'color' );
			}
		},

		showCalendar( fieldID, dateType ) {
			const t = this;

			const $container = $( '#frmdates_' + dateType + '_' + fieldID );
			if ( 0 === $container.length ) {
				return;
			}

			// Setup date picker for field (if needed).
			const $field = $container.find( '.frmdates_datepicker' );
			if ( 0 === $field.length ) {
				return;
			}

			const updateDateLinkStyles = () => {
				if ( ! [ 'blackout_dates', 'excepted_dates' ].includes( dateType ) ) {
					return;
				}

				const linkClass = dateType === 'blackout_dates' ? 'frmdates_add_blackout_date_link' : 'frmdates_add_exception_link';
				const addDateLink = document.querySelector( `.${ linkClass }[data-field-id="${ fieldID }"]` );
				const dateList = addDateLink.nextElementSibling;
				t.updateDateLinkStyle( dateList, addDateLink );
			};

			frmAdminDatepicker.prototype.callbacks.onSelect = ( selectedDates, dateStr, _instance ) => {
				t.toggleDateInList( fieldID, dateType, dateStr );
				updateDateLinkStyles();
			};
			frmAdminDatepicker.prototype.callbacks.onDayCreate = ( dateStr, _instance, dateObj ) => t.beforeShowDayHandler( fieldID, dateType, dateObj );
			frmAdminDatepicker.prototype.callbacks.onDisable = ( date ) => ! t.beforeShowDayHandler( fieldID, dateType, date )[ 0 ];

			frmAdminDatepicker.prototype.jqueryCallbacks.onSelect = ( date ) => {
				t.toggleDateInList( fieldID, dateType, date );
				updateDateLinkStyles();
			};
			frmAdminDatepicker.prototype.jqueryCallbacks.beforeShowDay = ( d ) => t.beforeShowDayHandler( fieldID, dateType, d );

			new frmAdminDatepicker( $field, fieldID );
		},

		getDaysOfTheWeek( fieldID ) {
			const $checked = $( 'input[name="field_options[days_of_the_week_' + fieldID + '][]"]:checked' );
			if ( $checked.length > 0 ) {
				return $checked.map(
					function() {
						return parseInt( $( this ).val(), 10 );
					}
				).get();
			}

			return [];
		},

		getDatesInList( fieldID, dateType ) {
			const $items = $( '#frmdates_' + dateType + '_' + fieldID + ' .frmdates_date_list_item' );
			const result = [];

			$items.each(
				function() {
					result.push( $( this ).data( 'date' ) );
				}
			);

			return result;
		},

		addDateToList( fieldID, dateType, date ) {
			let dateObj;
			let html;

			const $container = $( '#frmdates_' + dateType + '_' + fieldID );
			if ( 0 === $container.length ) {
				return;
			}

			const $list = $container.find( '.frmdates_date_list' );
			const $items = $list.find( '.frmdates_date_list_item' );

			// Check if item already exists.
			if ( $items.filter( '[data-date="' + date + '"]' ).length > 0 ) {
				return;
			}

			// Parse date for future reference.
			dateObj = null;

			try {
				dateObj = frmAdminDatepicker.parseDate( date, 'yy-mm-dd' );
			} catch ( err ) {
				console.log( err );
				return;
			}

			html = window.frmdates_admin_js.itemTemplate;
			html = html.replace( /%DATE%/g, date );

			html = html.replace( /%DATE_WITH_FORMAT%/g, frmAdminDatepicker.formatDate( dateObj, 'yy-mm-dd' ) );

			html = html.replace( /%DATE_TYPE%/g, dateType );
			html = html.replace( /%FIELD_ID%/g, fieldID );

			const $item = $( html );

			// Insert the item at the correct position (maintains order).
			const $nextItems = $items.filter(
				function() {
					return $( this ).data( 'date' ) > date;
				}
			);

			if ( $nextItems.length > 0 ) {
				$item.insertBefore( $nextItems.first() );
			} else {
				$list.append( $item );
			}

			// Show everything.
			$list.find( '.frmdates_show_all_placeholder' ).hide();
			$items.removeClass( 'frm_hidden' );

			if ( ! frmAdminDatepicker.prototype.isFlatpickrOn() ) {
				$item.effect( 'highlight', 'slow' );
			}
		},

		removeDateFromList( fieldID, dateType, date ) {
			const $list = $( '#frmdates_' + dateType + '_' + fieldID + ' .frmdates_date_list' );
			const $item = $list.find( '.frmdates_date_list_item[data-date="' + date + '"]' );

			if ( 0 === $item.length ) {
				return false;
			}

			$item.remove();

			const $items = $list.find( '.frmdates_date_list_item' );
			$items.filter( ':lt(5)' ).removeClass( 'frm_hidden' );

			// Update count for placeholder or hide it completely.
			const itemCount = $items.length;
			const $placeholder = $list.find( '.frmdates_show_all_placeholder' );
			if ( itemCount <= 5 ) {
				$placeholder.hide();
			} else {
				$placeholder.find( '.count' ).text( itemCount - 5 );
			}

			return true;
		},

		toggleDateInList( fieldID, dateType, date ) {
			if ( -1 === $.inArray( date, this.getDatesInList( fieldID, dateType ) ) ) {
				return this.addDateToList( fieldID, dateType, date );
			}

			return this.removeDateFromList( fieldID, dateType, date );
		},

		addBlackOutDatesHandler( e ) {
			const $link = $( e.currentTarget );
			const fieldID = $link.data( 'field-id' );

			e.preventDefault();

			this.showCalendar( fieldID, 'blackout_dates' );
		},

		addExceptionHandler( e ) {
			const $link = $( e.currentTarget );
			const fieldID = $link.data( 'field-id' );

			e.preventDefault();

			this.showCalendar( fieldID, 'excepted_dates' );
		},

		removeDateHandler( e ) {
			e.preventDefault();
			e.stopPropagation();

			const $link = $( e.target );
			const $item = $link.parents( '.frmdates_date_list_item' );
			const $list = $item.parents( '.frmdates_date_list' );
			const dateType = $list.data( 'date-type' );

			this.removeDateFromList( $list.data( 'field-id' ), dateType, $item.data( 'date' ) );

			if ( [ 'blackout_dates', 'excepted_dates' ].includes( dateType ) ) {
				const dateList = $list[ 0 ];
				const addDateLink = dateList.previousElementSibling;
				this.updateDateLinkStyle( dateList, addDateLink );
			}
		},

		showAllHandler( e ) {
			const $target = $( e.target );
			const $li = $target.is( 'li' ) ? $target : $target.parents( 'li' );
			const $list = $li.parents( '.frmdates_date_list' );
			const $items = $list.find( '.frmdates_date_list_item' );

			e.preventDefault();
			e.stopPropagation();

			$li.hide();
			$items.removeClass( 'frm_hidden' );

			if ( [ 'blackout_dates', 'excepted_dates' ].includes( $list.data( 'date-type' ) ) ) {
				const dateList = $list[ 0 ];
				const addBlackoutDateLink = dateList.previousElementSibling;
				this.updateDateLinkStyle( dateList, addBlackoutDateLink );
			}
		},

		daysOfTheWeekChangeHandler( e ) {
			const $target = $( e.target );
			const fieldID = $target.parents( '.frm_field_box' ).data( 'fid' );
			const $exceptionsRow = $( '#frmdates_excepted_dates_row_' + fieldID );
			const $allDaysToggle = $( '#frmdates_days_of_the_week_toggle_' + fieldID );
			const $days = $( '#frmdates_days_of_the_week_' + fieldID );
			const days = this.getDaysOfTheWeek( fieldID );

			if ( 7 === days.length ) {
				$exceptionsRow.hide();
				$allDaysToggle.prop( 'checked', true );
				$allDaysToggle.parent().show();
				$days.hide();
			} else {
				$exceptionsRow.show();
			}
		},

		daysOfTheWeekToggleHandler( e ) {
			const $target = $( e.target );
			const fieldID = $target.parents( '.frm_field_box, .frm-single-settings' ).data( 'fid' );
			const $days = $( 'input[name="field_options[days_of_the_week_' + fieldID + '][]"]' );
			const checked = $target.prop( 'checked' );

			if ( checked ) {
				$days.prop( 'checked', true );
				$target.parent().show();
			} else {
				$target.parent().hide();
				$( '#frmdates_days_of_the_week_' + fieldID ).show();
			}
		},

		beforeShowDayHandler( fieldID, dateType, d ) {
			let enabled = true;
			let cssClass = '';
			const dateISO = d.getFullYear() + '-' + ( '0' + ( d.getMonth() + 1 ) ).slice( -2 ) + '-' + ( '0' + d.getDate() ).slice( -2 );
			const inDaysOfTheWeek = ( -1 < $.inArray( d.getDay(), this.getDaysOfTheWeek( fieldID ) ) );
			if ( ( 'blackout_dates' === dateType && ! inDaysOfTheWeek ) || ( 'excepted_dates' === dateType && inDaysOfTheWeek ) ) {
				enabled = false;
			}

			if ( enabled ) {
				if ( 0 <= $.inArray( dateISO, this.getDatesInList( fieldID, dateType ) ) ) {
					cssClass = 'frm-selected-date';
				}
			}

			return [ enabled, cssClass ];
		},

		showHide( e ) {
			let showDiv = '';
			let hideDiv = '';
			const $target = $( e.target );
			const toShow = $target.data( 'show' );
			const toHide = $target.data( 'hide' );
			const requiredVal = $target.data( 'value' );
			let value = $target.val();

			if ( $target.is( ':checkbox' ) && ! $target.is( ':checked' ) ) {
				value = '';
			}

			if ( 'undefined' !== typeof toShow ) {
				showDiv = $target.closest( 'td' ).find( '.' + toShow );
			}

			if ( 'undefined' !== typeof toHide ) {
				hideDiv = $target.closest( 'td' ).find( '.' + toHide );
			}

			if ( value.toString() === requiredVal.toString() ) {
				this.showNow( showDiv, $target.data( 'default' ), value );

				if ( '' !== hideDiv ) {
					hideDiv.fadeOut();
				}
			} else {
				if ( '' !== showDiv ) {
					showDiv.fadeOut();
				}

				this.showNow( hideDiv, $target.data( 'default' ), value );
			}
		},

		showNow( showDiv, defaultVal, value ) {
			if ( '' !== showDiv ) {
				showDiv.fadeIn();
				defaultVal = this.getDefaultValue( defaultVal, value );
				if ( '' !== defaultVal ) {
					showDiv.attr( 'placeholder', defaultVal );
				}
			}
		},

		getDefaultValue( defaultVal, value ) {
			let opts, valueOpt, i;

			if ( 'undefined' !== typeof defaultVal ) {
				opts = defaultVal.split( '|' );
				for ( i = 0; i < opts.length; i++ ) {
					valueOpt = opts[ i ].split( ':' );
					if ( valueOpt[ 0 ] === value || valueOpt[ 0 ] === '' ) {
						return valueOpt[ 1 ];
					}
				}
			}
		},
	};

	frmdatesAdmin.init();
} );

/**
 * Initializes the datepicker.
 * @param {jQuery} $field  Field element.
 * @param {string} fieldID Field ID.
 */
function frmAdminDatepicker( $field, fieldID ) {
	/**
	 * The input field element.
	 *
	 * @type {jQuery|HTMLElement}
	 */
	this.field = this.isFlatpickrOn() ? $field[ 0 ] : $field;

	/**
	 * The Flatpickr instance.
	 *
	 * @type {Object|null}
	 */
	this.instance = null;

	/**
	 * The function to close the Flatpickr instance.
	 *
	 * @type {Function}
	 */
	this.closeInstance = () => {};

	/**
	 * Initializes the datepicker.
	 */
	this.init = () => {
		if ( this.isFlatpickrOn() ) {
			this.getInstance();
			this.initFlatpickr();
			return;
		}

		this.initJquery();
	};

	/**
	 * Gets the Flatpickr instance.
	 */
	this.getInstance = () => {
		if ( this.isFlatpickrOn() ) {
			this.instance = this.field._flatpickr || null;
		}
	};

	/**
	 * Initializes the Flatpickr instance.
	 */
	this.initFlatpickr = () => {
		if ( null !== this.instance ) {
			return;
		}

		this.instance = flatpickr( this.field, {
			dateFormat: 'Y-m-d',
			monthSelectorType: 'dropdown',
			changeYear: true,
			onOpen: ( selectedDates, dateStr, instance ) => this.addOutsideClickHandler( instance ),
			onReady: ( selectedDates, dateStr, instance ) => {
				this.field.style.visibility = 'hidden';
				this.field.style.position = 'absolute';
				this.field.style.pointerEvents = 'none';
				instance.calendarContainer.classList.add( 'frm-datepicker' );
			},
			disable: [
				( date ) => {
					if ( ! this.callbacks.onDisable ) {
						return false;
					}
					return this.callbacks.onDisable( date );
				},
			],
			onChange: this.onSelect,
			onDayCreate: ( dayEl, dateStr, instance, dayContainer ) => {
				const result = this.onDayCreate( dayEl, dateStr, instance, dayContainer.dateObj );
				if ( result && result[ 1 ] ) {
					dayContainer.classList.add( result[ 1 ] );
				}
			},
		} );

		// Prevent closing of the datepicker when a date is selected.
		this.closeInstance = this.instance.close;
		this.instance.close = () => {};
	};

	/**
	 * Initializes the jQuery datepicker instance.
	 */
	this.initJquery = () => {
		if ( ! this.field.hasClass( 'hasDatepicker' ) ) {
			const opts = jQuery.extend(
				{},
				jQuery.datepicker.regional[ this.field.parent().find( '.frmdates_add_blackout_date_link' ).data( 'locale' ) ],
				{
					dateFormat: 'yy-mm-dd',
					changeMonth: true,
					changeYear: true,
					beforeShow: () => {
						const datepickerDiv = document.getElementById( 'ui-datepicker-div' );
						if ( datepickerDiv ) {
							datepickerDiv.classList.add( 'frm-datepicker' );
						}
					},
					onSelect: ( date, inst ) => {
						this.jqueryCallbacks.onSelect( date );
						inst.frmDatesDateSelected = true;
					},
					showButtonPanel: true,
					beforeShowDay: ( d ) => {
						return this.jqueryCallbacks.beforeShowDay( d );
					},
				},
			);
			this.field.datepicker( opts );
		}

		this.field.datepicker(
			'option',
			'yearRange',
			jQuery( 'input[name="field_options[start_year_' + fieldID + '][]"]' ).val() + ':' + jQuery( 'input[name="field_options[end_year_' + fieldID + ']"]' ).val()
		);
	};
	/**
	 * Handles the selection of a date.
	 *
	 * @param {Array}  selectedDates Selected dates.
	 * @param {string} dateStr       Date string.
	 * @param {Object} instance      Flatpickr instance.
	 */
	this.onSelect = ( selectedDates, dateStr, instance ) => {
		if ( ! this.callbacks.onSelect ) {
			return;
		}

		this.callbacks.onSelect( selectedDates, dateStr, instance );
		setTimeout( () => instance.redraw(), 200 );
	};
	/**
	 * Handles the creation of a day.
	 *
	 * @param {HTMLElement} dayEl    Day element.
	 * @param {string}      dateStr  Date string.
	 * @param {Object}      instance Flatpickr instance.
	 * @param {Object}      dateObj  Date object.
	 */
	this.onDayCreate = ( dayEl, dateStr, instance, dateObj ) => {
		if ( ! this.callbacks.onDayCreate ) {
			return;
		}

		return this.callbacks.onDayCreate( dateStr, instance, dateObj );
	};

	/**
	 * Opens the datepicker.
	 */
	this.open = () => {
		if ( this.isFlatpickrOn() && this.instance ) {
			this.instance.open();
			return;
		}

		this.field.datepicker( 'show' );
	};

	/**
	 * Adds an outside click handler to the datepicker.
	 *
	 * @param {Object} instance Flatpickr instance.
	 */
	this.addOutsideClickHandler = ( instance ) => {
		const handleClickOutside = ( event ) => {
			const calendarContainer = instance.calendarContainer;
			if ( calendarContainer && ! calendarContainer.contains( event.target ) ) {
				this.closeInstance();
				document.removeEventListener( 'click', handleClickOutside );
			}
		};

		setTimeout( () => document.addEventListener( 'click', handleClickOutside ) );
	};

	this.init();
	this.open();
}

/**
 * Checks if Flatpickr is enabled.
 *
 * @return {boolean} True if Flatpickr is enabled, false otherwise.
 */
frmAdminDatepicker.prototype.isFlatpickrOn = () => window.frmdates_admin_js && window.frmdates_admin_js.datepickerLibrary === 'flatpickr';

/**
 * Flatpickr callbacks.
 *
 * @type {Object}
 */
frmAdminDatepicker.prototype.callbacks = {
	onSelect: null,
	onDayCreate: null,
	onDisable: null,
};

/**
 * jQuery callbacks.
 *
 * @type {Object}
 */
frmAdminDatepicker.prototype.jqueryCallbacks = {
	beforeShowDay: null,
	onSelect: null,
};

/**
 * jQuery datepicker format to Flatpickr format map.
 *
 * @type {Object}
 */
frmAdminDatepicker.jqueryToFlatpickrDateFormatMap = {
	'yy-mm-dd': 'Y-m-d',
};

/**
 * Converts a jQuery default datepicker format to a Flatpickr format.
 *
 * @param {string} dateFormat Date format.
 * @return {string} The converted date format.
 */
frmAdminDatepicker.maybeConvertjQueryDateFormatToFlatpickr = function( dateFormat ) {
	if ( ! frmAdminDatepicker.prototype.isFlatpickrOn() || ! dateFormat || ! frmAdminDatepicker.jqueryToFlatpickrDateFormatMap[ dateFormat ] ) {
		return dateFormat;
	}
	return frmAdminDatepicker.jqueryToFlatpickrDateFormatMap[ dateFormat ];
};

/**
 * Formats a date.
 *
 * @param {Date}   date   Date object.
 * @param {string} format Date format.
 * @return {string} The formatted date string.
 */
frmAdminDatepicker.formatDate = function( date, format ) {
	if ( frmAdminDatepicker.prototype.isFlatpickrOn() ) {
		const flatPickrDateFormat = frmAdminDatepicker.maybeConvertjQueryDateFormatToFlatpickr( format );
		return flatpickr.formatDate( date, flatPickrDateFormat );
	}

	const dateFormat = format;
	return jQuery.datepicker.formatDate( dateFormat, date );
};

/**
 * Parses a date string.
 *
 * @param {string} dateStr Date string.
 * @param {string} format  Date format.
 * @return {Date} The parsed date object.
 */
frmAdminDatepicker.parseDate = function( dateStr, format ) {
	if ( frmAdminDatepicker.prototype.isFlatpickrOn() ) {
		const flatPickrDateFormat = frmAdminDatepicker.maybeConvertjQueryDateFormatToFlatpickr( format );
		return flatpickr.parseDate( dateStr, flatPickrDateFormat );
	}

	const dateFormat = format;
	return jQuery.datepicker.parseDate( dateFormat, dateStr );
};

/**
 * Hack jQuery UI Datepicker to prevent closing of the dialog when a date is selected (only inside our datepickers).
 *
 * @param {jQuery} $ jQuery object.
 */
frmAdminDatepicker.jqueryPreventCalendarClose = ( $ ) => {
	if ( frmAdminDatepicker.prototype.isFlatpickrOn() ) {
		return;
	}
	const originalCallback = $.datepicker._hideDatepicker;
	$.datepicker._hideDatepicker = function() {
		const inst = this._curInst;
		const target = this._curInst.input[ 0 ];
		const wasDateSelected = ( 'undefined' !== typeof inst.frmDatesDateSelected && inst.frmDatesDateSelected );

		if ( wasDateSelected ) {
			inst.frmDatesDateSelected = false;
			$( target ).datepicker( 'refresh' );

			return;
		}

		return originalCallback.apply( this, arguments );
	};
};

function frmAdminDateRangesSyncFieldOptions() {}

/**
 * Gets the settings container for the 'End Date' field.
 *
 * @param {Event} event Event object.
 * @return {Object|null} The settings container for the 'End Date' field.
 */
frmAdminDateRangesSyncFieldOptions.getEndRangeFieldSettings = ( event ) => {
	const settingsContainer = event.target.closest( '.frm-single-settings' );
	const fieldId = settingsContainer.querySelector( 'input[name="frm_fields_submitted[]"]' ).value;
	const isStartRangeField = settingsContainer.querySelector( '#frm_is_range_start_field_' + fieldId );

	if ( null === isStartRangeField || '1' !== isStartRangeField.value ) {
		return null;
	}

	const endRangeFieldId = settingsContainer.querySelector( '#frm_range_end_field_' + fieldId ).value;
	frmAdminDateRangesSyncFieldOptions.dateRangeMoveFieldSettings( endRangeFieldId );

	return document.getElementById( 'frm-single-settings-' + endRangeFieldId );
};

/**
 * Handles the change of a days of the week field.
 *
 * @param {Event} event Event object.
 */
frmAdminDateRangesSyncFieldOptions.daysOfTheWeekChange = ( event ) => {
	const endRangeFieldSettings = frmAdminDateRangesSyncFieldOptions.getEndRangeFieldSettings( event );
	if ( null === endRangeFieldSettings ) {
		return;
	}

	let index = parseInt( event.target.value ) - 1;
	index = index < 0 ? 6 : index;

	const target = endRangeFieldSettings.querySelectorAll( '.frmdates_days_of_the_week input[type="checkbox"]' )[ index ];

	if ( null === target ) {
		return;
	}

	target.dispatchEvent( new MouseEvent( 'click', {
		bubbles: true,
		cancelable: true,
		view: window,
	} ) );
};

/**
 * Handles the change of a days of the week toggle field.
 *
 * @param {Event} event Event object.
 */
frmAdminDateRangesSyncFieldOptions.daysOfTheWeekToggle = ( event ) => {
	const endRangeFieldSettings = frmAdminDateRangesSyncFieldOptions.getEndRangeFieldSettings( event );

	if ( null === endRangeFieldSettings ) {
		return;
	}

	const target = endRangeFieldSettings.querySelector( '.frmdates_days_of_the_week_toggle input[type="checkbox"]' );

	if ( null === target ) {
		return;
	}

	target.dispatchEvent( new MouseEvent( 'click', {
		bubbles: true,
		cancelable: true,
		view: window,
	} ) );
};

/**
 * Handles the change of a show/hide field.
 *
 * @param {Event} event Event object.
 */
frmAdminDateRangesSyncFieldOptions.showHide = ( event ) => {
	const endRangeFieldSettings = frmAdminDateRangesSyncFieldOptions.getEndRangeFieldSettings( event );

	if ( null === endRangeFieldSettings ) {
		return;
	}

	const target = endRangeFieldSettings.querySelector( '.frm_date_show[data-show="' + event.target.dataset.show + '"]' ) || endRangeFieldSettings.querySelector( '.frm_date_show[data-hide="' + event.target.dataset.hide + '"]' );

	switch ( target.type ) {
		case 'checkbox':
			target.dispatchEvent( new MouseEvent( 'click', {
				bubbles: false,
				cancelable: true,
				view: window,
			} ) );
			break;
		case 'select-one':
			target.options[ event.target.selectedIndex ].selected = true;
			target.dispatchEvent( new MouseEvent( 'change', {
				bubbles: false,
				cancelable: true,
				view: window,
			} ) );
			break;
	}
};

/**
 * Handles the change of a year range field.
 *
 * @param {Event} event Event object.
 */
frmAdminDateRangesSyncFieldOptions.synYearRangeValues = ( event ) => {
	const endRangeFieldSettings = frmAdminDateRangesSyncFieldOptions.getEndRangeFieldSettings( event );
	if ( ! endRangeFieldSettings ) {
		return;
	}

	const endRangeFieldId = endRangeFieldSettings.querySelector( 'input[name="frm_fields_submitted[]"]' ).value;
	if ( ! endRangeFieldId ) {
		return;
	}

	const inputName = event.target.name.replace( /_([0-9]+)?\]/, '_' + endRangeFieldId + ']' );
	const endYearField = endRangeFieldSettings.querySelector( `input[name="${ inputName }"]` );

	if ( endYearField ) {
		endYearField.value = event.target.value;
	}
};

/**
 * Date range settings.
 *
 * @type {Object}
 * @property {Array} fieldSettingsMoved An array of field IDs that have had their settings moved to left sidebar form.
 */
frmAdminDateRangesSyncFieldOptions.dateRangeSettings = {
	fieldSettingsMoved: [],
};

/**
 * Moves the field settings to the left sidebar form.
 * This ensures that when a setting is changed from 'Start Date', the corresponding 'End Date' settings are also updated. Without this, synced changes won't apply to 'End Date' because its settings fields aren't included in the sidebar form
 *
 * @param {string} fieldId Field ID.
 */
frmAdminDateRangesSyncFieldOptions.dateRangeMoveFieldSettings = ( fieldId ) => {
	if ( 'undefined' === typeof window.frmAdminBuild || 'undefined' === typeof window.frmAdminBuild.moveFieldSettings || frmAdminDateRangesSyncFieldOptions.dateRangeSettings.fieldSettingsMoved.includes( fieldId ) ) {
		return;
	}
	window.frmAdminBuild.moveFieldSettings( document.getElementById( 'frm-single-settings-' + fieldId ) );
	frmAdminDateRangesSyncFieldOptions.dateRangeSettings.fieldSettingsMoved.push( fieldId );
};

/**
 * Makes the relationship between the start and end date fields.
 *
 * @param {string} startDateFieldId Start date field ID.
 * @param {string} endDateFieldId   End date field ID.
 * @param {string} fieldType        Field type.
 */
frmAdminDateRangesSyncFieldOptions.makeRelationshipBetweenStartEndDateFields = ( startDateFieldId, endDateFieldId, fieldType ) => {
	if ( 'date' !== fieldType ) {
		return;
	}

	const rangeOptionsContainer = document.querySelector( 'frm-date-range-options-', startDateFieldId );

	if ( ! rangeOptionsContainer ) {
		return;
	}

	const rangeOptionIsRangeStartField = frmDom.tag( 'input', { id: 'frm_is_range_start_field_' + startDateFieldId } );
	rangeOptionsContainer.appendChild( rangeOptionIsRangeStartField );

	rangeOptionIsRangeStartField.setAttribute( 'type', 'hidden' );
	rangeOptionIsRangeStartField.setAttribute( 'name', 'field_options[is_range_start_field_' + startDateFieldId + ']' );
	rangeOptionIsRangeStartField.setAttribute( 'value', 1 );

	const rangeOptionRangeEndField = frmDom.tag( 'input', { id: 'frm_range_end_field_' + startDateFieldId } );
	rangeOptionsContainer.appendChild( rangeOptionRangeEndField );

	rangeOptionRangeEndField.setAttribute( 'type', 'hidden' );
	rangeOptionRangeEndField.setAttribute( 'name', 'field_options[range_end_field_' + startDateFieldId + ']' );
	rangeOptionRangeEndField.setAttribute( 'value', endDateFieldId );
};
