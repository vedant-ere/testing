<?php
/**
 * Validator for CSV rows and payload.
 *
 * @package RT_Movie_Library
 */

namespace RT_Movie_Library\Classes\Cli\Helpers;

use RT_Movie_Library\Classes\Cli\Exceptions\Cli_Validation_Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Class Import_Payload_Validator
 */
class Import_Payload_Validator {

	/**
	 * Validate CSV header.
	 *
	 * @param array<string> $header Parsed header.
	 * @return void
	 * @throws Cli_Validation_Exception For invalid header.
	 */
	public function validate_header( array $header ): void {
		if ( Csv_Helper::HEADERS !== $header ) {
			throw new Cli_Validation_Exception( __( 'Invalid CSV header. Please use a file from `wp rt-movie export`.', 'rt-movie-library' ) );
		}
	}

	/**
	 * Validate minimum CSV columns.
	 *
	 * @param array<string> $columns Parsed columns.
	 * @param int           $row_number CSV row number.
	 * @return void
	 * @throws Cli_Validation_Exception For invalid row.
	 */
	public function validate_row_columns( array $columns, int $row_number ): void {
		if ( count( $columns ) < count( Csv_Helper::HEADERS ) ) {
			throw new Cli_Validation_Exception(
				sprintf(
					/* translators: %d: row number in CSV. */
					__( 'Skipping invalid row %d (not enough columns).', 'rt-movie-library' ),
					$row_number
				)
			);
		}
	}

	/**
	 * Validate payload array shape.
	 *
	 * @param mixed $payload Decoded payload.
	 * @return void
	 * @throws Cli_Validation_Exception For invalid payload.
	 */
	public function validate_payload( $payload ): void {
		if ( ! is_array( $payload ) || empty( $payload['post'] ) || ! is_array( $payload['post'] ) ) {
			throw new Cli_Validation_Exception( __( 'Skipping row: missing post data.', 'rt-movie-library' ) );
		}
	}
}
