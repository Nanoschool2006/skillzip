/* global frmProForm, jQuery, $, frm_js, flatpickr, frmdates */
jQuery( function( $ ) {
	const frmdates = {
		normalizeSettings( fieldSettings ) {
			return $.extend(
				{},
				{ triggerID: fieldSettings.triggerID, repeating: -1 !== fieldSettings.triggerID.indexOf( '^' ), locale: fieldSettings.locale },
				{ datepickerOptions: fieldSettings.options },
				fieldSettings.formidable_dates
			);
		},

		getTargets( fieldConfig ) {
			const targets = [];

			$( fieldConfig.triggerID ).each(
				function() {
					if ( fieldConfig.repeating && fieldConfig.inline ) {
						targets.push( $( this ).siblings( '.frm_date_inline' ) );
					} else {
						targets.push( $( this ) );
					}
				}
			);

			return targets;
		},

		setupFields() {
			let hasSettings;
			const dateSettings = window.__frmDatepicker;

			$.each(
				dateSettings,
				function() {
					if ( 'undefined' !== typeof this.formidable_dates && this.formidable_dates ) {
						// Trigger changes if any field in the form has extended settings.
						hasSettings = true;
					}
				}
			);

			if ( ! hasSettings ) {
				return;
			}

			$.each( dateSettings, function( index ) {
				const fieldConfig = frmdates.normalizeSettings( this );
				const hasConfig = 'undefined' !== typeof this.formidable_dates && this.formidable_dates;

				if ( 0 === $( fieldConfig.triggerID ).length ) {
					return;
				}

				if ( ! hasConfig ) {
					// Trigger changes in case other fields depend on it.
					if ( ! frmDatepickerInstance.isFlatpickrOn() ) {
						// TODO: Remove this once we're sure Flatpickr is always available.
						window.__frmDatepicker[ index ].options.onSelect = $.proxy( frmdates.callbacks.onSelect, fieldConfig );
					} else {
						window.__frmDatepicker[ index ].options.onChange = frmDatepickerInstance.onChange;
					}
					return;
				}

				if ( frmDatepickerInstance.isFlatpickrOn() ) {
					fieldConfig.datepickerOptions.onOpen = frmProForm.frmDatepicker.callbacks.onOpen;
					fieldConfig.datepickerOptions.onClose = frmProForm.frmDatepicker.callbacks.onClose;
					fieldConfig.datepickerOptions.onChange = frmDatepickerInstance.onChange;
				} else {
					// TODO: Remove this once we're sure flatpickr is always available.
					if ( ! fieldConfig.inline && 'undefined' !== typeof frmProForm && 'function' === typeof frmProForm.addFormidableClassToDatepicker && 'function' === typeof frmProForm.removeFormidableClassFromDatepicker ) {
						fieldConfig.datepickerOptions.beforeShow = frmProForm.addFormidableClassToDatepicker;
						fieldConfig.datepickerOptions.onClose = frmProForm.removeFormidableClassFromDatepicker;
					}
					fieldConfig.datepickerOptions.beforeShowDay = $.proxy( frmdates.callbacks.beforeShowDay, fieldConfig );
					fieldConfig.datepickerOptions.onSelect = $.proxy( frmdates.callbacks.onSelect, fieldConfig );
				}

				fieldConfig.datepickerOptions.minDate = ! fieldConfig.repeating ? frmdates.getMinOrMaxDate( 'minimum_date', fieldConfig ) : null;
				fieldConfig.datepickerOptions.maxDate = ! fieldConfig.repeating ? frmdates.getMinOrMaxDate( 'maximum_date', fieldConfig ) : null;

				// Hijack global settings so our functions are called.
				window.__frmDatepicker[ index ].options = fieldConfig.datepickerOptions;

				$.each( frmdates.getTargets( fieldConfig ), function() {
					let altField, dateFormat;
					let localConfig = fieldConfig.datepickerOptions;
					const frmDatePicker = new frmDatepickerInstance( this, fieldConfig );

					if ( fieldConfig.inline ) {
						this.addClass( 'frm-datepicker' );

						altField = document.getElementById( this.attr( 'id' ) + '_alt' );
						if ( null !== altField && '' !== altField.value ) {
							dateFormat = frmDatePicker.getDateFormat();
							if ( null !== dateFormat ) {
								localConfig.defaultDate = frmDatePicker.getDate();
							} else {
								localConfig.defaultDate = altField.value;
							}
						}

						// Get default date for inline datepicker based on offset when the active date is a blackout date
						// This will be ignored for default calculation dates which are handled in setInlineDatepickerAfterCalc
						frmdates.defaultDateOffset( fieldConfig, localConfig );
						frmDatePicker.setDefaultInlineDateForFlatpickrOnly( new Date( localConfig.defaultDate + 'T00:00:00' ) );
					}

					if ( fieldConfig.repeating ) {
						// Min. or max. date might need to be computed based on the repeating container.
						localConfig = $.extend(
							localConfig,
							{
								minDate: frmdates.getMinOrMaxDate( 'minimum_date', fieldConfig, this ),
								maxDate: frmdates.getMinOrMaxDate( 'maximum_date', fieldConfig, this ),
							}
						);
					}

					localConfig = frmdates.adjustYearRange( localConfig );

					// Handle localization.
					// TODO: Remove this once we're sure Flatpickr is always available.
					if ( ! frmDatepickerInstance.isFlatpickrOn() ) {
						localConfig = $.extend(
							{},
							$.datepicker.regional[ fieldConfig.locale ],
							localConfig
						);
					}

					if ( this.data( 'frmdates_configured' ) || this.hasClass( 'hasDatepicker' ) ) {
						frmDatePicker.updateConfig( localConfig );
					} else {
						fieldConfig.datepickerOptions.locale = fieldConfig.locale;
						frmDatePicker.initInstance( fieldConfig, localConfig );
					}

					// jQuery datepicker only
					if ( ! localConfig.defaultDate && fieldConfig.inline && ! frmDatepickerInstance.isFlatpickrOn() ) {
						frmDatePicker.setDate( null );
						this.find( '.ui-state-active' ).removeClass( 'ui-state-active ui-state-hover' ).parent().removeClass( 'ui-datepicker-current-day' );
					}

					this.data( 'frmdates_configured', true );

					if ( fieldConfig.repeating && fieldConfig.inline ) {
						altField = this.closest( '.frm_repeat_sec, .frm_repeat_inline, .frm_repeat_grid' ).find( 'input[id^="' + this.attr( 'id' ) + '"]' );
						if ( altField.length > 0 ) {
							frmDatepickerInstance.setAltField( this[ 0 ], altField[ 0 ] );
						}
					}
				} );
			} );
		},

		getMinOrMaxDate( limit, field, $instance ) {
			let $container, $sourceField;
			let result = null;

			const condition = field[ limit + '_cond' ];
			if ( ! condition ) {
				return null;
			}

			const val = field[ limit + '_val' ];

			// Specific date.
			if ( 'date' === condition ) {
				return frmDatepickerInstance.parseDate( val, 'yy-mm-dd' );
			}

			// Relative dates.
			if ( 'today' === condition ) {
				result = new Date();
			} else if ( 'field_' === condition.substr( 0, 6 ) ) {
				// First search for the condition field inside the same repeating container.
				if ( field.repeating && $instance ) {
					$container = $instance.closest( '.frm_repeat_sec, .frm_repeat_inline, .frm_repeat_grid' );
					$sourceField = $container.find( '[id^="' + condition + '"].frm_date_inline' );
					$sourceField = ( 0 === $sourceField.length ) ? $container.find( 'input[id^="' + condition + '"]' ) : $sourceField;
				}

				$sourceField = ( ! $sourceField || 0 === $sourceField.length ) ? $( '#' + condition ) : $sourceField;

				if ( $sourceField && 1 === $sourceField.length ) {
					// The field might be on a different page and it's hidden now.
					if ( $sourceField.is( 'input[type="hidden"]' ) ) {
						// All date fields use the same dateFormat value, so we can re-use the one from `field`.
						result = frmDatepickerInstance.parseDate( $sourceField.val(), null, field.datepickerOptions );
					} else {
						result = frmDatepickerInstance.getDate( $sourceField[ 0 ] );
						if ( ! result && $sourceField.val() ) {
							// if source field datepicker is not initialized, the case when source doesn't have custom settings
							result = new Date( $sourceField.val() );
						}
					}
				}

				if ( ! result ) {
					return null;
				}
			}

			result = this.applyDateOffset( result, val );
			return result;
		},

		adjustYearRange( localConfig ) {
			const parts = localConfig.yearRange.split( ':' );
			let start = parts[ 0 ];
			let end = parts[ 1 ];

			if ( null !== localConfig.minDate ) {
				start = localConfig.minDate.getFullYear();
			}

			if ( null !== localConfig.maxDate ) {
				end = localConfig.maxDate.getFullYear();
			}

			return $.extend(
				localConfig,
				{
					yearRange: start + ':' + end,
				}
			);
		},

		applyDateOffset( date, offset, settings ) {
			let matches;
			const pattern = /([+\-]?[0-9]+)\s*(d|day|days|w|week|weeks|m|month|months|y|year|years)?/g;

			if ( ! offset ) {
				return date;
			}

			date.setHours( 0 );
			date.setMinutes( 0 );
			date.setSeconds( 0 );
			date.setMilliseconds( 0 );

			const oldDate = new Date( date.getTime() );
			offset = offset.replaceAll( /\s/g, '' ).replace( '--', '' ).replace( '+-', '-' ).replace( '-+', '-' ).toLowerCase();
			matches = pattern.exec( offset );

			while ( matches ) {
				switch ( matches[ 2 ] ) {
					case 'd':
					case 'day':
					case 'days':
						date.setDate( date.getDate() + parseInt( matches[ 1 ], 10 ) );
						break;
					case 'w':
					case 'week':
					case 'weeks':
						date.setDate( date.getDate() + ( 7 * parseInt( matches[ 1 ], 10 ) ) );
						break;
					case 'm':
					case 'month':
					case 'months':
						date.setMonth( date.getMonth() + parseInt( matches[ 1 ], 10 ) );
						break;
					case 'y':
					case 'year':
					case 'years':
						date.setFullYear( date.getFullYear() + parseInt( matches[ 1 ], 10 ) );
						break;
				}

				matches = pattern.exec( offset );
			}

			if ( settings && settings.skipBlockedDatesFromCalc ) {
				return frmdates.maybeSkipBlockedDates( oldDate, date, settings );
			}

			return date;
		},

		maybeSkipBlockedDates( oldDate, newDate, settings ) {
			let i;

			const daysDiff = ( newDate.getTime() - oldDate.getTime() ) / 86400000;
			const isMinus = daysDiff < 0;

			// Increase or decrease date with a loop, skip blocked dates in each loop.
			for ( i = 0; i < Math.abs( daysDiff ); i++ ) {
				oldDate.setDate( isMinus ? ( oldDate.getDate() - 1 ) : ( oldDate.getDate() + 1 ) );
				oldDate = frmdates.getNextAvailableDate( oldDate, settings, isMinus );
			}

			return oldDate;
		},

		/**
		 * Gets date object from date string.
		 *
		 * @param {string} dateStr Date string.
		 * @return {Object|false} Date object or false if invalid date.
		 */
		getDateFromStr( dateStr ) {
			const date = new Date( dateStr );
			if ( date instanceof Date && ! isNaN( date ) ) {
				return date;
			}
			return false;
		},

		/**
		 * Gets date settings from field ID.
		 *
		 * @param {number} fieldId Field ID.
		 * @return {Object|false} Date settings object or false if not found.
		 */
		getDateSettingsFromFieldId( fieldId ) {
			let field, i;
			const dateSettings = window.__frmDatepicker;

			for ( i = 0; i < dateSettings.length; i++ ) {
				if ( parseInt( fieldId ) === parseInt( dateSettings[ i ].fieldId ) ) {
					field = dateSettings[ i ];
					break;
				}
			}

			if ( ! field ) {
				return false;
			}

			return this.normalizeSettings( field );
		},

		/**
		 * Parses date calculation string to get start date and diff string.
		 *
		 * @param {string} str      Date calculation string.
		 * @param {Object} calc     Calculation data.
		 * @param {Object} settings Normalized field settings.
		 * @return {Object|false|undefined}   Return an object with `start` and `diff` if success, false on error, undefined if start date is empty.
		 */
		parseCalcStr( str, calc, settings ) {
			const data = {
				start: '',
				diff: '',
			};
			const parsedStr = str.split( '+' );

			if ( ! parsedStr[ 0 ] ) { // Start date is empty.
				return;
			}

			if ( ! isNaN( parsedStr[ 0 ] ) ) { // Is number of days since 1/1/1970.
				data.start = new Date( parsedStr[ 0 ] * 24 * 60 * 60 * 1000 );
			} else {
				try {
					data.start = frmDatepickerInstance.parseDate( parsedStr[ 0 ], null, settings.datepickerOptions );
				} catch ( e ) {
					return false;
				}
			}

			if ( 2 === parsedStr.length ) {
				data.diff = parsedStr[ 1 ];
			} else if ( 3 === parsedStr.length ) { // [date]++3 days.
				data.diff = parsedStr[ 2 ];
			}

			return data;
		},

		/**
		 * Checks if the given date is blocked.
		 *
		 * @param {Object} date     Date object.
		 * @param {Object} settings Normalized field settings.
		 * @return {boolean} True if date is blocked, false otherwise.
		 */
		isBlockedDate( date, settings ) {
			let dateStr;

			// Check against blackout dates.
			if ( settings.datesDisabled && settings.datesDisabled.length ) {
				dateStr = frmDatepickerInstance.formatDate( date, 'yy-mm-dd' );
				if ( -1 !== settings.datesDisabled.indexOf( dateStr ) ) {
					return true;
				}
			}

			// Check against days of the week.
			if ( settings.daysEnabled && settings.daysEnabled.length ) {
				if ( -1 === settings.daysEnabled.indexOf( date.getDay() ) ) {
					return true;
				}
			}

			return false;
		},

		/**
		 * Gets the next available date.
		 *
		 * @param {Object}  date     Date object.
		 * @param {Object}  settings Normalized field settings.
		 * @param {boolean} isMinus  Is minus date.
		 * @return {Object} The next available date object.
		 */
		getNextAvailableDate( date, settings, isMinus ) {
			while ( this.isBlockedDate( date, settings ) ) {
				date.setDate( isMinus ? ( date.getDate() - 1 ) : ( date.getDate() + 1 ) );
			}

			return date;
		},

		setInlineDatepickerAfterCalc() {
			document.addEventListener( 'frmCalcUpdatedTotal', function( event ) {
				if ( ! event.frmData || ! event.frmData.totalField || ! event.frmData.totalField.length ) {
					return;
				}

				if ( ! event.frmData.totalField.hasClass( 'frm_date_inline' ) ) {
					return;
				}

				const hiddenInput = event.frmData.totalField.prev();
				if ( 0 === hiddenInput[ 0 ].name.indexOf( 'item_meta[' ) ) {
					hiddenInput.val( event.frmData.total );
				}
				frmDatepickerInstance.setDate( event.frmData.totalField[ 0 ], event.frmData.total );
			} );
		},

		resetInlineDatepickerAfterStartOver() {
			/**
			 * Gets default date from the input.
			 *
			 * @param {HTMLElement} input Input element.
			 * @return {Date|null} Date object or null if no value is found.
			 */
			function getDefaultVal( input ) {
				let val = input.getAttribute( 'data-frmval' );
				if ( ! val ) {
					return null;
				}

				val = new Date( val );
				if ( isNaN( val ) ) {
					return null;
				}

				return removeTimezoneFromDate( val );
			}

			function removeTimezoneFromDate( date ) {
				const offset = date.getTimezoneOffset() * 60000; // getTimezoneOffset() return minutes.
				date.setTime( date.getTime() + offset );
				return date;
			}

			document.addEventListener( 'frm_after_start_over', function( event ) {
				let i, defaultVal, currentCell;

				const datepickerEls = document.querySelectorAll( '#frm_form_' + event.frmData.formId + '_container .frm_date_inline' );
				if ( ! datepickerEls ) {
					return;
				}

				for ( i = 0; i < datepickerEls.length; i++ ) {
					defaultVal = getDefaultVal( datepickerEls[ i ].previousElementSibling );
					frmDatepickerInstance.setDate( datepickerEls[ i ], defaultVal );
					if ( ! defaultVal ) {
						// Reset styling of the current date cell if no default date.
						currentCell = datepickerEls[ i ].querySelector( '.ui-datepicker-today' );
						if ( currentCell ) {
							currentCell.classList.remove( 'ui-datepicker-current-day' );
							currentCell.querySelector( 'a' ).classList.remove( 'ui-state-active' );
						}
					}
				}
			} );
		},

		init() {
			if ( 'undefined' === typeof window.__frmDatepicker || ! window.__frmDatepicker ) {
				return;
			}

			frmdates.setupFields();

			$( document ).on( 'frmPageChanged frmFormComplete frmAfterAddRow frmAfterRemoveRow', frmdates.setupFields );
			$( document ).on( 'frmdates_date_changed', frmdates.callbacks.dateChanged );

			this.setInlineDatepickerAfterCalc();
			this.resetInlineDatepickerAfterStartOver();
		},

		defaultDateOffset( fieldConfig, localConfig ) {
			let isAllowed;
			let defaultDate = fieldConfig.datepickerOptions.defaultDate;
			const minDate = fieldConfig.datepickerOptions.minDate;

			if ( null === defaultDate || '' === defaultDate ) {
				return;
			}

			if ( ! frmDatepickerInstance.isFlatpickrOn() ) {
				defaultDate = new Date( defaultDate );
			}
			if ( minDate && defaultDate < minDate ) {
				defaultDate = minDate;
			}

			do {
				if ( ! frmDatepickerInstance.isFlatpickrOn() ) {
					isAllowed = fieldConfig.datepickerOptions.beforeShowDay( defaultDate );
					isAllowed = isAllowed[ 0 ];
				} else {
					isAllowed = ! fieldConfig.datesDisabled.includes( defaultDate );
				}

				if ( false === isAllowed ) {
					defaultDate = frmDatepickerInstance.isFlatpickrOn() ? frmdates.defaultDate( new Date( defaultDate + 'T00:00:00' ) ) : frmdates.defaultDate( defaultDate );
				}
			}
			while ( false === isAllowed );

			localConfig.defaultDate = frmDatepickerInstance.isFlatpickrOn() ? defaultDate : new Date( defaultDate.toISOString().slice( 0, -1 ) );
		},

		defaultDate( _date ) {
			_date.setDate( _date.getDate() + 1 );
			return _date;
		},

		callbacks: {
			beforeShowDay( date ) {
				let isAllowed = false;

				if ( ! date ) {
					return [ true, '' ];
				}

				const day = date.getDay();
				const year = date.getFullYear();
				const month = ( '0' + ( date.getMonth() + 1 ) ).slice( -2 );
				const day_ = ( '0' + date.getDate() ).slice( -2 );
				const dateISO = year + '-' + month + '-' + day_;

				if ( -1 !== $.inArray( dateISO, this.datesEnabled ) ) {
					isAllowed = true;
				} else if ( -1 !== $.inArray( dateISO, this.datesDisabled ) ) {
					isAllowed = false;
				} else if ( -1 !== $.inArray( day, this.daysEnabled ) ) {
					isAllowed = true;
				}

				return [ isAllowed && eval( this.selectableResponse ), '' ]; // eslint-disable-line no-eval
			},

			// TODO: Remove this once we're sure Flatpickr is always available.
			onSelect( dateText, instance ) {
				const field = instance.input.get( 0 );
				const fieldId = frmdates.getFieldIdFromField( field );
				const mockEventObject = {
					currentTarget: field,
					type: 'change',
					target: field,
				};

				$( document ).trigger( 'frmdates_date_changed', [ this, dateText, instance ] );
				$( document ).trigger( 'frmFieldChanged', [ field, fieldId, mockEventObject ] );
				instance.input.trigger( 'change' );
			},

			dateChanged() {
				frmdates.setupFields(); // TODO: For now, we refresh everything, but we should be more clever here.
			},
		},

		getFieldIdFromField( field ) {
			const $parentFormField = jQuery( field ).closest( '.frm_form_field' );
			const strippedFieldIdString = $parentFormField.attr( 'id' ).replace( 'frm_field_', '' ).replace( '_container', '' );
			const fieldIdParts = strippedFieldIdString.split( '-' );

			return fieldIdParts[ 0 ];
		},
	};

	frmdates.init();

	/**
	 * Handle date calculations.
	 *
	 * @param {string}           thisFullCalc
	 * @param {Object|undefined} args         Prior to v6.21 of Pro this is undefined.
	 * @return {string} The calculated date value.
	 */
	window.frmProGetCalcTotaldate = function( thisFullCalc, args ) {
		if ( 'undefined' === typeof window.__frmDatepicker ) {
			if ( 'undefined' !== typeof args && args.totalField ) {
				// args.totalField is a jQuery object.
				// Instead of trying to calculate the date,
				// return its value (so it remains unchanged).
				return args.totalField.val();
			}

			return '';
		}

		const settings = frmdates.getDateSettingsFromFieldId( this.field_id );
		if ( ! settings ) {
			return '';
		}

		const parsedData = frmdates.parseCalcStr( thisFullCalc, this, settings );
		if ( ! parsedData ) {
			return '';
		}

		const resultDate = frmdates.applyDateOffset( parsedData.start, parsedData.diff, settings );

		return frmDatepickerInstance.formatDate( resultDate, null, settings.datepickerOptions );
	};

	window.frmCalcDateDifferenceDays = function( a, b, fieldId, compareId ) {
		let swap, swapped, numberOfDays;

		const fieldSettings = frmdates.getDateSettingsFromFieldId( parseInt( fieldId ) );
		const compareSettings = frmdates.getDateSettingsFromFieldId( parseInt( compareId ) );

		if ( ! fieldSettings && ! compareSettings ) {
			return Math.floor( b - a ) / 86400000;
		}

		// Make sure a is always the lowest value.
		// This is because we loop from a to b.
		// If this gets swapped, the final result is returned as a negative value.
		swapped = false;
		if ( a > b ) {
			swapped = true;
			swap = b;
			b = a;
			a = swap;
		}

		const currentDateIsBlockedForSetting = function( settings ) {
			return settings && settings.skipBlockedDatesFromCalc && frmdates.isBlockedDate( currentDate, settings );
		};

		const currentDateIsBlocked = function() {
			return currentDateIsBlockedForSetting( fieldSettings ) || currentDateIsBlockedForSetting( compareSettings );
		};

		// Count all of the dates that are not blocked.
		const currentDate = a;
		numberOfDays = 0;
		while ( currentDate < b ) {
			if ( ! currentDateIsBlocked() ) {
				++numberOfDays;
			}
			currentDate.setDate( currentDate.getDate() + 1 );
		}

		if ( swapped ) {
			numberOfDays = -numberOfDays;
		}

		return numberOfDays;
	};
} );

