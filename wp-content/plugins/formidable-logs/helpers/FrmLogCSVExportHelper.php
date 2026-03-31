<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * FrmLogCSVExportHelper
 *
 * @since 1.0.1
 */
class FrmLogCSVExportHelper {

	/**
	 * CSV Separator.
	 *
	 * @var string|mixed $separator
	 */
	protected static $separator = ',';

	/**
	 * CSV Line break.
	 *
	 * @var string|mixed $line_break
	 */
	protected static $line_break = 'return';

	/**
	 * CSV Charset.
	 *
	 * @var string $charset
	 */
	protected static $charset = 'UTF-8';

	/**
	 * CSV Encoding.
	 *
	 * @var string|mixed $to_encoding
	 */
	protected static $to_encoding = 'UTF-8';

	/**
	 * CSV filename.
	 *
	 * @var string $filename
	 */
	public static $filename = 'formidable-logs-';

	/**
	 * PHP output handler
	 *
	 * @var resource $output_handle.
	 */
	protected $output_handle;

	/**
	 * FrmLogCSVExportHelper constructor.
	 *
	 * @since 1.0.1
	 */
	public function __construct() {
		/**
		 * CSV separator
		 *
		 * @since 1.0.1
		 *
		 * @param string|mixed $separator
		 */
		self::$separator = apply_filters( 'frm_logs_csv_sep', self::$separator );
		/**
		 * CSV Line break
		 *
		 * @since 1.0.1
		 *
		 * @param string|mixed $line_break
		 */
		self::$line_break = apply_filters( 'frm_logs_csv_line_break', self::$line_break );
		/**
		 * CSV encoding
		 *
		 * @since 1.0.1
		 *
		 * @param string|mixed $to_encoding
		 */
		self::$to_encoding = apply_filters( 'frm_logs_csv_format', self::$to_encoding );

		self::$charset = get_option( 'blog_charset' );

		$this->generate_csv();
	}

	/**
	 * Open steam and generate CSV file.
	 *
	 * @return void
	 */
	public function generate_csv() {
		if ( function_exists( 'set_time_limit' ) ) {
			// Remove time limit to execute this function.
			set_time_limit( 0 );
		}

		$mem_limit = str_replace( 'M', '', ini_get( 'memory_limit' ) );
		if ( (int) $mem_limit < 256 ) {
			wp_raise_memory_limit();
		}

		$this->output_handle = @fopen( 'php://output', 'w' );

		$filename = self::generate_csv_filename();

		self::print_file_headers( $filename );
	}

	/**
	 * Print to csv file.
	 *
	 * @since 1.0.1
	 * @param array<string> $row a row of csv file.
	 *
	 * @return void
	 */
	public function print_csv_row( $row ) {
		foreach ( $row as $k => $item ) {
			$val = self::encode_value( $item );
			if ( 'return' !== self::$line_break ) {
				$val = str_replace( array( "\r\n", "\r", "\n" ), self::$line_break, $val );
			}
			$row[ $k ] = $val;
		}
		fputcsv( $this->output_handle, $row, self::$separator );
	}

	/**
	 * Close stream.
	 *
	 * @since 1.0.1
	 *
	 * @return void
	 */
	public function close_stream() {
		fclose( $this->output_handle );
	}

	/**
	 * Generate CSV filename.
	 *
	 * @since 1.0.1
	 *
	 * @return string
	 */
	private static function generate_csv_filename() {
		/**
		 * Form Logs CSV Output file name.
		 *
		 * @since 1.0.1
		 */
		return apply_filters( 'frm_logs_csv_filename', self::$filename . wp_date( 'Y-m-dH:i:s' ) . '.csv' );
	}

	/**
	 * Print file headers for csv.
	 *
	 * @since 1.0.1
	 *
	 * @param string $filename csv filename.
	 * @return void
	 */
	private static function print_file_headers( $filename ) {
		$expires = gmdate( 'D, d M Y H:i:s' );
		header( 'Content-Description: File Transfer' );
		header( 'Content-Disposition: attachment; filename="' . esc_attr( $filename ) . '"' );
		header( 'Content-Type: text/csv; charset=' . self::$charset, true );
		header( 'Expires: ' . $expires . ' GMT' );
		header( 'Last-Modified: ' . $expires . ' GMT' );
		header( 'Cache-Control: no-cache, must-revalidate' );
		header( 'Pragma: no-cache' );

		/**
		 * Add more header option to csv.
		 *
		 * @since 1.0.1
		 */
		do_action( 'frm_log_csv_headers' );
	}

	/**
	 * Encode value.
	 *
	 * @since 1.0.1
	 *
	 * @param string $line csv line.
	 * @return string
	 */
	protected static function encode_value( $line ) {
		if ( '' === $line ) {
			return $line;
		}

		$convmap = false;

		switch ( self::$to_encoding ) {
			case 'macintosh':
				// this map was derived from the differences between the MacRoman and UTF-8 Charsets
				// Reference:
				// - http://www.alanwood.net/demos/macroman.html.
				$convmap = array( 256, 304, 0, 0xffff, 306, 337, 0, 0xffff, 340, 375, 0, 0xffff, 377, 401, 0, 0xffff, 403, 709, 0, 0xffff, 712, 727, 0, 0xffff, 734, 936, 0, 0xffff, 938, 959, 0, 0xffff, 961, 8210, 0, 0xffff, 8213, 8215, 0, 0xffff, 8219, 8219, 0, 0xffff, 8227, 8229, 0, 0xffff, 8231, 8239, 0, 0xffff, 8241, 8248, 0, 0xffff, 8251, 8259, 0, 0xffff, 8261, 8363, 0, 0xffff, 8365, 8481, 0, 0xffff, 8483, 8705, 0, 0xffff, 8707, 8709, 0, 0xffff, 8711, 8718, 0, 0xffff, 8720, 8720, 0, 0xffff, 8722, 8729, 0, 0xffff, 8731, 8733, 0, 0xffff, 8735, 8746, 0, 0xffff, 8748, 8775, 0, 0xffff, 8777, 8799, 0, 0xffff, 8801, 8803, 0, 0xffff, 8806, 9673, 0, 0xffff, 9675, 63742, 0, 0xffff, 63744, 64256, 0, 0xffff );
				break;
			case 'ISO-8859-1':
				$convmap = array( 256, 10000, 0, 0xffff );
		}

		if ( is_array( $convmap ) ) {
			$line = mb_encode_numericentity( $line, $convmap, self::$charset );
		}

		if ( self::$to_encoding !== self::$charset ) {
			$line = iconv( self::$charset, self::$to_encoding . '//IGNORE', $line );
		}

		return self::escape_csv( $line );
	}

	/**
	 * Escape a " in a csv with another ".
	 *
	 * @since 1.0.1
	 *
	 * @param mixed $value value.
	 * @return mixed
	 */
	protected static function escape_csv( $value ) {
		if ( ! is_string( $value ) ) {
			return $value;
		}

		// No to complicated condition and loops to have a better benchmark here.
		if ( '=' === $value[0] ) {
			// escape the = to prevent vulnerability.
			$value = "'" . $value;
		}

		if ( '+' === $value[0] ) {
			// escape the + to prevent vulnerability.
			$value = "'" . $value;
		}

		if ( '@' === $value[0] ) {
			// escape the @ to prevent vulnerability.
			$value = "'" . $value;
		}

		$value = str_replace( '"', '""', $value );

		return $value;
	}
}
