/**
 * Complianz TCF v2.2 Implementation
 *
 * This file implements IAB Transparency & Consent Framework v2.2 (TCF v2.2)
 * for WordPress/Complianz plugin integration.
 *
 * @package
 * @subpackage  TCF
 * @version     2.2
 * @since       TCF v2.2 support added
 *
 * Key Features:
 * - IAB TCF v2.2 consent management
 * - Google Additional Consent (AC) vendor support
 * - CCPA/US Privacy String implementation
 * - Multi-language support (27+ languages)
 * - Disclosed vendors tracking (v2.2)
 * - Data retention display (v2.2)
 * - Data categories display (v2.2)
 * - Backward compatibility with TCF v2.0 strings
 *
 * Architecture:
 * - Promise-based async initialization
 * - Event-driven consent updates
 * - LocalStorage persistence
 * - DOM-based UI rendering
 *
 * Storage:
 * - localStorage.cmplz_tcf_consent: TC String (encoded consent)
 * - localStorage.cmplz_ac_string: AC String (Google vendors)
 * - Cookie: cmplz_banner-status, cmplz_policy_id, cmplz_usprivacy
 *
 * Resources:
 * - IAB TCF Spec: https://github.com/InteractiveAdvertisingBureau/GDPR-Transparency-and-Consent-Framework
 * - IAB TCF ES Library: https://github.com/InteractiveAdvertisingBureau/iabtcf-es
 *
 * @author      Complianz
 * @license     GPL-2.0+
 */

/* global cmplz_tcf, complianz, cmplz_set_cookie, cmplz_in_array, localStorage, location, __uspapi */
/* eslint-disable camelcase, no-console */

import { CmpApi } from '@iabtechlabtcf/cmpapi';
import { GVL, TCModel, TCString } from '@iabtechlabtcf/core';

/**
 * ============================================================================
 * GLOBAL CONSTANTS
 * ============================================================================
 */

/**
 * CMP ID assigned by IAB to Complianz
 * @constant {number}
 */
const cmplzCMP = 332;

/**
 * CMP version number
 * @constant {number}
 */
const cmplzCMPVersion = 1;

/**
 * Whether this CMP serves a single publisher (true) or multiple (false)
 * Service-specific mode means consent is scoped to a single domain
 * @constant {boolean}
 */
const cmplzIsServiceSpecific = cmplz_tcf.isServiceSpecific === 1 ? true : false;

/**
 * List of available GVL translation languages
 * These are ISO 639-1 two-letter language codes supported by the IAB Global Vendor List
 * @constant {string[]}
 */
const cmplzExistingLanguages = [
	'gl', // Galician
	'eu', // Basque
	'bg', // Bulgarian
	'ca', // Catalan
	'cs', // Czech
	'da', // Danish
	'de', // German
	'el', // Greek
	'es', // Spanish
	'et', // Estonian
	'fi', // Finnish
	'fr', // French
	'hr', // Croatian
	'hu', // Hungarian
	'it', // Italian
	'ja', // Japanese
	'lt', // Lithuanian
	'lv', // Latvian
	'mt', // Maltese
	'nl', // Dutch
	'no', // Norwegian
	'pl', // Polish
	'pt', // Portuguese
	'ro', // Romanian
	'ru', // Russian
	'sk', // Slovak
	'sl', // Slovenian
	'sr', // Serbian
	'sv', // Swedish
	'tr', // Turkish
	'zh', // Chinese
];

/**
 * Total count of available languages for GVL
 * @constant {number}
 */
const langCount = cmplzExistingLanguages.length;

/**
 * Language code from HTML lang attribute, defaults to 'en'
 * Normalized to lowercase for consistent matching
 * @constant {string}
 */
const cmplz_html_lang_attr = document.documentElement.lang.length
	? document.documentElement.lang.toLowerCase()
	: 'en';

/**
 * Detected language code to use for GVL translations
 * Defaults to 'en', will be updated if a matching language is found in cmplzExistingLanguages
 * @type {string}
 */
let cmplzLanguage = 'en';

/**
 * Language detection loop
 * Matches the HTML lang attribute against available GVL languages
 * Uses indexOf() === 0 to ensure exact prefix match (e.g., 'en' in 'en-US')
 */
for ( let i = 0; i < langCount; i++ ) {
	const cmplzLocale = cmplzExistingLanguages[ i ];

	// Special case: Norwegian Bokmål (nb-no) should map to 'no'
	if ( cmplz_html_lang_attr === 'nb-no' ) {
		cmplzLanguage = 'no';
		break;
	}

	// Match language at the start of the string to avoid false positives
	// Example: 'ca' (Catalan) should not match 'fr-ca' (French Canadian)
	if ( cmplz_html_lang_attr.indexOf( cmplzLocale ) === 0 ) {
		cmplzLanguage = cmplzLocale;
		break;
	}
}
// Special case: Basque language code mapping (eu → eus)
if ( cmplzLanguage === 'eu' ) {
	cmplzLanguage = 'eus';
}

/**
 * ============================================================================
 * GLOBAL VARIABLES
 * ============================================================================
 */

/**
 * Loaded GVL language data (purposes, features, etc.)
 * @type {Object|undefined}
 */
let cmplzLanguageJson;

/**
 * TCF v2.2: Data categories from GVL (collected during vendor rendering)
 * @type {Array}
 */
let dataCategories = [];

/**
 * Google Additional Consent (AC) vendors list
 * Contains non-TCF vendors that use Google's AC framework
 * @type {Array}
 */
let ACVendors = [];

/**
 * Whether Additional Consent mode is enabled
 * @constant {boolean}
 */
const useAcVendors = cmplz_tcf.ac_mode;

/**
 * Whether we're on a CCPA opt-out policy page (US Privacy)
 * Detected by presence of US vendor container element
 * @constant {boolean}
 */
const onOptOutPolicyPage =
	document.getElementById( 'cmplz-tcf-us-vendor-container' ) !== null;

/**
 * ============================================================================
 * RESOURCE URLS
 * ============================================================================
 */

/**
 * URL for Additional Consent vendors CSV file
 * @constant {string}
 */
const ACVendorsUrl =
	cmplz_tcf.cmp_url + 'cmp/vendorlist/additional-consent-providers.csv';

/**
 * URL for localized purposes JSON file
 * Falls back to vendor-list.json if language not available
 * @type {string}
 */
let purposesUrl =
	cmplz_tcf.cmp_url +
	'cmp/vendorlist' +
	'/purposes-' +
	cmplzLanguage +
	'.json';

// Fallback to English if the detected language is not in the GVL
if ( ! cmplzExistingLanguages.includes( cmplzLanguage ) ) {
	cmplzLanguage = 'en';
	purposesUrl = cmplz_tcf.cmp_url + 'cmp/vendorlist' + '/vendor-list.json';
}

/**
 * ============================================================================
 * UTILITY FUNCTIONS
 * ============================================================================
 */

/**
 * Retrieves a cookie value by name
 *
 * Automatically prepends the Complianz prefix (default: 'cmplz_')
 * to the cookie name before searching.
 *
 * @param {string} name - Cookie name (without prefix)
 * @return {string} Cookie value, or empty string if not found
 *
 * @example
 * // Gets value of 'cmplz_banner-status' cookie
 * const status = cmplz_tcf_get_cookie('banner-status');
 */
function cmplz_tcf_get_cookie( name ) {
	if ( typeof document === 'undefined' ) {
		return '';
	}
	const prefix =
		typeof complianz !== 'undefined' ? complianz.prefix : 'cmplz_';
	const value = '; ' + document.cookie;
	const parts = value.split( '; ' + prefix + name + '=' );
	if ( parts.length === 2 ) {
		return parts.pop().split( ';' ).shift();
	}
	return '';
}

/**
 * Adds a delegated event listener to the document
 *
 * Uses event delegation pattern to handle dynamically added elements.
 * The callback only fires if the event target matches the selector.
 *
 * @param {string}   event    - The event type to listen for (e.g., 'click', 'change')
 * @param {string}   selector - CSS selector for the target element
 * @param {Function} callback - Function to execute when event occurs on matching element
 *
 * @example
 * cmplz_tcf_add_event('click', '.cmplz-save', function(e) {
 *   console.log('Save button clicked', e.target);
 * });
 */
function cmplz_tcf_add_event( event, selector, callback ) {
	document.addEventListener( event, ( e ) => {
		if ( e.target.closest( selector ) ) {
			callback( e );
		}
	} );
}

/**
 * Checks if an element is hidden from view
 *
 * Uses offsetParent to detect visibility. An element with offsetParent === null
 * is either hidden via display:none or has no layout parent.
 *
 * @param {HTMLElement} el - The element to check
 * @return {boolean} True if element is hidden, false otherwise
 */
function is_hidden( el ) {
	return el.offsetParent === null;
}

/**
 * ============================================================================
 * PROMISE-BASED INITIALIZATION SYSTEM
 * ============================================================================
 *
 * The TCF implementation uses promises to coordinate async initialization
 * of different components. This ensures proper sequencing:
 *
 * 1. bannerDataLoaded  - WordPress consent type defined (opt-in/opt-out)
 * 2. tcfLanguageLoaded - GVL language files loaded
 * 3. tcModelLoaded     - GVL ready and TCModel initialized
 * 4. bannerLoaded      - Banner UI fully rendered
 * 5. revoke            - User initiated consent revocation
 */

/**
 * Resolver functions for initialization promises
 * These are called when each initialization step completes
 */
let bannerDataLoadedResolve;
let tcModelLoadedResolve;
let tcfLanguageLoadedResolve;
let bannerLoadedResolve;
let revokeResolve;

/**
 * Promise that resolves when WordPress defines the consent type (opt-in/opt-out)
 * Triggered by 'wp_consent_type_defined' event
 * @type {Promise}
 */
const bannerDataLoaded = new Promise( function ( resolve ) {
	bannerDataLoadedResolve = resolve;
} );

/**
 * Promise that resolves when TC Model and GVL are initialized
 * Critical for starting consent management
 * @type {Promise}
 */
const tcModelLoaded = new Promise( function ( resolve ) {
	tcModelLoadedResolve = resolve;
} );

/**
 * Promise that resolves when GVL language files are loaded
 * Provides translated purposes, features, and vendor descriptions
 * @type {Promise}
 */
const tcfLanguageLoaded = new Promise( function ( resolve ) {
	tcfLanguageLoadedResolve = resolve;
} );

/**
 * Promise that resolves when the consent banner UI is loaded and rendered
 * Triggered by 'cmplz_cookie_warning_loaded' event
 * @type {Promise}
 */
const bannerLoaded = new Promise( function ( resolve ) {
	bannerLoadedResolve = resolve;
} );

/**
 * Promise that resolves when user clicks revoke consent
 * Used to clear stored consent and show banner again
 * @type {Promise}
 */