/**
 * Datepicker instance.
 *
 * @param {HTMLElement} dateInput Date input element.
 * @param {Object}      config    Datepicker config.
 * @return {Object} Datepicker instance object.
 */
function frmDatepickerInstance( dateInput = null, config = {} ) {
	const _this = this;

	this.isFlatpickrOn = frmDatepickerInstance.isFlatpickrOn();

	if ( this.isFlatpickrOn ) {
		this.instance = null !== dateInput && 'undefined' !== typeof dateInput[ 0 ] && 'undefined' !== typeof dateInput[ 0 ]._flatpickr ? dateInput[ 0 ]._flatpickr : new frmProForm.frmDatepicker( dateInput[ 0 ], config );
	} else {
		// TODO: Remove this once we're sure flatpickr is always available.
		this.instance = null !== dateInput ? dateInput : jQuery;
	}

	/**
	 * Initializes the datepicker instance.
	 *
	 * @param {Object} fieldConfig Field configuration options.
	 * @param {Object} localConfig Datepicker config.
	 */
	this.initInstance = function( fieldConfig, localConfig ) {
		if ( _this.isFlatpickrOn ) {
			const dateInputEl = null !== dateInput && 'undefined' !== typeof dateInput[ 0 ] ? dateInput[ 0 ] : dateInput;
			if ( null !== dateInputEl ) {
				if ( dateInputEl._flatpickr ) {
					_this.instance.destroy();
					dateInputEl._flatpickr.destroy();
					delete dateInputEl._flatpickr;
				}
				_this.instance = new frmProForm.frmDatepicker( dateInputEl, fieldConfig );
			}
			return;
		}

		// TODO: Remove this once we're sure flatpickr is always available.
		_this.instance.datepicker( localConfig );
	};

	/**
	 * Sets the date.
	 *
	 * @param {Date} date Date object.
	 */
	this.setDate = function( date ) {
		if ( _this.isFlatpickrOn ) {
			_this.instance.setDate( date );
		} else {
			// TODO: Remove this once we're sure flatpickr is always available.
			_this.instance.datepicker( 'setDate', date );
		}
	};

	/**
	 * Gets the date format.
	 *
	 * @return {string} The date format string.
	 */
	this.getDateFormat = function() {
		if ( _this.isFlatpickrOn ) {
			return _this.instance.config.dateFormat;
		}
		// TODO: Remove this once we're sure flatpickr is always available.
		return _this.instance.datepicker( 'option', 'dateFormat' );
	};

	/**
	 * Gets the date.
	 *
	 * @return {Date} The current date object.
	 */
	this.getDate = function() {
		if ( _this.isFlatpickrOn ) {
			if ( true === _this.instance.config.altInput ) {
				return _this.instance.config.altInputElement.value;
			}
			return _this.instance.getDate();
		}
		// TODO: Remove this once we're sure flatpickr is always available.
		return _this.instance.datepicker( 'getDate' );
	};

	/**
	 * Updates the datepicker config.
	 *
	 * @param {Object} newConfig Datepicker config.
	 */
	this.updateConfig = function( newConfig ) {
		if ( _this.isFlatpickrOn ) {
			_this.instance.config = newConfig;
		} else {
			// TODO: Remove this once we're sure flatpickr is always available.
			_this.instance.datepicker( 'option', newConfig );
		}
	};

	/**
	 * Sets the default date for Flatpickr only.
	 *
	 * @param {Date} date Date object.
	 */
	this.setDefaultInlineDateForFlatpickrOnly = function( date ) {
		if ( ! _this.isFlatpickrOn ) {
			return;
		}
		_this.instance.setDate( date );
	};

	return {
		instance: this.instance,
		initInstance: this.initInstance,
		setDate: this.setDate,
		getDate: this.getDate,
		getDateFormat: this.getDateFormat,
		updateConfig: this.updateConfig,
		setDefaultInlineDateForFlatpickrOnly: this.setDefaultInlineDateForFlatpickrOnly,
	};
}

