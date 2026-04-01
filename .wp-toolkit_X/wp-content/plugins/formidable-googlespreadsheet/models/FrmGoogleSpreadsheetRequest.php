<?php
/**
 * Interface for abstracting remote requests.
 *
 * @package formidable-google-sheets
 */

/**
 * FrmGoogleSpreadsheetRequest Interface.
 *
 * @since 1.0
 */
interface FrmGoogleSpreadsheetRequest {


	/**
	 * Do a Remote request to retrieve the contents of a remote URL.
	 *
	 * @since 1.0
	 *
	 * @param string $url     URL.
	 * @param array  $args Optional.
	 *
	 * @return object|array Response for the executed request.
	 */
	public function request( $url, $args = array());
}