const revoke = new Promise( function ( resolve ) {
	revokeResolve = resolve;
} );

/**
 * ============================================================================
 * DATA LOADING PROMISES
 * ============================================================================
 */

/**
 * Promise to load Google Additional Consent (AC) vendors from CSV
 *
 * Fetches and parses the AC vendor list CSV file. Each vendor includes:
 * - id: Numeric Google vendor ID
 * - name: Vendor display name
 * - policyUrl: Link to privacy policy
 * - domains: Comma-separated domain list
 * - consent: Consent status (0 = not consented)
 *
 * Only runs if AC mode is enabled (useAcVendors === true)
 *
 * @type {Promise}
 */
const acVendorsPromise = useAcVendors
	? fetch( ACVendorsUrl )
			.then( ( response ) => response.text() )
			.then( ( csvData ) => {
				// Parse the CSV data
				const rows = csvData.split( '\n' );
				// Remove header row
				rows.shift();
				// Convert CSV rows to vendor objects
				ACVendors = rows.map( ( row ) => {
					if ( row.length === 0 ) {
						return null;
					}
					const [ id, name, policyUrl, domains ] =
						cmplzParseCsvRow( row );
					return {
						id: parseInt( id ),
						name,
						policyUrl,
						domains,
						consent: 0, // Default: no consent
					};
				} );
				// Filter out null/empty values
				ACVendors = ACVendors.filter( ( el ) => el !== null );
			} )
			.catch( ( error ) => {
				if ( cmplz_tcf.debug ) {
					console.log( 'Error loading AC vendors:', error );
				}
			} )
	: Promise.resolve();

/**
 * Promise to load GVL purposes and translations
 *
 * Fetches the localized purposes JSON file which contains:
 * - Purposes (e.g., "Store and/or access information")
 * - Special Purposes (e.g., "Ensure security")
 * - Features (e.g., "Match and combine data")
 * - Special Features (e.g., "Use precise geolocation")
 * - Data Categories (v2.2)
 * - Stacks (common purpose combinations)
 *
 * @type {Promise}
 */
const purposesPromise = fetch( purposesUrl, {
	method: 'GET',
} )
	.then( ( response ) => response.json() )
	.then( ( data ) => {
		cmplzLanguageJson = data;
	} )
	.catch( ( error ) => {
		if ( cmplz_tcf.debug ) {
			console.log( 'Error loading purposes:', error );
		}
	} );

/**
 * Wait for both AC vendors and purposes to load before proceeding
 * Resolves tcfLanguageLoaded when both are ready
 */
Promise.all( [ acVendorsPromise, purposesPromise ] ).then( () => {
	tcfLanguageLoadedResolve();
} );

/**
 * ============================================================================
 * WORDPRESS EVENT LISTENERS
 * ============================================================================
 *
 * Integration with WordPress/Complianz plugin events
 */

/**
 * Listens for WordPress consent type definition
 * Fired by Complianz when it determines if the site requires opt-in or opt-out
 */
document.addEventListener( 'wp_consent_type_defined', function () {
	bannerDataLoadedResolve();
} );

/**
 * Listens for consent banner load completion
 * Fired when the Complianz banner HTML is fully loaded and rendered
 */
document.addEventListener( 'cmplz_cookie_warning_loaded', function () {
	if ( ! complianz.disable_cookiebanner ) {
		bannerLoadedResolve();
	}
} );

/**
 * Listens for consent revocation requests
 * Fired when user clicks to revoke/change their consent choices
 *
 * @param {Event} e - Event object with detail.reload indicating if page should reload
 */
document.addEventListener( 'cmplz_revoke', function ( e ) {
	const reload = e.detail;
	revokeResolve( reload );
} );

/**
 * ============================================================================
 * CORE TCF INITIALIZATION
 * ============================================================================
 *
 * Main initialization flow that sets up:
 * - Global Vendor List (GVL)
 * - TC Model (consent data structure)
 * - CMP API (__tcfapi interface)
 * - Event handlers for user interactions
 */

// Wait for banner data (no action needed, just placeholder)
bannerDataLoaded.then( () => {} );

/**
 * Initialize TCF after language files are loaded
 * This is the main initialization sequence for the entire TCF system
 */