/**
 * jQuery datepicker format to Flatpickr format map.
 *
 * @type {Object}
 */
frmDatepickerInstance.jqueryToFlatpickrDateFormatMap = {
	'yy-mm-dd': 'Y-m-d',
};

/**
 * Parses a date string.
 *
 * @param {string} dateStr Date string.
 * @param {string} format  Date format.
 * @param {Object} options Options.
 * @return {Date} The parsed date object.
 */
frmDatepickerInstance.parseDate = function( dateStr, format, options = {} ) {
	if ( frmDatepickerInstance.isFlatpickrOn() ) {
		const flatPickrDateFormat = frmDatepickerInstance.maybeConvertjQueryDateFormatToFlatpickr( format ) || options.fpDateFormat;
		return flatpickr.parseDate( dateStr, flatPickrDateFormat );
	}

	// TODO: Remove this once we're sure flatpickr is always available.
	const dateFormat = format || options.dateFormat;
	return jQuery.datepicker.parseDate( dateFormat, dateStr );
};

/**
 * Checks if Flatpickr is available.
 *
 * @return {boolean} True if Flatpickr is available, false otherwise.
 */
frmDatepickerInstance.isFlatpickrOn = function() {
	return window.frm_js && frm_js.datepickerLibrary === 'flatpickr';
};

/**
 * Sets the date.
 *
 * @param {HTMLElement} input     Input element.
 * @param {Date}        dateValue Date value.
 */
frmDatepickerInstance.setDate = function( input, dateValue ) {
	if ( frmDatepickerInstance.isFlatpickrOn() ) {
		input._flatpickr.setDate( dateValue );
	} else {
		// TODO: Remove this once we're sure flatpickr is always available.
		jQuery( input ).datepicker( 'setDate', dateValue );
	}
};

/**
 * Gets the date.
 *
 * @param {HTMLElement} input Input element.
 * @return {Date} The date object from the input.
 */
frmDatepickerInstance.getDate = function( input ) {
	if ( frmDatepickerInstance.isFlatpickrOn() ) {
		return input._flatpickr.getDate();
	}
	// TODO: Remove this once we're sure flatpickr is always available.
	return jQuery( input ).datepicker( 'getDate' );
};

/**
 * Sets the alt field.
 *
 * @param {HTMLElement} input    Input element.
 * @param {HTMLElement} altField Alt field element.
 */
frmDatepickerInstance.setAltField = function( input, altField ) {
	if ( frmDatepickerInstance.isFlatpickrOn() ) {
		input._flatpickr.config.altInput = true;
		input._flatpickr.altInput = altField[ 0 ];
	} else {
		// TODO: Remove this once we're sure flatpickr is always available.
		jQuery( input ).datepicker( 'option', 'altField', jQuery( altField ) );
	}
};