tcfLanguageLoaded.then( () => {
	// Retrieve stored consent strings from localStorage
	const storedTCString = cmplzGetTCString();
	const ACString = cmplzGetACString();

	// Configure GVL base URL for vendor list files
	GVL.baseUrl = cmplz_tcf.cmp_url + 'cmp/vendorlist';

	// TCF v2.2: Extract data categories from loaded GVL JSON
	dataCategories = cmplzLanguageJson.dataCategories;

	/**
	 * Initialize Global Vendor List (GVL)
	 * Contains all IAB registered vendors, purposes, features, etc.
	 */
	const gvl = new GVL( cmplzLanguageJson );

	/**
	 * Backup copy of GVL for resetting purposes
	 * Created after language change is complete
	 */
	let sourceGvl = null;

	/**
	 * Change GVL to user's language and wait for ready state
	 * This loads translated vendor descriptions and purpose texts
	 */
	gvl.changeLanguage( cmplzLanguage )
		.then( () => {
			return gvl.readyPromise;
		} )
		.then( () => {
			// Clone GVL as backup for potential resets
			sourceGvl = gvl.clone();
		} );

	/**
	 * Initialize TC Model (Transparency & Consent Model)
	 * This is the core data structure that stores all consent choices
	 *
	 * @type {TCModel}
	 */
	let tcModel = new TCModel( gvl );

	// Publisher information
	tcModel.publisherCountryCode = cmplz_tcf.publisherCountryCode;

	// CMP identification
	tcModel.cmpId = cmplzCMP;
	tcModel.cmpVersion = cmplzCMPVersion;
	tcModel.isServiceSpecific = cmplzIsServiceSpecific;

	/**
	 * TCF v2.2: Policy version indicates TCF spec compliance level
	 * - Version 4: TCF v2.0
	 * - Version 5: TCF v2.2 (current)
	 */
	tcModel.policyVersion = 5;

	/**
	 * Legacy property (deprecated but kept for compatibility)
	 * For multi-publisher CMPs, this should be 0
	 */
	tcModel.UseNonStandardStacks = 0;

	/**
	 * TCF v2.2: Configuration flags
	 * These flags communicate CMP behavior to vendors
	 */
	// Using IAB standard purpose/stack definitions
	tcModel.useNonStandardStacks = false;
	// Using IAB standard purpose texts (not custom)
	tcModel.useNonStandardTexts = false;
	// Not applying special treatment to Purpose 1 (storage access)
	tcModel.purposeOneTreatment = false;

	/**
	 * Initialize CMP API (__tcfapi interface)
	 *
	 * Creates the global __tcfapi() function that vendors use to:
	 * - Get consent data (getTCData command)
	 * - Listen for consent changes (addEventListener command)
	 * - Check if GDPR applies (ping command)
	 *
	 * The CmpApi handles:
	 * - Post-message communication for iframes
	 * - Event listeners and callbacks
	 * - TC String encoding/decoding
	 *
	 * @type {CmpApi}
	 */
	const cmpApi = new CmpApi(
		cmplzCMP,
		cmplzCMPVersion,
		cmplzIsServiceSpecific,
		{
			/**
			 * Custom getTCData command handler
			 *
			 * Enhances the default getTCData response with:
			 * - Google Additional Consent (AC) String
			 * - Disclosed vendors list (TCF v2.2)
			 * - v2.2 configuration flags
			 * - Policy version
			 *
			 * This is called by __tcfapi('getTCData', 2, callback)
			 *
			 * @param {Function} next    - Continue to next handler in chain
			 * @param {Object}   tcData  - TC data object from TC String
			 * @param {boolean}  success - Whether TC data was successfully retrieved
			 */
			getTCData: ( next, tcData, success ) => {
				// Enhance tcData object with additional fields
				// Note: Check is needed for removeEventListener to work properly
				if ( tcData && typeof tcData === 'object' ) {
					/**
					 * Add Google Additional Consent (AC) String
					 * Format: "1~vendor.id.id.id"
					 */
					if ( ACString ) {
						tcData.addtlConsent = ACString;
						// Enable advertiser consent mode if AC vendors exist with consent
						tcData.enableAdvertiserConsentMode = ! (
							ACVendors.length === 0 ||
							typeof ACVendors[ 0 ].consent === 'undefined'
						);
					}

					/**
					 * TCF v2.2: Add disclosed vendors
					 *
					 * Disclosed vendors are those shown to the user in the UI.
					 * This is required by TCF v2.2 spec for transparency.
					 *
					 * Format: { vendorId: true, ... }
					 */
					if ( tcModel.vendorsDisclosed ) {
						tcData.vendorsDisclosed = {};
						// Convert IAB Vector to plain JavaScript object
						const disclosedIds = Array.from(
							tcModel.vendorsDisclosed
						);
						disclosedIds.forEach( ( vendorId ) => {
							// Ensure vendorId is a number (not string)
							const id = parseInt( vendorId, 10 );
							if ( ! isNaN( id ) ) {
								tcData.vendorsDisclosed[ id ] = true;
							}
						} );
					}

					/**
					 * TCF v2.2: Add configuration flags and policy version
					 *
					 * Policy version 5 indicates TCF v2.2 compliance
					 * Flags indicate whether CMP uses IAB standards
					 */
					tcData.policyVersion = tcModel.policyVersion || 5;
					tcData.useNonStandardStacks =
						tcModel.useNonStandardStacks || false;
					tcData.useNonStandardTexts =
						tcModel.useNonStandardTexts || false;
					tcData.purposeOneTreatment =
						tcModel.purposeOneTreatment || false;
				}

				// Pass enhanced tcData to next handler
				next( tcData, success );
			},
		}
	);

	/**
	 * ============================================================================
	 * GVL READY & TC STRING DECODING
	 * ============================================================================
	 *
	 * Once the GVL is loaded and ready:
	 * 1. Filter vendors to only include those enabled in WordPress
	 * 2. Decode stored TC String (if exists) to restore user's consent choices
	 * 3. Handle backward compatibility for v2.0 TC Strings
	 * 4. Update AC vendors with their consent states
	 */

	tcModel.gvl.readyPromise.then( () => {
		// Get full vendor list from GVL
		const json = tcModel.gvl.getJson();
		const vendors = json.vendors;

		// Filter to only vendors enabled in WordPress settings
		const vendorIds = cmplzFilterVendors( vendors );

		// Narrow GVL to only include enabled vendors (improves performance)
		tcModel.gvl.narrowVendorsTo( vendorIds );

		/**
		 * Decode stored TC String and restore consent state
		 *
		 * TC String format: Base64-encoded consent data including:
		 * - Vendor consents and legitimate interests
		 * - Purpose consents and legitimate interests
		 * - Special feature opt-ins
		 * - v2.2 segments: disclosed vendors, flags
		 */

		// Pre-check: Ensure TC String exists and is valid before decoding
		if (
			storedTCString &&
			typeof storedTCString === 'string' &&
			storedTCString.length > 0
		) {
			try {
				// Decode TC String into tcModel
				tcModel = TCString.decode( storedTCString, tcModel );

				/**
				 * TCF v2.2: Backward Compatibility
				 *
				 * When decoding a v2.0 TC String, v2.2-specific fields will be missing.
				 * We initialize them here to ensure smooth upgrade path.
				 */
				if ( ! tcModel.vendorsDisclosed ) {
					tcModel.vendorsDisclosed = new Set();
					if ( cmplz_tcf.debug ) {
						console.log(
							'TCF v2.2: Upgraded v2.0 TC String, initialized disclosed vendors'
						);
					}
				}

				// Initialize v2.2 flags if missing (backward compatibility)
				if ( typeof tcModel.useNonStandardStacks === 'undefined' ) {
					tcModel.useNonStandardStacks = false;
				}
				if ( typeof tcModel.useNonStandardTexts === 'undefined' ) {
					tcModel.useNonStandardTexts = false;
				}
				if ( typeof tcModel.purposeOneTreatment === 'undefined' ) {
					tcModel.purposeOneTreatment = false;
				}

				// Re-encode TC String to ensure GDPR applies flag is set
				cmplzSetTCString( tcModel, cmplzUIVisible() );

				// Update AC vendors with their consent states from AC String
				ACVendors = updateACVendorsWithConsent( ACString, ACVendors );
			} catch ( err ) {
				/**
				 * Handle corrupted or invalid TC Strings
				 *
				 * This can occur if:
				 * - TC String was manually edited
				 * - Storage was corrupted
				 * - Incompatible library version
				 *
				 * Solution: Clear the invalid string and let user re-consent
				 */
				if ( cmplz_tcf.debug ) {
					console.error( 'TCF: Error decoding TC String:', err );
					console.log(
						'TCF: Clearing invalid TC String, user will need to re-consent'
					);
				}

				// Clear invalid TC String from localStorage
				if ( localStorage.cmplz_tcf_consent ) {
					localStorage.removeItem( 'cmplz_tcf_consent' );
				}
			}
		} else {
			/**
			 * No stored consent found
			 *
			 * This is the expected state for:
			 * - First-time visitors
			 * - Users who cleared browser data
			 * - Users in incognito/private mode
			 *
			 * The banner will show and user will be prompted for consent
			 */
			if ( cmplz_tcf.debug ) {
				console.log(
					'TCF: No stored consent found, waiting for user choice'
				);
			}
		}

		// Signal that TC Model is loaded and ready
		tcModelLoadedResolve();
	} );

	/**
	 * ============================================================================
	 * POST-INITIALIZATION ACTIONS
	 * ============================================================================
	 *
	 * After core components are loaded, perform additional setup:
	 * - Insert vendors into policy page
	 * - Handle consent expiration (1 year)
	 * - Configure banner UI
	 * - Set up revoke handlers
	 */

	/**
	 * Wait for banner data and TC Model to be ready
	 * Then insert vendors into the cookie policy page and handle expiration
	 */
	Promise.all( [ bannerDataLoaded, tcModelLoaded ] ).then( () => {
		// Insert vendors into cookie policy page for disclosure
		insertVendorsInPolicy( tcModel.gvl.vendors, ACVendors );

		if ( complianz.consenttype === 'optin' ) {
			if ( cmplz_tcf.debug ) {
				console.log( tcModel );
			}

			const date = new Date();
			/**
			 * TCF Consent Expiration Check
			 *
			 * IAB TCF v2.2 spec recommends expiring consent after 1 year
			 * to ensure users review their choices periodically
			 */
			if (
				Date.parse( tcModel.created ) <
				date.getTime() - 365 * 24 * 60 * 60 * 1000
			) {
				// Consent is over 1 year old - clear it
				cmplzSetTCString( null, cmplzUIVisible() );
			} else {
				// Consent is still valid - keep it
				cmplzSetTCString( tcModel, cmplzUIVisible() );
			}
		} else {
			// Not an opt-in region (e.g., opt-out, notice-only)
			if ( cmplz_tcf.debug ) {
				console.log( 'not an optin tcf region' );
			}
			cmplzSetTCString( null, false );
		}
	} );

	/**
	 * Wait for banner UI, TC Model, and language files
	 * Then configure the consent banner with all TCF data
	 */
	Promise.all( [ bannerLoaded, tcModelLoaded, tcfLanguageLoaded ] ).then(
		() => {
			configureOptinBanner();
		}
	);

	/**
	 * Handle consent revocation
	 * When user clicks to revoke consent, clear all stored data
	 */
	revoke.then( ( reload ) => {
		if ( cmplz_is_tcf_region( complianz.region ) ) {
			revokeAllVendors( reload );
		}
	} );

	/**
	 * ============================================================================
	 * CONSENT CATEGORY EVENT HANDLERS
	 * ============================================================================
	 */

	/**
	 * Listen for WordPress consent category acceptance
	 *
	 * When user accepts 'marketing' category in the banner,
	 * automatically consent to all TCF vendors and purposes
	 *
	 * This provides seamless integration between:
	 * - WordPress category-based consent (functional, marketing, etc.)
	 * - IAB TCF vendor-level consent
	 */
	document.addEventListener( 'cmplz_fire_categories', function ( e ) {
		// Only process in opt-in (GDPR) regions
		if ( complianz.consenttype !== 'optin' ) {
			return;
		}

		// If marketing category is accepted, consent to all TCF vendors
		if ( cmplz_in_array( 'marketing', e.detail.categories ) ) {
			acceptAllVendors();
		}
	} );

	/**
	 * ============================================================================
	 * CONSENT MANAGEMENT FUNCTIONS
	 * ============================================================================
	 */

	/**
	 * Accepts all TCF vendors, purposes, special features, and AC vendors
	 *
	 * This is called when:
	 * - User clicks "Accept All" button
	 * - User accepts the 'marketing' category
	 *
	 * Sets:
	 * - All vendor consents
	 * - All vendor legitimate interests
	 * - All purpose consents
	 * - All purpose legitimate interests
	 * - All special feature opt-ins
	 * - All AC vendor consents
	 *
	 * @fires Document#cmplz_consent_ui
	 */
	function acceptAllVendors() {
		consentAllACVendors();

		cmplzSetAllVendorLegitimateInterests();
		tcModel.setAllPurposeLegitimateInterests();
		for ( const key in cmplz_tcf.purposes ) {
			tcModel.purposeConsents.set( cmplz_tcf.purposes[ key ] );
			cmplzSetTypeByVendor(
				'purpose_legitimate_interest',
				cmplz_tcf.purposes[ key ]
			);
		}

		tcModel.setAllSpecialFeatureOptins();
		for ( const key in cmplz_tcf.specialFeatures ) {
			tcModel.specialFeatureOptins.set(
				cmplz_tcf.specialFeatures[ key ]
			);
			cmplzSetTypeByVendor(
				'specialfeature',
				cmplz_tcf.specialFeatures[ key ]
			);
		}

		tcModel.setAllPurposeConsents();
		for ( const key in cmplz_tcf.purposes ) {
			tcModel.purposeConsents.set( cmplz_tcf.purposes[ key ] );
			cmplzSetTypeByVendor(
				'purpose_consent',
				cmplz_tcf.purposes[ key ]
			);
		}

		tcModel.setAllVendorConsents();
		document
			.querySelectorAll( '.cmplz-tcf-input' )
			.forEach( ( checkbox ) => {
				checkbox.checked = true;
			} );
		cmplzSetTCString( tcModel, cmplzUIVisible() );
		cmplz_set_cookie( 'banner-status', 'dismissed' );
	}

	/**
	 * Revoke all vendors
	 * @param {boolean} reload - Whether to reload the page after revoking
	 */
	function revokeAllVendors( reload ) {
		denyAllACVendors();

		//legint should be handled by right to object checkbox in vendor overview.
		// tcModel.unsetAllVendorLegitimateInterests();
		tcModel.unsetAllPurposeLegitimateInterests();
		cmplzUnsetAllVendorLegitimateInterests();

		// Clear ALL special features, not just the configured ones
		tcModel.unsetAllSpecialFeatureOptins();

		for ( const key in cmplz_tcf.specialFeatures ) {
			tcModel.specialFeatureOptins.unset(
				cmplz_tcf.specialFeatures[ key ]
			);
			cmplzUnsetTypeByVendor(
				'specialfeature',
				cmplz_tcf.specialFeatures[ key ]
			);
		}

		for ( const key in cmplz_tcf.purposes ) {
			tcModel.purposeConsents.set( cmplz_tcf.purposes[ key ] );
			cmplzUnsetTypeByVendor(
				'purpose_consent',
				cmplz_tcf.purposes[ key ]
			);
		}

		tcModel.unsetAllVendorConsents();
		document
			.querySelectorAll( '.cmplz-tcf-input' )
			.forEach( ( checkbox ) => {
				if ( ! checkbox.disabled ) {
					checkbox.checked = false;
				}
			} );
		cmplzSetTCString( tcModel, cmplzUIVisible() );

		if ( reload ) {
			location.reload();
		}
	}

	/**
	 * Set all legitimate interests, except when a vendor does not have legints or special purposes.
	 */
	function cmplzSetAllVendorLegitimateInterests() {
		tcModel.setAllVendorLegitimateInterests();
		for ( const key in tcModel.gvl.vendors ) {
			const vendor = tcModel.gvl.vendors[ key ];
			/**
			 * no legint, and no special purposes, set legint signal to 0.
			 */
			if (
				vendor.legIntPurposes.length === 0 &&
				vendor.specialPurposes.length === 0
			) {
				tcModel.vendorLegitimateInterests.unset( vendor.id );
			}
		}
	}

	/**
	 * UnSet all legitimate interests, except when a vendor does not have legints or special purposes.
	 */
	function cmplzUnsetAllVendorLegitimateInterests() {
		tcModel.unsetAllVendorLegitimateInterests();
		for ( const key in tcModel.gvl.vendors ) {
			const vendor = tcModel.gvl.vendors[ key ];
			/**
			 * If a vendor only has special purposes, and no other purposes, there's no right to object.
			 */
			if (
				vendor.legIntPurposes.length === 0 &&
				vendor.purposes.length === 0 &&
				vendor.flexiblePurposes.length === 0 &&
				vendor.specialFeatures.length === 0 &&
				vendor.specialPurposes.length !== 0
			) {
				tcModel.vendorLegitimateInterests.set( vendor.id );
			}
		}
	}

	const updateACVendorsWithConsent = ( storedConsentString, vendors ) => {
		//retrieve array of consented vendors from the stored consent string
		const consentedVendors = decodeACString( storedConsentString );
		//loop through ACVendors, and set each vendor that exists in the consentedVendor array to consented
		vendors.forEach( ( vendor ) => {
			if ( consentedVendors.includes( vendor.id ) ) {
				vendor.consent = 1;
			}
		} );
		//strip out vendors that already exist in the tcf vendor list
		const tcfVendorNames = new Set(
			Object.values( tcModel.gvl.vendors ).map(
				( vendor ) => vendor.name
			)
		);
		return vendors.filter(
			( vendor ) => ! tcfVendorNames.has( vendor.name )
		);
	};

	const denyACVendor = ( vendorId ) => {
		ACVendors.forEach( ( vendor ) => {
			if ( parseInt( vendor.id ) === vendorId ) {
				vendor.consent = 0;
			}
		} );
	};
	const denyAllACVendors = () => {
		ACVendors.forEach( ( vendor ) => {
			vendor.consent = 0;
		} );
	};
	const consentAllACVendors = () => {
		ACVendors.forEach( ( vendor ) => {
			vendor.consent = 1;
		} );
	};
	const consentACVendor = ( vendorId ) => {
		ACVendors.forEach( ( vendor ) => {
			if ( parseInt( vendor.id ) === vendorId ) {
				vendor.consent = 1;
			}
		} );
	};

	const decodeACString = ( acString ) => {
		if ( ! acString || acString.length === 0 ) {
			return [];
		}

		//split the string on the ~
		const ACArray = acString.split( '~' );
		//get the array of vendor id's
		const vendors = ACArray[ 1 ].split( '.' );
		// change each vendor id to an integer
		return vendors.map( ( vendor ) => {
			return parseInt( vendor );
		} );
	};

	/**
	 * If a purpose has been selected/deselected, we need to re-check for al vendors if this has consenquences for legint
	 */
	function cmplzUpdateAllVendorLegitimateInterests() {
		for ( const key in tcModel.gvl.vendors ) {
			const vendor = tcModel.gvl.vendors[ key ];

			/**
			 * no legint, and no special purposes, set legint signal to 0.
			 */
			if (
				vendor.legIntPurposes.length === 0 &&
				vendor.specialPurposes.length === 0
			) {
				tcModel.vendorLegitimateInterests.unset( vendor.id );
			}

			if (
				vendor.legIntPurposes.length === 0 &&
				vendor.purposes.length === 0 &&
				vendor.flexiblePurposes.length === 0 &&
				vendor.specialFeatures.length === 0 &&
				vendor.specialPurposes.length !== 0
			) {
				tcModel.vendorLegitimateInterests.set( vendor.id );
			}
		}
	}

	/**
	 * We use this method to keep track of consents per vendor. This is not stored in the core tcString
	 *
	 * @param {string} type   - The type of consent (purpose_consent, purpose_legitimate_interest, specialfeature, feature)
	 * @param {number} typeId - The ID of the type
	 */
	function cmplzSetTypeByVendor( type, typeId ) {
		if ( type === 'purpose_consent' ) {
			tcModel.purposeConsents.set( typeId );
			for ( const key in tcModel.gvl.vendors ) {
				const vendor = tcModel.gvl.vendors[ key ];
				if (
					sourceGvl.vendors[ vendor.id ].purposes.includes(
						typeId
					) &&
					! vendor.purposes.includes( typeId )
				) {
					tcModel.gvl.vendors[ vendor.id ].purposes.push( typeId );
				}
			}
		}

		if ( type === 'purpose_legitimate_interest' ) {
			tcModel.purposeLegitimateInterests.set( typeId );
			for ( const key in tcModel.gvl.vendors ) {
				const vendor = tcModel.gvl.vendors[ key ];
				if (
					sourceGvl.vendors[ vendor.id ].purposes.includes(
						typeId
					) &&
					! vendor.purposes.includes( typeId )
				) {
					tcModel.gvl.vendors[ vendor.id ].purposes.push( typeId );
				}
			}
		}

		if ( type === 'specialfeature' ) {
			tcModel.specialFeatureOptins.set( typeId );
			for ( const key in tcModel.gvl.vendors ) {
				const vendor = tcModel.gvl.vendors[ key ];
				if (
					sourceGvl.vendors[ vendor.id ].specialFeatures.includes(
						typeId
					) &&
					! vendor.specialFeatures.includes( typeId )
				) {
					tcModel.gvl.vendors[ vendor.id ].specialFeatures.push(
						typeId
					);
				}
			}
		}

		if ( type === 'feature' ) {
			for ( const key in tcModel.gvl.vendors ) {
				const vendor = tcModel.gvl.vendors[ key ];
				if (
					sourceGvl.vendors[ vendor.id ].features.includes(
						typeId
					) &&
					! vendor.features.includes( typeId )
				) {
					tcModel.gvl.vendors[ vendor.id ].features.push( typeId );
				}
			}
		}
	}

	function cmplzUnsetTypeByVendor( type, typeId ) {
		if ( type === 'purpose_consent' ) {
			tcModel.purposeConsents.unset( typeId );
			for ( const key in tcModel.gvl.vendors ) {
				const vendor = tcModel.gvl.vendors[ key ];
				const index = vendor.purposes.indexOf( typeId );
				if ( index > -1 ) {
					tcModel.gvl.vendors[ vendor.id ].purposes.splice(
						index,
						1
					);
				}
			}
		}

		if ( type === 'purpose_legitimate_interest' ) {
			tcModel.purposeLegitimateInterests.unset( typeId );
			for ( const key in tcModel.gvl.vendors ) {
				const vendor = tcModel.gvl.vendors[ key ];
				const index = vendor.legIntPurposes.indexOf( typeId );
				if ( index > -1 ) {
					tcModel.gvl.vendors[ vendor.id ].legIntPurposes.splice(
						index,
						1
					);
				}
			}
		}

		if ( type === 'specialfeature' ) {
			tcModel.specialFeatureOptins.unset( typeId );
			for ( const key in tcModel.gvl.vendors ) {
				const vendor = tcModel.gvl.vendors[ key ];
				const index = vendor.specialFeatures.indexOf( typeId );
				if ( index > -1 ) {
					tcModel.gvl.vendors[ vendor.id ].specialFeatures.splice(
						index,
						1
					);
				}
			}
		}

		if ( type === 'feature' ) {
			for ( const key in tcModel.gvl.vendors ) {
				const vendor = tcModel.gvl.vendors[ key ];
				const index = vendor.features.indexOf( typeId );
				if ( index > -1 ) {
					tcModel.gvl.vendors[ vendor.id ].features.splice(
						index,
						1
					);
				}
			}
		}
	}

	/**
	 * When revoke button is clicked, so banner shows again
	 *
	 */

	cmplz_tcf_add_event( 'click', '.cmplz-manage-consent', function () {
		const currentTCString = cmplzGetTCString();
		cmpApi.update( currentTCString, true ); //just got the banner to show again, so we have to pass ui visible true
	} );

	/**
	 * Create a checkbox, clickable
	 * @param {string}      type      - The type of checkbox (purpose_consent, purpose_legitimate_interest, etc.)
	 * @param {Object}      object    - The object containing checkbox data (name, description, id, illustrations)
	 * @param {HTMLElement} container - The container element to append the checkbox to
	 * @param {boolean}     checked   - Whether the checkbox should be checked initially
	 * @param {boolean}     disabled  - Whether the checkbox should be disabled
	 */
	function cmplzRenderCheckbox( type, object, container, checked, disabled ) {
		const { name, description, id, illustrations } = object;
		const illustration =
			illustrations && illustrations.hasOwnProperty( 0 )
				? illustrations[ 0 ]
				: '';
		const vendors = tcModel.gvl.vendors;
		const vendorsWithPurpose = cmplzGetVendorsWithPurpose(
			'purposes',
			vendors,
			[ id ]
		);
		const count = vendorsWithPurpose.length;

		const descArr = description.split( '*' );
		const descriptionOut = descArr.join( ', ' );
		const template = document
			.getElementById( 'cmplz-tcf-type-template' )
			.innerHTML.replace( /{type_name}/g, name )
			.replace( /{type_count}/g, count )
			.replace( /{type_description}/g, descriptionOut )
			.replace( /{type_id}/g, id )
			.replace( /{type_example}/g, illustration )
			.replace( /{type}/g, type );

		const wrapper = document.createElement( 'div' );
		wrapper.innerHTML = template;
		const checkbox = wrapper.firstChild;
		const input = checkbox.querySelector( `.cmplz-tcf-${ type }-input` );
		input.checked = checked;
		input.disabled = disabled;
		input.setAttribute( `data-${ type }_id`, id );

		container.appendChild( checkbox );
	}

	/**
	 * Generate entire block of checkboxes with event listener
	 * @param type
	 * @param objects
	 * @param filterBy
	 */

	function generateTypeBlock( type, objects, filterBy ) {
		let containerid = type;
		let srcPurposes;
		if ( filterBy !== false ) {
			containerid = filterBy + '-' + containerid;
			srcPurposes = getPurposes( filterBy, false );
		}

		const container = document.getElementById(
			'cmplz-tcf-' + containerid + 's-container'
		);
		if ( container === null ) {
			return;
		}
		container.innerHTML = '';
		for ( const key in objects ) {
			if ( objects.hasOwnProperty( key ) ) {
				const object = objects[ key ];
				const addItem = filterBy
					? srcPurposes.includes( object.id )
					: true;

				if ( addItem ) {
					let checked = false;
					let disabled = false;
					if ( type === 'purpose_consent' ) {
						checked = tcModel.purposeConsents.has( object.id );
					}
					if ( type === 'purpose_legitimate_interest' ) {
						checked = tcModel.purposeLegitimateInterests.has(
							object.id
						);
					}
					if ( type === 'specialfeature' ) {
						checked = tcModel.specialFeatureOptins.has( object.id );
					}
					if ( type === 'feature' || type === 'specialpurpose' ) {
						checked = disabled = true;
					}

					cmplzRenderCheckbox(
						type,
						object,
						container,
						checked,
						disabled
					);
				}
			}
		}

		//add event listener
		cmplz_tcf_add_event(
			'click',
			'.cmplz-tcf-' + type + '-input',
			function ( e ) {
				const obj = e.target;
				const typeId = parseInt(
					obj.getAttribute( 'data-' + type + '_id' )
				);
				const checked = obj.checked;

				document
					.querySelectorAll(
						'[data-' + type + '_id="' + typeId + '"]'
					)
					.forEach( ( element ) => {
						element.checked = checked;
					} );

				if ( type === 'purpose_consent' ) {
					tcModel.purposeConsents[ checked ? 'set' : 'unset' ](
						typeId
					);
				}
				if ( type === 'purpose_legitimate_interest' ) {
					tcModel.purposeLegitimateInterests[
						checked ? 'set' : 'unset'
					]( typeId );
				}
				if ( type === 'specialfeature' ) {
					tcModel.specialFeatureOptins[ checked ? 'set' : 'unset' ](
						typeId
					);
				}

				if ( checked ) {
					cmplzSetTypeByVendor( type, typeId );
				} else {
					cmplzUnsetTypeByVendor( type, typeId );
				}

				cmplzUpdateAllVendorLegitimateInterests();
				cmplzSetTCString( tcModel, true );
				cmplz_set_cookie( 'banner-status', 'dismissed' );
			}
		);

		cmplz_tcf_add_event( 'click', '.cmplz-tcf-toggle', function ( e ) {
			const obj = e.target;

			e.preventDefault();
			const label = obj.closest( 'label' );
			const description = label.querySelector(
				'.cmplz-tcf-type-description'
			);

			if ( is_hidden( description ) ) {
				obj.classList.add( 'cmplz-tcf-rl' );
				obj.classList.remove( 'cmplz-tcf-rm' );
				description.style.display = 'block';
			} else {
				obj.classList.add( 'cmplz-tcf-rm' );
				obj.classList.remove( 'cmplz-tcf-rl' );
				description.style.display = 'none';
			}
		} );
	}

	/**
	 * Checks if the consent UI (banner/modal) is currently visible
	 *
	 * Used to determine the cmpDisplayStatus field in TC String:
	 * - visible: UI is shown (user is making choice)
	 * - hidden: UI is dismissed (user already made choice)
	 *
	 * @return {boolean} True if banner or policy page is visible
	 */
	function cmplzUIVisible() {
		let bannerVisible = true;
		const bannerStatus = cmplz_tcf_get_cookie( 'banner-status' );
		if ( bannerStatus === 'dismissed' ) {
			bannerVisible = false;
		}

		const policyVisible =
			document.getElementById( 'cmplz-tcf-vendor-container' ) !== null;
		return bannerVisible || policyVisible;
	}

	/**
	 * Inserts TCF and AC vendors into the cookie policy page
	 *
	 * This is the main rendering function for vendor disclosure. It:
	 *
	 * 1. Renders all TCF vendors from the GVL with their:
	 *    - Purposes (consent and legitimate interest)
	 *    - Special purposes
	 *    - Features and special features
	 *    - Data categories (v2.2)
	 *    - Data retention (v2.2)
	 *    - Privacy policy links
	 *
	 * 2. Renders AC (Additional Consent / Google) vendors with:
	 *    - Vendor name and privacy policy link
	 *    - Consent toggle checkboxes
	 *
	 * 3. Tracks disclosed vendors (TCF v2.2 requirement)
	 *    All vendors shown to users are added to tcModel.vendorsDisclosed
	 *
	 * TCF v2.2 Compliance:
	 * - vendorsDisclosed must include all vendors shown in UI
	 * - Data retention must be displayed for each vendor
	 * - Data categories must be displayed for each vendor
	 *
	 * @param {Object} vendors - TCF vendors object from GVL (keyed by vendor ID)
	 *
	 * @fires Document#cmplz_consent_ui - After all vendors are rendered
	 */
	function insertVendorsInPolicy( vendors ) {
		const vendorContainer = document.getElementById(
			'cmplz-tcf-vendor-container'
		);
		if ( vendorContainer === null ) {
			return;
		}

		vendorContainer.innerHTML = '';
		const template = document.getElementById(
			'cmplz-tcf-vendor-template'
		).innerHTML;
		const purposes = cmplzFilterArray(
			cmplzLanguageJson.purposes,
			cmplz_tcf.purposes
		);
		const specialPurposes = cmplzFilterArray(
			cmplzLanguageJson.specialPurposes,
			cmplz_tcf.specialPurposes
		);
		const features = cmplzFilterArray(
			cmplzLanguageJson.features,
			cmplz_tcf.features
		);
		const specialFeatures = cmplzFilterArray(
			cmplzLanguageJson.specialFeatures,
			cmplz_tcf.specialFeatures
		);

		generateTypeBlock( 'purpose_consent', purposes, 'statistics' );
		generateTypeBlock( 'purpose_consent', purposes, 'marketing' );
		generateTypeBlock(
			'purpose_legitimate_interest',
			purposes,
			'statistics'
		);
		generateTypeBlock(
			'purpose_legitimate_interest',
			purposes,
			'marketing'
		);
		generateTypeBlock( 'feature', features, false );
		generateTypeBlock( 'specialpurpose', specialPurposes, false );
		generateTypeBlock( 'specialfeature', specialFeatures, false );

		if ( specialFeatures.length === 0 ) {
			document.getElementById(
				'cmplz-tcf-specialfeatures-wrapper'
			).style.display = 'none';
		}
		for ( const vendorKey in vendors ) {
			if ( vendors.hasOwnProperty( vendorKey ) ) {
				let customTemplate = template;
				const vendor = vendors[ vendorKey ];

				// TCF v2.2: Track vendor as disclosed
				if ( tcModel.vendorsDisclosed ) {
					tcModel.vendorsDisclosed.set( vendor.id );
				}

				const vendorPurposes = vendor.purposes.concat(
					vendor.legIntPurposes
				);
				let purposeString = '';
				for ( const p_key in vendorPurposes ) {
					if ( vendorPurposes.hasOwnProperty( p_key ) ) {
						const vendorPurposeId = vendorPurposes[ p_key ];
						let purposeName = false;
						for ( const src_p_key in purposes ) {
							if (
								purposes.hasOwnProperty( src_p_key ) &&
								purposes[ src_p_key ].id === vendorPurposeId
							) {
								purposeName = purposes[ src_p_key ].name;
								const defaultRetention =
									vendor.dataRetention &&
									vendor.dataRetention.hasOwnProperty(
										'stdRetention'
									)
										? vendor.dataRetention.stdRetention
										: null;
								let retention =
									vendor.dataRetention &&
									vendor.dataRetention.hasOwnProperty(
										vendorPurposeId
									)
										? vendor.dataRetention[
												vendorPurposeId
										  ]
										: defaultRetention;
								if ( typeof retention === 'undefined' ) {
									retention = cmplz_tcf.undeclared_string;
								}
								const purposeLink =
									'https://cookiedatabase.org/tcf/' +
									purposeName
										.replace( / /g, '-' )
										.replace( /\//g, '-' )
										.toLowerCase();
								purposeString +=
									'<div class="cmplz-tcf-purpose"><a href="' +
									purposeLink +
									'" target="_blank" rel="noopener noreferrer nofollow">' +
									purposeName +
									'</a>| ' +
									cmplz_tcf.retention_string +
									': ' +
									retention +
									'</div>';
							}
						}
					}
				}

				// TCF v2.2: Use helper function to render data categories
				const categories = cmplzRenderVendorDataCategories(
					vendor,
					dataCategories
				);

				const retentionInDays = Math.round(
					vendor.cookieMaxAgeSeconds / ( 60 * 60 * 24 )
				);
				//if result is 0, get day in decimals.
				if ( cmplz_tcf.debug ) {
					console.log( vendor );
				}
				customTemplate = customTemplate.replace(
					/{cookie_retention_seconds}/g,
					vendor.cookieMaxAgeSeconds
				);
				customTemplate = customTemplate.replace(
					/{cookie_retention_days}/g,
					retentionInDays
				);
				customTemplate = customTemplate.replace(
					/{vendor_name}/g,
					vendor.name
				);
				customTemplate = customTemplate.replace(
					/{vendor_categories}/g,
					categories
				);
				customTemplate = customTemplate.replace(
					/{vendor_id}/g,
					vendor.id
				);
				customTemplate = customTemplate.replace(
					/{purposes}/g,
					purposeString
				);
				//get first array item
				let privacyPolicyUrl = false;
				let legitimateInterestUrl = false;
				if ( vendor.urls.hasOwnProperty( 0 ) ) {
					privacyPolicyUrl = vendor.urls[ 0 ].privacy;
					legitimateInterestUrl = vendor.urls[ 0 ].legIntClaim;

					if ( privacyPolicyUrl ) {
						customTemplate = customTemplate.replace(
							/{privacy_policy}/g,
							privacyPolicyUrl
						);
					}

					if ( legitimateInterestUrl ) {
						customTemplate = customTemplate.replace(
							/{legitimate_interest}/g,
							legitimateInterestUrl
						);
					}
				}

				// TCF v2.2: Add data retention display
				const retentionHTML = cmplzRenderVendorDataRetention(
					vendor,
					purposes
				);
				customTemplate = customTemplate.replace(
					/{data_retention}/g,
					retentionHTML
				);

				const wrapper = document.createElement( 'div' );
				wrapper.innerHTML = customTemplate;
				const checkbox = wrapper.firstChild;
				checkbox.querySelector( '.cmplz-tcf-vendor-input' ).checked =
					tcModel.vendorConsents.has( vendor.id ) ||
					tcModel.vendorLegitimateInterests.has( vendor.id );
				checkbox
					.querySelector( '.cmplz-tcf-vendor-input' )
					.setAttribute( 'data-vendor_id', vendor.id );

				//set consent
				checkbox.querySelector( '.cmplz-tcf-consent-input' ).checked =
					tcModel.vendorConsents.has( vendor.id );
				checkbox
					.querySelector( '.cmplz-tcf-consent-input' )
					.setAttribute( 'data-vendor_id', vendor.id );

				//show legint option if vendor has legintpurposes
				if ( vendor.legIntPurposes.length !== 0 ) {
					checkbox.querySelector(
						'.cmplz_tcf_legitimate_interest_checkbox'
					).style.display = 'block';
					checkbox
						.querySelector( '.cmplz-tcf-legitimate-interest-input' )
						.setAttribute( 'data-vendor_id', vendor.id );
					checkbox.querySelector(
						'.cmplz-tcf-legitimate-interest-input'
					).checked = tcModel.vendorLegitimateInterests.has(
						vendor.id
					);
				}

				//hide legint link if no legIntClaim URL
				if ( ! legitimateInterestUrl ) {
					checkbox.querySelector(
						'.cmplz-tcf-legint-link'
					).style.display = 'none';
					checkbox
						.querySelector( '.cmplz-tcf-legint-link' )
						.classList.add( 'not-available' );
				}

				//handle non cookie access
				if ( vendor.usesNonCookieAccess ) {
					wrapper.querySelector(
						'.non-cookie-storage-active'
					).style.display = 'block';
				} else {
					wrapper.querySelector(
						'.non-cookie-storage-inactive'
					).style.display = 'block';
				}

				if ( vendor.cookieRefresh ) {
					wrapper.querySelector(
						'.non-cookie-refresh-active'
					).style.display = 'block';
				} else {
					wrapper.querySelector(
						'.non-cookie-refresh-inactive'
					).style.display = 'block';
				}

				if ( vendor.cookieMaxAgeSeconds <= 0 ) {
					wrapper.querySelector( '.session-storage' ).style.display =
						'block';
				} else if ( vendor.cookieMaxAgeSeconds <= 60 * 60 * 24 ) {
					wrapper.querySelector(
						'.retention_seconds'
					).style.display = 'block';
				} else {
					wrapper.querySelector( '.retention_days' ).style.display =
						'block';
				}

				const fragment = document.createDocumentFragment();
				checkbox.classList.add( 'cmplz-vendortype-tcf' );
				fragment.appendChild( checkbox );

				vendorContainer.appendChild( checkbox );
			}
		}

		for ( const key in ACVendors ) {
			if ( ACVendors.hasOwnProperty( key ) ) {
				let customTemplate = template;
				const vendor = ACVendors[ key ];

				// TCF v2.2: Track AC vendor as disclosed
				if ( tcModel.vendorsDisclosed ) {
					tcModel.vendorsDisclosed.set( vendor.id );
				}

				customTemplate = customTemplate.replace(
					/{vendor_name}/g,
					vendor.name
				);
				customTemplate = customTemplate.replace(
					/{vendor_id}/g,
					vendor.id
				);
				customTemplate = customTemplate.replace(
					/{privacy_policy}/g,
					vendor.policyUrl
				);

				// TCF v2.2: AC vendors typically don't have data categories
				customTemplate = customTemplate.replace(
					/{vendor_categories}/g,
					cmplz_tcf.undeclared_string
				);

				// TCF v2.2: AC vendors may not have detailed retention data
				const acRetentionHTML =
					'<div class="cmplz-data-retention"><strong>' +
					cmplz_tcf.retention_string +
					':</strong> ' +
					cmplz_tcf.ac_vendor_retention_string +
					'</div>';
				customTemplate = customTemplate.replace(
					/{data_retention}/g,
					acRetentionHTML
				);

				const wrapper = document.createElement( 'div' );
				wrapper.innerHTML = customTemplate;
				const checkbox = wrapper.firstChild;
				checkbox.querySelector( '.cmplz-tcf-vendor-input' ).checked =
					vendor.consent === 1;
				checkbox
					.querySelector( '.cmplz-tcf-vendor-input' )
					.setAttribute( 'data-ac_vendor_id', vendor.id );

				//set consent
				checkbox.querySelector( '.cmplz-tcf-consent-input' ).checked =
					vendor.consent === 1;
				checkbox
					.querySelector( '.cmplz-tcf-consent-input' )
					.setAttribute( 'data-ac_vendor_id', vendor.id );

				const fragment = document.createDocumentFragment();
				checkbox.classList.add( 'cmplz-vendortype-ac' );
				fragment.appendChild( checkbox );
				vendorContainer.appendChild( checkbox );
			}
		}

		/**
		 * Helper function to sync main vendor checkbox state based on children
		 * Main vendor checkbox should be checked if either consent OR legitimate interest is checked
		 * Note: Only considers legitimate interest if it's visible (not CSS hidden)
		 * @param {HTMLElement} container - The vendor container element
		 */
		function syncMainVendorCheckboxState( container ) {
			const consentCheckbox = container.querySelector(
				'.cmplz-tcf-consent-input'
			);
			const legintCheckbox = container.querySelector(
				'.cmplz-tcf-legitimate-interest-input'
			);
			const legintContainer = container.querySelector(
				'.cmplz_tcf_legitimate_interest_checkbox'
			);
			const mainCheckbox = container.querySelector(
				'.cmplz-tcf-vendor-input'
			);

			// Check consent state
			const consentChecked = consentCheckbox
				? consentCheckbox.checked
				: false;

			// Only check legitimate interest if it's visible
			// If the container is hidden (display:none), don't consider its state
			const legintVisible =
				legintContainer && ! is_hidden( legintContainer );
			const legintChecked =
				legintVisible && legintCheckbox
					? legintCheckbox.checked
					: false;

			// Main should be checked if either visible child is checked
			mainCheckbox.checked = consentChecked || legintChecked;
		}

		cmplz_tcf_add_event(
			'click',
			'.cmplz-tcf-legitimate-interest-input',
			function ( e ) {
				const obj = e.target;
				const vendorId = parseInt(
					obj.getAttribute( 'data-vendor_id' )
				);
				const container = obj.closest( '.cmplz-tcf-vendor-container' );

				if ( obj.checked ) {
					tcModel.vendorLegitimateInterests.set( vendorId );
				} else {
					tcModel.vendorLegitimateInterests.unset( vendorId );
				}

				// Sync main checkbox based on both children states
				syncMainVendorCheckboxState( container );

				cmplzSetTCString( tcModel, true );
				cmplz_set_cookie( 'banner-status', 'dismissed' );
			}
		);

		cmplz_tcf_add_event(
			'click',
			'.cmplz-tcf-consent-input',
			function ( e ) {
				const obj = e.target;
				const vendorId = parseInt(
					obj.getAttribute( 'data-vendor_id' )
				);
				const container = obj.closest( '.cmplz-tcf-vendor-container' );
				if ( vendorId ) {
					if ( obj.checked ) {
						tcModel.vendorConsents.set( vendorId );
					} else {
						tcModel.vendorConsents.unset( vendorId );
					}
					// Sync main checkbox based on both children states
					syncMainVendorCheckboxState( container );
				}

				const ACVendorId = parseInt(
					obj.getAttribute( 'data-ac_vendor_id' )
				);
				if ( ACVendorId ) {
					if ( obj.checked ) {
						consentACVendor( ACVendorId );
						container.querySelector(
							'.cmplz-tcf-vendor-input'
						).checked = true;
					} else {
						denyACVendor( ACVendorId );
						container.querySelector(
							'.cmplz-tcf-vendor-input'
						).checked = false;
					}
				}
				//now we update the tcstring
				cmplzSetTCString( tcModel, true );
				cmplz_set_cookie( 'banner-status', 'dismissed' );
			}
		);

		cmplz_tcf_add_event(
			'click',
			'.cmplz-tcf-vendor-input',
			function ( e ) {
				const obj = e.target;
				const vendorId = parseInt(
					obj.getAttribute( 'data-vendor_id' )
				);
				const ACVendorId = parseInt(
					obj.getAttribute( 'data-ac_vendor_id' )
				);
				const container = obj.closest( '.cmplz-tcf-vendor-container' );
				if ( vendorId ) {
					if ( obj.checked ) {
						tcModel.vendorConsents.set( vendorId );
						//positive leg int should not be set.
						tcModel.vendorLegitimateInterests.set( vendorId );
						container.querySelector(
							'.cmplz-tcf-legitimate-interest-input'
						).checked = true;
						container.querySelector(
							'.cmplz-tcf-consent-input'
						).checked = true;
					} else {
						tcModel.vendorConsents.unset( vendorId );
						tcModel.vendorLegitimateInterests.unset( vendorId );
						container.querySelector(
							'.cmplz-tcf-legitimate-interest-input'
						).checked = false;
						container.querySelector(
							'.cmplz-tcf-consent-input'
						).checked = false;
					}
				} else if ( ACVendorId ) {
					if ( obj.checked ) {
						consentACVendor( ACVendorId );
						container.querySelector(
							'.cmplz-tcf-consent-input'
						).checked = true;
					} else {
						denyACVendor( ACVendorId );
						container.querySelector(
							'.cmplz-tcf-consent-input'
						).checked = false;
					}
				}
				cmplzSetTCString( tcModel, true );
				cmplz_set_cookie( 'banner-status', 'dismissed' );
			}
		);

		cmplz_tcf_add_event( 'click', '.cmplz-tcf-toggle-info', function ( e ) {
			const obj = e.target;
			e.preventDefault();
			if ( is_hidden() ) {
				obj.style.display = 'block';
			} else {
				obj.style.display = 'none';
			}
		} );

		cmplz_tcf_add_event(
			'click',
			'.cmplz-tcf-toggle-vendor',
			function ( e ) {
				const obj = e.target;
				e.preventDefault();
				const container = obj.closest( '.cmplz-tcf-vendor-container' );
				const info = container.querySelector( '.cmplz-tcf-info' );
				if ( is_hidden( info ) ) {
					obj.classList.add( 'cmplz-tcf-rl' );
					obj.classList.remove( 'cmplz-tcf-rm' );
					info.style.display = 'block';
				} else {
					obj.classList.add( 'cmplz-tcf-rm' );
					obj.classList.remove( 'cmplz-tcf-rl' );
					info.style.display = 'none';
				}
			}
		);

		cmplz_tcf_add_event( 'click', '#cmplz-tcf-selectall', function () {
			for ( const key in vendors ) {
				if ( vendors.hasOwnProperty( key ) ) {
					const vendor = vendors[ key ];
					tcModel.vendorConsents.set( vendor.id );
					document.querySelector(
						'#cmplz-tcf-' + vendor.id
					).checked = true;
				}
			}
			const vendorCheckboxes =
				document.querySelectorAll( '[data-vendor_id]' );
			vendorCheckboxes.forEach( ( vendorCheckbox ) => {
				vendorCheckbox.checked = true;
			} );
			acceptAllVendors();
		} );

		cmplz_tcf_add_event( 'click', '#cmplz-tcf-deselectall', function () {
			for ( const key in vendors ) {
				if ( vendors.hasOwnProperty( key ) ) {
					const vendor = vendors[ key ];
					tcModel.vendorConsents.unset( vendor.id );
					document.querySelector(
						'#cmplz-tcf-' + vendor.id
					).checked = false;
				}
			}
			revokeAllVendors( true );
		} );
		const event = new CustomEvent( 'cmplz_vendor_container_loaded', {
			detail: complianz.region,
		} );
		document.dispatchEvent( event );
	}

	/**
	 * Filter the list of vendors
	 *
	 * @param {Object} vendors - Object containing vendor data
	 * @return {Array} Array of filtered vendor IDs
	 */
	function cmplzFilterVendors( vendors ) {
		let vendorIds = Object.values( vendors ).map( ( vendor ) => vendor.id );

		let addVendorIds = cmplzFilterVendorsBy(
			'purposes',
			vendors,
			cmplz_tcf.purposes
		);
		vendorIds = vendorIds.filter( ( value ) =>
			addVendorIds.includes( value )
		);
		addVendorIds = cmplzFilterVendorsBy(
			'specialPurposes',
			vendors,
			cmplz_tcf.specialPurposes
		);
		vendorIds = vendorIds.filter( ( value ) =>
			addVendorIds.includes( value )
		);

		addVendorIds = cmplzFilterVendorsBy(
			'features',
			vendors,
			cmplz_tcf.features
		);
		vendorIds = vendorIds.filter( ( value ) =>
			addVendorIds.includes( value )
		);
		addVendorIds = cmplzFilterVendorsBy(
			'specialFeatures',
			vendors,
			cmplz_tcf.specialFeatures
		);
		vendorIds = vendorIds.filter( ( value ) =>
			addVendorIds.includes( value )
		);
		//remove all vendors that are included in cmplz_tcf.excludedVendors
		//convert cmplz_tcf.excludedVendors json to array
		const excludedVendors = Object.keys( cmplz_tcf.excludedVendors ).map(
			function ( key ) {
				return cmplz_tcf.excludedVendors[ key ];
			}
		);

		vendorIds = vendorIds.filter(
			( value ) => ! excludedVendors.includes( value )
		);

		return vendorIds;
	}

	/**
	 * Get all vendors who use this purpose
	 * @param {string} type              - The type of purpose to check
	 * @param {Object} vendors           - Object containing vendor data
	 * @param {Array}  category_purposes - Array of purpose IDs for the category
	 * @return {Array} Array of vendor IDs that use the specified purpose
	 */
	function cmplzGetVendorsWithPurpose( type, vendors, category_purposes ) {
		const output = [];
		for ( const vendor in vendors ) {
			for ( const purpose in category_purposes ) {
				if (
					vendors[ vendor ][ type ].includes(
						category_purposes[ '' + purpose ]
					)
				) {
					output.push( vendors[ vendor ].id );
					break;
				}
			}
		}
		return output;
	}

	/**
	 * Get vendors who only have one of these purposes
	 * @param {string} type              - The type of purpose to check
	 * @param {Object} vendors           - Object containing vendor data
	 * @param {Array}  category_purposes - Array of purpose IDs for the category
	 * @return {Array} Array of vendor IDs that match the criteria
	 */
	// function cmplzFilterVendorsBy(type, vendors, category_purposes) {
	// 	let output = [];
	// 	for (let key in vendors) {
	// 		if (vendors.hasOwnProperty(key)) {
	// 			const vendor = vendors[key];
	// 			//for each vendor purpose, check if it exists in the category purposes list. If not, don't add this vendor
	// 			let allPurposesAreCategoryPurpose = true;
	// 			const vendorProperties = vendor[type];
	// 			for (let p_key in vendorProperties) {
	// 				if (vendorProperties.hasOwnProperty(p_key)) {
	// 					const purpose = vendorProperties[p_key];
	// 					const inPurposeArray = category_purposes.includes(purpose);
	// 					if (!inPurposeArray) {
	// 						allPurposesAreCategoryPurpose = false;
	// 					}
	// 				}
	// 			}
	// 			const inOutPutArray = output.includes(vendor.id);
	// 			if (!inOutPutArray && allPurposesAreCategoryPurpose) {
	// 				output.push(vendor.id);
	// 			}
	// 		}
	// 	}
	// 	return output;
	// }

	function cmplzFilterVendorsBy( type, vendors, category_purposes ) {
		const output = [];

		for ( const vendor of Object.values( vendors ) ) {
			const vendorProperties = vendor[ type ];

			if (
				Object.values( vendorProperties ).every( ( purpose ) =>
					category_purposes.includes( purpose )
				)
			) {
				output.push( vendor.id );
			}
		}

		return output;
	}

	/**
	 * Retrieves the stored TC String from localStorage
	 *
	 * Performs policy ID validation:
	 * - If the user's stored policy_id doesn't match the current policy_id,
	 *   the TC String is cleared (privacy policy changed, user must re-consent)
	 *
	 * TC String format: Base64-encoded consent data (starts with 'C' for v2.x)
	 *
	 * @return {string|null} The stored TC consent string, or null if not found
	 */
	function cmplzGetTCString() {
		const user_policy_id = cmplz_tcf_get_cookie( 'policy_id' );

		// Clear consent if privacy policy has been updated
		if (
			! user_policy_id ||
			( typeof complianz !== 'undefined' &&
				complianz.current_policy_id !== user_policy_id )
		) {
			if ( localStorage.cmplz_tcf_consent ) {
				localStorage.removeItem( 'cmplz_tcf_consent' );
			}
		}

		return window.localStorage.getItem( 'cmplz_tcf_consent' );
	}

	/**
	 * Retrieves the stored AC (Additional Consent) String from localStorage
	 *
	 * AC String format: "version~vendor.id.id.id"
	 * Example: "1~123.456.789"
	 *
	 * @return {string|null} The stored AC consent string, or null if not found
	 */
	function cmplzGetACString() {
		return window.localStorage.getItem( 'cmplz_ac_string' );
	}

	/**
	 * Encodes and stores the AC (Additional Consent) String
	 *
	 * Creates an AC String containing consented Google vendor IDs:
	 * - Format: "1~vendor.id.id.id" (version 1)
	 * - Only includes vendors where consent === 1
	 * - Clears AC String if no vendors are consented
	 *
	 * AC String is used by Google vendors that aren't in the TCF framework
	 *
	 * @fires CmpApi#cmpuishown - Triggers CMP API event for AC String update
	 */
	function cmplzSetACString() {
		// skip if the ACVendors array objects do not have a consent attribute
		if (
			ACVendors.length === 0 ||
			typeof ACVendors[ 0 ].consent === 'undefined'
		) {
			return;
		}

		const ACversion = 1;
		let acString = ACversion + '~';
		//filter out all vendors where the 'consent' attribute is 0
		const consentedVendors = ACVendors.filter( ( vendor ) => {
			return vendor.consent === 1;
		} );

		//delete if no consent was given
		if ( consentedVendors.length === 0 ) {
			if ( localStorage.cmplz_ac_string ) {
				localStorage.removeItem( 'cmplz_ac_string' );
			}
			return;
		}

		//get an array of vendor id's, and join it.
		acString += consentedVendors
			.map( ( vendor ) => {
				return vendor.id;
			} )
			.join( '.' );

		window.localStorage.setItem( 'cmplz_ac_string', acString );
	}

	/**
	 * Set the tc string, and update the api if needed
	 * TCF v2.2: Includes disclosed vendors and v2.2 flags in encoding
	 *
	 * @param {Object}  tcModelParam - The TC model object
	 * @param {boolean} uiVisible    - Whether the UI is visible
	 */
	function cmplzSetTCString( tcModelParam, uiVisible ) {
		cmplzSetACString();
		let encodedTCString = null;
		if ( tcModelParam ) {
			// Normalize dates (TCF requires dates without time component)
			tcModelParam.created = cmplzRemoveTime( tcModelParam.lastUpdated );
			tcModelParam.lastUpdated = cmplzRemoveTime(
				tcModelParam.lastUpdated
			);

			// TCF v2.2: Ensure disclosed vendors is initialized
			if ( ! tcModelParam.vendorsDisclosed ) {
				tcModelParam.vendorsDisclosed = new Set();
			}

			// TCF v2.2: Ensure v2.2 flags are set
			if ( typeof tcModelParam.useNonStandardStacks === 'undefined' ) {
				tcModelParam.useNonStandardStacks = false;
			}
			if ( typeof tcModelParam.useNonStandardTexts === 'undefined' ) {
				tcModelParam.useNonStandardTexts = false;
			}
			if ( typeof tcModelParam.purposeOneTreatment === 'undefined' ) {
				tcModelParam.purposeOneTreatment = false;
			}

			// Encode TC String (includes v2.2 segments: disclosed vendors, flags)
			encodedTCString = TCString.encode( tcModelParam );
		}

		cmpApi.update( encodedTCString, uiVisible );
		window.localStorage.setItem( 'cmplz_tcf_consent', encodedTCString );
	}

	/**
	 * Ensure the date does not contain hours or minutes
	 * @param {Date} date - The date to process
	 * @return {Date} New date object without time components
	 */
	function cmplzRemoveTime( date ) {
		return new Date( date.getFullYear(), date.getMonth(), date.getDate() );
	}

	/**
	 * Get list of purposes
	 * @param {string}  category               - The category name (marketing, statistics, etc.)
	 * @param {boolean} includeLowerCategories - Whether to include lower categories
	 * @return {Array} Array of purpose IDs for the category
	 */
	function getPurposes( category, includeLowerCategories ) {
		//these categories aren't used
		if ( category === 'functional' || category === 'preferences' ) {
			return [];
		}

		if ( category === 'marketing' ) {
			if ( includeLowerCategories ) {
				return [ 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11 ];
			}
			return [ 1, 2, 3, 4, 5, 6, 10, 11 ];
		} else if ( category === 'statistics' ) {
			return [ 1, 7, 8, 9 ];
		}

		// Default return for unknown categories
		return [];
	}

	/**
	 * Check if a region is a TCF region
	 * @param {string} region - The region to check
	 * @return {boolean}      - True if the region is a TCF region, false otherwise
	 */
	function cmplz_is_tcf_region( region ) {
		return !! cmplz_in_array( region, complianz.tcf_regions );
	}

	function configureOptinBanner() {
		//don't do this for non TCF regions
		if ( ! cmplz_is_tcf_region( complianz.region ) ) {
			return;
		}
		/**
		 * Filter purposes based on passed purposes
		 */
		//only optin variant of tcf has these purposes on the banner.
		if ( complianz.consenttype === 'optin' ) {
			const srcMarketingPurposes = getPurposes( 'marketing', false );
			const srcStatisticsPurposes = getPurposes( 'statistics', false );
			const marketingPurposes = cmplzFilterArray(
				cmplzFilterArray(
					cmplzLanguageJson.purposes,
					cmplz_tcf.purposes
				),
				srcMarketingPurposes
			);
			const statisticsPurposes = cmplzFilterArray(
				cmplzFilterArray(
					cmplzLanguageJson.purposes,
					cmplz_tcf.purposes
				),
				srcStatisticsPurposes
			);
			const features = cmplzFilterArray(
				cmplzLanguageJson.features,
				cmplz_tcf.features
			);
			const specialPurposes = cmplzFilterArray(
				cmplzLanguageJson.specialPurposes,
				cmplz_tcf.specialPurposes
			);
			const specialFeatures = cmplzFilterArray(
				cmplzLanguageJson.specialFeatures,
				cmplz_tcf.specialFeatures
			);

			if ( features.length === 0 ) {
				document.querySelector(
					'.cmplz-tcf .cmplz-features'
				).style.display = 'none';
			}
			if ( specialPurposes.length === 0 ) {
				document.querySelector(
					'.cmplz-tcf .cmplz-specialpurposes'
				).style.display = 'none';
			}
			if ( specialFeatures.length === 0 ) {
				document.querySelector(
					'.cmplz-tcf .cmplz-specialfeatures'
				).style.display = 'none';
			}
			if ( statisticsPurposes.length === 0 ) {
				document.querySelector(
					'.cmplz-tcf .cmplz-statistics'
				).style.display = 'none';
			}
			document.querySelector(
				'.cmplz-tcf .cmplz-statistics .cmplz-description'
			).innerHTML = cmplzConcatenateString( statisticsPurposes );
			document.querySelector(
				'.cmplz-tcf .cmplz-marketing .cmplz-description'
			).innerHTML = cmplzConcatenateString( marketingPurposes );
			document.querySelector(
				'.cmplz-tcf .cmplz-features .cmplz-description'
			).innerHTML = cmplzConcatenateString( features );
			document.querySelector(
				'.cmplz-tcf .cmplz-specialfeatures .cmplz-title'
			).innerHTML = cmplzConcatenateString( specialFeatures );
			document.querySelector(
				'.cmplz-tcf .cmplz-specialpurposes .cmplz-title'
			).innerHTML = cmplzConcatenateString( specialPurposes );
		}

		const vendorCountContainers = document.querySelectorAll(
			'.cmplz-manage-vendors.tcf'
		);
		if ( vendorCountContainers ) {
			let count =
				complianz.consenttype === 'optin'
					? tcModel.gvl.vendorIds.size
					: '';
			if ( useAcVendors && complianz.consenttype === 'optin' ) {
				count += ACVendors.length;
			}
			vendorCountContainers.forEach( ( obj ) => {
				obj.innerHTML = obj.innerHTML.replace(
					'{vendor_count}',
					count
				);
			} );
		}

		//on pageload, show vendorlist area
		const wrapper = document.getElementById( 'cmplz-tcf-wrapper' );
		const noscript_wrapper = document.getElementById(
			'cmplz-tcf-wrapper-nojavascript'
		);
		if ( wrapper ) {
			wrapper.style.display = 'block';
			noscript_wrapper.style.display = 'none';
		}
	}

	/**
	 * Renders vendor data retention information for TCF v2.2 compliance
	 *
	 * @since TCF v2.2
	 * @param {Object} vendor                            - Vendor object from GVL
	 * @param {Object} vendor.dataRetention              - Retention data
	 * @param {number} vendor.dataRetention.stdRetention - Default retention in days
	 * @param {Object} vendor.dataRetention.purposes     - Purpose-specific retention periods
	 * @param {Array}  purposes                          - Array of purpose objects for display
	 * @return {string} HTML string with formatted retention information
	 */
	function cmplzRenderVendorDataRetention( vendor, purposes ) {
		if ( ! vendor.dataRetention ) {
			return (
				'<div class="cmplz-data-retention"><strong>' +
				cmplz_tcf.retention_string +
				':</strong> ' +
				cmplz_tcf.undeclared_string +
				'</div>'
			);
		}

		const defaultRetention =
			vendor.dataRetention.stdRetention || cmplz_tcf.undeclared_string;

		let html = '<div class="cmplz-data-retention">';
		html += '<strong>' + cmplz_tcf.retention_string + ':</strong> ';

		if ( defaultRetention === cmplz_tcf.undeclared_string ) {
			html += defaultRetention;
		} else {
			html += defaultRetention + ' ' + cmplz_tcf.days_string;
		}

		// Purpose-specific retention
		if (
			vendor.dataRetention.purposes &&
			Object.keys( vendor.dataRetention.purposes ).length > 0
		) {
			html +=
				'<br><strong>' +
				cmplz_tcf.purpose_retention_string +
				':</strong>';
			html += '<ul class="cmplz-retention-list">';

			for ( const [ purposeId, days ] of Object.entries(
				vendor.dataRetention.purposes
			) ) {
				const purpose = purposes.find(
					( p ) => p.id === parseInt( purposeId )
				);
				if ( purpose ) {
					html +=
						'<li>' +
						purpose.name +
						': ' +
						days +
						' ' +
						cmplz_tcf.days_string +
						'</li>';
				}
			}

			html += '</ul>';
		}

		html += '</div>';
		return html;
	}

	/**
	 * Renders vendor data categories for TCF v2.2 compliance
	 *
	 * @since TCF v2.2
	 * @param {Object} vendor                 - Vendor object from GVL
	 * @param {Array}  vendor.dataDeclaration - Array of data category IDs
	 * @param {Array}  allDataCategories      - Array of all data category objects from GVL
	 * @return {string} Comma-separated list of category names
	 */
	function cmplzRenderVendorDataCategories( vendor, allDataCategories ) {
		if ( ! vendor.dataDeclaration || vendor.dataDeclaration.length === 0 ) {
			return cmplz_tcf.undeclared_string;
		}

		const categories = [];

		for ( const catKey in vendor.dataDeclaration ) {
			if ( vendor.dataDeclaration.hasOwnProperty( catKey ) ) {
				const categoryId = vendor.dataDeclaration[ catKey ];

				// Find category by ID
				for ( const key in allDataCategories ) {
					if (
						allDataCategories.hasOwnProperty( key ) &&
						allDataCategories[ key ].id === categoryId
					) {
						categories.push( allDataCategories[ key ].name );
						break;
					}
				}
			}
		}

		return categories.length > 0
			? categories.join( ', ' )
			: cmplz_tcf.undeclared_string;
	}

	function cmplzFilterArray( arrayToFilter, arrayToFilterBy ) {
		const output = [];
		for ( const key in arrayToFilter ) {
			if (
				arrayToFilterBy.includes( '' + arrayToFilter[ key ].id ) ||
				arrayToFilterBy.includes( arrayToFilter[ key ].id )
			) {
				output.push( arrayToFilter[ key ] );
			}
		}
		return output;
	}

	function cmplzConcatenateString( array ) {
		let string = '';
		const max = array.length - 1;
		for ( const key in array ) {
			if ( array.hasOwnProperty( key ) ) {
				string += array[ key ].name;
				if ( key < max ) {
					string += ', ';
				} else {
					string += '.';
				}
			}
		}
		return string;
	}
} );

/**
 * TCF for CCPA
 */

const USPSTR_YN = '1YN';
const USPSTR_YY = '1YY';
const USPSTR_NA = '1---';
let ccpaVendorlistLoadedResolve;
const ccpaVendorlistLoaded = new Promise( function ( resolve ) {
	ccpaVendorlistLoadedResolve = resolve;
} );
const USvendorlistUrl = cmplz_tcf.cmp_url + 'cmp/vendorlist' + '/lspa.json';
let ccpaVendorList;
bannerDataLoaded.then( () => {
	if ( complianz.consenttype === 'optout' || onOptOutPolicyPage ) {
		fetch( USvendorlistUrl, {
			method: 'GET',
		} )
			.then( ( response ) => response.json() )
			.then( function ( data ) {
				ccpaVendorList = data;
				ccpaVendorlistLoadedResolve();
			} );
		cmplz_set_ccpa_tc_string();
		cmplzRenderUSVendorsInPolicy();
	} else if ( cmplz_tcf.debug ) {
		console.log( 'not an optout tcf region or page' );
	}
} );

/**
 * When CCPA applies, we set the TC string in the usprivacy cookie
 */
function cmplz_set_ccpa_tc_string() {
	if ( cmplz_tcf.ccpa_applies ) {
		cmplz_set_cookie( 'usprivacy', USPSTR_YN + cmplz_tcf.lspact, false );
		document.addEventListener( 'cmplz_fire_categories', function ( e ) {
			let val = USPSTR_YY + cmplz_tcf.lspact;
			if ( cmplz_in_array( 'marketing', e.detail.categories ) ) {
				val = USPSTR_YN + cmplz_tcf.lspact;
			}
			cmplz_set_cookie( 'usprivacy', val, false );
		} );
	} else {
		cmplz_set_cookie( 'usprivacy', USPSTR_NA + cmplz_tcf.lspact, false );
	}
}

function cmplzRenderUSVendorsInPolicy() {
	ccpaVendorlistLoaded.then( () => {
		const vendors = ccpaVendorList.signatories;
		const vendorContainer = document.getElementById(
			'cmplz-tcf-us-vendor-container'
		);

		if ( vendorContainer === null ) {
			return;
		}
		vendorContainer.innerHTML = '';
		const template = document.getElementById(
			'cmplz-tcf-vendor-template'
		).innerHTML;

		for ( const key in vendors ) {
			if ( vendors.hasOwnProperty( key ) ) {
				let customTemplate = template;
				const vendor = vendors[ key ];
				customTemplate = customTemplate.replace(
					/{vendor_name}/g,
					vendor.signatoryLegalName
				);
				let hasOptoutUrl = true;
				if ( vendor.optoutUrl.indexOf( 'http' ) === -1 ) {
					hasOptoutUrl = false;
					customTemplate = customTemplate.replace(
						/{optout_string}/g,
						vendor.optoutUrl
					);
				} else {
					customTemplate = customTemplate.replace(
						/{optout_url}/g,
						vendor.optoutUrl
					);
				}

				const wrapper = document.createElement( 'div' );
				wrapper.innerHTML = customTemplate;
				const html = wrapper.firstChild;
				if ( hasOptoutUrl ) {
					html.querySelector(
						'.cmplz-tcf-optout-string'
					).style.display = 'none';
					html.querySelector(
						'.cmplz-tcf-optout-url'
					).style.display = 'block';
				} else {
					html.querySelector(
						'.cmplz-tcf-optout-string'
					).style.display = 'block';
					html.querySelector(
						'.cmplz-tcf-optout-url'
					).style.display = 'none';
				}
				const fragment = document.createDocumentFragment();
				fragment.appendChild( html );
				vendorContainer.appendChild( html );
			}
		}

		document.querySelector( '#cmplz-tcf-wrapper' ).style.display = 'block';
		document.querySelector(
			'#cmplz-tcf-wrapper-nojavascript'
		).style.display = 'none';
	} );
}

/**
 * Parses a CSV row into an array of values.
 *
 * This function uses a regular expression to match values in a CSV row.
 * It handles values enclosed in double quotes and values separated by commas.
 *
 * @param {string} row - The CSV row string to parse
 * @return {Array} An array of values parsed from the CSV row
 */
function cmplzParseCsvRow( row ) {
	const regex = /"(.*?)"|([^,]+)/g;
	const values = [];
	let match;
	while ( ( match = regex.exec( row ) ) !== null ) {
		values.push( match[ 1 ] || match[ 2 ] );
	}
	return values;
}

/**
 * @todo get a list of ddr.js files. Not currently available
 * https://github.com/InteractiveAdvertisingBureau/USPrivacy/issues/17
 */
// Add all Vendor scripts; this is just an array of string sources
//https://github.com/InteractiveAdvertisingBureau/USPrivacy/blob/master/CCPA/Data%20Deletion%20Request%20Handling.md
// vendorDeleteScriptSources.forEach((vendorDeleteScriptSource) => {
//
// 	const scriptElement = document.createElement("script");
// 	scriptElement.src = vendorDeleteScriptSource;
//
// 	document.body.appendChild(scriptElement);
//
// });

/**
 * Fire a data deletion request.
 */
document.addEventListener( 'cmplz_dnsmpi_submit', function () {
	if ( cmplz_tcf.debug ) {
		console.log( 'fire data deletion request for TCF' );
	}
	__uspapi( 'performDeletion', 1 );
} );