/**
 * Formats a date.
 *
 * @param {Date}   date    Date object.
 * @param {string} format  Date format.
 * @param {Object} options Options.
 * @return {string} The formatted date string.
 */
frmDatepickerInstance.formatDate = function( date, format, options = {} ) {
	if ( frmDatepickerInstance.isFlatpickrOn() ) {
		const flatPickrDateFormat = frmDatepickerInstance.maybeConvertjQueryDateFormatToFlatpickr( format ) || options.fpDateFormat;
		return flatpickr.formatDate( date, flatPickrDateFormat );
	}

	// TODO: Remove this once we're sure flatpickr is always available.
	const dateFormat = format || options.dateFormat;
	return jQuery.datepicker.formatDate( dateFormat, date );
};

/**
 * Converts a jQuery default datepicker format to a Flatpickr format.
 *
 * @param {string} dateFormat Date format.
 * @return {string} The converted date format.
 */
frmDatepickerInstance.maybeConvertjQueryDateFormatToFlatpickr = function( dateFormat ) {
	if ( ! frmDatepickerInstance.isFlatpickrOn() || ! dateFormat || ! frmDatepickerInstance.jqueryToFlatpickrDateFormatMap[ dateFormat ] ) {
		return dateFormat;
	}
	return frmDatepickerInstance.jqueryToFlatpickrDateFormatMap[ dateFormat ];
};

/**
 * Handles the date change event in Flatpickr.
 *
 * @param {Array}  selectedDates Selected dates.
 * @param {string} dateText      Date text.
 * @param {Object} instance      Instance.
 */
frmDatepickerInstance.onChange = function( selectedDates, dateText, instance ) {
	const field = instance.element;
	const fieldId = frmdates.getFieldIdFromField( field );
	const mockEventObject = {
		currentTarget: field,
		type: 'change',
		target: field,
	};

	$( document ).trigger( 'frmdates_date_changed', [ this, dateText, instance ] );
	$( document ).trigger( 'frmFieldChanged', [ field, fieldId, mockEventObject ] );
	jQuery( field ).trigger( 'change' );
};
